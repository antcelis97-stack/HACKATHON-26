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

// =============================================================================
// BARCODES (/api/v1/barcodes/*) - Generacion de codigo base TIPO-FAMILIA-ARTICULO
// Requiere JWT valido (authMiddleware).
// =============================================================================
Flight::route('POST /api/v1/barcodes/generar', function() {
    if (!authMiddleware()) return;
    \app\controllers\BarcodeController::generar();
});

Flight::route('POST /api/v1/barcodes/generar/aula/@id', function($id) {
    if (!authMiddleware()) return;
    \app\controllers\BarcodeController::generarQRAula((int)$id);
});

Flight::route('GET /api/v1/barcodes/imagen', function() {
    if (!authMiddleware()) return;
    \app\controllers\BarcodeController::imagen();
});

Flight::route('GET /api/v1/barcodes/etiquetas/datos/@id', function($id) {
    if (!authMiddleware()) return;
    \app\controllers\BarcodeController::etiquetasDatos((int)$id);
});

Flight::route('GET /api/v1/barcodes/etiquetas/qr/datos/@id', function($id) {
    if (!authMiddleware()) return;
    \app\controllers\BarcodeController::qrEtiquetasDatos((int)$id);
});

// Obtener todas las aulas (con edificio)
Flight::route('GET /api/v1/aulas', function() {
    if (!authMiddleware()) return;
    \app\controllers\InformacionController::getAulas();
});

// Etiquetas de código de barras por aula
Flight::route('GET /api/v1/barcodes/etiquetas/datos/aula/@cve_aula', function($cve_aula) {
    if (!authMiddleware()) return;
    \app\controllers\BarcodeController::getEtiquetasBarcodePorAula((int)$cve_aula);
});

// Etiquetas de código QR por aula
Flight::route('GET /api/v1/barcodes/etiquetas/qr/datos/aula/@cve_aula', function($cve_aula) {
    if (!authMiddleware()) return;
    \app\controllers\BarcodeController::getEtiquetasQRPorAula((int)$cve_aula);
});

Flight::route('GET /api/v1/barcodes/buscar/@codigo', function($codigo) {
    if (!authMiddleware()) return;
    \app\controllers\BarcodeController::buscarPorCodigo($codigo);
});

Flight::route('PUT /api/v1/empleados/@id', function($id) {
    if (!authMiddleware()) return;
    \App\controllers\EjemploController::actualizar((int)$id);
});

Flight::route('DELETE /api/v1/empleados/@id', function($id) {
    if (!authMiddleware()) return;
    \App\controllers\EjemploController::eliminar((int)$id);
});

// Contexto del administrador (perfil + contadores - protegido)
Flight::route('GET /api/v1/administrador/contexto', function() {
    if (!authMiddleware()) return;
    \app\controllers\AdministradorController::contexto();
});

// Inventario general (solo administrador - protegido)
Flight::route('GET /api/v1/administrador/inventario', function() {
    if (!authMiddleware()) return;
    \app\controllers\AdministradorController::inventarioGeneral();
});

// Detalle de un bien por ID
Flight::route('GET /api/v1/administrador/inventario/detalle/@id', function($id) {
    if (!authMiddleware()) return;
    \app\controllers\AdministradorController::getBienDetalle((int)$id);
});

// Registrar bien (solo administrador - protegido)
Flight::route('POST /api/v1/administrador/inventario', function() {
    if (!authMiddleware()) return;
    \app\controllers\AdministradorController::registrarBien();
});

// Registrar aula (solo administrador - protegido)
Flight::route('POST /api/v1/administrador/aulas', function() {
    if (!authMiddleware()) return;
    \app\controllers\AdministradorController::registrarAula();
});

// =============================================================================
// GOOGLE DRIVE - Fotos de Bienes
// =============================================================================
// Subir foto de un bien (JWT requerido)
Flight::route('POST /api/v1/bienes/@id/foto', function($id) {
     if (!authMiddleware()) return;
    \app\controllers\GoogleDriveController::subirFoto((int)$id);
});

// Ver foto de un bien (JWT requerido)
Flight::route('GET /api/v1/bienes/@id/foto', function($id) {
     if (!authMiddleware()) return;
    \app\controllers\GoogleDriveController::verFoto((int)$id);
});

// Eliminar foto de un bien (JWT requerido)
Flight::route('DELETE /api/v1/bienes/@id/foto/@driveId', function($id, $driveId) {
     if (!authMiddleware()) return;
    \app\controllers\GoogleDriveController::eliminarFoto((int)$id, $driveId);
});

