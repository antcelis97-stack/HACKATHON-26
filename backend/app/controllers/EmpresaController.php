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

            // 3. Crear la Empresa básica
            $stmtEmp = $this->db->prepare("INSERT INTO empresas (id_usuario, id_denue, razon_social) VALUES (?, ?, ?)");
            $stmtEmp->execute([
                $id_usuario,
                $data['id_denue'] ?? null,
                $data['razon_social']
            ]);
            $id_empresa = $this->db->lastInsertId();

            // 4. Registrar el Convenio inicial en la nueva tabla
            $stmtConv = $this->db->prepare("
                INSERT INTO convenios (id_empresa, url_archivo_drive, estatus) 
                VALUES (?, ?, 'en_revision')
            ");
            $stmtConv->execute([$id_empresa, $resultadoDrive['url']]);

            // 5. Información de contacto inicial
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
                'mensaje' => 'Empresa registrada y convenio enviado a revisión',
                'id_usuario' => $id_usuario,
                'id_empresa' => $id_empresa,
                'url_convenio' => $resultadoDrive['url']
            ], 201);

        } catch (\Exception $e) {
            $this->db->rollBack();
            return Flight::json(['error' => 'Fallo al registrar empresa: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/v1/empresas/vacantes
     * Crear una nueva vacante con perfiles idóneos (benchmarks)
     */
    public function crearVacante() {
        $data = $this->getInput();
        
        // 1. Validación de campos obligatorios
        if (!isset($data['id_empresa'], $data['titulo_puesto'])) {
            return Flight::json(['error' => 'ID de empresa y título del puesto son requeridos'], 400);
        }

        try {
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

            return Flight::json(['mensaje' => 'Vacante publicada exitosamente con su perfil idóneo'], 201);

        } catch (\Exception $e) {
            return Flight::json(['error' => 'Error al publicar la vacante', 'detalle' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/v1/empresa/registrar-contratado
     * La empresa formaliza la contratación de un egresado que se postuló
     */
    public function registrarContratacion() {
        $user = Flight::get('user');
        $data = $this->getInput();

        if (!isset($data['id_postulacion'], $data['puesto'])) {
            return Flight::json(['error' => 'id_postulacion y puesto son requeridos'], 400);
        }

        try {
            $this->db->beginTransaction();

            // 1. Obtener datos de la postulación y verificar propiedad de la empresa
            $stmtPost = $this->db->prepare("
                SELECT p.cve_alumno, v.id_empresa 
                FROM postulaciones p 
                JOIN vacantes v ON p.id_vacante = v.id_vacante 
                WHERE p.id_postulacion = ?
            ");
            $stmtPost->execute([$data['id_postulacion']]);
            $postulacion = $stmtPost->fetch(\PDO::FETCH_ASSOC);

            if (!$postulacion) {
                return Flight::json(['error' => 'Postulación no encontrada'], 404);
            }

            // 2. Crear registro en contrataciones
            $stmtCont = $this->db->prepare("
                INSERT INTO contrataciones (cve_alumno, id_empresa, puesto_asignado, estatus) 
                VALUES (?, ?, ?, 'activo')
            ");
            $stmtCont->execute([
                $postulacion['cve_alumno'], 
                $postulacion['id_empresa'], 
                $data['puesto']
            ]);

            // 3. Actualizar estatus del egresado
            $stmtEgr = $this->db->prepare("UPDATE egresados SET estatus_laboral = 'contratado' WHERE cve_alumno = ?");
            $stmtEgr->execute([$postulacion['cve_alumno']]);

            // 4. Actualizar estatus de la postulación
            $stmtUpPost = $this->db->prepare("UPDATE postulaciones SET estatus = 'contratado' WHERE id_postulacion = ?");
            $stmtUpPost->execute([$data['id_postulacion']]);

            $this->db->commit();
            return Flight::json(['mensaje' => 'Contratación formalizada exitosamente'], 201);

        } catch (\Exception $e) {
            $this->db->rollBack();
            return Flight::json(['error' => $e->getMessage()], 500);
        }
    }
}