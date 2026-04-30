<?php
namespace App\Controllers;

use Flight;

class EmpresaController extends BaseController {

    /**
     * POST /api/v1/empresas/registrar
     * Registro de empresa y creación de usuario
     */
    public function registrarEmpresa() {
        $data = $this->getInput();
        
        // Validación mínima
        if (empty($data['usuario']) || empty($data['password']) || empty($data['razon_social'])) {
            return Flight::json(['error' => 'Usuario, contraseña y razón social son requeridos'], 400);
        }

        $this->db->beginTransaction();

        try {
            // 1. Crear el Usuario
            $passHash = password_hash($data['password'], PASSWORD_BCRYPT);
            $stmtUser = $this->db->prepare("INSERT INTO usuarios (username, password_hash, rol) VALUES (?, ?, 'empresa')");
            $stmtUser->execute([$data['usuario'], $passHash]);
            $id_usuario = $this->db->lastInsertId();

            // 2. Crear la Empresa vinculada
            $stmtEmp = $this->db->prepare("INSERT INTO empresas (id_usuario, id_denue, razon_social) VALUES (?, ?, ?)");
            $stmtEmp->execute([
                $id_usuario,
                $data['id_denue'] ?? null,
                $data['razon_social']
            ]);

            // 3. (Opcional) Información de contacto inicial
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
            return Flight::json(['mensaje' => 'Empresa registrada con éxito', 'id_usuario' => $id_usuario], 201);

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