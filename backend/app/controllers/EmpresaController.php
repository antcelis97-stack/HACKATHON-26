<?php
namespace App\Controllers;

use Flight;
use PDO;

class EmpresaController extends BaseController {
    
    /**
     * POST /api/v1/empresas/registrar
     * Registro de perfil de empresa vinculado a un usuario
     */
    public function registrarEmpresa() {
        $data = $this->getInput();
        
        if (!isset($data['id_usuario'], $data['razon_social'])) {
            return Flight::json(['error' => 'ID Usuario y Razón Social son requeridos'], 400);
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO empresas (id_usuario, razon_social, id_denue, sector, ubicacion) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['id_usuario'], 
                $data['razon_social'], 
                $data['id_denue'] ?? null,
                $data['sector'] ?? null,
                $data['ubicacion'] ?? null
            ]);

            return Flight::json(['mensaje' => 'Perfil de empresa registrado exitosamente'], 201);

        } catch (\Exception $e) {
            return Flight::json(['error' => 'Error al registrar perfil', 'detalle' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/v1/empresas/vacantes
     * Crear una nueva vacante de empleo
     */
    public function crearVacante() {
        $data = $this->getInput();
        
        if (!isset($data['id_empresa'], $data['titulo_puesto'])) {
            return Flight::json(['error' => 'ID Empresa y Título de Puesto son requeridos'], 400);
        }

        try {
            $this->db->beginTransaction();

            // 2. SEGURIDAD: Verificar si la empresa tiene un convenio ACTIVO
            $stmtCheck = $this->db->prepare("
                SELECT COUNT(*) FROM convenios 
                WHERE id_empresa = ? AND estatus = 'activo' AND (fecha_vencimiento >= CURRENT_DATE OR fecha_vencimiento IS NULL)
            ");
            $stmtCheck->execute([$data['id_empresa']]);
            if ($stmtCheck->fetchColumn() == 0) {
                return Flight::json(['error' => 'Su empresa no tiene un convenio activo o vigente. No puede publicar vacantes aún.'], 403);
            }

            // 3. Inserción de la vacante
            $stmt = $this->db->prepare("
                INSERT INTO vacantes (
                    id_empresa, 
                    titulo_puesto, 
                    descripcion, 
                    min_psicometrico, 
                    min_cognitivo, 
                    min_tecnico, 
                    min_proyectivo
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['id_empresa'], 
                $data['titulo_puesto'], 
                $data['descripcion'] ?? null,
                $data['min_psicometrico'] ?? 0,
                $data['min_cognitivo'] ?? 0,
                $data['min_tecnico'] ?? 0,
                $data['min_proyectivo'] ?? 0
            ]);

            $this->db->commit();
            return Flight::json(['mensaje' => 'Vacante publicada exitosamente'], 201);

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/v1/empresa/registrar-contratado
     * Registrar que un egresado ha sido contratado
     */
    public function registrarContratacion() {
        $data = $this->getInput();
        
        if (!isset($data['cve_alumno'], $data['id_vacante'])) {
            return Flight::json(['error' => 'cve_alumno e id_vacante son requeridos'], 400);
        }

        try {
            $this->db->beginTransaction();

            // 1. Insertar en tabla contrataciones
            $stmt = $this->db->prepare("
                INSERT INTO contrataciones (cve_alumno, id_vacante, fecha_contratacion) 
                VALUES (?, ?, CURRENT_DATE)
            ");
            $stmt->execute([$data['cve_alumno'], $data['id_vacante']]);

            // 2. Actualizar estatus de la postulación si existe
            $stmtP = $this->db->prepare("
                UPDATE postulaciones SET estatus = 'contratado' 
                WHERE cve_alumno = ? AND id_vacante = ?
            ");
            $stmtP->execute([$data['cve_alumno'], $data['id_vacante']]);

            // 3. Actualizar estatus laboral del egresado
            $stmtE = $this->db->prepare("
                UPDATE egresados SET estatus_laboral = 'contratado' WHERE cve_alumno = ?
            ");
            $stmtE->execute([$data['cve_alumno']]);

            $this->db->commit();
            return Flight::json(['mensaje' => 'Contratación registrada correctamente'], 201);

        } catch (\Exception $e) {
            $this->db->rollBack();
            return Flight::json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * GET /api/v1/empresas/vacantes/@id
     * Listar vacantes activas de una empresa específica
     */
    public function obtenerVacantesActivas($id_empresa) {
        try {
            $stmt = $this->db->prepare("
                SELECT id_vacante, titulo_puesto, descripcion
                FROM vacantes 
                WHERE id_empresa = ? AND estado = TRUE
                ORDER BY id_vacante DESC
            ");
            $stmt->execute([$id_empresa]);
            return Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC), 200);
        } catch (\Exception $e) {
            return Flight::json(['error' => 'Error al obtener vacantes'], 500);
        }
    }

    /**
     * GET /api/v1/vacantes/detalle/@id
     * Obtener todos los detalles y requisitos de una vacante para la gráfica
     */
    public function obtenerDetalleVacante($id_vacante) {
        try {
            $stmt = $this->db->prepare("
                SELECT id_vacante, titulo_puesto, descripcion, 
                       min_psicometrico, min_cognitivo, min_tecnico, min_proyectivo
                FROM vacantes 
                WHERE id_vacante = ? AND estado = TRUE
            ");
            $stmt->execute([$id_vacante]);
            $vacante = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$vacante) {
                return Flight::json(['error' => 'Vacante no encontrada'], 404);
            }
            
            return Flight::json($vacante, 200);
        } catch (\Exception $e) {
            return Flight::json(['error' => 'Error al obtener detalle', 'detalle' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/v1/contratacion/externa
     * Registra una contratación y crea la empresa desde el DENUE si no existe.
     */
    public function registrarContratacionExterna() {
        $data = $this->getInput();
        $id_denue = $data['id_denue'] ?? null;
        $cve_alumno = $data['cve_alumno'] ?? null;

        if (!$id_denue || !$cve_alumno) {
            return Flight::json(['error' => 'id_denue y cve_alumno son requeridos'], 400);
        }

        try {
            $this->db->beginTransaction();

            // 1. Verificar si la empresa ya existe
            $stmt = $this->db->prepare("SELECT id_empresa FROM empresas WHERE id_denue = ?");
            $stmt->execute([$id_denue]);
            $empresa = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$empresa) {
                // 2. Si no existe, obtener datos del INEGI
                $token = $_ENV['INEGI_DENUE_TOKEN'] ?? '';
                $url = "https://www3.inegi.org.mx/sistemas/api/denue/v1/stats/id/{$id_denue}/{$token}";
                $res = @file_get_contents($url);
                $datosInegi = json_decode($res, true);

                if (empty($datosInegi) || !isset($datosInegi[0])) {
                    throw new \Exception("Error al consultar INEGI");
                }

                $info = $datosInegi[0];
                // 3. Registrar la empresa automáticamente
                $stmtIns = $this->db->prepare("
                    INSERT INTO empresas (razon_social, id_denue, sector, ubicacion, estado) 
                    VALUES (?, ?, ?, ?, TRUE) RETURNING id_empresa
                ");
                $stmtIns->execute([
                    $info['Nombre'] ?? 'Empresa Externa',
                    $id_denue,
                    $info['Clase'] ?? 'General',
                    $info['Ubicacion'] ?? 'No especificada'
                ]);
                $id_empresa = $stmtIns->fetchColumn();
            } else {
                $id_empresa = $empresa['id_empresa'];
            }

            // 4. Registrar la contratación
            $stmtC = $this->db->prepare("INSERT INTO contrataciones (cve_alumno, fecha_contratacion) VALUES (?, CURRENT_DATE)");
            $stmtC->execute([$cve_alumno]);

            // 5. Actualizar estatus del egresado
            $stmtE = $this->db->prepare("UPDATE egresados SET estatus_laboral = 'contratado' WHERE cve_alumno = ?");
            $stmtE->execute([$cve_alumno]);

            $this->db->commit();
            return Flight::json(['mensaje' => 'Contratación registrada con éxito'], 201);

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/v1/contratacion/externa
     * Registra una contratación en una empresa Nacional (DENUE).
     * REGLA: El convenio solo se crea en este momento (cuando el egresado consigue el trabajo).
     */
    public function registrarContratacionExterna() {
        $data = $this->getInput();
        $id_denue = $data['id_denue'] ?? null;
        $cve_alumno = $data['cve_alumno'] ?? null;

        if (!$id_denue || !$cve_alumno) {
            return Flight::json(['error' => 'id_denue y cve_alumno son requeridos'], 400);
        }

        try {
            $this->db->beginTransaction();

            // 1. Verificar si la empresa ya existe
            $stmt = $this->db->prepare("SELECT id_empresa FROM empresas WHERE id_denue = ?");
            $stmt->execute([$id_denue]);
            $empresa = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$empresa) {
                // 2. Si es nueva y Nacional, consultar INEGI
                $token = $_ENV['INEGI_DENUE_TOKEN'] ?? '';
                $url = "https://www3.inegi.org.mx/sistemas/api/denue/v1/stats/id/{$id_denue}/{$token}";
                $res = @file_get_contents($url);
                $datosInegi = json_decode($res, true);

                if (empty($datosInegi) || !isset($datosInegi[0])) {
                    throw new \Exception("No se pudo obtener información del INEGI");
                }

                $info = $datosInegi[0];
                
                // 3. Registrar empresa (Sin URL de convenio aún, ya que es automática)
                $stmtIns = $this->db->prepare("
                    INSERT INTO empresas (razon_social, id_denue, sector, ubicacion) 
                    VALUES (?, ?, ?, ?) RETURNING id_empresa
                ");
                $stmtIns->execute([
                    $info['Nombre'] ?? 'Empresa Nacional',
                    $id_denue,
                    $info['Clase'] ?? 'Sector Nacional',
                    $info['Ubicacion'] ?? 'Nacional'
                ]);
                $id_empresa = $stmtIns->fetchColumn();

                // 4. CREAR CONVENIO PENDIENTE (Regla: El administrador debe formalizarlo)
                $stmtConv = $this->db->prepare("
                    INSERT INTO convenios (id_empresa, estatus, comentarios) 
                    VALUES (?, 'pendiente', 'Registro Nacional vía DENUE: Pendiente de formalización por Administrador UT')
                ");
                $stmtConv->execute([$id_empresa]);
            } else {
                $id_empresa = $empresa['id_empresa'];
            }

            // 5. Registrar la contratación
            $stmtC = $this->db->prepare("INSERT INTO contrataciones (cve_alumno, id_vacante, fecha_contratacion) VALUES (?, NULL, CURRENT_DATE)");
            $stmtC->execute([$cve_alumno]);

            // 6. Actualizar estatus del egresado
            $stmtE = $this->db->prepare("UPDATE egresados SET estatus_laboral = 'contratado' WHERE cve_alumno = ?");
            $stmtE->execute([$cve_alumno]);

            $this->db->commit();
            return Flight::json(['mensaje' => 'Contratación registrada. Empresa nacional y convenio creados correctamente.'], 201);

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return Flight::json(['error' => $e->getMessage()], 500);
        }
    }
}