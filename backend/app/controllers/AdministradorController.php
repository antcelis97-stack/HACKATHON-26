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
 * AdministradorController - Módulo de Administración de Inventario
 *
 * Endpoints disponibles:
 * - GET  /api/v1/administrador/inventario  -> Retorna todos los bienes (protegido)
 * - POST /api/v1/administrador/inventario         -> Registrar un bien (protegido)
 * - POST /api/v1/administrador/aulas              -> Registrar un aula (protegido)
 * - GET  /api/v1/administrador/prestamos          -> Ver préstamos realizados (protegido)
 * - GET  /api/v1/administrador/prestamos/no-devueltos -> Ver préstamos vencidos (protegido)
 * - GET  /api/v1/administrador/inventario/mantenimiento -> Ver bienes en reparación (protegido)
 * - GET  /api/v1/administrador/inventario/bajas           -> Ver bienes dados de baja (protegido)
 * - GET  /api/v1/administrador/inventario/por-aula        -> Ver bienes por aula (protegido)
 * - POST /api/v1/administrador/prestamos/solicitar -> Solicitar préstamo (protegido)
 * - GET  /api/v1/administrador/estadisticas/estado-fisico -> Contar bienes por estado físico (protegido)
 * - POST /api/v1/administrador/bitacora            -> Registrar operación en bitácora (protegido)
 * - GET  /api/v1/administrador/bitacora            -> Listar movimientos de bitácora (protegido)
 * - POST /api/v1/administrador/auditorias          -> Registrar nueva auditoría (protegido)
 * - GET  /api/v1/administrador/auditorias          -> Ver auditorías realizadas (protegido)
 */
class AdministradorController
{
    // =========================================================================
    // CONEXIÓN A BASE DE DATOS
    // =========================================================================

    /**
     * Retorna una instancia PDO reutilizable.
     * Centraliza el require y la llamada a getPgConnection()
     * para no repetir código en cada método.
     */
    private static function getDb(): \PDO
    {
        require_once __DIR__ . '/../../config/database.php';
        return getPgInventarioConnection();
    }


