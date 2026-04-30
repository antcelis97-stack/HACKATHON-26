<?php
namespace App\Controllers;

use Flight;
use PDO;

class EgresadoController extends BaseController {
    
    /**
     * GET /api/v1/egresados/perfil/@usuarioId
     */
    public function obtenerPerfil($usuarioId) {
        try {
            $stmt = $this->db->prepare("
                SELECT u.id_usuario as usuario_id, u.username as usuario,
                       e.cve_alumno, e.nombre, e.apellido_paterno, e.apellido_materno,
                       e.url_foto_drive, e.url_cv_drive, c.direccion, c.email as email_contacto, c.telefono
                FROM usuarios u
                LEFT JOIN egresados e ON u.id_usuario = e.id_usuario
                LEFT JOIN usuario_contacto c ON u.id_usuario = c.id_usuario
                WHERE u.id_usuario = ?
            ");
            $stmt->execute([$usuarioId]);
            $perfil = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$perfil) return Flight::json(['error' => 'Egresado no encontrado'], 404);
            return Flight::json($perfil, 200);
        } catch (\Exception $e) {
            return Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/v1/egresados/perfil/actualizar
     */
    public function actualizarPerfil() {
        try {
            $data = $this->getInput();
            $usuario_id = $data['usuario_id'] ?? null;
            if (!$usuario_id) return Flight::json(['error' => 'usuario_id es requerido'], 400);

            $stmtE = $this->db->prepare("UPDATE egresados SET nombre = ?, apellido_paterno = ?, apellido_materno = ? WHERE id_usuario = ?");
            $stmtE->execute([$data['nombre'] ?? null, $data['apellido_paterno'] ?? null, $data['apellido_materno'] ?? null, $usuario_id]);

            $stmtC = $this->db->prepare("INSERT INTO usuario_contacto (id_usuario, direccion, email, telefono) VALUES (?, ?, ?, ?) ON CONFLICT (id_usuario) DO UPDATE SET direccion = EXCLUDED.direccion, email = EXCLUDED.email, telefono = EXCLUDED.telefono");
            $stmtC->execute([$usuario_id, $data['direccion'] ?? null, $data['email_contacto'] ?? null, $data['telefono'] ?? null]);
            
            return Flight::json(['mensaje' => 'Perfil actualizado'], 200);
        } catch (\Exception $e) {
            return Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function searchVacantes() {
        try {
            $stmt = $this->db->query("SELECT * FROM vacantes WHERE estado = TRUE");
            return Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC), 200);
        } catch (\Exception $e) {
            return Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarEstatusLaboral() {
        $user = Flight::get('user');
        $data = $this->getInput();
        if (!isset($data['estatus'])) return Flight::json(['error' => 'Estatus requerido'], 400);

        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("UPDATE egresados SET estatus_laboral = ? WHERE id_usuario = ?");
            $stmt->execute([$data['estatus'], $user->sub]);
            $this->db->commit();
            return Flight::json(['mensaje' => 'Estatus actualizado'], 200);
        } catch (\Exception $e) {
            $this->db->rollBack();
            return Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function postularseAVacante() {
        $user = Flight::get('user');
        $data = $this->getInput();
        $id_vacante = $data['id_vacante'] ?? null;
        if (!$id_vacante) return Flight::json(['error' => 'ID vacante requerido'], 400);

        try {
            $stmtAlumno = $this->db->prepare("SELECT cve_alumno FROM egresados WHERE id_usuario = ?");
            $stmtAlumno->execute([$user->sub]);
            $cve_alumno = $stmtAlumno->fetchColumn();

            $stmt = $this->db->prepare("INSERT INTO postulaciones (cve_alumno, id_vacante, estatus) VALUES (?, ?, 'pendiente')");
            $stmt->execute([$cve_alumno, $id_vacante]);
            return Flight::json(['mensaje' => 'Postulación exitosa'], 201);
        } catch (\Exception $e) {
            return Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/v1/vacantes/nacionales
     * Consulta vacantes externas a través de Adzuna
     */
    public function explorarNacionales() {
        $query = Flight::request()->query;
        $busqueda = $query['what'] ?? 'empleo';

        try {
            $servicio = new \App\Services\BolsaNacionalAPI();
            $vacantes = $servicio->getVacantesNacionales($busqueda);
            return Flight::json($vacantes, 200);
        } catch (\Exception $e) {
            return Flight::json(['error' => 'Error en Bolsa Nacional', 'detalle' => $e->getMessage()], 500);
        }
    }
}