<?php

namespace app\controllers;

use Flight;
use PDO;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Lib\ResponseFormatter;
use App\Lib\Logger;
use App\Lib\AuditLog;
use App\Lib\NotFoundException;
use App\Lib\UnauthorizedException;
use App\Lib\ValidationException;

/**
 * EjemploController - Módulo Completo de Ejemplo
 * 
 * Este controller demuestra el patrón completo de desarrollo:
 * - CRUD de empleados
 * - Autenticación con JWT
 * - Paginación
 * - Validación
 * - Auditoría
 * - Logging
 * 
 * Endpoints disponibles:
 * - POST   /api/v1/login           -> Iniciar sesión
 * - GET    /api/v1/empleados       -> Listar empleados (público)
 * - GET    /api/v1/empleados/{id} -> Ver empleado (protegido)
 * - POST   /api/v1/empleados      -> Crear empleado (protegido)
 * - PUT    /api/v1/empleados/{id} -> Actualizar empleado (protegido)
 * - DELETE /api/v1/empleados/{id}  -> Eliminar empleado (protegido)
 */
class EjemploController
{
    private static string $jwtSecret;
    private static int $jwtExpire = 3600; // 1 hora

    // =========================================================================
    // AUTENTICACIÓN
    // =========================================================================

    /**
     * POST /api/v1/login
     * Iniciar sesión y obtener JWT
     */
    public static function login(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            // Validar campos requeridos
            if (empty($data['usuario']) || empty($data['contrasena'])) {
                Flight::json(ResponseFormatter::validationError([
                    ['field' => 'usuario', 'message' => 'Usuario requerido'],
                    ['field' => 'contrasena', 'message' => 'Contraseña requerida']
                ]), 400);
                return;
            }

            require_once __DIR__ . '/../../config/database.php';
            $pdo = getPgConnection();

            // Buscar usuario
            $stmt = $pdo->prepare("
                SELECT u.*, r.nombre as rol_nombre
                FROM public.usuarios u
                LEFT JOIN public.roles r ON u.rol_id = r.id
                WHERE u.usuario = :usuario AND u.activo = true
            ");
            $stmt->execute([':usuario' => $data['usuario']]);
            $user = $stmt->fetch();

            if (!$user) {
                throw new UnauthorizedException("Usuario o contraseña incorrectos");
            }

            // Verificar contraseña
            if (!password_verify($data['contrasena'], $user['contrasena_hash'])) {
                throw new UnauthorizedException("Usuario o contraseña incorrectos");
            }

            // Generar JWT
            $token = self::generarToken($user);

            // Generar refresh token
            $refreshToken = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

            // Guardar refresh token
            $stmt = $pdo->prepare("
                INSERT INTO public.refresh_tokens (usuario_id, token, expires_at)
                VALUES (:usuario_id, :token, :expires)
            ");
            $stmt->execute([
                ':usuario_id' => $user['id'],
                ':token' => hash('sha256', $refreshToken),
                ':expires' => $expiresAt
            ]);

            Logger::info("Login exitoso", ['usuario' => $user['usuario']]);

            Flight::json(ResponseFormatter::success([
                'token' => $token,
                'refresh_token' => $refreshToken,
                'expires_in' => self::$jwtExpire,
                'usuario' => [
                    'id' => $user['id'],
                    'usuario' => $user['usuario'],
                    'nombre' => $user['nombre'],
                    'rol' => $user['rol_nombre']
                ]
            ]));

        } catch (UnauthorizedException $e) {
            Logger::warning("Login fallido", ['usuario' => $data['usuario'] ?? 'desconocido']);
            Flight::json(ResponseFormatter::unauthorized($e->getMessage()), 401);
        } catch (\Exception $e) {
            Logger::error("Error en login", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * POST /api/v1/refresh-token
     * Renovar access token
     */
    public static function refreshToken(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['refresh_token'])) {
                Flight::json(ResponseFormatter::validationError([
                    ['field' => 'refresh_token', 'message' => 'Refresh token requerido']
                ]), 400);
                return;
            }

            require_once __DIR__ . '/../../config/database.php';
            $pdo = getPgConnection();

            // Buscar refresh token
            $tokenHash = hash('sha256', $data['refresh_token']);
            $stmt = $pdo->prepare("
                SELECT rt.*, u.*
                FROM public.refresh_tokens rt
                JOIN public.usuarios u ON rt.usuario_id = u.id
                WHERE rt.token = :token AND rt.expires_at > NOW() AND u.activo = true
            ");
            $stmt->execute([':token' => $tokenHash]);
            $record = $stmt->fetch();

            if (!$record) {
                throw new UnauthorizedException("Refresh token inválido o expirado");
            }

            // Generar nuevo access token
            $token = self::generarToken($record);

            Flight::json(ResponseFormatter::success([
                'token' => $token,
                'expires_in' => self::$jwtExpire
            ]));

        } catch (UnauthorizedException $e) {
            Flight::json(ResponseFormatter::unauthorized($e->getMessage()), 401);
        } catch (\Exception $e) {
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    // =========================================================================
    // CRUD EMPLEADOS (EJEMPLO DE MÓDULO)
    // =========================================================================

    /**
     * GET /api/v1/empleados
     * Listar empleados con paginación
     */
    public static function listar(): void
    {
        try {
            require_once __DIR__ . '/../../config/database.php';
            $pdo = getPgConnection();

            // Parámetros de paginación
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;

            // Filtros
            $where = [];
            $params = [];

            if (!empty($_GET['area'])) {
                $where[] = "area ILIKE :area";
                $params[':area'] = '%' . $_GET['area'] . '%';
            }

            if (isset($_GET['activo'])) {
                $where[] = "activo = :activo";
                $params[':activo'] = $_GET['activo'] === 'true';
            }

            if (!empty($_GET['search'])) {
                $where[] = "(nombre_completo ILIKE :search OR numero_empleado ILIKE :search)";
                $params[':search'] = '%' . $_GET['search'] . '%';
            }

            $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            // Query principal
            $sql = "SELECT * FROM public.empleados $whereSql ORDER BY nombre_completo LIMIT :limit OFFSET :offset";
            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $empleados = $stmt->fetchAll();

            // Count total
            $countSql = "SELECT COUNT(*) FROM public.empleados $whereSql";
            $countStmt = $pdo->prepare($countSql);
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $total = (int)$countStmt->fetchColumn();

            Logger::debug("Empleados listados", ['page' => $page, 'total' => $total]);

            Flight::json(ResponseFormatter::paginated($empleados, $page, $limit, $total));

        } catch (\Exception $e) {
            Logger::error("Error al listar empleados", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/empleados/{id}
     * Ver un empleado por ID
     */
    public static function ver(int $id): void
    {
        try {
            require_once __DIR__ . '/../../config/database.php';
            $pdo = getPgConnection();

            $stmt = $pdo->prepare("SELECT * FROM public.empleados WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $empleado = $stmt->fetch();

            if (!$empleado) {
                throw new NotFoundException("Empleado no encontrado");
            }

            Flight::json(ResponseFormatter::success($empleado));

        } catch (NotFoundException $e) {
            Flight::json(ResponseFormatter::notFound("Empleado"), 404);
        } catch (\Exception $e) {
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * POST /api/v1/empleados
     * Crear un nuevo empleado
     */
    public static function crear(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            // Validar
            $errors = self::validarEmpleado($data);
            if (!empty($errors)) {
                Flight::json(ResponseFormatter::validationError($errors), 400);
                return;
            }

            require_once __DIR__ . '/../../config/database.php';
            $pdo = getPgConnection();
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO public.empleados 
                (numero_empleado, nombre_completo, email, telefono, area, puesto, activo)
                VALUES (:num, :nombre, :email, :telefono, :area, :puesto, :activo)
                RETURNING id
            ");

            $stmt->execute([
                ':num' => $data['numero_empleado'],
                ':nombre' => $data['nombre_completo'],
                ':email' => $data['email'] ?? null,
                ':telefono' => $data['telefono'] ?? null,
                ':area' => $data['area'] ?? null,
                ':puesto' => $data['puesto'] ?? null,
                ':activo' => $data['activo'] ?? true
            ]);

            $nuevoId = $stmt->fetch()['id'];
            $pdo->commit();

            // Auditoría
            AuditLog::create('empleados', $nuevoId, $data, self::getUserIdFromToken());
            Logger::info("Empleado creado", ['id' => $nuevoId, 'numero' => $data['numero_empleado']]);

            Flight::json(ResponseFormatter::created(['id' => $nuevoId]), 201);

        } catch (\Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            
            // Handle duplicate key
            if (strpos($e->getMessage(), 'unique') !== false) {
                Flight::json(ResponseFormatter::validationError([
                    ['field' => 'numero_empleado', 'message' => 'Ya existe un empleado con este número']
                ]), 400);
                return;
            }
            
            Logger::error("Error al crear empleado", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * PUT /api/v1/empleados/{id}
     * Actualizar un empleado
     */
    public static function actualizar(int $id): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            require_once __DIR__ . '/../../config/database.php';
            $pdo = getPgConnection();

            // Obtener estado actual
            $stmt = $pdo->prepare("SELECT * FROM public.empleados WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $antes = $stmt->fetch();

            if (!$antes) {
                throw new NotFoundException("Empleado no encontrado");
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                UPDATE public.empleados 
                SET numero_empleado = COALESCE(:num, numero_empleado),
                    nombre_completo = COALESCE(:nombre, nombre_completo),
                    email = COALESCE(:email, email),
                    telefono = COALESCE(:telefono, telefono),
                    area = COALESCE(:area, area),
                    puesto = COALESCE(:puesto, puesto),
                    activo = COALESCE(:activo, activo),
                    updated_at = NOW()
                WHERE id = :id
            ");

            $stmt->execute([
                ':id' => $id,
                ':num' => $data['numero_empleado'] ?? null,
                ':nombre' => $data['nombre_completo'] ?? null,
                ':email' => $data['email'] ?? null,
                ':telefono' => $data['telefono'] ?? null,
                ':area' => $data['area'] ?? null,
                ':puesto' => $data['puesto'] ?? null,
                ':activo' => $data['activo'] ?? null
            ]);

            $pdo->commit();

            // Auditoría
            AuditLog::update('empleados', $id, $antes, $data, self::getUserIdFromToken());
            Logger::info("Empleado actualizado", ['id' => $id]);

            Flight::json(ResponseFormatter::success(['id' => $id]));

        } catch (NotFoundException $e) {
            Flight::json(ResponseFormatter::notFound("Empleado"), 404);
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * DELETE /api/v1/empleados/{id}
     * Eliminar un empleado (soft delete - cambiar activo a false)
     */
    public static function eliminar(int $id): void
    {
        try {
            require_once __DIR__ . '/../../config/database.php';
            $pdo = getPgConnection();

            // Obtener datos antes
            $stmt = $pdo->prepare("SELECT * FROM public.empleados WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $empleado = $stmt->fetch();

            if (!$empleado) {
                throw new NotFoundException("Empleado no encontrado");
            }

            $pdo->beginTransaction();

            // Soft delete: cambiar activo a false
            $stmt = $pdo->prepare("UPDATE public.empleados SET activo = false, updated_at = NOW() WHERE id = :id");
            $stmt->execute([':id' => $id]);

            $pdo->commit();

            // Auditoría
            AuditLog::delete('empleados', $id, $empleado, self::getUserIdFromToken());
            Logger::warning("Empleado eliminado", ['id' => $id, 'numero' => $empleado['numero_empleado']]);

            Flight::json(ResponseFormatter::noContent());

        } catch (NotFoundException $e) {
            Flight::json(ResponseFormatter::notFound("Empleado"), 404);
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    // =========================================================================
    // MÉTODOS AUXILIARES
    // =========================================================================

    /**
     * Generar JWT para un usuario
     */
    private static function generarToken(array $user): string
    {
        self::$jwtSecret = $_ENV['API_KEY'] ?? 'clave_secreta_para_desarrollo_32chars';

        $payload = [
            'sub' => $user['id'],
            'usuario' => $user['usuario'],
            'rol' => $user['rol_nombre'] ?? 'Sin rol',
            'iat' => time(),
            'exp' => time() + self::$jwtExpire
        ];

        return JWT::encode($payload, self::$jwtSecret, 'HS256');
    }

    /**
     * Validar datos de empleado
     */
    private static function validarEmpleado(array $data): array
    {
        $errors = [];

        if (empty($data['numero_empleado'])) {
            $errors[] = ['field' => 'numero_empleado', 'message' => 'Número de empleado requerido'];
        } elseif (strlen($data['numero_empleado']) > 20) {
            $errors[] = ['field' => 'numero_empleado', 'message' => 'Máximo 20 caracteres'];
        }

        if (empty($data['nombre_completo'])) {
            $errors[] = ['field' => 'nombre_completo', 'message' => 'Nombre requerido'];
        } elseif (strlen($data['nombre_completo']) > 150) {
            $errors[] = ['field' => 'nombre_completo', 'message' => 'Máximo 150 caracteres'];
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = ['field' => 'email', 'message' => 'Formato de email inválido'];
        }

        return $errors;
    }

    /**
     * Obtener user_id del token JWT (si existe)
     */
    private static function getUserIdFromToken(): ?int
    {
        try {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            if (preg_match('/^Bearer\s+(.+)$/', $authHeader, $matches)) {
                $token = $matches[1];
                self::$jwtSecret = $_ENV['API_KEY'] ?? 'clave_secreta_para_desarrollo_32chars';
                $decoded = JWT::decode($token, new Key(self::$jwtSecret, 'HS256'));
                return $decoded->sub ?? null;
            }
        } catch (\Exception $e) {
            // Token inválido o no presente
        }
        return null;
    }
}
