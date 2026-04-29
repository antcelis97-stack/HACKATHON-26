<?php

namespace app\controllers;

use App\Lib\ResponseFormatter;
use App\Lib\Logger;
use Flight;
use PDO;

/**
 * Controlador del módulo alumno — préstamos e historial.
 */
class AlumnoController
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
     * GET /api/v1/alumno/prestamos
     * Listado simplificado del historial de préstamos del alumno.
     */
    public static function listarPrestamos(): void
    {
        try {
            $user = Flight::get('user');
            $id_persona = $user->cve_persona ?? 0;

            if ($id_persona < 1) {
                Flight::json(ResponseFormatter::unauthorized(), 401);
                return;
            }

            $pdo = self::getDb();
            $stmt = $pdo->prepare("
                SELECT 
                    p.cve_prestamo,
                    p.fecha_solicitud,
                    p.fecha_devolucion_pactada,
                    p.fecha_devolucion_real,
                    p.estado_prestamo,
                    p.observaciones,
                    b.nombre AS nombre_bien,
                    a.nombre AS nombre_aula,
                    e.nombre AS nombre_edificio
                FROM prestamos p
                JOIN bienes b ON p.cve_bien = b.cve_bien
                LEFT JOIN aula a ON b.cve_aula = a.cve_aula
                LEFT JOIN edificio e ON a.cve_edificio = e.cve_edificio
                WHERE p.cve_persona_solicita = :id
                ORDER BY p.fecha_solicitud DESC
            ");
            $stmt->execute([':id' => $id_persona]);
            $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Flight::json(ResponseFormatter::success($prestamos));
        } catch (\Exception $e) {
            Logger::error('Alumno listarPrestamos', ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/alumno/bien-qr
     * Busca un bien por su código QR y verifica que esté disponible para préstamo.
     */
    public static function buscarBienPorQR(): void
    {
        try {
            $qr = Flight::request()->query->qr;
            if (empty($qr)) {
                Flight::json(ResponseFormatter::error('El código QR es requerido', 400), 400);
                return;
            }

            $pdo = self::getDb();
            $stmt = $pdo->prepare("
                SELECT 
                    b.cve_bien,
                    b.nombre,
                    b.codigo_qr,
                    b.no_serie,
                    b.estado_prestamo,
                    b.activo,
                    m.nombre_modelo,
                    mar.nombre_marca,
                    a.nombre AS nombre_aula,
                    e.nombre AS nombre_edificio
                FROM bienes b
                LEFT JOIN modelos m ON b.cve_modelo = m.cve_modelo
                LEFT JOIN marcas mar ON m.cve_marca = mar.cve_marca
                LEFT JOIN aula a ON b.cve_aula = a.cve_aula
                LEFT JOIN edificio e ON a.cve_edificio = e.cve_edificio
                WHERE b.codigo_qr = :qr
            ");
            $stmt->execute([':qr' => $qr]);
            $bien = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$bien) {
                Flight::json(ResponseFormatter::error("No se encontró un bien con ese código QR", 404), 404);
                return;
            }

            // Verificar disponibilidad
            $disponible = $bien['activo'] && $bien['estado_prestamo'] === 'disponible';

            Flight::json(ResponseFormatter::success(array_merge($bien, [
                'disponible' => $disponible
            ])));
        } catch (\Exception $e) {
            Logger::error('Alumno buscarBienPorQR', ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/alumno/contexto
     * Perfil del estudiante con contadores de préstamos.
     */
    public static function contexto(): void
    {
        try {
            $user = Flight::get('user');
            $cvePersona = $user->cve_persona ?? null;
            if (!$cvePersona) {
                Flight::json(ResponseFormatter::unauthorized('No se pudo identificar al usuario'), 401);
                return;
            }

            $pdo = self::getDb();

            // Datos del usuario
            $stmt = $pdo->prepare("
                SELECT u.cve_usuario, u.usuario, u.email, u.activo,
                       p.nombre, p.apellido_paterno, p.apellido_materno,
                       CONCAT(p.nombre, ' ', p.apellido_paterno, ' ', p.apellido_materno) AS nombre_completo
                FROM usuarios u
                LEFT JOIN persona p ON u.cve_persona = p.cve_persona
                WHERE u.cve_persona = :id AND u.activo = true
            ");
            $stmt->execute([':id' => $cvePersona]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                Flight::json(ResponseFormatter::notFound('Usuario'), 404);
                return;
            }

            // Contadores de préstamos
            $stmtCount = $pdo->prepare("
                SELECT 
                    COUNT(*) AS prestamos_historial,
                    COUNT(*) FILTER (WHERE estado_prestamo IN ('pendiente', 'aprobado')) AS prestamos_activos,
                    COUNT(*) FILTER (WHERE estado_prestamo = 'pendiente') AS prestamos_pendientes
                FROM prestamos
                WHERE cve_persona_solicita = :id
            ");
            $stmtCount->execute([':id' => $cvePersona]);
            $counts = $stmtCount->fetch(PDO::FETCH_ASSOC);

            Flight::json(ResponseFormatter::success(array_merge($row, [
                'prestamos_activos' => (int)($counts['prestamos_activos'] ?? 0),
                'prestamos_historial' => (int)($counts['prestamos_historial'] ?? 0),
                'prestamos_pendientes' => (int)($counts['prestamos_pendientes'] ?? 0)
            ])));
        } catch (\Exception $e) {
            Logger::error('Alumno contexto', ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }
}