    /**
     * GET /api/v1/administrador/inventario/detalle/@id
     *
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
 
            Logger::info("Detalle de bien consultado", ['cve_bien' => $id]);
 
            Flight::json(ResponseFormatter::success($bien));
 
        } catch (\Exception $e) {
            Logger::error("Error al obtener detalle del bien", ['cve_bien' => $id, 'error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/administrador/inventario
     *
     * Retorna todos los registros de la tabla bienes.
     */
    public static function inventarioGeneral(): void
    {
        try {
            $pdo = self::getDb();

            //Obtener cve_persona del token JWT
            $cve_persona = self::getPersonaFromToken();

            //Si es Admin, filtrar por suAdscripción
            $filtroAdscripcion = '';
            $params = [];

            if ($cve_persona) {
                //Buscar laAdscripción del usuario
                $stmtAds = $pdo->prepare("
                    SELECT ap.cve_adscripcion
                    FROM adscripcion_persona ap
                    WHERE ap.cve_persona = :cve_persona AND ap.activo = TRUE
                ");
                $stmtAds->execute([':cve_persona' => $cve_persona]);
                $cve_adscripcion = $stmtAds->fetchColumn();

                if ($cve_adscripcion) {
                    //Filtrar bienes por las aulas de esaAdscripción
                    $filtroAdscripcion = "AND a.cve_adscripcion = :cve_adscripcion";
                    $params[':cve_adscripcion'] = $cve_adscripcion;
                }
            }

            $sql = "
                SELECT
                    b.cve_bien,
                    b.nombre,
                    b.nfc,
                    b.codigo_qr,
                    b.no_serie,
                    b.no_factura,
                    b.descripcion,
                    b.cve_modelo,
                    b.cve_articulo,
                    b.costo_unitario,
                    b.cve_aula,
                    a.nombre as nombre_aula,
                    b.estado_fisico,
                    b.estado_prestamo,
                    b.activo,
                    b.fecha_registro,
                    e.nombre as nombre_edificio
                FROM bienes b
                LEFT JOIN aula a ON b.cve_aula = a.cve_aula
                LEFT JOIN edificio e ON a.cve_edificio = e.cve_edificio
                WHERE b.activo = true
                $filtroAdscripcion
                ORDER BY b.nombre ASC
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $bienes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Logger::info("Inventario general consultado", ['total' => count($bienes), 'cve_persona' => $cve_persona]);

            Flight::json(ResponseFormatter::success($bienes));

        } catch (\Exception $e) {
            Logger::error("Error al obtener inventario general", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
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
            //Token inválido o no presente
        }
        return null;
    }

    public static function registrarBien(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            // -----------------------------------------------------------------
            // VALIDACIÓN
            // -----------------------------------------------------------------
            $errors = [];

            if (empty($data['nombre'])) {
                $errors[] = ['field' => 'nombre', 'message' => 'El nombre del bien es obligatorio'];
            }
            
            $codigo_barras_final = $data['codigo_barras'] ?? null;

            if (empty($codigo_barras_final)) {
                if (empty($data['cve_tipo'])) {
                    $errors[] = ['field' => 'cve_tipo', 'message' => 'El tipo de bien (cve_tipo) es obligatorio para el código de barras'];
                }
                if (empty($data['cve_familia'])) {
                    $errors[] = ['field' => 'cve_familia', 'message' => 'La familia (cve_familia) es obligatoria para el código de barras'];
                }
                if (empty($data['cve_articulo'])) {
                    $errors[] = ['field' => 'cve_articulo', 'message' => 'El artículo (cve_articulo) es obligatorio'];
                }
                if (empty($data['identificador'])) {
                    $errors[] = ['field' => 'identificador', 'message' => 'El identificador es obligatorio para generar el código de barras'];
                }
            }

            if (!empty($errors)) {
                Flight::json(ResponseFormatter::validationError($errors), 400);
                return;
            }

            // -----------------------------------------------------------------
            // GENERACIÓN DEL CÓDIGO DE BARRAS (si no se envió uno manual)
            // -----------------------------------------------------------------
            if (empty($codigo_barras_final)) {
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
            $pdo = self::getDb();
            $pdo->beginTransaction();

            $cveModelo = !empty($data['cve_modelo']) ? (int)$data['cve_modelo'] : null;

            // 1. Procesar Marca dinámicamente si se envía nombre_marca
            $cveMarca = null;
            if (!empty($data['nombre_marca'])) {
                $stmtMarca = $pdo->prepare("SELECT cve_marca FROM marcas WHERE nombre_marca = :nombre");
                $stmtMarca->execute([':nombre' => $data['nombre_marca']]);
                $marcaExistente = $stmtMarca->fetch();

                if ($marcaExistente) {
                    $cveMarca = $marcaExistente['cve_marca'];
                } else {
                    $stmtInsMarca = $pdo->prepare("INSERT INTO marcas (nombre_marca) VALUES (:nombre) RETURNING cve_marca");
                    $stmtInsMarca->execute([':nombre' => $data['nombre_marca']]);
                    $cveMarca = $stmtInsMarca->fetch()['cve_marca'];
                }
            }

            // 2. Procesar Modelo dinámicamente si se envía nombre_modelo
            if (!empty($data['nombre_modelo'])) {
                // Necesitamos una marca para el modelo. Si no se envió nombre_marca, pero sí cve_modelo,
                // tal vez no deberíamos hacer nada dinámico. Pero si se envía nombre_modelo,
                // intentamos asociarlo a la cveMarca detectada arriba.
                
                $stmtModelo = $pdo->prepare("
                    SELECT cve_modelo FROM modelos 
                    WHERE nombre_modelo = :nombre 
                    AND (cve_marca = :cve_marca OR (:cve_marca IS NULL AND cve_marca IS NULL))
                ");
                $stmtModelo->execute([
                    ':nombre' => $data['nombre_modelo'],
                    ':cve_marca' => $cveMarca
                ]);
                $modeloExistente = $stmtModelo->fetch();

                if ($modeloExistente) {
                    $cveModelo = $modeloExistente['cve_modelo'];
                } else {
                    $stmtInsModelo = $pdo->prepare("
                        INSERT INTO modelos (nombre_modelo, cve_marca, descripcion) 
                        VALUES (:nombre, :cve_marca, :desc) 
                        RETURNING cve_modelo
                    ");
                    $stmtInsModelo->execute([
                        ':nombre' => $data['nombre_modelo'],
                        ':cve_marca' => $cveMarca,
                        ':desc' => $data['descripcion_modelo'] ?? null
                    ]);
                    $cveModelo = $stmtInsModelo->fetch()['cve_modelo'];
                }
            }

            // 3. Insertar el Bien
            $stmt = $pdo->prepare("
                INSERT INTO bienes (
                    nombre,
                    codigo_barras,
                    codigo_qr,
                    no_serie,
                    no_factura,
                    descripcion,
                    cve_modelo,
                    cve_articulo,
                    costo_unitario,
                    cve_aula,
                    estado_fisico,
                    foto_url,
                    foto_drive_id
                ) VALUES (
                    :nombre,
                    :codigo_barras,
                    :codigo_qr,
                    :no_serie,
                    :no_factura,
                    :descripcion,
                    :cve_modelo,
                    :cve_articulo,
                    :costo_unitario,
                    :cve_aula,
                    :estado_fisico,
                    :foto_url,
                    :foto_drive_id
                ) RETURNING cve_bien
            ");

            $stmt->execute([
                ':nombre'         => $data['nombre'],
                ':codigo_barras'  => $codigo_barras_final,
                ':codigo_qr'      => $data['codigo_qr']      ?? null,
                ':no_serie'       => $data['no_serie']       ?? null,
                ':no_factura'     => $data['no_factura']     ?? null,
                ':descripcion'    => $data['descripcion']    ?? null,
                ':cve_modelo'     => $cveModelo,
                ':cve_articulo'   => !empty($data['cve_articulo']) ? (int)$data['cve_articulo'] : null,
                ':costo_unitario' => isset($data['costo_unitario']) ? (float)$data['costo_unitario'] : null,
                ':cve_aula'       => !empty($data['cve_aula'])     ? (int)$data['cve_aula']     : null,
                ':estado_fisico'  => $data['estado_fisico']  ?? 'bueno',
                ':foto_url'       => $data['foto_url']       ?? null,
                ':foto_drive_id'  => $data['foto_drive_id']  ?? null
            ]);

            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            $nuevoCveBien = (int)$resultado['cve_bien'];

            // 4. Generación automática de código QR si no se proporcionó
            $codigoQrFinal = $data['codigo_qr'] ?? null;
            if (empty($codigoQrFinal)) {
                // Formato: URL del frontend para ver el bien
                $codigoQrFinal = "http://localhost:4200/bien/" . $nuevoCveBien;
                $stmtUpdateQr = $pdo->prepare("UPDATE bienes SET codigo_qr = :qr WHERE cve_bien = :id");
                $stmtUpdateQr->execute([':qr' => $codigoQrFinal, ':id' => $nuevoCveBien]);
            }

            $pdo->commit();

            // -----------------------------------------------------------------
            // AUDITORÍA Y RESPUESTA
            // -----------------------------------------------------------------
            AuditLog::create('bienes', $nuevoCveBien, array_merge($data, ['codigo_qr' => $codigoQrFinal]), Flight::get('user_id'));
            Logger::info("Bien registrado", ['cve_bien' => $nuevoCveBien, 'nombre' => $data['nombre']]);

            Flight::json(ResponseFormatter::created([
                'cve_bien'      => $nuevoCveBien,
                'codigo_qr'     => $codigoQrFinal,
                'codigo_barras' => $codigo_barras_final,
                'cve_modelo'    => $cveModelo,
                'cve_marca'     => $cveMarca,
                'cve_tipo'      => $data['cve_tipo'] ?? null,
                'cve_familia'   => $data['cve_familia'] ?? null,
                'cve_articulo'  => $data['cve_articulo'] ?? null,
                'identificador' => $data['identificador'] ?? null
            ]), 201);

        } catch (\Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Logger::error("Error al registrar bien", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    public static function registrarAula(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            // -----------------------------------------------------------------
            // Validación
            // -----------------------------------------------------------------
            $errors = [];

            if (empty($data['nombre'])) {
                $errors[] = ['field' => 'nombre', 'message' => 'El nombre del aula es requerido'];
            } elseif (strlen($data['nombre']) > 30) {
                $errors[] = ['field' => 'nombre', 'message' => 'El nombre no puede superar 30 caracteres'];
            }

            if (empty($data['cve_edificio'])) {
                $errors[] = ['field' => 'cve_edificio', 'message' => 'El edificio es requerido (cve_edificio)'];
            }
            if (empty($data['cve_tipo_aula'])) {
                $errors[] = ['field' => 'cve_tipo_aula', 'message' => 'El tipo de aula es requerido (cve_tipo_aula)'];
            }

            if (!empty($errors)) {
                Flight::json(ResponseFormatter::validationError($errors), 400);
                return;
            }

            $pdo = self::getDb();
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO aula (cve_edificio, cve_tipo_aula, cve_profesor, nombre, capacidad)
                VALUES (:cve_edificio, :cve_tipo_aula, :cve_profesor, :nombre, :capacidad)
                RETURNING cve_aula
            ");

            // Asegurar tipos correctos y manejar campos opcionales
            $cveProfesor = !empty($data['cve_profesor']) ? (int)$data['cve_profesor'] : null;
            $stmt->execute([
                ':cve_edificio'  => (int)$data['cve_edificio'],
                ':cve_tipo_aula' => (int)$data['cve_tipo_aula'],
                ':cve_profesor'  => $cveProfesor,
                ':nombre'        => $data['nombre'],
                ':capacidad'     => (!empty($data['capacidad']) || $data['capacidad'] === 0) ? (int)$data['capacidad'] : null
            ]);

            $nuevoCveAula = $stmt->fetch()['cve_aula'];

            $pdo->commit();

            // -----------------------------------------------------------------
            // Auditoría y logging
            // -----------------------------------------------------------------
            AuditLog::create('aula', (int)$nuevoCveAula, $data, Flight::get('user_id'));
            Logger::info("Aula registrada", ['cve_aula' => $nuevoCveAula, 'nombre' => $data['nombre']]);

            Flight::json(ResponseFormatter::created([
                'cve_aula'  => $nuevoCveAula
            ]), 201);

        } catch (\Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            Logger::error("Error al registrar aula", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/administrador/contexto
     * Perfil del administrador con contadores de su adscripción.
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

            // Obtener adscripción del admin
            $stmtAds = $pdo->prepare("
                SELECT ap.cve_adscripcion
                FROM adscripcion_persona ap
                WHERE ap.cve_persona = :cve_persona AND ap.activo = TRUE
            ");
            $stmtAds->execute([':cve_persona' => $cvePersona]);
            $cveAdscripcion = $stmtAds->fetchColumn();

            // Datos del admin
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
                Flight::json(ResponseFormatter::notFound('Perfil de administrador'), 404);
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
            Logger::error('Administrador contexto', ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    // =========================================================================
    // REPORTES Y ESTADÍSTICAS
    // =========================================================================

    public static function contarPorEstadoFisico(): void
    {
        try {
            $pdo = self::getDb();

            $stmt = $pdo->prepare("
                SELECT estado_fisico, COUNT(*) as total 
                FROM bienes 
                WHERE activo = true 
                GROUP BY estado_fisico
                ORDER BY total DESC
            ");
            $stmt->execute();
            
            $resultados = $stmt->fetchAll();

            Logger::info("Conteo de bienes por estado físico consultado");

            Flight::json(ResponseFormatter::success($resultados));

        } catch (\Exception $e) {
            Logger::error("Error al contar bienes por estado físico", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    public static function listarBienesPorAula(): void
    {
        try {
            $pdo = self::getDb();

            // Obtener cve_aula de query params (?cve_aula=X)
            $cve_aula = Flight::request()->query->cve_aula;

            $sql = "
                SELECT 
                    a.nombre as nombre_aula,
                    b.cve_bien,
                    b.nombre as nombre_bien,
                    b.no_serie,
                    b.estado_fisico,
                    m.nombre_modelo
                FROM bienes b
                JOIN aula a ON b.cve_aula = a.cve_aula
                LEFT JOIN modelos m ON b.cve_modelo = m.cve_modelo
                WHERE b.activo = TRUE
            ";

            $params = [];
            if ($cve_aula) {
                $sql .= " AND a.cve_aula = :cve_aula";
                $params[':cve_aula'] = (int)$cve_aula;
            }

            $sql .= " ORDER BY a.nombre ASC, b.nombre ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $bienes_aula = $stmt->fetchAll();

            Logger::info("Listado de bienes por aula consultado", ['total' => count($bienes_aula)]);

            Flight::json(ResponseFormatter::success($bienes_aula));

        } catch (\Exception $e) {
            Logger::error("Error al listar bienes por aula", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }


    public static function registrarMovimientoBitacora(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            // -----------------------------------------------------------------
            // Validación
            // -----------------------------------------------------------------
            $errors = [];
            if (empty($data['cve_bien'])) {
                $errors[] = ['field' => 'cve_bien', 'message' => 'El ID del bien (cve_bien) es requerido'];
            }
            if (empty($data['cve_motivo'])) {
                $errors[] = ['field' => 'cve_motivo', 'message' => 'El motivo del movimiento (cve_motivo) es requerido'];
            }
            if (empty($data['cve_persona_accion'])) {
                $errors[] = ['field' => 'cve_persona_accion', 'message' => 'El responsable de la acción (cve_persona_accion) es requerido'];
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

            $stmt = $pdo->prepare("
                INSERT INTO bitacora_movimientos (
                    cve_bien, 
                    cve_motivo, 
                    cve_persona_accion, 
                    fecha_movimiento, 
                    observaciones
                ) VALUES (
                    :cve_bien, 
                    :cve_motivo, 
                    :cve_persona_accion, 
                    CURRENT_TIMESTAMP, 
                    :observaciones
                ) RETURNING cve_movimiento
            ");

            $stmt->execute([
                ':cve_bien' => $data['cve_bien'],
                ':cve_motivo' => $data['cve_motivo'],
                ':cve_persona_accion' => $data['cve_persona_accion'],
                ':observaciones' => $data['observaciones'] ?? null
            ]);

            $nuevoMov = $stmt->fetch()['cve_movimiento'];
            $pdo->commit();

            // -----------------------------------------------------------------
            // Auditoría y logging
            // -----------------------------------------------------------------
            AuditLog::create('bitacora_movimientos', $nuevoMov, $data, Flight::get('user_id'));
            Logger::info("Movimiento en bitácora registrado", ['cve_movimiento' => $nuevoMov, 'cve_bien' => $data['cve_bien']]);

            Flight::json(ResponseFormatter::created(['cve_movimiento' => $nuevoMov]), 201);

        } catch (\Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            Logger::error("Error al registrar movimiento en bitácora", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/administrador/bitacora
     *
     * Retorna todos los movimientos registrados en la bitácora junto con sus tablas relacionadas.
     */
    public static function listarBitacora(): void
    {
        try {
            $pdo = self::getDb();

            //Obtener cve_persona del token JWT
            $cve_persona = self::getPersonaFromToken();

            //Si es Admin, filtrar por suAdscripción
            $filtroAdscripcion = '';
            $params = [];

            if ($cve_persona) {
                //Buscar laAdscripción del usuario
                $stmtAds = $pdo->prepare("
                    SELECT ap.cve_adscripcion
                    FROM adscripcion_persona ap
                    WHERE ap.cve_persona = :cve_persona AND ap.activo = TRUE
                ");
                $stmtAds->execute([':cve_persona' => $cve_persona]);
                $cve_adscripcion = $stmtAds->fetchColumn();

                if ($cve_adscripcion) {
                    //Filtrar bienes por las aulas de esaAdscripción
                    $filtroAdscripcion = "AND a.cve_adscripcion = :cve_adscripcion";
                    $params[':cve_adscripcion'] = $cve_adscripcion;
                }
            }

            $sql = "
                SELECT 
                    bm.cve_movimiento,
                    bm.fecha_movimiento,
                    bm.observaciones,
                    b.nombre as nombre_bien,
                    b.no_serie,
                    m.nombre_motivo,
                    p.nombre as persona_nombre,
                    p.apellido_paterno
                FROM bitacora_movimientos bm
                LEFT JOIN bienes b ON bm.cve_bien = b.cve_bien
                LEFT JOIN aula a ON b.cve_aula = a.cve_aula
                LEFT JOIN motivo_de_movimiento m ON bm.cve_motivo = m.cve_motivo
                LEFT JOIN persona p ON bm.cve_persona_accion = p.cve_persona
                WHERE 1=1
                $filtroAdscripcion
                ORDER BY bm.fecha_movimiento DESC
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $movimientos = $stmt->fetchAll();

            Logger::info("Bitácora de movimientos consultada", ['total' => count($movimientos), 'cve_persona' => $cve_persona]);

            Flight::json(ResponseFormatter::success($movimientos));

        } catch (\Exception $e) {
            Logger::error("Error al listar bitácora", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }
}