// Solicitar préstamo (protegido)
Flight::route('POST /api/v1/prestamos/solicitar', function() {
    if (!authMiddleware()) return;
    \app\controllers\PrestamosController::solicitarPrestamo();
});

// Estadísticas: Bienes por estado físico (solo administrador - protegido)
Flight::route('GET /api/v1/administrador/estadisticas/estado-fisico', function() {
    if (!authMiddleware()) return;
    \app\controllers\AdministradorController::contarPorEstadoFisico();
});

// Listar préstamos (protegido)
Flight::route('GET /api/v1/prestamos', function() {
    if (!authMiddleware()) return;
    \app\controllers\PrestamosController::listarPrestamos();
});

// Listar préstamos aceptados/aprobados (General)
Flight::route('GET /api/v1/prestamos/aceptados', function() {
    if (!authMiddleware()) return;
    \app\controllers\PrestamosController::listarPrestamosAceptados();
});

// Listar préstamos aceptados por persona
Flight::route('GET /api/v1/prestamos/aceptados/persona/@id', function($id) {
    if (!authMiddleware()) return;
    \app\controllers\PrestamosController::listarPrestamosAceptadosPorPersona((int)$id);
});

// Listar préstamos no devueltos/vencidos (protegido)
Flight::route('GET /api/v1/prestamos/no-devueltos', function() {
    if (!authMiddleware()) return;
    \app\controllers\PrestamosController::listarPrestamosNoDevueltos();
});

// Aceptar préstamo (protegido)
Flight::route('PUT /api/v1/prestamos/aceptar/@id', function($id) {
    if (!authMiddleware()) return;
    \app\controllers\PrestamosController::aceptarPrestamo((int)$id);
});

// Registrar préstamo (solo administrador - protegido)
Flight::route('POST /api/v1/prestamos/solicitar-adm', function() {
    if (!authMiddleware()) return;
    \app\controllers\PrestamosController::solicitarPrestamoAdministrador();
});

// Rechazar préstamo (protegido)
Flight::route('PUT /api/v1/prestamos/rechazar/@id', function($id) {
    if (!authMiddleware()) return;
    \app\controllers\PrestamosController::rechazarPrestamo((int)$id);
});

// Devolver préstamo (protegido)
Flight::route('PUT /api/v1/prestamos/devolver/@id', function($id) {
    if (!authMiddleware()) return;
    \app\controllers\PrestamosController::devolverPrestamo((int)$id);
});

// Ver préstamos no devueltos de una persona (solo administrador o persona - protegido)
Flight::route('GET /api/v1/prestamos/no-devueltos/@id_persona', function($id_persona) {
    if (!authMiddleware()) return;
    \app\controllers\PrestamosController::prestamosNoDevueltosPorPersona((int)$id_persona);
});

// Listar préstamos pendientes por encargado del bien (profesor)
Flight::route('GET /api/v1/prestamos/pendientes/encargado/@id_profesor', function($id_profesor) {
    if (!authMiddleware()) return;
    \app\controllers\PrestamosController::listarPrestamosPendientesPorEncargado((int)$id_profesor);
});

// Aceptar devolución (protegido)
Flight::route('PUT /api/v1/prestamos/aceptar-devolucion/@id', function($id) {
    if (!authMiddleware()) return;
    \app\controllers\PrestamosController::aceptarDevolucion((int)$id);
});

// Rechazar devolución (protegido)
Flight::route('PUT /api/v1/prestamos/rechazar-devolucion/@id', function($id) {
    if (!authMiddleware()) return;
    \app\controllers\PrestamosController::rechazarDevolucion((int)$id);
});

// Listar todas las devoluciones pendientes (Administrador)
Flight::route('GET /api/v1/prestamos/devoluciones-pendientes', function() {
    if (!authMiddleware()) return;
    \app\controllers\PrestamosController::listarTodasLasDevolucionesPendientes();
});

// Listar devoluciones pendientes por encargado (Profesor)
Flight::route('GET /api/v1/prestamos/devoluciones-pendientes/encargado/@id_profesor', function($id_profesor) {
    if (!authMiddleware()) return;
    \app\controllers\PrestamosController::listarDevolucionesPendientesPorEncargado((int)$id_profesor);
});

// Listar TODOS los préstamos pendientes (Administrador)
Flight::route('GET /api/v1/prestamos/pendientes', function() {
    if (!authMiddleware()) return;
    \app\controllers\PrestamosController::listarTodosLosPrestamosPendientes();
});

