<?php

namespace app\controllers;

use Flight;
use PDO;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Lib\ResponseFormatter;
use App\Lib\Logger;

/**
 * DirectorController — Módulo de Director de Carrera
 *
 * Endpoints disponibles:
 * - GET /api/v1/director/contexto -> Perfil + contadores filtrados por adscripción
 */
class DirectorController
{
    private static function getDb(): \PDO
    {
        require_once __DIR__ . '/../../config/database.php';
        return getPgInventarioConnection();
    }

    /**
     * Obtiene el cve_persona del token JWT
     */
    private static function getPersonaFromToken(): ?int
    {
        try {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            if (preg_match('/^Bearer\s+(.+)$/', $authHeader, $matches)) {
                $token = $matches[1];
                $secret = $_ENV['API_KEY'] ?? 'clave_secreta_para_desarrollo_32chars';
                $decoded = JWT::decode($token, new Key($secret, 'HS256'));
                return $decoded->cve_persona ?? null;
            }
        } catch (\Exception $e) {
            // Token inválido o no presente
        }
        return null;
    }

    /**
     * GET /api/v1/director/contexto
     * Perfil del director con contadores de su adscripción.
     */
    public static function contexto(): void
    {
        try {
            $pdo = self::getDb();
            $cvePersona = self::getPersonaFromToken();

            if (!$cvePersona) {
                Flight::json(ResponseFormatter::unauthorized('No se pudo identificar al usuario'), 401);
                return;
            }

            // Obtener adscripción del director
            $stmtAds = $pdo->prepare("
                SELECT ap.cve_adscripcion
                FROM adscripcion_persona ap
                WHERE ap.cve_persona = :cve_persona AND ap.activo = TRUE
            ");
            $stmtAds->execute([':cve_persona' => $cvePersona]);
            $cveAdscripcion = $stmtAds->fetchColumn();

            // Datos del director
            $stmt = $pdo->prepare("
                SELECT CONCAT(pe.nombre, ' ', pe.apellido_paterno, ' ', pe.apellido_materno) AS nombre_completo,
                       ad.nombre_adscripcion AS carrera
                FROM persona pe
                LEFT JOIN adscripcion_persona ap ON ap.cve_persona = pe.cve_persona AND ap.activo = TRUE
                LEFT JOIN adscripcion ad ON ad.cve_adscripcion = ap.cve_adscripcion
                WHERE pe.cve_persona = :cve_persona
            ");
            $stmt->execute([':cve_persona' => $cvePersona]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                Flight::json(ResponseFormatter::notFound('Perfil de director'), 404);
                return;
            }

            // Contadores filtrados por adscripción
            $filtroAds = $cveAdscripcion ? "AND a.cve_adscripcion = :cve_adscripcion" : "";
            $params = $cveAdscripcion ? [':cve_adscripcion' => $cveAdscripcion] : [];

            $stmtCount = $pdo->prepare("
                SELECT
                    COUNT(DISTINCT b.cve_bien) AS bienes_total,
                    COUNT(DISTINCT pr.cve_prestamo) FILTER (WHERE pr.estado_prestamo = 'pendiente') AS prestamos_pendientes,
                    COUNT(DISTINCT pr.cve_prestamo) FILTER (WHERE pr.estado_prestamo IN ('aprobado', 'por_devolver')) AS prestamos_activos,
                    COUNT(DISTINCT b.cve_bien) FILTER (WHERE b.estado_fisico IN ('baja', 'Baja')) AS bienes_baja
                FROM bienes b
                LEFT JOIN aula a ON b.cve_aula = a.cve_aula
                LEFT JOIN prestamos pr ON pr.cve_bien = b.cve_bien
                WHERE b.activo = true
                $filtroAds
            ");
            $stmtCount->execute($params);
            $counts = $stmtCount->fetch(PDO::FETCH_ASSOC);

            Flight::json(ResponseFormatter::success(array_merge($row, [
                'bienes_total' => (int)($counts['bienes_total'] ?? 0),
                'prestamos_pendientes' => (int)($counts['prestamos_pendientes'] ?? 0),
                'prestamos_activos' => (int)($counts['prestamos_activos'] ?? 0),
                'bienes_baja' => (int)($counts['bienes_baja'] ?? 0)
            ])));
        } catch (\Exception $e) {
            Logger::error('Director contexto', ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }
}
