<?php
namespace App\Controllers;

use Flight;
use PDO;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class LoginController extends BaseController {
    
    /**
     * Inicio de sesión con generación de JWT
     */
    public function iniciarSesion() {
        $datos = $this->getInput();
        
        if (!isset($datos['usuario'], $datos['password'])) {
            return Flight::json(['error' => 'Usuario y contraseña requeridos'], 400);
        }

        $stmt = $this->db->prepare("SELECT id_usuario, password_hash, rol FROM usuarios WHERE username = ? AND estado = TRUE");
        $stmt->execute([$datos['usuario']]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($datos['password'], $usuario['password_hash'])) {
            $id_usuario = $usuario['id_usuario'];
            $rol = $usuario['rol'];

            // Generar JWT Real
            $secreto = $_ENV['API_KEY'] ?? 'clave_secreta_para_desarrollo_32chars';
            $payload = [
                'sub' => $id_usuario,
                'rol' => $rol,
                'iat' => time(),
                'exp' => time() + (60 * 60) // Expira en 1 hora
            ];
            
            $token = JWT::encode($payload, $secreto, 'HS256');

            return Flight::json([
                'mensaje' => 'Inicio de sesión exitoso',
                'token' => $token, 
                'rol' => $rol,
                'usuario_id' => $id_usuario
            ], 200);
        }
        
        return Flight::json(['error' => 'Credenciales inválidas o cuenta desactivada'], 401);
    }

    /**
     * Renueva el token actual por uno nuevo
     */
    public function refreshToken() {
        $usuario = Flight::get('user'); // Obtenido por el middleware authMiddleware
        
        if (!$usuario) {
            return Flight::json(['error' => 'Token inválido o sesión expirada'], 401);
        }

        $secreto = $_ENV['API_KEY'] ?? 'clave_secreta_para_desarrollo_32chars';
        $payload = [
            'sub' => $usuario->sub,
            'rol' => $usuario->rol,
            'iat' => time(),
            'exp' => time() + (60 * 60) // Extender 1 hora más
        ];

        $nuevoToken = JWT::encode($payload, $secreto, 'HS256');

        return Flight::json([
            'mensaje' => 'Token renovado con éxito',
            'token' => $nuevoToken
        ], 200);
    }

    /**
     * Finaliza la sesión
     */
    public function cerrarSesion() {
        return Flight::json(['mensaje' => 'Sesión cerrada correctamente'], 200);
    }
}
