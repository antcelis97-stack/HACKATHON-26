<?php 
namespace App\Controllers; 

use Flight; 
use PDO;
use App\Lib\GoogleDriveService;

class AdministradorController extends BaseController {

    private $driveService;

    public function __construct() {
        try {
            $this->driveService = new GoogleDriveService();
        } catch (\Exception $e) {
            // Error manejado
        }
    }

    /**
     * GET /api/v1/admin/convenios/pendientes
     * Listar todas las empresas (locales o nacionales) que requieren formalización
     */
    public function listarEmpresasPendientes() {
        try {
            $stmt = $this->db->query("
                SELECT 
                    e.id_empresa,
                    e.razon_social,
                    e.id_denue,
                    c.id_convenio,
                    c.fecha_registro,
                    c.estatus,
                    c.comentarios
                FROM empresas e
                JOIN convenios c ON e.id_empresa = c.id_empresa
                WHERE c.estatus = 'pendiente' OR c.estatus = 'en_revision'
                ORDER BY c.fecha_registro ASC
            ");
            return Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC), 200);
        } catch (\Exception $e) {
            return Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/v1/admin/empresas/formalizar/@id
     * El Administrador sube el convenio a Drive y activa la empresa
     */
    public function formalizarEmpresa($id_empresa) {
        $files = Flight::request()->files;
        $convenioFile = $files['convenio'] ?? null;

        if (!$convenioFile || $convenioFile['error'] !== UPLOAD_ERR_OK) {
            return Flight::json(['error' => 'El archivo del convenio es obligatorio para formalizar'], 400);
        }

        if (!$this->driveService) {
            return Flight::json(['error' => 'Servicio de Google Drive no disponible'], 503);
        }

        try {
            $this->db->beginTransaction();

            // 1. Subir convenio a Google Drive
            $folderId = $_ENV['DRIVE_FOLDER_CONVENIOS'];
            $resultadoDrive = $this->driveService->uploadFile(
                $convenioFile['name'],
                $convenioFile['tmp_name'],
                $convenioFile['type'],
                $folderId
            );

            // 2. Activar el convenio en la base de datos
            $fechaInicio = date('Y-m-d');
            $fechaVencimiento = date('Y-m-d', strtotime('+2 years'));

            $stmt = $this->db->prepare("
                UPDATE convenios 
                SET estatus = 'activo', 
                    url_archivo_drive = ?, 
                    fecha_inicio = ?, 
                    fecha_vencimiento = ?,
                    comentarios = 'Formalizado por Administrador'
                WHERE id_empresa = ?
            ");
            $stmt->execute([$resultadoDrive['url'], $fechaInicio, $fechaVencimiento, $id_empresa]);

            // 3. Activar el estado de la empresa si estaba desactivado
            $stmtEmp = $this->db->prepare("UPDATE empresas SET estado = TRUE WHERE id_empresa = ?");
            $stmtEmp->execute([$id_empresa]);

            $this->db->commit();
            return Flight::json([
                'mensaje' => 'Empresa formalizada y activada correctamente',
                'url_convenio' => $resultadoDrive['url']
            ], 200);

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return Flight::json(['error' => 'Error al formalizar: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/v1/admin/convenios/rechazar/@id
     */
    public function rechazarConvenio($id) {
        $data = $this->getInput();
        $motivo = $data['motivo'] ?? 'No cumple con los requisitos institucionales';

        try {
            $stmt = $this->db->prepare("UPDATE convenios SET estatus = 'rechazado', comentarios = ? WHERE id_convenio = ?");
            $stmt->execute([$motivo, $id]);
            return Flight::json(['mensaje' => 'Convenio rechazado'], 200);
        } catch (\Exception $e) {
            return Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function listarConveniosAceptados() {
        $stmt = $this->db->query("SELECT c.id_convenio, e.razon_social, c.fecha_inicio, c.fecha_vencimiento, c.url_archivo_drive FROM convenios c JOIN empresas e ON c.id_empresa = e.id_empresa WHERE c.estatus = 'activo'");
        return Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC), 200);
    }
}
