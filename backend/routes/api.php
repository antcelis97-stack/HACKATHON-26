<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\AdministradorController;
use App\Controllers\AuthController;
use App\Controllers\LoginController;
use App\Controllers\EgresadoController;
use App\Controllers\EmpresaController;
use App\Controllers\EvaluacionController;
use App\Controllers\MatchController;
use App\Controllers\ReporteController;
use App\Controllers\InegiController;
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
        Flight::set('user', $decoded);
        return true;
    } catch (\Exception $e) {
        Flight::json(['error' => 'Token expirado o inválido'], 401);
        return false;
    }
}

// Instanciar controladores
$admin = new AdministradorController();
$login = new LoginController();
$egresado = new EgresadoController();
$empresa = new EmpresaController();
$evaluacion = new EvaluacionController();
$match = new MatchController();
$reporte = new ReporteController();
$inegi = new InegiController();
$drive = new GoogleDriveController();
$grafica = new GraficaController();

// =============================================================================
// RUTAS PÚBLICAS
// =============================================================================

Flight::route('GET /api/status', function(){
    Flight::json(['status' => 'API Bolsa de Trabajo UT', 'version' => '1.0.0'], 200);
});

Flight::route('POST /api/v1/iniciar-sesion', [$login, 'iniciarSesion']);
Flight::route('POST /api/v1/auth/register', [new AuthController(), 'register']);
Flight::route('GET /api/inegi/buscar', [$inegi, 'buscar']);

// =============================================================================
// RUTAS PROTEGIDAS
// =============================================================================

// --- SESIÓN ---
Flight::route('POST /api/v1/refresh-token', function() use ($login) {
    if (!authMiddleware()) return;
    $login->refreshToken();
});
Flight::route('POST /api/v1/cerrar-sesion', [$login, 'cerrarSesion']);

// --- MÓDULO ADMINISTRADOR (CONVENIOS) ---
Flight::route('GET /api/v1/admin/convenios/pendientes', function() use ($admin) {
    if (!authMiddleware()) return;
    $admin->listarConveniosPendientes();
});
Flight::route('POST /api/v1/admin/convenios/aprobar/@id', function($id) use ($admin) {
    if (!authMiddleware()) return;
    $admin->aprobarConvenio($id);
});
Flight::route('POST /api/v1/admin/convenios/rechazar/@id', function($id) use ($admin) {
    if (!authMiddleware()) return;
    $admin->rechazarConvenio($id);
});
Flight::route('GET /api/v1/admin/convenios/aceptados', function() use ($admin) {
    if (!authMiddleware()) return;
    $admin->listarConveniosAceptados();
});
Flight::route('GET /api/v1/admin/convenios/rechazados', function() use ($admin) {
    if (!authMiddleware()) return;
    $admin->listarConveniosRechazados();
});
Flight::route('GET /api/v1/admin/convenios/vencidos', function() use ($admin) {
    if (!authMiddleware()) return;
    $admin->listarConveniosVencidos();
});

// --- MÓDULO EMPRESA ---
Flight::route('POST /api/v1/empresas/registrar', [$empresa, 'registrarEmpresa']);
Flight::route('POST /api/v1/empresas/vacantes', function() use ($empresa) {
    if (!authMiddleware()) return;
    $empresa->crearVacante();
});
Flight::route('GET /api/v1/empresas/vacantes/@id', function($id) use ($empresa) {
    if (!authMiddleware()) return;
    $empresa->obtenerVacantesActivas($id);
});
Flight::route('GET /api/v1/vacantes/detalle/@id', function($id) use ($empresa) {
    if (!authMiddleware()) return;
    $empresa->obtenerDetalleVacante($id);
});
Flight::route('POST /api/v1/contratacion/externa', function() use ($empresa) {
    if (!authMiddleware()) return;
    $empresa->registrarContratacionExterna();
});
Flight::route('POST /api/v1/empresa/registrar-contratado', function() use ($empresa) {
    if (!authMiddleware()) return;
    $empresa->registrarContratacion();
});

// --- MÓDULO EGRESADO ---
Flight::route('GET /api/v1/egresados/perfil/@id', function($id) use ($egresado) {
    if (!authMiddleware()) return;
    $egresado->obtenerPerfil($id);
});
Flight::route('POST /api/v1/egresados/perfil/actualizar', function() use ($egresado) {
    if (!authMiddleware()) return;
    $egresado->actualizarPerfil();
});
Flight::route('PATCH /api/v1/egresado/mi-disponibilidad', function() use ($egresado) {
    if (!authMiddleware()) return;
    $egresado->actualizarEstatusLaboral();
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

// --- MÓDULO REPORTES ---
Flight::route('GET /api/v1/reportes/encabezado/@id', function($id) use ($reporte) {
    if (!authMiddleware()) return;
    $reporte->getEncabezadoReporte($id);
});
Flight::route('GET /api/v1/reportes/insercion/carrera', function() use ($reporte) {
    if (!authMiddleware()) return;
    $reporte->getInsercionLaboral();
});
Flight::route('GET /api/v1/reportes/mapa-calor', function() use ($reporte) {
    if (!authMiddleware()) return;
    $reporte->getMapaCalor();
});
Flight::route('GET /api/v1/reportes/ranking-competencias', function() use ($reporte) {
    if (!authMiddleware()) return;
    $reporte->getRankingCompetencias();
});
Flight::route('GET /api/v1/reportes/convenios/estatus', function() use ($reporte) {
    if (!authMiddleware()) return;
    $reporte->getEstatusConvenios();
});

// Nuevo: Encabezado para reportes (Foto, Nombre, Carrera)
Flight::route('GET /api/v1/reportes/encabezado/@id', function($id) use ($reporte) {
    if (!authMiddleware()) return;
    $reporte->getEncabezadoReporte($id);
});
Flight::route('GET /api/v1/reportes/histograma/@id', function($id) use ($reporte) {
    if (!authMiddleware()) return;
    $reporte->getHistograma($id);
});
Flight::route('GET /api/v1/reportes/radar/@id/@vacanteId', function($id, $vacanteId) use ($reporte) {
    if (!authMiddleware()) return;
    $reporte->getRadarComparativo($id, $vacanteId);
});
Flight::route('GET /api/v1/reportes/analitica/vacantes/@id', function($id) use ($reporte) {
    if (!authMiddleware()) return;
    $reporte->getAnaliticaVacantes($id);
});
Flight::route('GET /api/v1/reportes/plantilla/@id', function($id) use ($reporte) {
    if (!authMiddleware()) return;
    $reporte->getPlantillaUT($id);
});

// --- OTROS MÓDULOS ---
Flight::route('GET /api/v1/graficas/radar/@usuarioId', function($usuarioId) use ($grafica) {
    if (!authMiddleware()) return;
    $grafica->generateSkillGraph($usuarioId);
});
Flight::route('POST /api/v1/evaluaciones', function() use ($evaluacion) {
    if (!authMiddleware()) return;
    $evaluacion->saveResultados();
});
Flight::route('GET /api/v1/match/ranking/@id', function($id) use ($match) {
    if (!authMiddleware()) return;
    $match->getRankingByVacante($id);
});

// =============================================================================
// MANEJO DE ERRORES
// =============================================================================
Flight::map('notFound', function() {
    Flight::json(['error' => 'Endpoint no encontrado'], 404);
});

Flight::start();