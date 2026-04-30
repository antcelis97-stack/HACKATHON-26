<?php
namespace App\Controllers;

use Flight;
use PDO;

class EgresadoController extends BaseController {
    
    /**
     * GET /api/v1/egresados/perfil/@usuarioId
     * Obtener perfil del egresado con información de contacto
     */
    public function obtenerPerfil($usuarioId) {
        try {
            // Consultar información del egresado con datos del usuario y contacto
            $stmt = $this->db->prepare("
                SELECT 
                    u.id_usuario as usuario_id,
                    u.username as usuario,
                    e.cve_alumno,
                    e.nombre,
                    e.apellido_paterno,
                    e.apellido_materno,
                    e.url_foto_drive,
                    e.url_cv_drive,
                    c.direccion,
                    c.email as email_contacto,
                    c.telefono
                FROM usuarios u
                LEFT JOIN egresados e ON u.id_usuario = e.id_usuario
                LEFT JOIN usuario_contacto c ON u.id_usuario = c.id_usuario
                WHERE u.id_usuario = ?
            ");
            
            $stmt->execute([$usuarioId]);
            $perfil = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$perfil) {
                return Flight::json(['error' => 'Egresado no encontrado'], 404);
            }
            
            return Flight::json($perfil, 200);
            
        } catch (\Exception $e) {
            return Flight::json([
                'error' => 'Error al obtener el perfil',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/v1/egresados/perfil/actualizar
     * Actualizar perfil e información de contacto del egresado
     */
    public function actualizarPerfil() {
        try {
            $data = $this->getInput();
            $usuario_id = $data['usuario_id'] ?? null;
            
            if (!$usuario_id) {
                return Flight::json(['error' => 'usuario_id es requerido'], 400);
            }
            
            // 1. Actualizar tabla Egresados
            $stmtE = $this->db->prepare("
                UPDATE egresados 
                SET nombre = ?, apellido_paterno = ?, apellido_materno = ?
                WHERE id_usuario = ?
            ");
            $stmtE->execute([
                $data['nombre'] ?? null,
                $data['apellido_paterno'] ?? null,
                $data['apellido_materno'] ?? null,
                $usuario_id
            ]);

            // 2. Actualizar o Insertar en usuario_contacto
            $stmtC = $this->db->prepare("
                INSERT INTO usuario_contacto (id_usuario, direccion, email, telefono)
                VALUES (?, ?, ?, ?)
                ON CONFLICT (id_usuario) 
                DO UPDATE SET direccion = EXCLUDED.direccion, email = EXCLUDED.email, telefono = EXCLUDED.telefono
            ");
            $stmtC->execute([
                $usuario_id,
                $data['direccion'] ?? null,
                $data['email_contacto'] ?? null,
                $data['telefono'] ?? null
            ]);
            
            return Flight::json(['mensaje' => 'Perfil y contacto actualizados correctamente'], 200);
            
        } catch (\Exception $e) {
            return Flight::json([
                'error' => 'Error al actualizar perfil',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    public function searchVacantes() {
        // Obtenemos solo vacantes activas según el esquema actual
        $query = "SELECT * FROM vacantes WHERE estado = TRUE";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $vacantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return Flight::json($vacantes, 200);
        } catch (\Exception $e) {
            return Flight::json(['error' => 'Error al buscar vacantes', 'detalle' => $e->getMessage()], 500);
        }
    }

    /**
     * PATCH /api/v1/egresado/mi-disponibilidad
     * El egresado actualiza su propio estatus laboral
     */
    public function actualizarEstatusLaboral() {
        $user = Flight::get('user');
        $data = $this->getInput();
        
        if (!isset($data['estatus'])) {
            return Flight::json(['error' => 'El campo estatus es requerido'], 400);
        }

        try {
            $this->db->beginTransaction();

            // 1. Actualizar estatus en la tabla egresados
            $stmt = $this->db->prepare("UPDATE egresados SET estatus_laboral = ? WHERE id_usuario = ?");
            $stmt->execute([$data['estatus'], $user->sub]);

            // 2. Si es contratado, registrar en la tabla contrataciones (inserción externa)
            if ($data['estatus'] === 'contratado' && !empty($data['empresa'])) {
                $stmtEgresado = $this->db->prepare("SELECT cve_alumno FROM egresados WHERE id_usuario = ?");
                $stmtEgresado->execute([$user->sub]);
                $cve_alumno = $stmtEgresado->fetchColumn();

                $stmtCont = $this->db->prepare("
                    INSERT INTO contrataciones (cve_alumno, puesto_asignado, estatus) 
                    VALUES (?, ?, 'activo')
                ");
                $stmtCont->execute([$cve_alumno, $data['puesto'] ?? 'No especificado']);
                // Nota: id_empresa queda NULL si es contratación externa
            }

            $this->db->commit();
            return Flight::json(['mensaje' => 'Estatus laboral actualizado correctamente'], 200);

        } catch (\Exception $e) {
            $this->db->rollBack();
            return Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/v1/postulaciones/aplicar
     * El egresado se postula a una vacante activa
     */
    public function postularseAVacante() {
        $user = Flight::get('user');
        $data = $this->getInput();
        $id_vacante = $data['id_vacante'] ?? null;

        if (!$id_vacante) {
            return Flight::json(['error' => 'ID de vacante es requerido'], 400);
        }

        try {
            // 1. Obtener la matrícula del alumno
            $stmtAlumno = $this->db->prepare("SELECT cve_alumno FROM egresados WHERE id_usuario = ?");
            $stmtAlumno->execute([$user->sub]);
            $cve_alumno = $stmtAlumno->fetchColumn();

            // 2. Registrar la postulación
            $stmt = $this->db->prepare("
                INSERT INTO postulaciones (cve_alumno, id_vacante, estatus) 
                VALUES (?, ?, 'pendiente')
            ");
            $stmt->execute([$cve_alumno, $id_vacante]);

            return Flight::json(['mensaje' => 'Postulación registrada exitosamente'], 201);
        } catch (\Exception $e) {
            return Flight::json(['error' => $e->getMessage()], 500);
        }
    }
}