// Listar préstamos pendientes de una persona (Estudiante/Profesor)
Flight::route('GET /api/v1/prestamos/pendientes/persona/@id_persona', function($id_persona) {
    if (!authMiddleware()) return;
    \app\controllers\PrestamosController::listarPrestamosPendientesPorPersona((int)$id_persona);
});

// Listar información de bienes por aula (solo administrador - protegido)
Flight::route('GET /api/v1/administrador/inventario/por-aula', function() {
    if (!authMiddleware()) return;
    \app\controllers\AdministradorController::listarBienesPorAula();
});

// Registrar movimiento manualmente en bitácora (solo administrador - protegido)
Flight::route('POST /api/v1/administrador/bitacora', function() {
    if (!authMiddleware()) return;
    \app\controllers\AdministradorController::registrarMovimientoBitacora();
});

// Listar movimientos de bitácora (solo administrador - protegido)
Flight::route('GET /api/v1/administrador/bitacora', function() {
    if (!authMiddleware()) return;
    \app\controllers\AdministradorController::listarBitacora();
});

// Registrar una nueva auditoría y sus detalles (solo administrador - protegido)
Flight::route('POST /api/v1/auditorias/registrar', function() {
    if (!authMiddleware()) return;
    \app\controllers\AuditoriasController::registrarAuditoria();
});

// Buscar bien por NFC (para auditoría)
Flight::route('GET /api/v1/auditorias/buscar-nfc/@nfc', function($nfc) {
    if (!authMiddleware()) return;
    \app\controllers\AuditoriasController::buscarBienPorNFC($nfc);
});

// Buscar bien por QR (para auditoría)
Flight::route('GET /api/v1/auditorias/buscar-qr', function() {
    if (!authMiddleware()) return;
    $qr = Flight::request()->query->qr;
    \app\controllers\AuditoriasController::buscarBienPorQR($qr);
});

// Listar auditorías (solo administrador - protegido)
Flight::route('GET /api/v1/administrador/auditorias', function() {
    if (!authMiddleware()) return;
    \app\controllers\AuditoriasController::listarAuditorias();
});

// =============================================================================
// PAPELERA (Bienes Inactivos)
// =============================================================================

// Listar bienes inactivos
Flight::route('GET /api/v1/papelera/bienes', function() {
    if (!authMiddleware()) return;
    \app\controllers\PapeleraController::listarBienesInactivos();
});

// Detalle de bien inactivo (Papelera)
Flight::route('GET /api/v1/papelera/bienes/@id', function($id) {
    if (!authMiddleware()) return;
    \app\controllers\PapeleraController::getBienDetalle((int)$id);
});

// Desactivar bien (mover a papelera)
Flight::route('PUT /api/v1/papelera/desactivar/@id', function($id) {
    if (!authMiddleware()) return;
    \app\controllers\PapeleraController::desactivarBien((int)$id);
});

// Restaurar bien (sacar de papelera)
Flight::route('PUT /api/v1/papelera/restaurar/@id', function($id) {
    if (!authMiddleware()) return;
    \app\controllers\PapeleraController::restaurarBien((int)$id);
});

// Eliminar bien permanentemente
Flight::route('DELETE /api/v1/papelera/eliminar/@id', function($id) {
    if (!authMiddleware()) return;
    \app\controllers\PapeleraController::eliminarBienPermanente((int)$id);
});

// Listar bienes inactivos por persona (resguardatario)
Flight::route('GET /api/v1/papelera/bienes/persona/@cve_persona', function($cve_persona) {
    if (!authMiddleware()) return;
    \app\controllers\PapeleraController::listarBienesInactivosPorPersona((int)$cve_persona);
});

// Resguardo individual (Profesores, Director y Administradores - protegido)
// Soporta filtro opcional por aula: /api/v1/reportes/resguardo-individual/@id_persona?id_aula=123
Flight::route('GET /api/v1/reportes/resguardo-individual/@id_persona', function($id_persona) {
    if (!authMiddleware()) return;
    $id_aula = Flight::request()->query->getData()['id_aula'] ?? null;
    \app\controllers\ReportesController::getResguardoIndividual((int)$id_persona, $id_aula ? (int)$id_aula : null);
});

// Encabezado de reportes (Profesores, Director y Administradores - protegido)
Flight::route('GET /api/v1/reportes/encabezado/@id_persona', function($id_persona) {
    if (!authMiddleware()) return;
    \app\controllers\ReportesController::getEncabezadoReporte((int)$id_persona);
});

