<?php
namespace App\Controllers;

use Flight;
use PDO;

class AuthController extends BaseController {
    /**
     * POST /api/v1/auth/register
     * Registro de usuario genérico
     */
    public function register() {
        $data = $this->getInput();
        
        if (!isset($data['usuario'], $data['password'], $data['rol'])) {
            return Flight::json(['error' => 'Usuario, contraseña y rol son requeridos'], 400);
        }
        
        try {
            $hash = password_hash($data['password'], PASSWORD_BCRYPT);
            $stmt = $this->db->prepare("INSERT INTO usuarios (username, password_hash, rol) VALUES (?, ?, ?)");
            
            if ($stmt->execute([$data['usuario'], $hash, $data['rol']])) {
                return Flight::json(['mensaje' => 'Usuario registrado exitosamente'], 201);
            }
        } catch (\Exception $e) {
            return Flight::json(['error' => 'Error al registrar usuario', 'detalle' => $e->getMessage()], 500);
        }
    }
}