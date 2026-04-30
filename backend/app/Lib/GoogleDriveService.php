<?php
namespace App\Lib;

use Google\Client;
use Google\Service\Drive;
use App\Lib\Logger;
use Exception;

class GoogleDriveService
{
    private $service;
    private $folderId;

    public function __construct()
    {
        // Cuenta de servicio: bolsa-de-trabajo@modulo-de-egresaods.iam.gserviceaccount.com
        try {
            $client = new Client();
            
            // Ruta al archivo de credenciales de la cuenta de servicio
            $keyFilePath = __DIR__ . '/../../config/google-key.json';
            
            if (!file_exists($keyFilePath)) {
                Logger::error("Archivo de credenciales de Google no encontrado en: " . $keyFilePath);
                throw new Exception("Configuración de Google Drive no disponible.");
            }

            $client->setAuthConfig($keyFilePath);
            $client->addScope(Drive::DRIVE_FILE);
            
            $this->service = new Drive($client);
            
        } catch (Exception $e) {
            Logger::error("Error inicializando Google Drive Service", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Sube un archivo a Google Drive
     * 
     * @param string $fileName Nombre con el que se guardará el archivo
     * @param string $tempPath Ruta temporal del archivo en el servidor
     * @param string $mimeType Tipo de archivo (ej. image/jpeg, application/pdf)
     * @param string|null $parentFolderId ID de la carpeta destino (opcional)
     * @return array [id, url]
     */
    public function uploadFile($fileName, $tempPath, $mimeType, $parentFolderId = null)
    {
        try {
            $targetFolder = $parentFolderId ?? $this->folderId;
            
            $fileMetadata = new Drive\DriveFile([
                'name' => $fileName,
                'parents' => $targetFolder ? [$targetFolder] : []
            ]);

            $content = file_get_contents($tempPath);

            $file = $this->service->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $mimeType,
                'uploadType' => 'multipart',
                'fields' => 'id, webViewLink'
            ]);

            // Hacer el archivo público para lectura (opcional, dependiendo de requerimientos)
            // $this->makeFilePublic($file->id);

            return [
                'id' => $file->id,
                'url' => $file->webViewLink
            ];

        } catch (Exception $e) {
            Logger::error("Error al subir archivo a Google Drive", [
                'fileName' => $fileName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Elimina un archivo de Google Drive
     * 
     * @param string $fileId ID del archivo en Drive
     */
    public function deleteFile($fileId)
    {
        try {
            $this->service->files->delete($fileId);
        } catch (Exception $e) {
            Logger::error("Error al eliminar archivo de Google Drive", [
                'fileId' => $fileId,
                'error' => $e->getMessage()
            ]);
            // No lanzamos excepción aquí para no romper el flujo si el archivo ya no existe
        }
    }

    /**
     * Opcional: Cambia los permisos del archivo para que cualquiera con el link pueda verlo
     */
    private function makeFilePublic($fileId)
    {
        $userPermission = new Drive\Permission([
            'type' => 'anyone',
            'role' => 'viewer'
        ]);
        
        $this->service->permissions->create($fileId, $userPermission);
    }
}
