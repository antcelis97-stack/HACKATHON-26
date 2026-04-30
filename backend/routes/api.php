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
use App\Controllers\GraficaController;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// 1. Cargar variables de entorno
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// 2. Middleware de Autenticación JWT
function authMiddleware(): bool {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    if (!preg_match('/^Bearer\s+(.+)$/', $authHeader, $matches)) {
        Flight::json(['error' => 'Token requerido o formato inválido. Use "Bearer {token}"'], 401);
        return false;
    }

    try {
        $token = $matches[1];
        $secret = $_ENV['API_KEY'] ?? 'clave_secreta_para_desarrollo_32chars'; 
        $decoded = JWT::decode($token, new Key($secret, 'HS256'));
        
        // Guardar la info del usuario en Flight
        Flight::set('user', $decoded);
        return true;
        
    } catch (\Exception $e) {
        Flight::json(['error' => 'Token expirado o inválido'], 401);
        return false;
    }
}

// Instanciar controladores
$login = new LoginController();
$egresado = new EgresadoController();
$empresa = new EmpresaController();
$evaluacion = new EvaluacionController();
$match = new MatchController();
$reporte = new ReporteController();
$drive = new GoogleDriveController();
$grafica = new GraficaController();

// =============================================================================
// RUTAS PÚBLICAS
// =============================================================================

Flight::route('GET /api/status', function(){
    Flight::json(['status' => 'API Bolsa de Trabajo UT', 'version' => '1.0.0'], 200);
});

// Autenticación
Flight::route('POST /api/v1/iniciar-sesion', [$login, 'iniciarSesion']);
Flight::route('POST /api/v1/refresh-token', function() use ($login) {
    if (!authMiddleware()) return;
    $login->refreshToken();
});
Flight::route('POST /api/v1/cerrar-sesion', [$login, 'cerrarSesion']);

// =============================================================================
// RUTAS PROTEGIDAS
// =============================================================================

// Registro de empresa
Flight::route('POST /api/v1/empresas/registrar', [$empresa, 'registrarEmpresa']);

// --- MÓDULO EGRESADO ---
Flight::route('GET /api/v1/egresados/perfil/@id', function($id) use ($egresado) {
    if (!authMiddleware()) return;
    $egresado->obtenerPerfil($id);
});

Flight::route('POST /api/v1/egresados/perfil/actualizar', function() use ($egresado) {
    if (!authMiddleware()) return;
    $egresado->actualizarPerfil();
});

// --- MÓDULO GOOGLE DRIVE ---
Flight::route('POST /api/v1/drive/subir/cv', function() use ($drive) {
    if (!authMiddleware()) return;
    $drive->subirCV();
});

Flight::route('POST /api/v1/drive/subir/foto', function() use ($drive) {
    if (!authMiddleware()) return;
    $drive->subirFoto();
});

Flight::route('POST /api/v1/drive/subir/logo', function() use ($drive) {
    if (!authMiddleware()) return;
    $drive->subirLogo();
});

Flight::route('POST /api/v1/drive/subir/convenio', function() use ($drive) {
    if (!authMiddleware()) return;
    $drive->subirConvenio();
});

Flight::route('DELETE /api/v1/drive/eliminar/@id', function($id) use ($drive) {
    if (!authMiddleware()) return;
    $drive->eliminarArchivo($id);
});

// --- MÓDULO REPORTES (DASHBOARDS ESTRATÉGICOS) ---
Flight::route('GET /api/v1/reportes/egresados/aptitudes', function() use ($reporte) {
    if (!authMiddleware()) return;
    $reporte->obtenerAptitudesPredominantes();
});

Flight::route('GET /api/v1/reportes/empresas/estadisticas', function() use ($reporte) {
    if (!authMiddleware()) return;
    $reporte->obtenerEstadisticasEmpresariales();
});

Flight::route('GET /api/v1/reportes/institucional/monitoreo', function() use ($reporte) {
    if (!authMiddleware()) return;
    $reporte->obtenerMonitoreoInstitucional();
});

Flight::route('GET /api/v1/reportes/insercion/carrera', function() use ($reporte) {
    if (!authMiddleware()) return;
    $reporte->obtenerInsercionLaboralPorCarrera();
});

Flight::route('GET /api/v1/reportes/convenios/estatus', function() use ($reporte) {
    if (!authMiddleware()) return;
    $reporte->obtenerEstatusConvenios();
});

Flight::route('GET /api/v1/reportes/radar/@usuarioId', function($usuarioId) use ($reporte) {
    if (!authMiddleware()) return;
    $reporte->getRadarCompetencias($usuarioId);
});

// --- MÓDULO GRÁFICAS ---
Flight::route('GET /api/v1/graficas/radar/@usuarioId', function($usuarioId) use ($grafica) {
    if (!authMiddleware()) return;
    $grafica->generateSkillGraph($usuarioId);
});

// --- OTROS MÓDULOS ---
Flight::route('POST /api/v1/evaluaciones', function() use ($evaluacion) {
    if (!authMiddleware()) return;
    $evaluacion->saveResultados();
});

Flight::route('POST /api/v1/empresas/vacantes', function() use ($empresa) {
    if (!authMiddleware()) return;
    $empresa->crearVacante();
});

Flight::route('GET /api/v1/match/talento/@id', function($id) use ($match) {
    if (!authMiddleware()) return;
    $match->matchTalento($id);
});

// =============================================================================
// MANEJO DE ERRORES
// =============================================================================
Flight::map('error', function(\Throwable $e) {
    $code = $e->getCode() ?: 500;
    if ($code < 100 || $code > 599) $code = 500;
    Flight::json(['error' => 'Error del servidor', 'detalle' => $e->getMessage()], $code);
});

Flight::map('notFound', function() {
    Flight::json(['error' => 'Endpoint no encontrado'], 404);
});