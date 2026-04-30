<?php
/**
 * Rutas del Sandbox SIEst
 * 
 * Este archivo define todas las rutas de la API.
 * Agrega tus rutas aquí siguiendo el patrón de ejemplo.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/controllers/EjemploController.php';
require_once __DIR__ . '/../app/controllers/LoginController.php';
require_once __DIR__ . '/../app/controllers/ProfesorController.php';
require_once __DIR__ . '/../app/controllers/AlumnoController.php';
require_once __DIR__ . '/../app/controllers/BarcodeController.php';
require_once __DIR__ . '/../app/controllers/AdministradorController.php';
require_once __DIR__ . '/../app/controllers/InformacionController.php';
require_once __DIR__ . '/../app/controllers/ReportesController.php';
require_once __DIR__ . '/../app/controllers/AuditoriasController.php';
require_once __DIR__ . '/../app/controllers/PapeleraController.php';
require_once __DIR__ . '/../app/controllers/PrestamosController.php';
require_once __DIR__ . '/../app/controllers/DirectorController.php';
require_once __DIR__ . '/../app/controllers/GoogleDriveController.php';


use App\Lib\ResponseFormatter;
use App\Lib\UnauthorizedException;
use App\Lib\Logger;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Cargar variables de entorno
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// Configurar Flight
Flight::register('view', 'Smarty');

/**
 * Middleware de autenticación
 * Verifica el JWT en el header Authorization
 */
function authMiddleware(): bool
{
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    if (!preg_match('/^Bearer\s+(.+)$/', $authHeader, $matches)) {
        Flight::json(ResponseFormatter::unauthorized("Token requerido"), 401);
        return false;
    }

    try {
        $token = $matches[1];
        $secret = $_ENV['API_KEY'] ?? 'clave_secreta_para_desarrollo_32chars';
        $decoded = JWT::decode($token, new Key($secret, 'HS256'));
        
        // Guardar usuario en Flight para usar después
        Flight::set('user', $decoded);
        Flight::set('user_id', $decoded->sub);
        
        return true;
        
    } catch (\Exception $e) {
        Flight::json(ResponseFormatter::unauthorized("Token inválido"), 401);
        return false;
    }
}

require_once __DIR__ . '/../app/Lib/ProfesorMiddleware.php';
require_once __DIR__ . '/../app/Lib/AlumnoMiddleware.php';

// =============================================================================
// RUTAS PÚBLICAS
// =============================================================================

// Health check
Flight::route('GET /', function() {
    Flight::json(ResponseFormatter::success([
        'message' => 'SIEst Sandbox API',
        'version' => '1.0.0',
        'endpoints' => [
            'POST /api/v1/login' => 'Iniciar sesión',
            'POST /api/v1/refresh-token' => 'Renovar token',
            'GET /api/v1/empleados' => 'Listar empleados (público)',
            'GET /api/v1/empleados/{id}' => 'Ver empleado',
            'GET /api/v1/profesor/contexto' => 'Módulo profesor (JWT rol Profesor)',
            'GET /api/v1/alumno/contexto' => 'Módulo alumno (JWT rol Estudiante)',
            'POST /api/v1/barcodes/generar' => 'Generar barcode base (JWT)',
            'GET /api/v1/barcodes/imagen' => 'Imagen barcode base (JWT)',
            'GET /api/v1/barcodes/etiquetas/datos' => 'Datos JSON de etiquetas para el frontend (JWT)',
        ]
    ]));
});

// Login
Flight::route('POST /api/v1/login', function() {
    \app\controllers\LoginController::login();
});

// Refresh token
Flight::route('POST /api/v1/refresh-token', function() {
    \App\controllers\EjemploController::refreshToken();
});

// Listar empleados (público - sin auth)
Flight::route('GET /api/v1/empleados', function() {
    \App\controllers\EjemploController::listar();
});

// Ver empleado por ID (público)
Flight::route('GET /api/v1/empleados/@id', function($id) {
    \App\controllers\EjemploController::ver((int)$id);
});

// Ver empleado por ID (público)
Flight::route('GET /api/v1/empleados/@id', function($id) {
    \App\controllers\EjemploController::ver((int)$id);
});

// =============================================================================
// RUTAS PROTEGIDAS (requieren autenticación)
// =============================================================================

// Rutas protegidas con middleware de autenticación
Flight::route('POST /api/v1/empleados', function() {
    if (!authMiddleware()) return;
    \App\controllers\EjemploController::crear();
});

// Perfil de usuario
Flight::route('GET /api/v1/perfil/nombre', function() {
    if (!authMiddleware()) return;
    \app\controllers\LoginController::getNombreUsuario();
});

// Google Drive
Flight::route('POST /api/v1/drive/subir/cv', function() {
    if (!authMiddleware()) return;
    $controller = new \App\Controllers\GoogleDriveController();
    $controller->subirCV();
});

Flight::route('POST /api/v1/drive/subir/foto', function() {
    if (!authMiddleware()) return;
    $controller = new \App\Controllers\GoogleDriveController();
    $controller->subirFoto();
});

Flight::route('POST /api/v1/drive/subir/logo', function() {
    if (!authMiddleware()) return;
    $controller = new \App\Controllers\GoogleDriveController();
    $controller->subirLogo();
});

Flight::route('POST /api/v1/drive/subir/convenio', function() {
    if (!authMiddleware()) return;
    $controller = new \App\Controllers\GoogleDriveController();
    $controller->subirConvenio();
});

Flight::route('DELETE /api/v1/drive/eliminar/@id', function($id) {
    if (!authMiddleware()) return;
    $controller = new \App\Controllers\GoogleDriveController();
    $controller->eliminarArchivo($id);
});


// =============================================================================
// MANEJO DE ERRORES
// =============================================================================

// Manejo global de excepciones
Flight::map('error', function(\Throwable $e) {
    Logger::error("Error no manejado", [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    // Detectar circuit breaker de Supabase → devolver 503
    if (strpos($e->getMessage(), 'ECIRCUITBREAKER') !== false) {
        Flight::json(ResponseFormatter::serviceUnavailable('Base de datos temporalmente no disponible. Reintentando...'), 503);
        return;
    }
    
    $code = $e->getCode() ?: 500;
    if ($code < 100 || $code > 599) $code = 500;
    
    Flight::json(ResponseFormatter::error($e->getMessage()), $code);
});

// Manejo de 404
Flight::map('notFound', function() {
    Flight::json(ResponseFormatter::error("Endpoint no encontrado", 'NOT_FOUND'), 404);
});

// Iniciar la aplicación
Flight::start();
