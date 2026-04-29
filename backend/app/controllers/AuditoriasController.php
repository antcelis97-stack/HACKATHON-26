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
 * AuditoriasController - Módulo de Auditorías de Inventario
 *
 * Endpoints disponibles:
 * - GET  /api/v1/administrador/auditorias          -> Ver auditorías realizadas (protegido)
 */
class AuditoriasController
{
    /**
     * Retorna una instancia PDO reutilizable.
     */
    private static function getDb(): \PDO
    {
        require_once __DIR__ . '/../../config/database.php';
        return getPgInventarioConnection();
    }

    /**
     * GET /api/v1/auditorias/buscar-nfc
     * 
     * Busca un bien en el inventario por su tag NFC.
     * Adaptado de la consulta de inmuebles/bien.
     */
    public static function buscarBienPorNFC($nfc)
    {
        try {
            if (empty($nfc)) {
                Flight::json(ResponseFormatter::error("No se recibió NFC", 400), 400);
                return;
            }

            $pdo = self::getDb();

            $sql = "SELECT 
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
                        b.cve_aula,
                        b.foto_url,
                        b.foto_drive_id,
                        m.nombre_modelo,
                        mar.nombre_marca,
                        a.nombre as nombre_aula,
                        e.nombre as nombre_edificio
                    FROM bienes b
                    LEFT JOIN modelos m ON b.cve_modelo = m.cve_modelo
                    LEFT JOIN marcas mar ON m.cve_marca = mar.cve_marca
                    LEFT JOIN aula a ON b.cve_aula = a.cve_aula
                    LEFT JOIN edificio e ON a.cve_edificio = e.cve_edificio
                    WHERE b.nfc = :nfc";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([':nfc' => $nfc]);
            $bien = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$bien) {
                Flight::json([
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'No se encontró bien con esa etiqueta NFC'
                    ]
                ], 404);
                return;
            }

            // Comparar ubicación con el aula enviada por el frontend
            $cve_aula_escaneada = Flight::request()->query->cve_aula;
            $ubicacion_correcta = false;
            
            if ($cve_aula_escaneada !== null) {
                $ubicacion_correcta = ((int)$cve_aula_escaneada === (int)$bien['cve_aula']);
            }

