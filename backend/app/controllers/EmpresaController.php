<?php
namespace App\Controllers;

use Flight;

use App\Lib\GoogleDriveService;

class EmpresaController extends BaseController {

    private $driveService;

    public function __construct() {
        try {
            $this->driveService = new GoogleDriveService();
        } catch (\Exception $e) {
            // Error manejado internamente
        }
    }

    /**
     * POST /api/v1/empresas/registrar
     * Registro de empresa, creación de usuario y subida de convenio
     */
    public function registrarEmpresa() {
        $data = $this->getInput();
        $files = Flight::request()->files;
        $convenioFile = $files['convenio'] ?? null;
        
        // Validación obligatoria incluyendo el archivo
        if (empty($data['usuario']) || empty($data['password']) || empty($data['razon_social']) || !$convenioFile) {
            return Flight::json(['error' => 'Usuario, contraseña, razón social y archivo de convenio son requeridos'], 400);
        }

        if ($convenioFile['error'] !== UPLOAD_ERR_OK) {
            return Flight::json(['error' => 'Error al recibir el archivo del convenio'], 400);
        }

        $this->db->beginTransaction();

        try {
            // 1. Crear el Usuario
            $passHash = password_hash($data['password'], PASSWORD_BCRYPT);
            $stmtUser = $this->db->prepare("INSERT INTO usuarios (username, password_hash, rol) VALUES (?, ?, 'empresa')");
            $stmtUser->execute([$data['usuario'], $passHash]);
            $id_usuario = $this->db->lastInsertId();

            // 2. Subir convenio a Google Drive
            $folderId = $_ENV['DRIVE_FOLDER_CONVENIOS'];
            $resultadoDrive = $this->driveService->uploadFile(
                $convenioFile['name'],
                $convenioFile['tmp_name'],
                $convenioFile['type'],
                $folderId
            );

            // 3. Crear la Empresa vinculada con la URL de Drive
            $stmtEmp = $this->db->prepare("INSERT INTO empresas (id_usuario, id_denue, razon_social, url_convenio_drive) VALUES (?, ?, ?, ?)");
            $stmtEmp->execute([
                $id_usuario,
                $data['id_denue'] ?? null,
                $data['razon_social'],
                $resultadoDrive['url']
            ]);

            // 4. Información de contacto inicial
            if (!empty($data['email']) || !empty($data['telefono'])) {
                $stmtCont = $this->db->prepare("INSERT INTO usuario_contacto (id_usuario, email, telefono, direccion) VALUES (?, ?, ?, ?)");
                $stmtCont->execute([
                    $id_usuario,
                    $data['email'] ?? null,
                    $data['telefono'] ?? null,
                    $data['direccion'] ?? null
                ]);
            }

            $this->db->commit();
            return Flight::json([
                'mensaje' => 'Empresa registrada y convenio subido con éxito',
                'id_usuario' => $id_usuario,
                'url_convenio' => $resultadoDrive['url']
            ], 201);

        } catch (\Exception $e) {
            $this->db->rollBack();
            return Flight::json(['error' => 'Fallo al registrar empresa: ' . $e->getMessage()], 500);
        }
    }

    public function createVacante() {
        $data = $this->getInput();
        
        if (!isset($data['empresa_id'], $data['titulo'], $data['req_psicometricos'], $data['req_tecnicos'])) {
            return Flight::json(['error' => 'Faltan datos obligatorios para la vacante'], 400);
        }

        $stmt = $this->db->prepare("INSERT INTO vacantes (empresa_id, titulo, perfil_idoneo, req_psicometricos, req_tecnicos, estatus) VALUES (?, ?, ?, ?, ?, 'activa')");
        
        // perfil_idoneo se guarda como JSON (ej. habilidades blandas)
        if ($stmt->execute([
            $data['empresa_id'], 
            $data['titulo'], 
            json_encode($data['perfil_idoneo'] ?? []), 
            $data['req_psicometricos'], 
            $data['req_tecnicos']
        ])) {
            return Flight::json(['message' => 'Vacante publicada exitosamente'], 201);
        }
        
        return Flight::json(['error' => 'Error al publicar la vacante'], 500);
    }
}