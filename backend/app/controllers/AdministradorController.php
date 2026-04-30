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
}
