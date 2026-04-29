<?php
namespace App\Controllers;

use Flight;

class AuthController extends BaseController {
    public function register() {
        $data = $this->getInput();
        
        if (!isset($data['email'], $data['password'], $data['rol'])) {
            // Utilizamos el método nativo de Flight para devolver JSON con el código HTTP
            return Flight::json(['error' => 'Datos incompletos'], 400);
        }
        
        $hash = password_hash($data['password'], PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("INSERT INTO usuarios (email, password, rol) VALUES (?, ?, ?)");
        
        if ($stmt->execute([$data['email'], $hash, $data['rol']])) {
            return Flight::json(['message' => 'Usuario registrado exitosamente'], 201);
        }
        
        return Flight::json(['error' => 'Error al registrar usuario'], 500);
    }
}