// Encabezado de dirección (Nombre de carrera y adscripción)
Flight::route('GET /api/v1/reportes/encabezado-direccion/@id_persona', function($id_persona) {
    if (!authMiddleware()) return;
    \app\controllers\ReportesController::getEncabezadoDireccion((int)$id_persona);
});

// Tabla de bienes en movimiento interno (Profesores, Director y Administradores - protegido)
Flight::route('GET /api/v1/reportes/movimiento-interno/@id_prestamo', function($id_prestamo) {
    if (!authMiddleware()) return;
    \app\controllers\ReportesController::getMovimientoInterno((int)$id_prestamo);
});

// Listar bienes dados de baja (Administrador y Director - protegido)
Flight::route('GET /api/v1/reportes/prestamos/no-devueltos', function() {
    if (!authMiddleware()) return;
    \app\controllers\ReportesController::listarPrestamosNoDevueltos();
});

// Listar bienes dados de baja (Administrador y Director - protegido)
Flight::route('GET /api/v1/reportes/inventario/bajas', function() {
    if (!authMiddleware()) return;
    \app\controllers\ReportesController::listarBienesDadosDeBaja();
});

// Listar bienes en mantenimiento/reparación (solo administrador - protegido)
Flight::route('GET /api/v1/reportes/inventario/mantenimiento', function() {
    if (!authMiddleware()) return;
    \app\controllers\ReportesController::listarBienesEnMantenimiento();
});

// Trazabilidad de un bien específico (solo administrador - protegido)
Flight::route('GET /api/v1/reportes/trazabilidad/@id_bien', function($id_bien) {
    if (!authMiddleware()) return;
    \app\controllers\ReportesController::getTrazabilidadBien((int)$id_bien);
});

// Total de valor monetario por aula
Flight::route('GET /api/v1/reportes/estadisticas/valor-por-aula', function() {
    if (!authMiddleware()) return;
    \app\controllers\ReportesController::getValorMonetarioPorAula();
});

// Estadísticas de estado físico (Reportes)
Flight::route('GET /api/v1/reportes/estadisticas/estado-fisico', function() {
    if (!authMiddleware()) return;
    \app\controllers\ReportesController::getConteoPorEstadoFisico();
});

// Estadísticas de estado de préstamo (Reportes)
Flight::route('GET /api/v1/reportes/estadisticas/estado-prestamo', function() {
    if (!authMiddleware()) return;
    \app\controllers\ReportesController::getConteoPorEstadoPrestamo();
});

// =============================================================================
// CATALOGOS E INFORMACIÓN GENERAL
// =============================================================================

// Obtener aulas por edificio
Flight::route('GET /api/v1/aulas/edificio/@cve_edificio', function($cve_edificio) {
    \app\controllers\InformacionController::getAulasPorEdificio((int)$cve_edificio);
});

// Obtener catálogo de todas las aulas
Flight::route('GET /api/v1/informacion/aulas', function() {
    \app\controllers\InformacionController::getAulas();
});

// Obtener personal por carrera
Flight::route('GET /api/v1/informacion/personal/carrera/@cve_carrera', function($cve_carrera) {
    \app\controllers\InformacionController::getPersonalPorCarrera((int)$cve_carrera);
});

// Obtener catálogo de profesores
Flight::route('GET /api/v1/informacion/profesores', function() {
    \app\controllers\InformacionController::getProfesores();
});

// Obtener catálogo de bienes
Flight::route('GET /api/v1/informacion/bienes', function() {
    \app\controllers\InformacionController::getBienesCatalogo();
});

// Obtener bienes disponibles (sin préstamo activo)
Flight::route('GET /api/v1/informacion/bienes/disponibles', function() {
    \app\controllers\InformacionController::getBienesDisponibles();
});

// Catálogo de tipos de bien
Flight::route('GET /api/v1/informacion/tipos-bien', function() {
    \app\controllers\InformacionController::getTiposBien();
});

// Catálogo de familias por tipo
Flight::route('GET /api/v1/informacion/familias/tipo/@cve_tipo', function($cve_tipo) {
    \app\controllers\InformacionController::getFamiliasPorTipo((int)$cve_tipo);
});

// Catálogo de artículos por familia
Flight::route('GET /api/v1/informacion/articulos/familia/@cve_familia', function($cve_familia) {
    \app\controllers\InformacionController::getArticulosPorFamilia((int)$cve_familia);
});

// --- NUEVOS CATÁLOGOS ---

