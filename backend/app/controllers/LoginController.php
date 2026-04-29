<?php

namespace app\controllers;

use Flight;
use PDO;
use Firebase\JWT\JWT;
use App\Lib\ResponseFormatter;
use App\Lib\Logger;

class LoginController
{
    /**
     * Obtiene la conexión a la base de datos de inventario.
     */
    private static function getDb(): \PDO
    {
        require_once __DIR__ . '/../../config/database.php';
        return getPgInventarioConnection();
    }

    /**
     * POST /api/v1/login
     * Autentica al usuario y devuelve un token JWT.
     */
    public static function login(): void
    {
        try {
            $data = Flight::request()->data;
            $usuario = $data->usuario ?? '';
            $password = $data->password ?? '';

            if (empty($usuario) || empty($password)) {
                Flight::json(ResponseFormatter::error('Usuario y contraseña son obligatorios', 400), 400);
                return;
            }

            $pdo = self::getDb();

            // 1. Buscar usuario y datos básicos de la persona
            $stmt = $pdo->prepare("
                SELECT u.*, p.nombre AS persona_nombre, p.apellido_paterno, p.apellido_materno
                FROM usuarios u
                LEFT JOIN persona p ON u.cve_persona = p.cve_persona
                WHERE u.usuario = :usuario AND u.activo = true
            ");
            $stmt->execute([':usuario' => $usuario]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                Flight::json(ResponseFormatter::unauthorized('Credenciales inválidas'), 401);
                return;
            }

            // 2. Verificar contraseña
            if (!password_verify($password, $user['contrasena_hash'])) {
                Flight::json(ResponseFormatter::unauthorized('Credenciales inválidas'), 401);
                return;
            }

            // 3. Cargar roles del usuario
            $stmtRoles = $pdo->prepare("
                SELECT r.nombre
                FROM roles r
                WHERE r.cve_rol = :cve_rol
            ");
            $stmtRoles->execute([':cve_rol' => $user['cve_rol']]);
            $rol = $stmtRoles->fetchColumn();
            $roles = $rol ? [$rol] : [];

            // 4. Generar JWT
            $secret = $_ENV['API_KEY'] ?? 'clave_secreta_para_desarrollo_32chars';
            $payload = [
                'sub' => (int)$user['cve_usuario'],
                'usuario' => $user['usuario'],
                'cve_persona' => $user['cve_persona'],
                'rol' => $rol, // Usamos 'rol' para compatibilidad con authMiddleware
                'roles' => $roles,
                'iat' => time(),
                'exp' => time() + (3600 * 24 * 7) // 7 días
            ];

            $jwt = JWT::encode($payload, $secret, 'HS256');

            // 5. Registrar en Flight (opcional, para esta petición)
            Flight::set('user_id', $user['cve_usuario']);

            Logger::info("Inicio de sesión exitoso", ['usuario' => $user['usuario']]);

            Flight::json(ResponseFormatter::success([
                'access_token' => $jwt,
                'token_type' => 'Bearer',
                'expires_in' => 3600 * 24 * 7,
                'user' => [
                    'cve_usuario' => (int)$user['cve_usuario'],
                    'cve_persona' => $user['cve_persona'],
                    'usuario' => $user['usuario'],
                    'nombre' => $user['persona_nombre'] ?? $user['nombre'],
                    'apellido_paterno' => $user['apellido_paterno'],
                    'apellido_materno' => $user['apellido_materno'],
                    'email' => $user['email'],
                    'rol' => $rol,
                    'roles' => $roles,
                    'activo' => (bool)$user['activo']
                ]
            ]));

        } catch (\Exception $e) {
            Logger::error('Error en login: ' . $e->getMessage());
            Flight::json(ResponseFormatter::error('Error interno del servidor: ' . $e->getMessage(), 500), 500);
        }
    }

    /**
     * GET /api/v1/perfil/nombre
     * Obtiene el nombre completo del "dueño" (persona) asociada a la cuenta.
     */
    public static function getNombreUsuario(): void
    {
        try {
            $userId = Flight::get('user_id');

            if (!$userId) {
                Flight::json(ResponseFormatter::unauthorized("Usuario no autenticado"), 401);
                return;
            }

            $pdo = self::getDb();
            // Buscamos los datos directamente en la tabla persona asociada al usuario
            $stmt = $pdo->prepare("
                SELECT 
                    p.nombre || ' ' || COALESCE(p.apellido_paterno, '') || ' ' || COALESCE(p.apellido_materno, '') AS nombre_completo
                FROM usuarios u
                INNER JOIN persona p ON u.cve_persona = p.cve_persona
                WHERE u.cve_usuario = :id
            ");
            $stmt->execute([':id' => $userId]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$resultado) {
                Flight::json(ResponseFormatter::error("No se encontró una persona asociada a este usuario"), 404);
                return;
            }

            Flight::json(ResponseFormatter::success([
                "nombre" => trim($resultado['nombre_completo'])
            ]));

        } catch (\Exception $e) {
            Logger::error("Error al obtener nombre del dueño", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }
}