            Flight::json([
                'success' => true,
                'data' => [
                    'cve_bien' => $bien['cve_bien'],
                    'nombre' => $bien['nombre'],
                    'codigo_barras' => $bien['codigo_barras'],
                    'codigo_qr' => $bien['codigo_qr'],
                    'no_serie' => $bien['no_serie'],
                    'estado_fisico' => $bien['estado_fisico'],
                    'nombre_aula' => $bien['nombre_aula'],
                    'foto_url' => $bien['foto_url'],
                    'foto_drive_id' => $bien['foto_drive_id'],
                    'ubicacion_correcta' => $ubicacion_correcta,
                    'encontrado_previamente' => false
                ]
            ]);

        } catch (\Exception $e) {
            Logger::error("Error al buscar bien por NFC: " . $e->getMessage());
            Flight::json([
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => "Error en la búsqueda: " . $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * GET /api/v1/auditorias/buscar-qr
     * 
     * Busca un bien en el inventario por su código QR y proporciona un link de reporte.
     */
    public static function buscarBienPorQR($qr)
    {
        try {
            if (empty($qr)) {
                Flight::json(ResponseFormatter::error("No se recibió el código QR", 400), 400);
                return;
            }

            $pdo = self::getDb();

            $sql = "SELECT 
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
                        b.cve_aula,
                        b.foto_url,
                        b.foto_drive_id,
                        m.nombre_modelo,
                        mar.nombre_marca,
                        a.nombre as nombre_aula,
                        e.nombre as nombre_edificio
                    FROM bienes b
                    LEFT JOIN modelos m ON b.cve_modelo = m.cve_modelo
                    LEFT JOIN marcas mar ON m.cve_marca = mar.cve_marca
                    LEFT JOIN aula a ON b.cve_aula = a.cve_aula
                    LEFT JOIN edificio e ON a.cve_edificio = e.cve_edificio
                    WHERE b.codigo_qr = :qr";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([':qr' => $qr]);
            $bien = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$bien) {
                Flight::json(ResponseFormatter::error("No se encontró ningún bien con el QR: $qr", 404), 404);
                return;
            }

            // Comparar ubicación con el aula enviada por el frontend
            $cve_aula_escaneada = Flight::request()->query->cve_aula;
            $ubicacion_correcta = false;
            
            if ($cve_aula_escaneada !== null) {
                $ubicacion_correcta = ((int)$cve_aula_escaneada === (int)$bien['cve_aula']);
            }

            // Construir el enlace al reporte 
            $urlReporte = "/api/v1/reportes/detalle-bien/" . $bien['cve_bien'];

            Flight::json(ResponseFormatter::success(array_merge($bien, [
                'ubicacion_correcta' => $ubicacion_correcta,
                'url_reporte' => $urlReporte
            ])));

        } catch (\Exception $e) {
            Logger::error("Error al buscar bien por QR: " . $e->getMessage());
            Flight::json(ResponseFormatter::error("Error en la búsqueda por QR: " . $e->getMessage(), 500), 500);
        }
    }

    public static function listarAuditorias()
    {
        try {
            $pdo = self::getDb();
            
            // Obtener auditorías ordenadas por fecha descendente
            // Nota: Se corrigió 'fecha' por 'fecha_auditoria' según el esquema bd.sql
            $stmt = $pdo->query("SELECT * FROM auditorias ORDER BY fecha_auditoria DESC");
            $auditorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            Flight::json(ResponseFormatter::success($auditorias));
            
        } catch (\Exception $e) {
            Logger::error("Error al listar auditorías: " . $e->getMessage());
            Flight::json(ResponseFormatter::error("Error al obtener auditorías", 500), 500);
        }
    }

    public static function registrarAuditoria(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            // -----------------------------------------------------------------
            // Validación
            // -----------------------------------------------------------------
            $errors = [];
            if (empty($data['cve_auditor'])) {
                $errors[] = ['field' => 'cve_auditor', 'message' => 'El auditor (cve_auditor) es requerido'];
            }

            if (!empty($errors)) {
                Flight::json(ResponseFormatter::validationError($errors), 400);
                return;
            }

            // -----------------------------------------------------------------
            // Conexión e inserción con transacción
            // -----------------------------------------------------------------
            $pdo = self::getDb();
            $pdo->beginTransaction();

            $stmtAuditoria = $pdo->prepare("
                INSERT INTO auditorias (cve_auditor, observaciones_generales, fecha_auditoria)
                VALUES (:cve_auditor, :observaciones_generales, CURRENT_DATE)
                RETURNING cve_auditoria
            ");

            $stmtAuditoria->execute([
                ':cve_auditor' => $data['cve_auditor'],
                ':observaciones_generales' => $data['observaciones_generales'] ?? null
            ]);

            $nuevoCveAuditoria = $stmtAuditoria->fetch()['cve_auditoria'];

            // Inserción de detalles de auditoría si se enviaron
            if (!empty($data['detalles']) && is_array($data['detalles'])) {
                $stmtDetalle = $pdo->prepare("
                    INSERT INTO auditoria_detalle (cve_auditoria, cve_bien, encontrado, estado_encontrado)
                    VALUES (:cve_auditoria, :cve_bien, :encontrado, :estado_encontrado)
                ");

                foreach ($data['detalles'] as $detalle) {
                    $stmtDetalle->execute([
                        ':cve_auditoria'     => $nuevoCveAuditoria,
                        ':cve_bien'          => $detalle['cve_bien'] ?? null,
                        ':encontrado'        => isset($detalle['encontrado']) ? $detalle['encontrado'] : 0,
                        ':estado_encontrado' => $detalle['estado_encontrado'] ?? null
                    ]);
                }
            }

            $pdo->commit();

            // -----------------------------------------------------------------
            // Auditoría y logging
            // -----------------------------------------------------------------
            AuditLog::create('auditorias', $nuevoCveAuditoria, $data, Flight::get('user_id'));
            Logger::info("Auditoría registrada", ['cve_auditoria' => $nuevoCveAuditoria]);

            Flight::json(ResponseFormatter::created(['cve_auditoria' => $nuevoCveAuditoria]), 201);

        } catch (\Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            Logger::error("Error al registrar auditoría", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }
}