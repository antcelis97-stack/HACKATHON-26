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

// Integración de los nuevos servicios
use App\Services\SiestIntegration;
use App\Services\BolsaNacionalAPI;

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
        $secret = $_ENV['JWT_SECRET'] ?? 'clave_secreta_hackathon_2026'; 
        $decoded = JWT::decode($token, new Key($secret, 'HS256'));
        
        Flight::set('jwt_user', $decoded);
        return true;
        
    } catch (\Exception $e) {
        Flight::json(['error' => 'Token expirado o inválido'], 401);
        return false;
    }
}

// Instanciar controladores y servicios
$auth = new AuthController();
$login = new LoginController();
$egresado = new EgresadoController();
$empresa = new EmpresaController();
$evaluacion = new EvaluacionController();
$match = new MatchController();
$reporte = new ReporteController();
$drive = new GoogleDriveController();

$siest = new SiestIntegration();
$bolsaNacional = new BolsaNacionalAPI();

// =============================================================================
// RUTAS PÚBLICAS (No requieren token)
// =============================================================================

// Health check
Flight::route('GET /api/status', function(){
    Flight::json(['status' => 'API Bolsa de Trabajo UT', 'version' => '1.0.0'], 200);
});

// Autenticación
Flight::route('POST /api/auth/register', [$auth, 'register']);
Flight::route('POST /api/auth/login', [$login, 'login']);

// Validación SIEst 2.0 (Para autocompletar datos en el registro)
Flight::route('GET /api/siest/validar/@matricula', function($matricula) use ($siest) {
    Flight::json($siest->getDatosEgresado($matricula));
});

// Búsqueda de vacantes locales y nacionales
Flight::route('GET /api/egresados/vacantes', [$egresado, 'searchVacantes']);

Flight::route('GET /api/nacional/vacantes', function() use ($bolsaNacional) {
    // Permite buscar por área (ej. ?q=Software, ?q=Acuicultura, ?q=Turismo)
    $query = Flight::request()->query->q ?? 'profesional';
    Flight::json($bolsaNacional->getVacantesNacionales($query));
});

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

// --- MÓDULO ADMINISTRADOR (CONVENIOS Y FORMALIZACIÓN) ---
Flight::route('GET /api/v1/admin/convenios/pendientes', function() use ($admin) {
    if (!authMiddleware()) return;
    $admin->listarConveniosPendientes();
});
Flight::route('GET /api/v1/admin/nacionales/pendientes', function() use ($admin) {
    if (!authMiddleware()) return;
    $admin->listarNacionalesPendientes();
});
Flight::route('POST /api/v1/admin/nacionales/formalizar/@id', function($id) use ($admin) {
    if (!authMiddleware()) return;
    $admin->formalizarEmpresaNacional($id);
});

// --- MÓDULO EGRESADO (POSTULACIONES Y BÚSQUEDA) ---
Flight::route('GET /api/v1/vacantes/explorar', function() use ($egresado) {
    if (!authMiddleware()) return;
    $egresado->searchVacantes();
});
Flight::route('POST /api/v1/postulaciones/aplicar', function() use ($egresado) {
    if (!authMiddleware()) return;
    $egresado->postularseAVacante();
});

Flight::route('GET /api/v1/vacantes/nacionales', function() use ($egresado) {
    if (!authMiddleware()) return;
    $egresado->explorarNacionales();
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

// --- MÓDULO REPORTES (DASHBOARDS E HISTOGRAMAS) ---
// 1. Institucionales
Flight::route('GET /api/reportes/insercion', function() use ($reporte) {
    if (!authMiddleware()) return;
    $reporte->getInsercionLaboral();
});

Flight::route('GET /api/reportes/mapa-calor', function() use ($reporte) {
    if (!authMiddleware()) return;
    $reporte->getMapaCalor();
});

Flight::route('GET /api/reportes/competencias/ranking', function() use ($reporte) {
    if (!authMiddleware()) return;
    $reporte->getRankingCompetencias();
});

Flight::route('GET /api/reportes/convenios/estatus', function() use ($reporte) {
    if (!authMiddleware()) return;
    $reporte->getEstatusConvenios();
});

// 2. Talento / Egresado
Flight::route('GET /api/reportes/histograma/@egresadoId', function($egresadoId) use ($reporte) {
    if (!authMiddleware()) return;
    $reporte->getHistograma($egresadoId);
});

Flight::route('GET /api/reportes/radar/@egresadoId/@vacanteId', function($egresadoId, $vacanteId) use ($reporte) {
    if (!authMiddleware()) return;
    $reporte->getRadarComparativo($egresadoId, $vacanteId);
});

// 3. Empresarial
Flight::route('GET /api/reportes/empresas/@empresaId/analitica', function($empresaId) use ($reporte) {
    if (!authMiddleware()) return;
    $reporte->getAnaliticaVacantes($empresaId);
});

Flight::route('GET /api/reportes/empresas/@empresaId/plantilla-ut', function($empresaId) use ($reporte) {
    if (!authMiddleware()) return;
    $reporte->getPlantillaUT($empresaId);
});

// =============================================================================
// MANEJO DE ERRORES GLOBAL
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