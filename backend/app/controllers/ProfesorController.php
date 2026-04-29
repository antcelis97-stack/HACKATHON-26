<?php

namespace app\controllers;

use App\Lib\ResponseFormatter;
use App\Lib\Logger;
use Flight;
use PDO;

/**
 * Controlador del módulo profesor — conexión API / PostgreSQL.
 */
class ProfesorController
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
     * Obtiene el cve_persona del usuario autenticado o el proporcionado.
     */
    private static function resolvePersonaId($id = null): int
    {
        if ($id !== null && (int)$id > 0) {
            return (int)$id;
        }

        // Si no hay ID, intentamos sacarlo del token JWT (vía Flight set en authMiddleware)
        $user = Flight::get('user');
        if ($user && isset($user->cve_persona)) {
            return (int)$user->cve_persona;
        }

        return 0;
    }

    /**
     * GET /api/v1/profesor/contexto
     * Perfil del profesor autenticado con contadores.
     */
    public static function contexto(): void
    {
        try {
            $id_persona = self::resolvePersonaId();
            if ($id_persona < 1) {
                Flight::json(ResponseFormatter::unauthorized('Usuario no identificado'), 401);
                return;
            }

            $pdo = self::getDb();

            // Datos del profesor
            $stmt = $pdo->prepare("
                SELECT p.cve_persona,
                       CONCAT(pe.nombre, ' ', pe.apellido_paterno, ' ', pe.apellido_materno) AS nombre_completo
                FROM profesor p
                JOIN persona pe ON p.cve_persona = pe.cve_persona
                WHERE p.cve_persona = :id AND p.activo = true
            ");
            $stmt->execute([':id' => $id_persona]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                Flight::json(ResponseFormatter::notFound('Perfil de profesor'), 404);
                return;
            }

            // Contadores
            $stmtCount = $pdo->prepare("
                SELECT
                    COUNT(DISTINCT a.cve_aula) AS aulas_encargadas,
                    COUNT(DISTINCT b.cve_bien) AS bienes_a_cargo,
                    COUNT(DISTINCT pr.cve_prestamo) FILTER (WHERE pr.estado_prestamo IN ('pendiente', 'aprobado')) AS prestamos_activos,
                    COUNT(DISTINCT pr.cve_prestamo) FILTER (WHERE pr.estado_prestamo = 'pendiente') AS prestamos_pendientes
                FROM profesor p
                LEFT JOIN aula a ON a.cve_profesor = p.cve_profesor
                LEFT JOIN bienes b ON b.cve_aula = a.cve_aula AND b.activo = true
                LEFT JOIN prestamos pr ON pr.cve_persona_solicita = p.cve_persona
                WHERE p.cve_persona = :id
            ");
            $stmtCount->execute([':id' => $id_persona]);
            $counts = $stmtCount->fetch(PDO::FETCH_ASSOC);

            Flight::json(ResponseFormatter::success(array_merge($row, [
                'aulas_encargadas' => (int)($counts['aulas_encargadas'] ?? 0),
                'bienes_a_cargo' => (int)($counts['bienes_a_cargo'] ?? 0),
                'prestamos_activos' => (int)($counts['prestamos_activos'] ?? 0),
                'prestamos_pendientes' => (int)($counts['prestamos_pendientes'] ?? 0)
            ])));
        } catch (\Exception $e) {
            Logger::error('Profesor contexto', ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/profesor/aulas(/@id)
     * Aulas o espacios asignados a este profesor (responsable / encargado).
     */
    public static function listarAulas($id = null): void
    {
        try {
            $id_persona = self::resolvePersonaId($id);

            if ($id_persona < 1) {
                Flight::json(ResponseFormatter::error("ID de profesor no proporcionado o inválido"), 400);
                return;
            }

            $pdo = self::getDb();

            $stmt = $pdo->prepare("
                SELECT 
                    a.cve_aula, 
                    a.nombre AS nombre_aula, 
                    a.capacidad,
                    e.nombre as nombre_edificio,
                    ta.nombre as tipo_aula,
                    COUNT(b.cve_bien) as total_bienes
                FROM aula a
                LEFT JOIN edificio e ON a.cve_edificio = e.cve_edificio
                LEFT JOIN tipo_aula ta ON a.cve_tipo_aula = ta.cve_tipo_aula
                LEFT JOIN bienes b ON b.cve_aula = a.cve_aula AND b.activo = true
                INNER JOIN profesor p ON a.cve_profesor = p.cve_profesor
                WHERE p.cve_persona = :id_persona
                GROUP BY a.cve_aula, a.nombre, a.capacidad, e.nombre, ta.nombre
                ORDER BY a.nombre ASC
            ");

            $stmt->execute([':id_persona' => $id_persona]);
            $aulas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Flight::json(ResponseFormatter::success($aulas));

        } catch (\Exception $e) {
            Logger::error('Profesor listarAulas', ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/profesor/prestamos
     * Lista del historial de préstamos solicitados por el profesor.
     */
    public static function listarPrestamos(): void
    {
        try {
            $id_persona = self::resolvePersonaId();
            if ($id_persona < 1) {
                Flight::json(ResponseFormatter::unauthorized(), 401);
                return;
            }

            $pdo = self::getDb();
            $stmt = $pdo->prepare("
                SELECT 
                    p.cve_prestamo,
                    p.cve_bien,
                    p.cve_persona_solicita,
                    p.fecha_solicitud,
                    p.fecha_devolucion_pactada,
                    p.fecha_devolucion_real,
                    p.estado_prestamo,
                    p.observaciones,
                    b.nombre AS nombre_bien,
                    pe.nombre AS persona_nombre,
                    pe.apellido_paterno,
                    pe.apellido_materno,
                    a.nombre AS nombre_aula,
                    e.nombre AS nombre_edificio
                FROM prestamos p
                JOIN bienes b ON p.cve_bien = b.cve_bien
                LEFT JOIN aula a ON b.cve_aula = a.cve_aula
                LEFT JOIN edificio e ON a.cve_edificio = e.cve_edificio
                LEFT JOIN persona pe ON p.cve_persona_solicita = pe.cve_persona
                WHERE p.cve_persona_solicita = :id
                ORDER BY p.fecha_solicitud DESC
            ");
            $stmt->execute([':id' => $id_persona]);
            $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Flight::json(ResponseFormatter::success($prestamos));
        } catch (\Exception $e) {
            Logger::error('Profesor listarPrestamos', ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/profesor/inventario
     * Bienes vinculados al profesor (por ser responsable de aulas).
     */
    public static function listarInventario(): void
    {
        try {
            $id_persona = self::resolvePersonaId();
            if ($id_persona < 1) {
                Flight::json(ResponseFormatter::unauthorized(), 401);
                return;
            }

            $pdo = self::getDb();
            $stmt = $pdo->prepare("
                SELECT 
                    b.cve_bien,
                    b.nombre,
                    b.no_serie,
                    b.codigo_qr,
                    b.estado_fisico,
                    b.estado_prestamo,
                    b.cve_aula,
                    a.nombre AS nombre_aula,
                    b.fecha_registro
                FROM bienes b
                JOIN aula a ON b.cve_aula = a.cve_aula
                JOIN profesor p ON a.cve_profesor = p.cve_profesor
                WHERE p.cve_persona = :id AND b.activo = true
                ORDER BY b.nombre ASC
            ");
            $stmt->execute([':id' => $id_persona]);
            $inventario = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Flight::json(ResponseFormatter::success($inventario));
        } catch (\Exception $e) {
            Logger::error('Profesor listarInventario', ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }
    /**
     * GET /api/v1/profesor/bien/@id
     * Obtiene los detalles completos de un bien específico por su ID.
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
                WHERE b.cve_bien = :id
            ");
            $stmt->execute([':id' => $id]);
 
            $bien = $stmt->fetch(PDO::FETCH_ASSOC);
 
            if (!$bien) {
                Flight::json(ResponseFormatter::error("No se encontró ningún bien con el ID: $id", 404), 404);
                return;
            }
 
            Logger::info("Detalle de bien consultado (Profesor)", ['cve_bien' => $id]);
 
            Flight::json(ResponseFormatter::success($bien));
 
        } catch (\Exception $e) {
            Logger::error("Error al obtener detalle del bien", ['cve_bien' => $id, 'error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * POST /api/v1/profesor/inventario
     * Registrar un nuevo bien en un aula del profesor.
     * Valida que el aula pertenezca al profesor autenticado.
     */
    public static function registrarBien(): void
    {
        try {
            $id_persona = self::resolvePersonaId();
            if ($id_persona < 1) {
                Flight::json(ResponseFormatter::unauthorized('Usuario no identificado'), 401);
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            // -----------------------------------------------------------------
            // VALIDACIÓN
            // -----------------------------------------------------------------
            $errors = [];

            if (empty($data['nombre'])) {
                $errors[] = ['field' => 'nombre', 'message' => 'El nombre del bien es obligatorio'];
            }
            if (empty($data['cve_aula'])) {
                $errors[] = ['field' => 'cve_aula', 'message' => 'El aula es obligatoria'];
            }
            if (empty($data['no_serie'])) {
                $errors[] = ['field' => 'no_serie', 'message' => 'El número de serie es obligatorio'];
            }
            if (empty($data['no_factura'])) {
                $errors[] = ['field' => 'no_factura', 'message' => 'El número de factura es obligatorio'];
            }
            if (empty($data['cve_articulo'])) {
                $errors[] = ['field' => 'cve_articulo', 'message' => 'El artículo es obligatorio'];
            }
            if (empty($data['costo_unitario']) && $data['costo_unitario'] !== 0) {
                $errors[] = ['field' => 'costo_unitario', 'message' => 'El costo unitario es obligatorio'];
            }

            if (!empty($errors)) {
                Flight::json(ResponseFormatter::validationError($errors), 400);
                return;
            }

            $pdo = self::getDb();

            // -----------------------------------------------------------------
            // VALIDAR QUE EL AULA PERTENECE AL PROFESOR
            // -----------------------------------------------------------------
            $stmtAula = $pdo->prepare("
                SELECT a.cve_aula
                FROM aula a
                INNER JOIN profesor p ON a.cve_profesor = p.cve_profesor
                WHERE a.cve_aula = :cve_aula AND p.cve_persona = :id_persona
            ");
            $stmtAula->execute([
                ':cve_aula' => (int)$data['cve_aula'],
                ':id_persona' => $id_persona
            ]);

            if (!$stmtAula->fetch()) {
                Flight::json(ResponseFormatter::error(
                    'El aula seleccionada no está asignada a tu perfil de profesor',
                    403
                ), 403);
                return;
            }

            // -----------------------------------------------------------------
            // GENERAR CÓDIGO DE BARRAS (si no se envió uno manual)
            // -----------------------------------------------------------------
            $codigo_barras_final = $data['codigo_barras'] ?? null;

            if (empty($codigo_barras_final) && !empty($data['cve_tipo']) && !empty($data['cve_familia']) && !empty($data['cve_articulo']) && !empty($data['identificador'])) {
                $codigo_barras_final = \app\controllers\BarcodeController::generarCodigoBarrasInterno(
                    (int)$data['cve_tipo'],
                    (int)$data['cve_familia'],
                    (int)$data['cve_articulo'],
                    (string)$data['identificador']
                );
            }

            // -----------------------------------------------------------------
            // INSERCIÓN EN BASE DE DATOS
            // -----------------------------------------------------------------
            $pdo->beginTransaction();

            $cveModelo = !empty($data['cve_modelo']) ? (int)$data['cve_modelo'] : null;

            $stmt = $pdo->prepare("
                INSERT INTO bienes (
                    nombre,
                    codigo_barras,
                    codigo_qr,
                    nfc,
                    no_serie,
                    no_factura,
                    descripcion,
                    cve_modelo,
                    cve_articulo,
                    costo_unitario,
                    cve_aula,
                    estado_fisico,
                    estado_prestamo,
                    foto_url,
                    foto_drive_id
                ) VALUES (
                    :nombre,
                    :codigo_barras,
                    :codigo_qr,
                    :nfc,
                    :no_serie,
                    :no_factura,
                    :descripcion,
                    :cve_modelo,
                    :cve_articulo,
                    :costo_unitario,
                    :cve_aula,
                    :estado_fisico,
                    :estado_prestamo,
                    :foto_url,
                    :foto_drive_id
                ) RETURNING cve_bien
            ");

            $stmt->execute([
                ':nombre'         => $data['nombre'],
                ':codigo_barras'  => $codigo_barras_final,
                ':codigo_qr'      => $data['codigo_qr'] ?? null,
                ':nfc'            => $data['nfc'] ?? null,
                ':no_serie'       => $data['no_serie'],
                ':no_factura'     => $data['no_factura'],
                ':descripcion'    => $data['descripcion'] ?? null,
                ':cve_modelo'     => $cveModelo,
                ':cve_articulo'   => (int)$data['cve_articulo'],
                ':costo_unitario' => (float)$data['costo_unitario'],
                ':cve_aula'       => (int)$data['cve_aula'],
                ':estado_fisico'  => $data['estado_fisico'] ?? 'bueno',
                ':estado_prestamo' => $data['estado_prestamo'] ?? 'disponible',
                ':foto_url'       => $data['foto_url'] ?? null,
                ':foto_drive_id'  => $data['foto_drive_id'] ?? null
            ]);

            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            $nuevoCveBien = (int)$resultado['cve_bien'];

            $pdo->commit();

            Logger::info('Profesor registró bien', [
                'cve_bien' => $nuevoCveBien,
                'nombre' => $data['nombre'],
                'cve_aula' => $data['cve_aula'],
                'id_persona' => $id_persona
            ]);

            Flight::json(ResponseFormatter::success([
                'cve_bien' => $nuevoCveBien,
                'nombre' => $data['nombre']
            ], 'Bien registrado correctamente'), 201);

        } catch (\Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Logger::error('Profesor registrarBien', ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/profesor/prestamos/no-devueltos
     * Préstamos activos del profesor que aún no han sido devueltos.
     */
    public static function listarPrestamosNoDevueltos(): void
    {
        try {
            $id_persona = self::resolvePersonaId();
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
                    p.estado_prestamo,
                    b.nombre AS nombre_bien,
                    a.nombre AS nombre_aula,
                    e.nombre AS nombre_edificio
                FROM prestamos p
                JOIN bienes b ON p.cve_bien = b.cve_bien
                LEFT JOIN aula a ON b.cve_aula = a.cve_aula
                LEFT JOIN edificio e ON a.cve_edificio = e.cve_edificio
                WHERE p.cve_persona_solicita = :id
                  AND p.estado_prestamo IN ('pendiente', 'aprobado', 'por_devolver')
                ORDER BY p.fecha_solicitud DESC
            ");
            $stmt->execute([':id' => $id_persona]);
            $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Flight::json(ResponseFormatter::success($prestamos));
        } catch (\Exception $e) {
            Logger::error('Profesor listarPrestamosNoDevueltos', ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/profesor/movimientos
     * Bitácora de movimientos de bienes en las aulas del profesor.
     */
    public static function listarMovimientos(): void
    {
        try {
            $id_persona = self::resolvePersonaId();
            if ($id_persona < 1) {
                Flight::json(ResponseFormatter::unauthorized(), 401);
                return;
            }

            $pdo = self::getDb();
            $stmt = $pdo->prepare("
                SELECT 
                    bm.cve_movimiento,
                    b.cve_bien,
                    p.cve_prestamo,
                    b.nombre AS nombre_bien,
                    b.no_serie,
                    bm.fecha_movimiento,
                    mm.nombre_motivo,
                    pa.nombre || ' ' || COALESCE(pa.apellido_paterno, '') || ' ' || COALESCE(pa.apellido_materno, '') AS persona_accion,
                    bm.observaciones,
                    a.nombre AS nombre_aula,
                    e.nombre AS nombre_edificio
                FROM bitacora_movimientos bm
                JOIN bienes b ON bm.cve_bien = b.cve_bien
                JOIN aula a ON b.cve_aula = a.cve_aula
                JOIN profesor pf ON a.cve_profesor = pf.cve_profesor
                LEFT JOIN motivo_de_movimiento mm ON bm.cve_motivo = mm.cve_motivo
                LEFT JOIN persona pa ON bm.cve_persona_accion = pa.cve_persona
                LEFT JOIN edificio e ON a.cve_edificio = e.cve_edificio
                LEFT JOIN prestamos p ON p.cve_bien = b.cve_bien 
                    AND p.fecha_solicitud::date = bm.fecha_movimiento::date
                    AND p.cve_persona_solicita = bm.cve_persona_accion
                WHERE pf.cve_persona = :id_persona
                ORDER BY bm.fecha_movimiento DESC
            ");
            $stmt->execute([':id_persona' => $id_persona]);
            $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Flight::json(ResponseFormatter::success($movimientos));
        } catch (\Exception $e) {
            Logger::error('Profesor listarMovimientos', ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/profesor/reporte-bienes
     * Reporte completo de bienes en las aulas del profesor.
     */
    public static function listarBienesReporte(): void
    {
        try {
            $id_persona = self::resolvePersonaId();
            if ($id_persona < 1) {
                Flight::json(ResponseFormatter::unauthorized(), 401);
                return;
            }

            $pdo = self::getDb();
            $stmt = $pdo->prepare("
                SELECT 
                    b.cve_bien,
                    b.codigo_barras AS no_inventario,
                    b.nombre AS descripcion,
                    m.nombre_modelo AS modelo,
                    b.no_serie,
                    mar.nombre_marca AS marca,
                    b.costo_unitario,
                    b.no_factura,
                    a.nombre AS nombre_aula,
                    e.nombre AS nombre_edificio,
                    b.estado_fisico,
                    b.estado_prestamo,
                    b.codigo_qr,
                    b.nfc,
                    b.fecha_registro
                FROM bienes b
                JOIN aula a ON b.cve_aula = a.cve_aula
                JOIN profesor pf ON a.cve_profesor = pf.cve_profesor
                LEFT JOIN modelos m ON b.cve_modelo = m.cve_modelo
                LEFT JOIN marcas mar ON m.cve_marca = mar.cve_marca
                LEFT JOIN edificio e ON a.cve_edificio = e.cve_edificio
                WHERE pf.cve_persona = :id_persona AND b.activo = true
                ORDER BY a.nombre, b.nombre
            ");
            $stmt->execute([':id_persona' => $id_persona]);
            $bienes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Flight::json(ResponseFormatter::success($bienes));
        } catch (\Exception $e) {
            Logger::error('Profesor listarBienesReporte', ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }
}
