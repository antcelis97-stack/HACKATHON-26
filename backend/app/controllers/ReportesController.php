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

class ReportesController
{
    private static function getDb(): \PDO
    {
        require_once __DIR__ . '/../../config/database.php';
        return getPgInventarioConnection();
    }

    /**
     * GET /api/v1/reportes/resguardo-individual/@id_persona
     * Obtiene la lista de bienes asignados a una persona específica
     * Soporta filtro opcional por id_aula
     */
    public static function getResguardoIndividual(int $id_persona, ?int $id_aula = null): void
    {
        try {
            $pdo = self::getDb();

            // Primero verificamos si es profesor para buscar bienes en sus aulas
            $verificarRol = $pdo->prepare("SELECT cve_profesor FROM profesor WHERE cve_persona = :id_persona");
            $verificarRol->execute([':id_persona' => $id_persona]);
            $profesor = $verificarRol->fetch(PDO::FETCH_ASSOC);

            if ($profesor && !empty($profesor['cve_profesor'])) {
                // Es profesor: traer bienes en sus aulas (con filtro opcional por aula)
                $params = [':id_persona' => $id_persona];
                $filtroAula = "";
                
                if ($id_aula) {
                    $filtroAula = "AND au.cve_aula = :id_aula";
                    $params[':id_aula'] = $id_aula;
                }

                $stmt = $pdo->prepare("
                    SELECT
                        b.cve_bien,
                        b.nombre AS descripcion_bien,
                        m.nombre_modelo,
                        ma.nombre_marca,
                        b.no_serie,
                        b.costo_unitario,
                        b.no_factura,
                        b.codigo_barras AS no_inventario,
                        b.estado_fisico,
                        au.cve_aula,
                        au.nombre AS nombre_aula
                    FROM bienes b
                    JOIN aula au ON b.cve_aula = au.cve_aula
                    JOIN profesor pf ON au.cve_profesor = pf.cve_profesor
                    LEFT JOIN modelos m ON b.cve_modelo = m.cve_modelo
                    LEFT JOIN marcas ma ON m.cve_marca = ma.cve_marca
                    WHERE pf.cve_persona = :id_persona
                      AND b.activo = true
                      $filtroAula
                    ORDER BY au.nombre, b.nombre ASC
                ");
                $stmt->execute($params);
            } else {
                // No es profesor: buscar por préstamos aprovados (lógica original)
                $stmt = $pdo->prepare("
                    SELECT
                        b.cve_bien,
                        b.nombre AS descripcion_bien,
                        m.nombre_modelo,
                        ma.nombre_marca,
                        b.no_serie,
                        b.costo_unitario,
                        b.no_factura,
                        b.codigo_barras AS no_inventario,
                        b.estado_fisico,
                        au.cve_aula,
                        au.nombre AS nombre_aula,
                        p.estado_prestamo AS estado_prestamo
                    FROM bienes b
                    LEFT JOIN modelos m ON b.cve_modelo = m.cve_modelo
                    LEFT JOIN marcas ma ON m.cve_marca = ma.cve_marca
                    LEFT JOIN aula au ON b.cve_aula = au.cve_aula
                    LEFT JOIN prestamos p ON b.cve_bien = p.cve_bien
                    WHERE p.cve_persona_solicita = :id_persona
                      AND p.estado_prestamo = 'aprobado'
                      AND p.fecha_devolucion_real IS NULL
                      AND b.activo = true
                    ORDER BY b.nombre ASC
                ");
                $stmt->execute([':id_persona' => $id_persona]);
            }

            $bienes = $stmt->fetchAll();

            if (empty($bienes)) {
                Logger::info("No se encontraron bienes para el resguardo", ['id_persona' => $id_persona, 'id_aula' => $id_aula]);
                Flight::json(ResponseFormatter::success([], 200, "La persona no tiene bienes asignados actualmente."));
                return;
            }

            Logger::info("Resguardo individual consultado", [
                'id_persona' => $id_persona, 
                'id_aula' => $id_aula,
                'total_bienes' => count($bienes)
            ]);

            Flight::json(ResponseFormatter::success($bienes));

        } catch (\Exception $e) {
            Logger::error("Error al obtener resguardo individual", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

       /**
     * Obtiene exclusivamente los datos dinámicos del encabezado para un reporte.
     * Basado en la relación de persona, adscripción y su área académica/administrativa.
     */
    public static function getEncabezadoReporte(int $id_persona): void
    {
        try {
            $pdo = self::getDb();

            $stmt = $pdo->prepare("
                SELECT 
                    p.nombre,
                    p.apellido_paterno,
                    p.apellido_materno,
                    ar.nombre AS area_departamento,
                    ar.abreviatura AS area_abreviatura,
                    ads.clave_adscripcion,
                    ads.nombre_adscripcion AS adscripcion,
                    fs.clave_siia AS siia,
                    nr.no_resguardo AS numero_resguardo,
                    CURRENT_TIMESTAMP AS fecha_generacion
                FROM persona p
                LEFT JOIN profesor prof ON p.cve_persona = prof.cve_persona
                LEFT JOIN area ar ON prof.cve_area = ar.cve_area
                LEFT JOIN adscripcion_persona ap ON p.cve_persona = ap.cve_persona AND ap.activo = TRUE
                LEFT JOIN adscripcion ads ON ap.cve_adscripcion = ads.cve_adscripcion
                LEFT JOIN folio_siia fs ON p.cve_persona = fs.cve_persona AND fs.estado = TRUE
                LEFT JOIN numero_resguardo nr ON p.cve_persona = nr.cve_persona AND nr.estado = TRUE
                WHERE p.cve_persona = :id
            ");

            $stmt->execute([':id' => $id_persona]);
            $datos = $stmt->fetch();

            if (!$datos) {
                Flight::json(ResponseFormatter::error("Persona no encontrada o sin vinculación laboral"), 404);
                return;
            }

            // Retornamos únicamente la data cruda de la DB
            Flight::json(ResponseFormatter::success($datos));

        } catch (\Exception $e) {
            Logger::error("Error al obtener encabezado dinámico", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/reportes/encabezado-direccion/@id_persona
     * 
     * Obtiene exclusivamente el nombre de la carrera (desde tabla area)
     * y la adscripción para los reportes de dirección.
     */
    public static function getEncabezadoDireccion(int $id_persona): void
    {
        try {
            $pdo = self::getDb();

            $stmt = $pdo->prepare("
                SELECT 
                    ar.nombre AS nombre_area,
                    ar.abreviatura AS abreviatura
                FROM persona p
                LEFT JOIN area ar ON p.cve_carrera = ar.cve_area
                WHERE p.cve_persona = :id
            ");

            $stmt->execute([':id' => $id_persona]);
            $datos = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$datos) {
                Flight::json(ResponseFormatter::error("Persona no encontrada o sin datos de carrera/adscripción"), 404);
                return;
            }

            Flight::json(ResponseFormatter::success($datos));

        } catch (\Exception $e) {
            Logger::error("Error al obtener encabezado de dirección", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/reportes/movimiento-interno-tabla/@id_prestamo
     * Obtiene la lista de bienes que integran el movimiento interno.
     */
    /**
     * GET /api/v1/reportes/movimiento-interno-completo/@id_prestamo
     * Obtiene toda la información dinámica para el formato de Movimientos Internos.
     */
    /**
     * GET /api/v1/reportes/movimiento-interno/@id_prestamo
     * 
     * Obtiene la información completa para el formato de Movimiento Interno,
     * incluyendo datos de Baja (quien entrega), Alta (quien recibe) y la lista de bienes.
     */
    public static function getMovimientoInterno(int $id_prestamo): void
    {
try {
            $pdo = self::getDb();

            // 1. Obtener datos del encabezado (Responsables y Datos del Movimiento)
            $stmtEncabezado = $pdo->prepare("
                SELECT 
                    -- RESPONSABLE - BAJA (Quien entrega - el que hace el movimiento)
                    p_baja.nombre || ' ' || p_baja.apellido_paterno || ' ' || COALESCE(p_baja.apellido_materno, '') AS nombre_baja,
                    ar_baja.nombre AS area_baja,
                    ads_baja.nombre_adscripcion AS adscripcion_baja,
                    
                    -- RESPONSABLE - ALTA (Quien recibe - puede ser el mismo o diferente)
                    p_alta.nombre || ' ' || p_alta.apellido_paterno || ' ' || COALESCE(p_alta.apellido_materno, '') AS nombre_alta,
                    ar_alta.nombre AS area_alta,
                    ads_alta.nombre_adscripcion AS adscripcion_alta,
  
                    -- DATOS DEL MOVIMIENTO
                    pres.cve_prestamo AS no_resguardo_movimiento,
                    pres.estado_prestamo AS motivo_movimiento,
                    pres.observaciones AS observaciones_movimiento,
                    TO_CHAR(pres.fecha_solicitud, 'DD/MM/YYYY') AS fecha_emision
                FROM prestamos pres
                
                -- Datos de quien recibe (Alta) - usar LEFT JOIN para que siempre retorne datos
                LEFT JOIN persona p_alta ON pres.cve_persona_solicita = p_alta.cve_persona
                LEFT JOIN adscripcion_persona ap_alta ON p_alta.cve_persona = ap_alta.cve_persona AND ap_alta.activo = TRUE
                LEFT JOIN adscripcion ads_alta ON ap_alta.cve_adscripcion = ads_alta.cve_adscripcion
                LEFT JOIN profesor prof_alta ON p_alta.cve_persona = prof_alta.cve_persona
                LEFT JOIN area ar_alta ON prof_alta.cve_area = ar_alta.cve_area
                
                -- Datos del usuario actual (para Responsable Baja)
                LEFT JOIN persona p_baja ON p_baja.cve_persona = pres.cve_persona_solicita
                LEFT JOIN adscripcion_persona ap_baja ON p_baja.cve_persona = ap_baja.cve_persona AND ap_baja.activo = TRUE
                LEFT JOIN adscripcion ads_baja ON ap_baja.cve_adscripcion = ads_baja.cve_adscripcion
                LEFT JOIN profesor prof_baja ON p_baja.cve_persona = prof_baja.cve_persona
                LEFT JOIN area ar_baja ON prof_baja.cve_area = ar_baja.cve_area
                
                WHERE pres.cve_prestamo = :id_p
            ");

            $stmtEncabezado->execute([':id_p' => $id_prestamo]);
            $encabezado = $stmtEncabezado->fetch(PDO::FETCH_ASSOC);

            if (!$encabezado) {
                Flight::json(ResponseFormatter::error("Movimiento no encontrado"), 404);
                return;
            }

            // 2. Obtener la tabla de bienes relacionados con ubicación completa
            $stmtBienes = $pdo->prepare("
                SELECT 
                    b.cve_bien AS no_inventario,
                    b.nombre AS descripcion,
                    m.nombre_modelo AS modelo,
                    b.no_serie,
                    ma.nombre_marca AS marca,
                    b.costo_unitario AS costo,
                    b.no_factura AS factura,
                    ed.nombre || ' - ' || au.nombre AS ubicacion
                FROM prestamos p
                INNER JOIN bienes b ON p.cve_bien = b.cve_bien
                LEFT JOIN modelos m ON b.cve_modelo = m.cve_modelo
                LEFT JOIN marcas ma ON m.cve_marca = ma.cve_marca
                LEFT JOIN aula au ON b.cve_aula = au.cve_aula
                LEFT JOIN edificio ed ON au.cve_edificio = ed.cve_edificio
                WHERE p.cve_prestamo = :id_p
            ");

            $stmtBienes->execute([':id_p' => $id_prestamo]);
            $bienes = $stmtBienes->fetchAll(PDO::FETCH_ASSOC);

            // 3. Respuesta unificada
            Flight::json(ResponseFormatter::success([
                "encabezado" => $encabezado,
                "relacion_bienes" => $bienes
            ]));

        } catch (\Exception $e) {
            Logger::error("Error en reporte de movimientos", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    
    /**
     * GET /api/v1/administrador/inventario/bajas
     *
     * Retorna los bienes que han sido dados de baja (activo = false/0).
     * Filtra por cve_adscripcion del usuario.
     */
    public static function listarBienesDadosDeBaja(): void
    {
        try {
            $pdo = self::getDb();

            // Obtener cve_adscripcion del usuario logueado
            $user = Flight::get('user');
            $cve_persona = $user->cve_persona ?? null;
            $cve_adscripcion = null;
            $filtroAdscripcion = "";
            $params = [];

            if ($cve_persona) {
                $stmtAds = $pdo->prepare("
                    SELECT ap.cve_adscripcion
                    FROM adscripcion_persona ap
                    WHERE ap.cve_persona = :cve_persona AND ap.activo = TRUE
                ");
                $stmtAds->execute([':cve_persona' => $cve_persona]);
                $cve_adscripcion = $stmtAds->fetchColumn();

                if ($cve_adscripcion) {
                    $filtroAdscripcion = "AND au.cve_adscripcion = :cve_adscripcion";
                    $params[':cve_adscripcion'] = $cve_adscripcion;
                }
            }

            $stmt = $pdo->prepare("
                SELECT 
                    b.cve_bien,
                    b.nombre AS descripcion_bien,
                    mo.nombre_modelo,
                    ma.nombre_marca,
                    b.no_serie,
                    b.estado_fisico,
                    au.nombre AS ultima_ubicacion,
                    b.fecha_registro AS fecha_alta,
                    b.descripcion AS observaciones_baja
                FROM bienes b
                LEFT JOIN modelos mo ON b.cve_modelo = mo.cve_modelo
                LEFT JOIN marcas ma ON mo.cve_marca = ma.cve_marca
                LEFT JOIN aula au ON b.cve_aula = au.cve_aula
                WHERE b.activo = false
                $filtroAdscripcion
                ORDER BY b.nombre ASC
            ");
            $stmt->execute($params);
            
            $bienes_dados_baja = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Logger::info("Reporte de bienes dados de baja consultado", ['total' => count($bienes_dados_baja), 'cve_adscripcion' => $cve_adscripcion]);

            Flight::json(ResponseFormatter::success($bienes_dados_baja));

        } catch (\Exception $e) {
            Logger::error("Error al obtener reporte de bajas", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    public static function listarBienesEnMantenimiento(): void
    {
        try {
            $pdo = self::getDb();

            // Obtener cve_adscripcion del usuario logueado
            $user = Flight::get('user');
            $cve_persona = $user->cve_persona ?? null;
            $cve_adscripcion = null;
            $filtroAdscripcion = "";
            $params = [];

            if ($cve_persona) {
                $stmtAds = $pdo->prepare("
                    SELECT ap.cve_adscripcion
                    FROM adscripcion_persona ap
                    WHERE ap.cve_persona = :cve_persona AND ap.activo = TRUE
                ");
                $stmtAds->execute([':cve_persona' => $cve_persona]);
                $cve_adscripcion = $stmtAds->fetchColumn();

                if ($cve_adscripcion) {
                    $filtroAdscripcion = "AND au.cve_adscripcion = :cve_adscripcion";
                    $params[':cve_adscripcion'] = $cve_adscripcion;
                }
            }

            $stmt = $pdo->prepare("
                SELECT 
                    b.cve_bien,
                    b.nombre AS descripcion_bien,
                    mo.nombre_modelo,
                    ma.nombre_marca,
                    b.no_serie,
                    b.estado_fisico,
                    au.nombre AS ubicacion_actual,
                    b.fecha_registro AS fecha_alta,
                    b.descripcion AS observaciones_mantenimiento
                FROM bienes b
                LEFT JOIN modelos mo ON b.cve_modelo = mo.cve_modelo
                LEFT JOIN marcas ma ON mo.cve_marca = ma.cve_marca
                LEFT JOIN aula au ON b.cve_aula = au.cve_aula
                WHERE b.estado_fisico = 'reparacion'
                AND b.activo = true
                $filtroAdscripcion
                ORDER BY b.nombre ASC
            ");
            $stmt->execute($params);
            
            $bienes_mantenimiento = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Logger::info("Reporte de bienes en mantenimiento consultado", ['total' => count($bienes_mantenimiento), 'cve_adscripcion' => $cve_adscripcion]);

            Flight::json(ResponseFormatter::success($bienes_mantenimiento));

        } catch (\Exception $e) {
            Logger::error("Error al obtener reporte de mantenimiento", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/reportes/trazabilidad/@id_bien
     * 
     * Obtiene el historial completo de movimientos, préstamos y cambios de un bien.
     */
    public static function getTrazabilidadBien(int $id_bien): void
    {
        try {
            $pdo = self::getDb();

            // 1. Obtener información básica del bien para el encabezado
            $stmtBien = $pdo->prepare("
                SELECT b.nombre, b.no_serie, a.nombre as aula_actual, b.estado_fisico
                FROM bienes b
                LEFT JOIN aula a ON b.cve_aula = a.cve_aula
                WHERE b.cve_bien = :id
            ");
            $stmtBien->execute([':id' => $id_bien]);
            $bien = $stmtBien->fetch();

            if (!$bien) {
                Flight::json(ResponseFormatter::error("Bien no encontrado"), 404);
                return;
            }

            // 2. Obtener historial unificado (Bitácora + Préstamos + Devoluciones)
            $sql = "
                SELECT 
                    fecha,
                    tipo_movimiento,
                    responsable,
                    observaciones
                FROM (
                    -- 2.1 Movimientos registrados en la bitácora manual
                    SELECT 
                        bm.fecha_movimiento AS fecha,
                        m.nombre_motivo AS tipo_movimiento,
                        p.nombre || ' ' || COALESCE(p.apellido_paterno, '') AS responsable,
                        bm.observaciones
                    FROM bitacora_movimientos bm
                    JOIN motivo_de_movimiento m ON bm.cve_motivo = m.cve_motivo
                    JOIN persona p ON bm.cve_persona_accion = p.cve_persona
                    WHERE bm.cve_bien = :id1

                    UNION ALL

                    -- 2.2 Registro de Préstamos (Salidas)
                    SELECT 
                        pr.fecha_solicitud AS fecha,
                        'Préstamo (' || pr.estado_prestamo || ')' AS tipo_movimiento,
                        p.nombre || ' ' || COALESCE(p.apellido_paterno, '') AS responsable,
                        'El bien fue solicitado para préstamo externo/interno.' AS observaciones
                    FROM prestamos pr
                    JOIN persona p ON pr.cve_persona_solicita = p.cve_persona
                    WHERE pr.cve_bien = :id2

                    UNION ALL

                    -- 2.3 Registro de Devoluciones (Retornos)
                    SELECT 
                        pr.fecha_devolucion_real AS fecha,
                        'Devolución Confirmada' AS tipo_movimiento,
                        p.nombre || ' ' || COALESCE(p.apellido_paterno, '') AS responsable,
                        'El bien ha sido devuelto y reintegrado al inventario.' AS observaciones
                    FROM prestamos pr
                    JOIN persona p ON pr.cve_persona_solicita = p.cve_persona
                    WHERE pr.cve_bien = :id3 AND pr.fecha_devolucion_real IS NOT NULL
                ) historia
                ORDER BY fecha DESC
            ";

            $stmtHistorial = $pdo->prepare($sql);
            $stmtHistorial->execute([
                ':id1' => $id_bien, 
                ':id2' => $id_bien,
                ':id3' => $id_bien
            ]);
            $movimientos = $stmtHistorial->fetchAll();

            // 3. Respuesta estructurada
            Flight::json(ResponseFormatter::success([
                'bien' => $bien,
                'total_movimientos' => count($movimientos),
                'historial' => $movimientos
            ]));

        } catch (\Exception $e) {
            Logger::error("Error al obtener trazabilidad", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
    }
    }

    /**
     * GET /api/v1/reportes/estadisticas/valor-por-aula
     * 
     * Obtiene el acumulado del valor monetario de los bienes agrupados por cada aula.
     * Filtra por cve_adscripcion del usuario.
     */
    public static function getValorMonetarioPorAula(): void
    {
        try {
            $pdo = self::getDb();

            // Obtener cve_adscripcion del usuario logueado
            $user = Flight::get('user');
            $cve_persona = $user->cve_persona ?? null;
            $cve_adscripcion = null;
            $filtroAdscripcion = "";
            $params = [];

            if ($cve_persona) {
                $stmtAds = $pdo->prepare("
                    SELECT ap.cve_adscripcion
                    FROM adscripcion_persona ap
                    WHERE ap.cve_persona = :cve_persona AND ap.activo = TRUE
                ");
                $stmtAds->execute([':cve_persona' => $cve_persona]);
                $cve_adscripcion = $stmtAds->fetchColumn();

                if ($cve_adscripcion) {
                    $filtroAdscripcion = "AND a.cve_adscripcion = :cve_adscripcion";
                    $params[':cve_adscripcion'] = $cve_adscripcion;
                }
            }

            $stmt = $pdo->prepare("
                SELECT 
                    a.nombre AS nombre_aula, 
                    COUNT(b.cve_bien) AS total_bienes,
                    CAST(SUM(COALESCE(b.costo_unitario, 0)) AS FLOAT) AS valor_total
                FROM bienes b
                JOIN aula a ON b.cve_aula = a.cve_aula
                WHERE b.activo = true
                $filtroAdscripcion
                GROUP BY a.nombre
                ORDER BY valor_total DESC
            ");

            $stmt->execute($params);
            $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Logger::info("Reporte valor monetario por aula consultado", ['total' => count($resultado), 'cve_adscripcion' => $cve_adscripcion]);

            Flight::json(ResponseFormatter::success($resultado));

        } catch (\Exception $e) {
            Logger::error("Error al obtener valor monetario por aula", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/reportes/prestamos/no-devueltos
     *
     * Retorna los préstamos que no han sido devueltos para el módulo de reportes.
     * Filtra por cve_adscripcion del usuario.
     */
    public static function listarPrestamosNoDevueltos(): void
    {
        try {
            $pdo = self::getDb();

            // Obtener cve_adscripcion del usuario logueado
            $user = Flight::get('user');
            $cve_persona = $user->cve_persona ?? null;
            $cve_adscripcion = null;
            $filtroAdscripcion = "";
            $params = [];

            if ($cve_persona) {
                $stmtAds = $pdo->prepare("
                    SELECT ap.cve_adscripcion
                    FROM adscripcion_persona ap
                    WHERE ap.cve_persona = :cve_persona AND ap.activo = TRUE
                ");
                $stmtAds->execute([':cve_persona' => $cve_persona]);
                $cve_adscripcion = $stmtAds->fetchColumn();

                if ($cve_adscripcion) {
                    $filtroAdscripcion = "AND a.cve_adscripcion = :cve_adscripcion";
                    $params[':cve_adscripcion'] = $cve_adscripcion;
                }
            }

            $stmt = $pdo->prepare("
                SELECT 
                    p.cve_prestamo,
                    p.fecha_solicitud,
                    p.fecha_devolucion_pactada,
                    p.estado_prestamo,
                    b.cve_bien,
                    b.nombre as nombre_bien,
                    b.no_serie,
                    pe.nombre as persona_nombre,
                    pe.apellido_paterno,
                    pe.apellido_materno,
                    a.nombre as nombre_aula,
                    e.nombre as nombre_edificio
                FROM prestamos p
                JOIN bienes b ON p.cve_bien = b.cve_bien
                JOIN persona pe ON p.cve_persona_solicita = pe.cve_persona
                LEFT JOIN aula a ON b.cve_aula = a.cve_aula
                LEFT JOIN edificio e ON a.cve_edificio = e.cve_edificio
                WHERE p.fecha_devolucion_real IS NULL
                AND p.estado_prestamo = 'aprobado'
                $filtroAdscripcion
                ORDER BY p.fecha_devolucion_pactada ASC
            ");
            $stmt->execute($params);
            
            $prestamos_pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Logger::info("Reporte de préstamos no devueltos consultado", ['total' => count($prestamos_pendientes), 'cve_adscripcion' => $cve_adscripcion]);

            Flight::json(ResponseFormatter::success($prestamos_pendientes));

        } catch (\Exception $e) {
            Logger::error("Error al obtener reporte de préstamos no devueltos", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/reportes/estadisticas/estado-fisico
     * 
     * Obtiene el conteo de bienes agrupados por estado físico.
     * Filtra por cve_adscripcion del usuario.
     */
    public static function getConteoPorEstadoFisico(): void
    {
        try {
            $pdo = self::getDb();

            // Obtener cve_adscripcion del usuario logueado
            $user = Flight::get('user');
            $cve_persona = $user->cve_persona ?? null;
            $cve_adscripcion = null;
            $filtroAdscripcion = "";
            $params = [];

            if ($cve_persona) {
                $stmtAds = $pdo->prepare("
                    SELECT ap.cve_adscripcion
                    FROM adscripcion_persona ap
                    WHERE ap.cve_persona = :cve_persona AND ap.activo = TRUE
                ");
                $stmtAds->execute([':cve_persona' => $cve_persona]);
                $cve_adscripcion = $stmtAds->fetchColumn();

                if ($cve_adscripcion) {
                    $filtroAdscripcion = "AND (a.cve_adscripcion = :cve_adscripcion OR a.cve_adscripcion IS NULL)";
                    $params[':cve_adscripcion'] = $cve_adscripcion;
                }
            }

            $stmt = $pdo->prepare("
                SELECT 
                    b.estado_fisico, 
                    COUNT(*) as total
                FROM bienes b
                LEFT JOIN aula a ON b.cve_aula = a.cve_aula
                WHERE b.activo = true
                $filtroAdscripcion
                GROUP BY b.estado_fisico
                ORDER BY total DESC
            ");

            $stmt->execute($params);
            $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Logger::info("Reporte estado físico consultado", ['total' => count($resultado), 'cve_adscripcion' => $cve_adscripcion]);

            Flight::json(ResponseFormatter::success($resultado));

        } catch (\Exception $e) {
            Logger::error("Error al obtener conteo por estado físico", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/reportes/estadisticas/estado-prestamo
     * 
     * Obtiene el conteo de bienes agrupados por estado de préstamo.
     * Filtra por cve_adscripcion del usuario.
     */
    public static function getConteoPorEstadoPrestamo(): void
    {
        try {
            $pdo = self::getDb();

            // Obtener cve_adscripcion del usuario logueado
            $user = Flight::get('user');
            $cve_persona = $user->cve_persona ?? null;
            $cve_adscripcion = null;
            $filtroAdscripcion = "";
            $params = [];

            if ($cve_persona) {
                $stmtAds = $pdo->prepare("
                    SELECT ap.cve_adscripcion
                    FROM adscripcion_persona ap
                    WHERE ap.cve_persona = :cve_persona AND ap.activo = TRUE
                ");
                $stmtAds->execute([':cve_persona' => $cve_persona]);
                $cve_adscripcion = $stmtAds->fetchColumn();

                if ($cve_adscripcion) {
                    $filtroAdscripcion = "AND (a.cve_adscripcion = :cve_adscripcion OR a.cve_adscripcion IS NULL)";
                    $params[':cve_adscripcion'] = $cve_adscripcion;
                }
            }

            $stmt = $pdo->prepare("
                SELECT 
                    b.estado_prestamo, 
                    COUNT(*) as total
                FROM bienes b
                LEFT JOIN aula a ON b.cve_aula = a.cve_aula
                WHERE b.activo = true
                $filtroAdscripcion
                GROUP BY b.estado_prestamo
                ORDER BY total DESC
            ");

            $stmt->execute($params);
            $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Logger::info("Reporte estado préstamo consultado", ['total' => count($resultado), 'cve_adscripcion' => $cve_adscripcion]);

            Flight::json(ResponseFormatter::success($resultado));

        } catch (\Exception $e) {
            Logger::error("Error al obtener conteo por estado de préstamo", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }
}

