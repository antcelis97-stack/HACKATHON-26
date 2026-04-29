<?php

namespace app\controllers;

use Flight;
use PDO;
use App\Lib\ResponseFormatter;
use App\Lib\Logger;
use App\Lib\AuditLog;
use App\Lib\GoogleDriveService;

/**
 * GoogleDriveController - Subir y eliminar fotos de bienes en Google Drive
 * 
 * Endpoints disponibles:
 * - POST /api/v1/bienes/@id/foto     -> Subir foto a Google Drive
 * - DELETE /api/v1/bienes/@id/foto/@drive_id -> Eliminar foto de Google Drive
 */
class GoogleDriveController
{
    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/jpg'];

    /**
     * Obtiene conexión a BD
     */
    private static function getDb(): PDO
    {
        require_once __DIR__ . '/../../config/database.php';
        return getPgInventarioConnection();
    }

    /**
     * POST /api/v1/bienes/@id/foto
     * 
     * Sube una foto del bien a Google Drive y guarda el link en BD.
     * 
     * Request: multipart/form-data
     *   - foto: archivo (requerido)
     */
    public static function subirFoto(int $id): void
    {
        try {
            // 1. Validar que existe el bien
            $pdo = self::getDb();
            $stmt = $pdo->prepare("SELECT cve_bien, nombre FROM bienes WHERE cve_bien = :id AND activo = true");
            $stmt->execute([':id' => $id]);
            $bien = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$bien) {
                Flight::json(ResponseFormatter::error("Bien no encontrado", 404), 404);
                return;
            }

            // 2. Obtener archivo del request
            $request = Flight::request();
            $files = $request->files;
            
            if (empty($files['foto'])) {
                Flight::json(ResponseFormatter::error("No se recibió archivo", 400), 400);
                return;
            }

            $file = $files['foto'];
            
            // Validaciones del archivo
            if ($file['error'] !== UPLOAD_ERR_OK) {
                Flight::json(ResponseFormatter::error("Error al subir archivo", 400), 400);
                return;
            }

            if ($file['size'] > self::MAX_FILE_SIZE) {
                Flight::json(ResponseFormatter::error("El archivo excede 5MB", 400), 400);
                return;
            }

            $mimeType = $file['type'];
            if (!in_array($mimeType, self::ALLOWED_TYPES)) {
                Flight::json(ResponseFormatter::error("Formato no permitido. Use JPG o PNG", 400), 400);
                return;
            }

            // 3. Leer contenido del archivo
            $tmpPath = $file['tmp_name'];
            $originalName = $file['name'];
            $binaryData = file_get_contents($tmpPath);

            // 4. Generar nombre único: bien_{id}_{timestamp}.ext
            $ext = pathinfo($originalName, PATHINFO_EXTENSION);
            $filename = "bien_{$id}_" . time() . ".{$ext}";

            // 5. Subir a Google Drive
            $drive = new GoogleDriveService();
            $result = $drive->uploadBinary($binaryData, $filename);

            // 6. Guardar en BD
            $webViewLink = $result['web_view_link'];
            $fileId = $result['file_id'];

            // Si ya tenía foto anterior, la conservamos (puede avere varias fotos)
            // Opcional: actualizar con la última foto
            $updateStmt = $pdo->prepare("
                UPDATE bienes 
                SET foto_url = :foto_url, 
                    foto_drive_id = :foto_drive_id 
                WHERE cve_bien = :id
            ");
            $updateStmt->execute([
                ':foto_url' => $webViewLink,
                ':foto_drive_id' => $fileId,
                ':id' => $id
            ]);

            // 7. Log de auditoría
            AuditLog::update('bienes', $id, ['foto_url' => 'old'], ['foto_url' => $webViewLink]);

            // 8. Responder
            Flight::json(ResponseFormatter::success([
                'foto_url' => $webViewLink,
                'foto_drive_id' => $fileId,
                'nombre_original' => $originalName
            ]), 200);

        } catch (\Exception $e) {
            Logger::error("GoogleDriveController::subirFoto", $e->getMessage());
            Flight::json(ResponseFormatter::error("Error al subir foto: " . $e->getMessage(), 500), 500);
        }
    }

    /**
     * DELETE /api/v1/bienes/@id/foto/@drive_id
     * 
     * Elimina una foto del bien de Google Drive.
     */
    public static function eliminarFoto(int $id, string $driveId): void
    {
        try {
            $pdo = self::getDb();

            // 1. Verificar que el bien tiene esa foto asociada
            $stmt = $pdo->prepare("SELECT foto_drive_id, nombre FROM bienes WHERE cve_bien = :id");
            $stmt->execute([':id' => $id]);
            $bien = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$bien) {
                Flight::json(ResponseFormatter::error("Bien no encontrado", 404), 404);
                return;
            }

            if ($bien['foto_drive_id'] !== $driveId) {
                Flight::json(ResponseFormatter::error("Foto no asociada a este bien", 400), 400);
                return;
            }

            // 2. Eliminar de Google Drive
            $drive = new GoogleDriveService();
            $drive->delete($driveId);

            // 3. Limpiar en BD
            $updateStmt = $pdo->prepare("
                UPDATE bienes 
                SET foto_url = NULL, 
                    foto_drive_id = NULL 
                WHERE cve_bien = :id
            ");
            $updateStmt->execute([':id' => $id]);

            // 4. Log
            AuditLog::update('bienes', $id, ['foto_drive_id' => $driveId], ['foto_drive_id' => null]);

            Flight::json(ResponseFormatter::success([
                'message' => 'Foto eliminada correctamente'
            ]), 200);

        } catch (\Exception $e) {
            Logger::error("GoogleDriveController::eliminarFoto", $e->getMessage());
            Flight::json(ResponseFormatter::error("Error al eliminar foto: " . $e->getMessage(), 500), 500);
        }
    }

    /**
     * GET /api/v1/bienes/@id/foto
     * 
     * Ver datos de la foto del bien (NO el archivo, solo metadata).
     */
    public static function verFoto(int $id): void
    {
        try {
            $pdo = self::getDb();
            
            $stmt = $pdo->prepare("
                SELECT b.foto_url, b.foto_drive_id, b.nombre 
                FROM bienes b 
                WHERE b.cve_bien = :id
            ");
            $stmt->execute([':id' => $id]);
            $bien = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$bien) {
                Flight::json(ResponseFormatter::error("Bien no encontrado", 404), 404);
                return;
            }

            Flight::json(ResponseFormatter::success([
                'bien' => $bien['nombre'],
                'foto_url' => $bien['foto_url'],
                'foto_drive_id' => $bien['foto_drive_id']
            ]), 200);

        } catch (\Exception $e) {
            Logger::error("GoogleDriveController::verFoto", $e->getMessage());
            Flight::json(ResponseFormatter::error("Error al obtener foto", 500), 500);
        }
    }
}