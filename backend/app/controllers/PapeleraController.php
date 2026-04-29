<?php

namespace app\controllers;

use App\Lib\ResponseFormatter;
use App\Lib\Logger;
use App\Lib\AuditLog;
use Flight;
use PDO;

/**
 * Controlador para gestionar la Papelera (Bienes Inactivos).
 */
class PapeleraController
{
    private static function getDb(): PDO
    {
        require_once __DIR__ . '/../../config/database.php';
        return getPgInventarioConnection();
    }

    /**
     * GET /api/v1/papelera/bienes
     * Retorna todos los bienes inactivos (activo = false).
     */
    public static function listarBienesInactivos(): void
    {
        try {
            $pdo = self::getDb();

            $stmt = $pdo->prepare("
                SELECT 
                    cve_bien, 
                    no_serie, 
                    nombre, 
                    estado_fisico AS estado
                FROM bienes
                WHERE activo = false
                ORDER BY nombre ASC
            ");
            $stmt->execute();
            
            $bienes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Logger::info("Listado simplificado de bienes inactivos (Papelera) consultado", ['total' => count($bienes)]);

            Flight::json(ResponseFormatter::success($bienes));

        } catch (\Exception $e) {
            Logger::error("Error al listar bienes inactivos", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * PUT /api/v1/papelera/desactivar/@id
     * Desactiva un bien (activo = false), moviéndolo a la papelera.
     */
    public static function desactivarBien(int $id): void
    {
        try {
            $pdo = self::getDb();

            $stmt = $pdo->prepare("
                UPDATE bienes
                SET activo = false
                WHERE cve_bien = :id
                RETURNING nombre
            ");
            $stmt->execute([':id' => $id]);
            $bien = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$bien) {
                Flight::json(ResponseFormatter::error("No se encontró el bien con ID: $id", 404), 404);
                return;
            }

            AuditLog::create('bienes', $id, ['accion' => 'desactivacion', 'nombre' => $bien['nombre']], Flight::get('user_id'));
            Logger::info("Bien desactivado (enviado a papelera)", ['cve_bien' => $id, 'nombre' => $bien['nombre']]);

            Flight::json(ResponseFormatter::success(null, "Bien enviado a papelera correctamente"));

        } catch (\Exception $e) {
            Logger::error("Error al desactivar bien", ['cve_bien' => $id, 'error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * PUT /api/v1/papelera/restaurar/@id
     * Restaura un bien (activo = true) de la papelera.
     */
    public static function restaurarBien(int $id): void
    {
        try {
            $pdo = self::getDb();

            $stmt = $pdo->prepare("
                UPDATE bienes
                SET activo = true
                WHERE cve_bien = :id
                RETURNING nombre
            ");
            $stmt->execute([':id' => $id]);
            $bien = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$bien) {
                Flight::json(ResponseFormatter::error("No se encontró el bien inactivo con ID: $id", 404), 404);
                return;
            }

            AuditLog::create('bienes', $id, ['accion' => 'restauracion', 'nombre' => $bien['nombre']], Flight::get('user_id'));
            Logger::info("Bien restaurado de la papelera", ['cve_bien' => $id, 'nombre' => $bien['nombre']]);

            Flight::json(ResponseFormatter::success(null, "Bien restaurado correctamente"));

        } catch (\Exception $e) {
            Logger::error("Error al restaurar bien", ['cve_bien' => $id, 'error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * DELETE /api/v1/papelera/eliminar/@id
     * Elimina permanentemente un bien de la base de datos.
     */
    public static function eliminarBienPermanente(int $id): void
    {
        try {
            $pdo = self::getDb();

            // Primero obtener el nombre para el log
            $stmtName = $pdo->prepare("SELECT nombre FROM bienes WHERE cve_bien = :id");
            $stmtName->execute([':id' => $id]);
            $bien = $stmtName->fetch(PDO::FETCH_ASSOC);

            if (!$bien) {
                Flight::json(ResponseFormatter::error("No se encontró el bien con ID: $id", 404), 404);
                return;
            }

            $stmt = $pdo->prepare("DELETE FROM bienes WHERE cve_bien = :id");
            $stmt->execute([':id' => $id]);

            AuditLog::create('bienes', $id, ['accion' => 'eliminacion_permanente', 'nombre' => $bien['nombre']], Flight::get('user_id'));
            Logger::info("Bien eliminado permanentemente", ['cve_bien' => $id, 'nombre' => $bien['nombre']]);

            Flight::json(ResponseFormatter::success(null, "Bien eliminado permanentemente correctamente"));

        } catch (\Exception $e) {
            Logger::error("Error al eliminar bien permanentemente", ['cve_bien' => $id, 'error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/papelera/bienes/persona/@cve_persona
     */
    public static function listarBienesInactivosPorPersona(int $cve_persona): void
    {
        try {
            $pdo = self::getDb();

            $stmt = $pdo->prepare("
                SELECT
                    b.cve_bien,
                    b.no_serie,
                    b.nombre,
                    b.estado_fisico AS estado
                FROM bienes b
                JOIN numero_resguardo nr ON b.cve_bien = nr.cve_bien
                WHERE b.activo = false
                AND nr.cve_persona = :cve_persona
                ORDER BY b.nombre ASC
            ");
            $stmt->execute([':cve_persona' => $cve_persona]);
            
            $bienes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Logger::info("Listado simplificado de bienes inactivos por persona consultado", [
                'cve_persona' => $cve_persona,
                'total' => count($bienes)
            ]);

            Flight::json(ResponseFormatter::success($bienes));

        } catch (\Exception $e) {
            Logger::error("Error al listar bienes inactivos por persona", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/papelera/bienes/@id
     * Obtiene el detalle completo de un bien inactivo por su ID.
     */
    public static function getBienDetalle(int $id): void
    {
        try {
            $pdo = self::getDb();

            $stmt = $pdo->prepare("
                SELECT 
                    b.cve_bien,
                    b.nombre,
                    b.codigo_barras,
                    b.codigo_qr,
                    b.no_serie,
                    b.no_factura,
                    b.descripcion,
                    b.costo_unitario,
                    b.estado_fisico,
                    b.estado_prestamo,
                    b.activo,
                    b.fecha_registro,
                    m.nombre_modelo,
                    mar.nombre_marca,
                    a.nombre as nombre_aula,
                    e.nombre as nombre_edificio
                FROM bienes b
                LEFT JOIN modelos m ON b.cve_modelo = m.cve_modelo
                LEFT JOIN marcas mar ON m.cve_marca = mar.cve_marca
                LEFT JOIN aula a ON b.cve_aula = a.cve_aula
                LEFT JOIN edificio e ON a.cve_edificio = e.cve_edificio
                WHERE b.cve_bien = :id AND b.activo = false
            ");
            $stmt->execute([':id' => $id]);
            $bien = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$bien) {
                Flight::json(ResponseFormatter::error("No se encontró el bien inactivo con ID: $id", 404), 404);
                return;
            }

            Flight::json(ResponseFormatter::success($bien));

        } catch (\Exception $e) {
            Logger::error("Error al obtener detalle del bien inactivo", ['id' => $id, 'error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }
}