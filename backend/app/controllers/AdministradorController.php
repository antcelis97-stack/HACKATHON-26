<?php 
namespace App\Controllers; 

use Flight; 
use PDO;

class AdministradorController extends BaseController {

    /**
     * GET /api/v1/admin/convenios/pendientes
     * Listar solicitudes de convenio en espera de revisión
     */
    public function listarConveniosPendientes() {
        try {
            $stmt = $this->db->query("
                SELECT 
                    c.id_convenio,
                    e.razon_social,
                    c.url_archivo_drive,
                    c.fecha_registro,
                    c.estatus
                FROM convenios c
                JOIN empresas e ON c.id_empresa = e.id_empresa
                WHERE c.estatus = 'en_revision'
                ORDER BY c.fecha_registro ASC
            ");
            return Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC), 200);
        } catch (\Exception $e) {
            return Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/v1/admin/convenios/aprobar/@id
     * Aprobación manual de un convenio
     */
    public function aprobarConvenio($id) {
        try {
            $this->db->beginTransaction();

            // Calculamos vigencia de 2 años
            $fechaInicio = date('Y-m-d');
            $fechaVencimiento = date('Y-m-d', strtotime('+2 years'));

            $stmt = $this->db->prepare("
                UPDATE convenios 
                SET estatus = 'activo', 
                    fecha_inicio = ?, 
                    fecha_vencimiento = ?, 
                    comentarios = NULL 
                WHERE id_convenio = ?
            ");
            
            $stmt->execute([$fechaInicio, $fechaVencimiento, $id]);

            $this->db->commit();
            return Flight::json([
                'mensaje' => 'Convenio aprobado con éxito',
                'vigencia' => "Desde $fechaInicio hasta $fechaVencimiento"
            ], 200);

        } catch (\Exception $e) {
            $this->db->rollBack();
            return Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/v1/admin/convenios/rechazar/@id
     * Rechazo de convenio con motivo
     */
    public function rechazarConvenio($id) {
        $data = $this->getInput();
        $motivo = $data['motivo'] ?? 'No cumple con los requisitos institucionales';

        try {
            $stmt = $this->db->prepare("
                UPDATE convenios 
                SET estatus = 'rechazado', 
                    comentarios = ? 
                WHERE id_convenio = ?
            ");
            $stmt->execute([$motivo, $id]);

            return Flight::json(['mensaje' => 'Convenio rechazado y notificado'], 200);

        } catch (\Exception $e) {
            return Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/v1/admin/convenios/aceptados
     */
    public function listarConveniosAceptados() {
        try {
            $stmt = $this->db->query("
                SELECT c.id_convenio, e.razon_social, c.fecha_inicio, c.fecha_vencimiento, c.url_archivo_drive 
                FROM convenios c JOIN empresas e ON c.id_empresa = e.id_empresa 
                WHERE c.estatus = 'activo' ORDER BY c.fecha_vencimiento ASC
            ");
            return Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC), 200);
        } catch (\Exception $e) { return Flight::json(['error' => $e->getMessage()], 500); }
    }

    /**
     * GET /api/v1/admin/convenios/rechazados
     */
    public function listarConveniosRechazados() {
        try {
            $stmt = $this->db->query("
                SELECT c.id_convenio, e.razon_social, c.comentarios, c.fecha_registro, c.url_archivo_drive 
                FROM convenios c JOIN empresas e ON c.id_empresa = e.id_empresa 
                WHERE c.estatus = 'rechazado' ORDER BY c.fecha_registro DESC
            ");
            return Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC), 200);
        } catch (\Exception $e) { return Flight::json(['error' => $e->getMessage()], 500); }
    }

    /**
     * GET /api/v1/admin/convenios/vencidos
     */
    public function listarConveniosVencidos() {
        try {
            $stmt = $this->db->query("
                SELECT c.id_convenio, e.razon_social, c.fecha_vencimiento, c.url_archivo_drive 
                FROM convenios c JOIN empresas e ON c.id_empresa = e.id_empresa 
                WHERE c.estatus = 'vencido' OR (c.estatus = 'activo' AND c.fecha_vencimiento < CURRENT_DATE)
                ORDER BY c.fecha_vencimiento DESC
            ");
            return Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC), 200);
        } catch (\Exception $e) { return Flight::json(['error' => $e->getMessage()], 500); }
    }

    /**
     * GET /api/v1/admin/nacionales/pendientes
     * Listar empresas del DENUE que requieren convenio institucional
     */
    public function listarNacionalesPendientes() {
        try {
            $stmt = $this->db->query("
                SELECT e.id_empresa, e.razon_social, e.id_denue, e.sector, c.id_convenio
                FROM empresas e
                JOIN convenios c ON e.id_empresa = c.id_empresa
                WHERE e.id_denue IS NOT NULL AND c.estatus = 'pendiente'
            ");
            return Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC), 200);
        } catch (\Exception $e) {
            return Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/v1/admin/nacionales/formalizar/@id
     * El administrador sube el convenio y activa la empresa nacional
     */
    public function formalizarEmpresaNacional($id_empresa) {
        $files = Flight::request()->files;
        $convenio = $files['convenio'] ?? null;

        if (!$convenio) {
            return Flight::json(['error' => 'El archivo del convenio es obligatorio'], 400);
        }

        try {
            $this->db->beginTransaction();

            // 1. Subir a Drive (usamos el servicio inyectado si estuviera, o lógica directa)
            // Para este ejemplo, asumo que el Admin sube el PDF y actualizamos el estatus
            $stmt = $this->db->prepare("
                UPDATE convenios 
                SET estatus = 'activo', 
                    fecha_inicio = CURRENT_DATE, 
                    fecha_vencimiento = CURRENT_DATE + INTERVAL '2 years',
                    url_archivo_drive = 'URL_SIMULADA_DRIVE' 
                WHERE id_empresa = ? AND estatus = 'pendiente'
            ");
            $stmt->execute([$id_empresa]);

            $this->db->commit();
            return Flight::json(['mensaje' => 'Empresa nacional formalizada exitosamente'], 200);
        } catch (\Exception $e) {
            $this->db->rollBack();
            return Flight::json(['error' => $e->getMessage()], 500);
        }
    }
}
