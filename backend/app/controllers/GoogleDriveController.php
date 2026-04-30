<?php
namespace App\Controllers;

use Flight;
use App\Lib\GoogleDriveService;
use App\Lib\ResponseFormatter;
use App\Lib\Logger;
use Exception;
use PDO;

class GoogleDriveController extends BaseController {
    
    private $driveService;

    public function __construct() {
        try {
            $this->driveService = new GoogleDriveService();
        } catch (Exception $e) {
            // Error manejado internamente
        }
    }

    public function subirCV() { return $this->procesarFlujoSubida('cv'); }
    public function subirFoto() { return $this->procesarFlujoSubida('foto'); }
    public function subirLogo() { return $this->procesarFlujoSubida('logo'); }
    public function subirConvenio() { return $this->procesarFlujoSubida('convenio'); }

    private function procesarFlujoSubida($tipo) {
        if (!$this->driveService) {
            return Flight::json(ResponseFormatter::error("Servicio de Drive no disponible"), 503);
        }

        $files = Flight::request()->files;
        $file = $files['archivo'] ?? $files['convenio'] ?? $files['foto'] ?? $files['cv'] ?? null;
        $usuario_id = Flight::get('user')->sub ?? null;

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return Flight::json(ResponseFormatter::error("Archivo no recibido o error en subida"), 400);
        }

        $config = $this->obtenerConfiguracionPorTipo($tipo);
        $resultadoDrive = $this->ejecutarSubidaDrive($file, $config['folderId']);

        if (!$resultadoDrive['success']) {
            return Flight::json(ResponseFormatter::error($resultadoDrive['error']), 500);
        }

        $registroDB = $this->persistirEnBaseDatos($config, $resultadoDrive['url'], $usuario_id);

        return Flight::json(ResponseFormatter::success([
            'id_drive' => $resultadoDrive['id'],
            'url_drive' => $resultadoDrive['url'],
            'db_actualizada' => $registroDB['success']
        ], "Subida de $tipo completada"));
    }

    private function obtenerConfiguracionPorTipo($tipo) {
        switch ($tipo) {
            case 'cv':
                return [
                    'folderId' => $_ENV['DRIVE_FOLDER_CVS'],
                    'tabla' => 'egresados',
                    'columna' => 'url_cv_drive',
                    'id_columna' => 'id_usuario'
                ];
            case 'foto':
                return [
                    'folderId' => $_ENV['DRIVE_FOLDER_FOTOS'],
                    'tabla' => 'egresados',
                    'columna' => 'url_foto_drive',
                    'id_columna' => 'id_usuario'
                ];
            case 'convenio':
                return [
                    'folderId' => $_ENV['DRIVE_FOLDER_CONVENIOS'],
                    'tabla' => 'empresas',
                    'columna' => 'url_convenio_drive',
                    'id_columna' => 'id_usuario'
                ];
            default:
                throw new Exception("Tipo de archivo no soportado");
        }
    }

    private function ejecutarSubidaDrive($file, $folderId) {
        try {
            $res = $this->driveService->uploadFile($file['name'], $file['tmp_name'], $file['type'], $folderId);
            return ['success' => true, 'id' => $res['id'], 'url' => $res['url']];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function persistirEnBaseDatos($config, $url, $usuario_id) {
        if (!$config['tabla'] || !$usuario_id) return ['success' => false];
        try {
            $sql = "UPDATE {$config['tabla']} SET {$config['columna']} = ? WHERE {$config['id_columna']} = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$url, $usuario_id]);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false];
        }
    }

    public function eliminarArchivo($id) {
        if (!$this->driveService) return Flight::json(ResponseFormatter::error("Servicio no disponible"), 503);
        try {
            $this->driveService->deleteFile($id);
            return Flight::json(ResponseFormatter::success(null, "Eliminado correctamente"));
        } catch (Exception $e) {
            return Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }
}