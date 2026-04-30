<?php
namespace App\Controllers;

use Flight;
use App\Lib\GoogleDriveService;
use App\Lib\ResponseFormatter;
use App\Lib\Logger;
use Exception;

class GoogleDriveController extends BaseController {
    
    private $driveService;

    public function __construct() {
        try {
            $this->driveService = new GoogleDriveService();
        } catch (Exception $e) {
            // El error ya se loguea en el servicio
        }
    }

    /**
     * Endpoint específico para subir CVs
     * POST /api/v1/drive/subir/cv
     */
    public function subirCV() {
        return $this->procesarFlujoSubida('cv');
    }

    /**
     * Endpoint específico para subir Fotos de Egresados
     * POST /api/v1/drive/subir/foto
     */
    public function subirFoto() {
        return $this->procesarFlujoSubida('foto');
    }

    /**
     * Endpoint específico para subir Logos de Empresas
     * POST /api/v1/drive/subir/logo
     */
    public function subirLogo() {
        return $this->procesarFlujoSubida('logo');
    }

    /**
     * Endpoint específico para subir Convenios
     * POST /api/v1/drive/subir/convenio
     */
    public function subirConvenio() {
        return $this->procesarFlujoSubida('convenio');
    }

    /**
     * Función interna que coordina el flujo según el tipo
     */
    private function procesarFlujoSubida($tipo) {
        if (!$this->driveService) {
            return Flight::json(ResponseFormatter::error("Servicio de Drive no disponible"), 503);
        }

        $files = Flight::request()->files;
        $file = $files['archivo'] ?? null;
        $usuario_id = Flight::get('user')->sub ?? null;

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return Flight::json(ResponseFormatter::error("Archivo no recibido"), 400);
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

    /**
     * Determina la tabla y carpetas según el tipo de archivo
     */
    private function obtenerConfiguracionPorTipo($tipo) {
        $config = ['folderId' => null, 'tabla' => null, 'columna' => null, 'id_columna' => null];

        switch ($tipo) {
            case 'cv':
                $config = [
                    'folderId' => $_ENV['DRIVE_FOLDER_CVS'],
                    'tabla' => 'egresados',
                    'columna' => 'url_cv_drive',
                    'id_columna' => 'cve_alumno'
                ];
                break;
            case 'foto':
                $config = [
                    'folderId' => $_ENV['DRIVE_FOLDER_FOTOS'],
                    'tabla' => 'egresados',
                    'columna' => 'url_foto_drive',
                    'id_columna' => 'cve_alumno'
                ];
                break;
            case 'logo':
                $config = [
                    'folderId' => $_ENV['DRIVE_FOLDER_LOGOS'],
                    'tabla' => 'empresas',
                    'columna' => 'id_denue',
                    'id_columna' => 'id_empresa'
                ];
                break;
            case 'convenio':
                $config = [
                    'folderId' => $_ENV['DRIVE_FOLDER_CONVENIOS'],
                    'tabla' => 'empresas',
                    'columna' => 'url_convenio_drive',
                    'id_columna' => 'id_empresa'
                ];
                break;
        }
        return $config;
    }

    /**
     * Maneja la subida física a Google Drive
     */
    private function ejecutarSubidaDrive($file, $folderId) {
        try {
            $res = $this->driveService->uploadFile($file['name'], $file['tmp_name'], $file['type'], $folderId);
            return ['success' => true, 'id' => $res['id'], 'url' => $res['url']];
        } catch (Exception $e) {
            Logger::error("Fallo subida Drive", ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Maneja la actualización de la base de datos
     */
    private function persistirEnBaseDatos($config, $url, $usuario_id) {
        if (!$config['tabla'] || !$usuario_id) return ['success' => false, 'error' => 'Config inactiva'];

        try {
            $sql = "UPDATE {$config['tabla']} SET {$config['columna']} = ? WHERE {$config['id_columna']} = (SELECT {$config['id_columna']} FROM {$config['tabla']} WHERE id_usuario = ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$url, $usuario_id]);
            return ['success' => true];
        } catch (Exception $e) {
            Logger::error("Fallo DB", ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Elimina un archivo de Drive
     */
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
    }
}