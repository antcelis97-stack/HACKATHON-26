<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\AuthController;
use App\Controllers\LoginController;
use App\Controllers\EgresadoController;
use App\Controllers\EmpresaController;
use App\Controllers\EvaluacionController;
use App\Controllers\MatchController;
use App\Controllers\ReporteController;
use App\Controllers\GoogleDriveController;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// 1. Cargar variables de entorno (Excelente práctica de tu proyecto pasado)
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// 2. Rescatamos tu Middleware de Autenticación JWT
function authMiddleware(): bool {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    if (!preg_match('/^Bearer\s+(.+)$/', $authHeader, $matches)) {
        Flight::json(['error' => 'Token requerido o formato inválido. Use "Bearer {token}"'], 401);
        return false;
    }

    try {
        $token = $matches[1];
        // En producción, esto debe venir de $_ENV['API_KEY']
        $secret = $_ENV['JWT_SECRET'] ?? 'clave_secreta_hackathon_2026'; 
        $decoded = JWT::decode($token, new Key($secret, 'HS256'));
        
        // Guardar la info del usuario en la memoria de Flight para usarla en los controladores
        Flight::set('jwt_user', $decoded);
        return true;
        
    } catch (\Exception $e) {
        Flight::json(['error' => 'Token expirado o inválido'], 401);
        return false;
    }
}

// Instanciar controladores
$auth = new AuthController();
$login = new LoginController();
$egresado = new EgresadoController();
$empresa = new EmpresaController();
$evaluacion = new EvaluacionController();
$match = new MatchController();
$reporte = new ReporteController();
$drive = new GoogleDriveController();

// =============================================================================
// RUTAS PÚBLICAS (No requieren token)
// =============================================================================

// Health check para el equipo de Frontend
Flight::route('GET /api/status', function(){
    Flight::json(['status' => 'API Bolsa de Trabajo UT', 'version' => '1.0.0'], 200);
});

// Autenticación
Flight::route('POST /api/auth/register', [$auth, 'register']);
Flight::route('POST /api/auth/login', [$login, 'login']);

// Búsqueda de vacantes (Las empresas quieren que las vacantes sean públicas para atraer talento)
Flight::route('GET /api/egresados/vacantes', [$egresado, 'searchVacantes']);

// =============================================================================
// RUTAS PROTEGIDAS (Requieren pasar por el authMiddleware)
// =============================================================================

// --- MÓDULO EGRESADO ---
Flight::get('/api/egresados/:usuarioId', function($usuarioId) use ($egresado) {
    if (!authMiddleware()) return;
    $egresado->getProfile($usuarioId);
});

Flight::route('POST /api/egresados/update-profile', function() use ($egresado) {
    if (!authMiddleware()) return;
    $egresado->updateProfile();
});

Flight::route('PUT /api/egresados/perfil', function() use ($egresado) {
    if (!authMiddleware()) return;
    $egresado->updateProfile();
});

Flight::route('POST /api/egresados/cv', function() use ($drive) {
    if (!authMiddleware()) return;
    $drive->uploadCV();
});

Flight::route('POST /api/evaluaciones', function() use ($evaluacion) {
    if (!authMiddleware()) return;
    $evaluacion->saveResultados();
});

// --- MÓDULO EMPRESA ---
Flight::route('POST /api/empresas/vacantes', function() use ($empresa) {
    if (!authMiddleware()) return;
    $empresa->createVacante();
});

Flight::route('GET /api/match/vacante/@id', function($id) use ($match) {
    if (!authMiddleware()) return;
    $match->getCandidatosIdoneos($id);
});

// --- MÓDULO REPORTES (DASHBOARDS INSTITUCIONALES) ---
Flight::route('GET /api/reportes/insercion', function() use ($reporte) {
    if (!authMiddleware()) return;
    $reporte->getInsercionLaboral();
});

Flight::route('GET /api/reportes/mapa-calor', function() use ($reporte) {
    if (!authMiddleware()) return;
    $reporte->getMapaCalor();
});

Flight::route('GET /api/reportes/radar/@egresadoId', function($egresadoId) use ($reporte) {
    if (!authMiddleware()) return;
    $reporte->getRadarCompetencias($egresadoId);
});

// =============================================================================
// MANEJO DE ERRORES GLOBAL (Rescatado de tu archivo)
// =============================================================================
Flight::map('error', function(\Throwable $e) {
    $code = $e->getCode() ?: 500;
    if ($code < 100 || $code > 599) $code = 500;
    
    Flight::json([
        'error' => 'Error interno del servidor',
        'detalle' => $e->getMessage()
    ], $code);
});

Flight::map('notFound', function() {
    Flight::json(['error' => 'Endpoint de Bolsa de Trabajo no encontrado'], 404);
});