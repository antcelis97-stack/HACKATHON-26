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

    /**
     * POST /api/v1/empresas/vacantes
     * Crear una nueva vacante con perfiles idóneos (benchmarks)
     */
    public function crearVacante() {
        $data = $this->getInput();
        
        // Validación de campos obligatorios
        if (!isset($data['id_empresa'], $data['titulo_puesto'])) {
            return Flight::json(['error' => 'ID de empresa y título del puesto son requeridos'], 400);
        }

        try {
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