// Catálogo de edificios
Flight::route('GET /api/v1/informacion/edificios', function() {
    \app\controllers\InformacionController::getEdificios();
});

// Catálogo de tipos de aula
Flight::route('GET /api/v1/informacion/tipos-aula', function() {
    \app\controllers\InformacionController::getTiposAula();
});

// Catálogo de marcas
Flight::route('GET /api/v1/informacion/marcas', function() {
    \app\controllers\InformacionController::getMarcas();
});

// Catálogo de modelos
Flight::route('GET /api/v1/informacion/modelos', function() {
    \app\controllers\InformacionController::getModelos();
});


// =============================================================================
// MÓDULO PROFESOR (/api/v1/profesor/*) — ver docs/MODULO_PROFESOR_API.md
// Todas las rutas: JWT válido + rol "Profesor" (ProfesorMiddleware::requireProfesorRole).
// Si el token falta o el rol no es Profesor → 401 / 403.
// =============================================================================

// GET /contexto
Flight::route('GET /api/v1/profesor/contexto', function() {
    if (!authMiddleware()) return;
    \app\controllers\ProfesorController::contexto();
});

// GET /prestamos
Flight::route('GET /api/v1/profesor/prestamos', function() {
    if (!authMiddleware()) return;
    \app\controllers\ProfesorController::listarPrestamos();
});

// GET /aulas
Flight::route('GET /api/v1/profesor/aulas(/@id)', function($id) {
    if (!authMiddleware()) return;
    \app\controllers\ProfesorController::listarAulas($id);
});

// GET /inventario
Flight::route('GET /api/v1/profesor/inventario', function() {
    if (!authMiddleware()) return;
    \app\controllers\ProfesorController::listarInventario();
});

// GET /auditoria
Flight::route('GET /api/v1/profesor/auditoria', function() {
    if (!authMiddleware()) return;
    \app\controllers\ProfesorController::listarAuditoria();
});

// GET /bien/@id — Detalle de un bien específico.
Flight::route('GET /api/v1/profesor/bien/@id', function($id) {
    if (!authMiddleware()) return;
    \app\controllers\ProfesorController::getBienDetalle((int)$id);
});

// POST /inventario
Flight::route('POST /api/v1/profesor/inventario', function() {
    if (!authMiddleware()) return;
    \app\controllers\ProfesorController::registrarBien();
});

// GET /prestamos/no-devueltos — Préstamos activos del profesor que aún no han sido devueltos.
Flight::route('GET /api/v1/profesor/prestamos/no-devueltos', function() {
    if (!\App\Lib\ProfesorMiddleware::requireProfesorRole()) return;
    \app\controllers\ProfesorController::listarPrestamosNoDevueltos();
});

// GET /movimientos — Bitácora de movimientos de bienes en aulas del profesor.
Flight::route('GET /api/v1/profesor/movimientos', function() {
    if (!authMiddleware()) return;
    \app\controllers\ProfesorController::listarMovimientos();
});

// GET /reporte-bienes — Reporte completo de bienes en aulas del profesor.
Flight::route('GET /api/v1/profesor/reporte-bienes', function() {
    if (!authMiddleware()) return;
    \app\controllers\ProfesorController::listarBienesReporte();
});

// =============================================================================
// MÓDULO ALUMNO (/api/v1/alumno/*) — ver docs/ALUMNO_ENDPOINTS.md
// JWT válido + rol "Estudiante" (AlumnoMiddleware::requireAlumnoRole).
// =============================================================================

Flight::route('GET /api/v1/alumno/contexto', function() {
    if (!authMiddleware()) return;
    \app\controllers\AlumnoController::contexto();
});

Flight::route('GET /api/v1/alumno/prestamos', function() {
    if (!authMiddleware()) return;
    \app\controllers\AlumnoController::listarPrestamos();
});

Flight::route('POST /api/v1/alumno/prestamos', function() {
    if (!authMiddleware()) return;
    \app\controllers\PrestamosController::solicitarPrestamo();
});

Flight::route('GET /api/v1/alumno/bien-qr', function() {
    if (!authMiddleware()) return;
    \app\controllers\AlumnoController::buscarBienPorQR();
});

// =============================================================================
// MÓDULO DIRECTOR (/api/v1/director/*)
// JWT válido + rol "Director" (validación en frontend via roleGuard).
// =============================================================================

Flight::route('GET /api/v1/director/contexto', function() {
    if (!authMiddleware()) return;
    \app\controllers\DirectorController::contexto();
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
