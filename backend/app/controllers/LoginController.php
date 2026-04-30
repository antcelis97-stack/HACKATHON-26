<?php
namespace App\Controllers;

use Flight;
use PDO;

class LoginController extends BaseController {
    public function login() {
        $data = $this->getInput();
        
        if (!isset($data['email'], $data['password'])) {
            return Flight::json(['error' => 'Email y contraseña requeridos'], 400);
        }

        $stmt = $this->db->prepare("SELECT id, password, rol FROM usuarios WHERE email = ?");
        $stmt->execute([$data['email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($data['password'], $user['password'])) {
            // Mock de un token para la sesión en Angular
            $token = base64_encode(random_bytes(32)); 
            return Flight::json([
                'mensaje' => 'Login exitoso',
                'token' => $token, 
                'rol' => $user['rol'],
                'usuario_id' => $user['id']
            ], 200);
        }
        
        return Flight::json(['error' => 'Credenciales inválidas'], 401);
    }
}
