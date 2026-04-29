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

class PrestamosController{

    private static function getDb(): PDO
    {
        require_once __DIR__ . '/../../config/database.php';
        return getPgInventarioConnection();
    }

    public static function solicitarPrestamo(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            // Extraer cve_persona_solicita del JWT (seguridad: no confiar en el body)
            $user = Flight::get('user');
            $cvePersona = $user->cve_persona ?? null;
            if (!$cvePersona) {
                Flight::json(ResponseFormatter::unauthorized('No se pudo identificar al usuario'), 401);
                return;
            }

            // -----------------------------------------------------------------
            // Validación
            // -----------------------------------------------------------------
            $errors = [];

            if (empty($data['cve_bien'])) {
                $errors[] = ['field' => 'cve_bien', 'message' => 'El bien a prestar es requerido'];
            }
            if (empty($data['fecha_devolucion_pactada'])) {
                $errors[] = ['field' => 'fecha_devolucion_pactada', 'message' => 'La fecha de devolución pactada es requerida'];
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

            // Verificar si el bien existe y está disponible
            $stmtCheck = $pdo->prepare("SELECT estado_prestamo, activo FROM bienes WHERE cve_bien = :cve_bien FOR UPDATE");
            $stmtCheck->execute([':cve_bien' => $data['cve_bien']]);
            $bien = $stmtCheck->fetch();

            if (!$bien) {
                $pdo->rollBack();
                Flight::json(ResponseFormatter::error("El bien especificado no existe"), 404);
                return;
            }
            if (!$bien['activo']) {
                $pdo->rollBack();
                Flight::json(ResponseFormatter::error("El bien especificado está inactivo"), 400);
                return;
            }
            if ($bien['estado_prestamo'] !== 'disponible') {
                $pdo->rollBack();
                Flight::json(ResponseFormatter::error("El bien no está disponible para préstamo actual (estado: {$bien['estado_prestamo']})"), 400);
                return;
            }

            // Insertar el préstamo
            $stmt = $pdo->prepare("
                INSERT INTO prestamos (
                    cve_bien, 
                    cve_persona_solicita, 
                    fecha_solicitud, 
                    fecha_devolucion_pactada, 
                    estado_prestamo,
                    observaciones
                ) VALUES (
                    :cve_bien, 
                    :cve_persona_solicita, 
                    CURRENT_TIMESTAMP, 
                    :fecha_devolucion_pactada, 
                    'pendiente',
                    :observaciones
                ) RETURNING cve_prestamo
            ");

            $stmt->execute([
                ':cve_bien'                 => $data['cve_bien'],
                ':cve_persona_solicita'     => $cvePersona,
                ':fecha_devolucion_pactada' => $data['fecha_devolucion_pactada'],
                ':observaciones'            => $data['observaciones'] ?? null
            ]);

            $nuevoCvePrestamo = $stmt->fetch()['cve_prestamo'];
            $pdo->commit();

            // -----------------------------------------------------------------
            // Auditoría y logging
            // -----------------------------------------------------------------
            AuditLog::create('prestamos', $nuevoCvePrestamo, $data, Flight::get('user_id'));
            Logger::info("Préstamo solicitado", ['cve_prestamo' => $nuevoCvePrestamo, 'cve_bien' => $data['cve_bien']]);

            Flight::json(ResponseFormatter::created(['cve_prestamo' => $nuevoCvePrestamo, 'estado' => 'pendiente']), 201);

        } catch (\Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            Logger::error("Error al solicitar préstamo", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * PUT /api/v1/administrador/prestamos/aceptar/{id}
     * Acepta (aprueba) un préstamo pendiente
     */
    public static function aceptarPrestamo(int $id): void
    {
        try {
            $pdo = self::getDb();

            $stmt = $pdo->prepare("
                UPDATE prestamos
                SET estado_prestamo = 'aprobado'
                WHERE cve_prestamo = :id
                AND estado_prestamo = 'pendiente'
                RETURNING cve_prestamo, cve_bien
            ");
            $stmt->execute([':id' => $id]);
            $prestamo = $stmt->fetch();

            if (!$prestamo) {
                Flight::json(ResponseFormatter::error("No se encontró un préstamo pendiente con ese ID."), 404);
                return;
            }

            // Actualizar estado del bien
            $pdo->prepare("UPDATE bienes SET estado_prestamo = 'prestado' WHERE cve_bien = :id")
                ->execute([':id' => $prestamo['cve_bien']]);

            // Auditoría
            AuditLog::create('prestamos', $id, ['estado_prestamo' => 'aprobado'], Flight::get('user_id'));
            Logger::info("Préstamo aprobado (aceptado)", ['cve_prestamo' => $id]);

            Flight::json(ResponseFormatter::success(null, "Préstamo aceptado correctamente"));

        } catch (\Exception $e) {
            Logger::error("Error al aceptar préstamo", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    public static function solicitarPrestamoAdministrador(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            // -----------------------------------------------------------------
            // Validación
            // -----------------------------------------------------------------
            $errors = [];

            if (empty($data['cve_bien'])) {
                $errors[] = ['field' => 'cve_bien', 'message' => 'El bien a prestar es requerido'];
            }
            if (empty($data['cve_persona_solicita'])) {
                $errors[] = ['field' => 'cve_persona_solicita', 'message' => 'La persona solicitante es requerida'];
            }
            if (empty($data['fecha_devolucion_pactada'])) {
                $errors[] = ['field' => 'fecha_devolucion_pactada', 'message' => 'La fecha de devolución pactada es requerida'];
            }

            if (!empty($errors)) {
                Flight::json(ResponseFormatter::validationError($errors), 400);
                return;
            }

            // Determinar estado según aprobación directa
            $estadoPrestamo = ($data['aprobacion_directa'] ?? false) ? 'aprobado' : 'pendiente';

            // -----------------------------------------------------------------
            // Conexión e inserción con transacción
            // -----------------------------------------------------------------
            $pdo = self::getDb();
            $pdo->beginTransaction();

            // Verificar si el bien existe
            $stmtCheck = $pdo->prepare("SELECT estado_prestamo, activo FROM bienes WHERE cve_bien = :cve_bien FOR UPDATE");
            $stmtCheck->execute([':cve_bien' => $data['cve_bien']]);
            $bien = $stmtCheck->fetch();

            if (!$bien) {
                $pdo->rollBack();
                Flight::json(ResponseFormatter::error("El bien especificado no existe"), 404);
                return;
            }
            if (!$bien['activo']) {
                $pdo->rollBack();
                Flight::json(ResponseFormatter::error("El bien especificado está inactivo"), 400);
                return;
            }

            // Si el bien no está disponible, verificar si tiene préstamos huérfanos
            if ($bien['estado_prestamo'] !== 'disponible') {
                $aprobacionDirecta = $data['aprobacion_directa'] ?? false;

                // Buscar préstamos activos de este bien
                $stmtActive = $pdo->prepare("
                    SELECT cve_prestamo, estado_prestamo 
                    FROM prestamos 
                    WHERE cve_bien = :cve_bien 
                    AND fecha_devolucion_real IS NULL 
                    AND estado_prestamo IN ('pendiente', 'aprobado', 'por_devolver')
                    ORDER BY fecha_solicitud ASC
                ");
                $stmtActive->execute([':cve_bien' => $data['cve_bien']]);
                $prestamosActivos = $stmtActive->fetchAll();

                if (count($prestamosActivos) > 0) {
                    // Si es aprobación directa, auto-devolver préstamos huérfanos
                    if ($aprobacionDirecta) {
                        foreach ($prestamosActivos as $prestamo) {
                            $stmtDev = $pdo->prepare("
                                UPDATE prestamos 
                                SET estado_prestamo = 'devuelto', 
                                    fecha_devolucion_real = CURRENT_TIMESTAMP 
                                WHERE cve_prestamo = :id
                            ");
                            $stmtDev->execute([':id' => $prestamo['cve_prestamo']]);
                            Logger::info("Préstamo huérfano auto-devuelto", [
                                'cve_prestamo' => $prestamo['cve_prestamo'],
                                'estado_anterior' => $prestamo['estado_prestamo']
                            ]);
                        }
                    } else {
                        $pdo->rollBack();
                        Flight::json(ResponseFormatter::error(
                            "El bien no está disponible (estado: {$bien['estado_prestamo']}). " .
                            "Tiene " . count($prestamosActivos) . " préstamo(s) activo(s)."
                        ), 400);
                        return;
                    }
                }

                // Resetear estado del bien
                $stmtReset = $pdo->prepare("UPDATE bienes SET estado_prestamo = 'disponible' WHERE cve_bien = :id");
                $stmtReset->execute([':id' => $data['cve_bien']]);
            }

            // Insertar el préstamo
            $stmt = $pdo->prepare("
                INSERT INTO prestamos (
                    cve_bien, 
                    cve_persona_solicita, 
                    fecha_solicitud, 
                    fecha_devolucion_pactada, 
                    estado_prestamo,
                    observaciones
                ) VALUES (
                    :cve_bien, 
                    :cve_persona_solicita, 
                    CURRENT_TIMESTAMP, 
                    :fecha_devolucion_pactada, 
                    :estado_prestamo,
                    :observaciones
                ) RETURNING cve_prestamo
            ");

            $stmt->execute([
                ':cve_bien'                 => $data['cve_bien'],
                ':cve_persona_solicita'     => $data['cve_persona_solicita'],
                ':fecha_devolucion_pactada' => $data['fecha_devolucion_pactada'],
                ':estado_prestamo'          => $estadoPrestamo,
                ':observaciones'            => $data['observaciones'] ?? null
            ]);

            $nuevoCvePrestamo = $stmt->fetch()['cve_prestamo'];
            $pdo->commit();

            // -----------------------------------------------------------------
            // Auditoría y logging
            // -----------------------------------------------------------------
            AuditLog::create('prestamos', $nuevoCvePrestamo, $data, Flight::get('user_id'));
            Logger::info("Préstamo solicitado por admin", [
                'cve_prestamo' => $nuevoCvePrestamo, 
                'cve_bien' => $data['cve_bien'],
                'estado' => $estadoPrestamo,
                'aprobacion_directa' => $data['aprobacion_directa'] ?? false
            ]);

            Flight::json(ResponseFormatter::created(['cve_prestamo' => $nuevoCvePrestamo, 'estado' => $estadoPrestamo]), 201);

        } catch (\Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            Logger::error("Error al solicitar préstamo por admin", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * PUT /api/v1/administrador/prestamos/{id}/rechazar
     * Rechaza un préstamo pendiente
     */
    public static function rechazarPrestamo(int $id): void
    {
        try {
            $pdo = self::getDb();

            $stmt = $pdo->prepare("
                UPDATE prestamos
                SET estado_prestamo = 'rechazado'
                WHERE cve_prestamo = :id
                AND estado_prestamo = 'pendiente'
                RETURNING cve_prestamo
            ");
            $stmt->execute([':id' => $id]);
            $prestamo = $stmt->fetch();

            if (!$prestamo) {
                Flight::json(ResponseFormatter::error("No se encontró un préstamo pendiente con ese ID o ya no está en estado pendiente."), 404);
                return;
            }

            // Actualizar estado del bien a disponible
            $pdo->prepare("UPDATE bienes SET estado_prestamo = 'disponible' WHERE cve_bien = (
                SELECT cve_bien FROM prestamos WHERE cve_prestamo = :id
            )")->execute([':id' => $id]);

            // Auditoría
            AuditLog::create('prestamos', $id, ['estado_prestamo' => 'rechazado'], Flight::get('user_id'));
            Logger::info("Préstamo rechazado", ['cve_prestamo' => $id]);

            Flight::json(ResponseFormatter::success(null, "Préstamo rechazado correctamente"));

        } catch (\Exception $e) {
            Logger::error("Error al rechazar préstamo", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    public static function devolverPrestamo(int $id): void
    {
        try {
            $pdo = self::getDb();
            
            // Obtener rol del usuario
            $user = Flight::get('user');
            $rol = $user->rol ?? '';
            $isAdmin = ($rol === 'Administrador' || $rol === 'Director');

            $nuevoEstado = $isAdmin ? 'devuelto' : 'por_devolver';
            $sqlFecha = $isAdmin ? ", fecha_devolucion_real = CURRENT_TIMESTAMP" : "";

            $stmt = $pdo->prepare("
                UPDATE prestamos
                SET estado_prestamo = :nuevo_estado
                $sqlFecha
                WHERE cve_prestamo = :id
                AND estado_prestamo = 'aprobado'
                RETURNING cve_prestamo
            ");
            
            $stmt->execute([
                ':id' => $id,
                ':nuevo_estado' => $nuevoEstado
            ]);
            $prestamo = $stmt->fetch();

            if (!$prestamo) {
                Flight::json(ResponseFormatter::error("No se encontró un préstamo aprobado con ese ID."), 404);
                return;
            }

            // Si es admin/director, el bien vuelve a disponible
            if ($isAdmin) {
                $pdo->prepare("UPDATE bienes SET estado_prestamo = 'disponible' WHERE cve_bien = (
                    SELECT cve_bien FROM prestamos WHERE cve_prestamo = :id
                )")->execute([':id' => $id]);
            }

            // Auditoría
            AuditLog::create('prestamos', $id, ['estado_prestamo' => $nuevoEstado], Flight::get('user_id'));
            Logger::info("Préstamo marcado como $nuevoEstado", ['cve_prestamo' => $id, 'admin' => $isAdmin]);

            $mensaje = $isAdmin ? "Préstamo devuelto correctamente" : "Solicitud de devolución registrada, pendiente de aceptación";
            Flight::json(ResponseFormatter::success(null, $mensaje));

        } catch (\Exception $e) {
            Logger::error("Error al devolver préstamo", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * PUT /api/v1/prestamos/aceptar-devolucion/{id}
     * Acepta la devolución de un bien
     */
    public static function aceptarDevolucion(int $id): void
    {
        try {
            $pdo = self::getDb();

            $stmt = $pdo->prepare("
                UPDATE prestamos
                SET estado_prestamo = 'devuelto',
                    fecha_devolucion_real = CURRENT_TIMESTAMP
                WHERE cve_prestamo = :id
                AND estado_prestamo = 'por_devolver'
                RETURNING cve_prestamo
            ");
            $stmt->execute([':id' => $id]);
            $prestamo = $stmt->fetch();

            if (!$prestamo) {
                Flight::json(ResponseFormatter::error("No se encontró una devolución pendiente con ese ID."), 404);
                return;
            }

            // Actualizar estado del bien a disponible
            $pdo->prepare("UPDATE bienes SET estado_prestamo = 'disponible' WHERE cve_bien = (
                SELECT cve_bien FROM prestamos WHERE cve_prestamo = :id
            )")->execute([':id' => $id]);

            AuditLog::create('prestamos', $id, ['estado_prestamo' => 'devuelto'], Flight::get('user_id'));
            Logger::info("Devolución aceptada", ['cve_prestamo' => $id]);

            Flight::json(ResponseFormatter::success(null, "Devolución aceptada correctamente"));

        } catch (\Exception $e) {
            Logger::error("Error al aceptar devolución", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * PUT /api/v1/prestamos/rechazar-devolucion/{id}
     * Rechaza la devolución de un bien
     */
    public static function rechazarDevolucion(int $id): void
    {
        try {
            $pdo = self::getDb();

            $stmt = $pdo->prepare("
                UPDATE prestamos
                SET estado_prestamo = 'aprobado'
                WHERE cve_prestamo = :id
                AND estado_prestamo = 'por_devolver'
                RETURNING cve_prestamo
            ");
            $stmt->execute([':id' => $id]);
            $prestamo = $stmt->fetch();

            if (!$prestamo) {
                Flight::json(ResponseFormatter::error("No se encontró una devolución pendiente con ese ID."), 404);
                return;
            }

            AuditLog::create('prestamos', $id, ['estado_prestamo' => 'aprobado'], Flight::get('user_id'));
            Logger::info("Devolución rechazada", ['cve_prestamo' => $id]);

            Flight::json(ResponseFormatter::success(null, "Devolución rechazada, el bien sigue en estado prestado"));

        } catch (\Exception $e) {
            Logger::error("Error al rechazar devolución", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }



    public static function listarPrestamos(): void
    {
        try {
            $pdo = self::getDb();

            $stmt = $pdo->prepare("
                SELECT 
                    p.cve_prestamo,
                    p.cve_bien,
                    p.fecha_solicitud,
                    p.fecha_devolucion_pactada,
                    p.fecha_devolucion_real,
                    p.estado_prestamo,
                    p.observaciones,
                    b.nombre as nombre_bien,
                    p.cve_persona_solicita,
                    pe.nombre as persona_nombre,
                    pe.apellido_paterno,
                    pe.apellido_materno,
                    a.nombre as nombre_aula,
                    e.nombre as nombre_edificio
                FROM prestamos p
                LEFT JOIN bienes b ON p.cve_bien = b.cve_bien
                LEFT JOIN persona pe ON p.cve_persona_solicita = pe.cve_persona
                LEFT JOIN aula a ON b.cve_aula = a.cve_aula
                LEFT JOIN edificio e ON a.cve_edificio = e.cve_edificio
                ORDER BY p.fecha_solicitud DESC
            ");
            $stmt->execute();
            
            $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Logger::info("Listado de préstamos consultado", ['total' => count($prestamos)]);

            Flight::json(ResponseFormatter::success($prestamos));

        } catch (\Exception $e) {
            Logger::error("Error al listar préstamos", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }
    public static function listarPrestamosNoDevueltos(): void
    {
        try {
            $pdo = self::getDb();

            $stmt = $pdo->prepare("
                SELECT 
                    p.cve_prestamo,
                    p.cve_bien,
                    p.fecha_solicitud,
                    p.fecha_devolucion_pactada,
                    p.fecha_devolucion_real,
                    p.estado_prestamo,
                    p.observaciones,
                    b.nombre as nombre_bien,
                    p.cve_persona_solicita,
                    pe.nombre as persona_nombre,
                    pe.apellido_paterno,
                    pe.apellido_materno,
                    a.nombre as nombre_aula,
                    e.nombre as nombre_edificio
                FROM prestamos p
                LEFT JOIN bienes b ON p.cve_bien = b.cve_bien
                LEFT JOIN persona pe ON p.cve_persona_solicita = pe.cve_persona
                LEFT JOIN aula a ON b.cve_aula = a.cve_aula
                LEFT JOIN edificio e ON a.cve_edificio = e.cve_edificio
                WHERE p.fecha_devolucion_real IS NULL
                AND p.estado_prestamo = 'aprobado'
                ORDER BY p.fecha_devolucion_pactada ASC
            ");
            $stmt->execute();
            
            $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Logger::info("Listado de préstamos no devueltos consultado", ['total' => count($prestamos)]);

            Flight::json(ResponseFormatter::success($prestamos));

        } catch (\Exception $e) {
            Logger::error("Error al listar préstamos no devueltos", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/prestamos/detalle/@id
     * Obtiene toda la información de un préstamo por su ID.
     */
    public static function getDetallePrestamo(int $id): void
    {
        try {
            $pdo = self::getDb();

            $stmt = $pdo->prepare("
                SELECT 
                    p.cve_prestamo,
                    p.cve_bien,
                    p.fecha_solicitud,
                    p.fecha_devolucion_pactada,
                    p.fecha_devolucion_real,
                    p.estado_prestamo AS estado,
                    p.cve_persona_solicita,
                    p.cve_persona_aprueba,
                    b.nombre AS nombre_bien,
                    b.no_serie,
                    b.codigo_barras,
                    b.foto_url,
                    b.foto_drive_id,
                    b.cve_aula,
                    pe_sol.nombre as persona_nombre,
                    pe_sol.apellido_paterno,
                    pe_sol.apellido_materno,
                    pe_enc.nombre as encargado_nombre,
                    pe_enc.apellido_paterno as encargado_apellido,
                    a.nombre AS nombre_aula,
                    a.cve_adscripcion,
                    e.nombre AS nombre_edificio
                FROM prestamos p
                JOIN bienes b ON p.cve_bien = b.cve_bien
                JOIN persona pe_sol ON p.cve_persona_solicita = pe_sol.cve_persona
                LEFT JOIN aula a ON b.cve_aula = a.cve_aula
                LEFT JOIN edificio e ON a.cve_edificio = e.cve_edificio
                LEFT JOIN profesor prof ON a.cve_profesor = prof.cve_profesor
                LEFT JOIN persona pe_enc ON prof.cve_persona = pe_enc.cve_persona
                WHERE p.cve_prestamo = :id
            ");
            $stmt->execute([':id' => $id]);
            $detalle = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$detalle) {
                Flight::json(ResponseFormatter::error("No se encontró el préstamo con ID: $id", 404), 404);
                return;
            }

            Flight::json(ResponseFormatter::success($detalle));

        } catch (\Exception $e) {
            Logger::error("Error al obtener detalle del préstamo", ['id' => $id, 'error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    public static function prestamosNoDevueltosPorPersona(int $id_persona): void
    {
        try {
            $pdo = self::getDb();

            $stmt = $pdo->prepare("
                SELECT 
                    p.cve_prestamo,
                    p.cve_bien,
                    p.fecha_solicitud,
                    p.fecha_devolucion_pactada,
                    p.fecha_devolucion_real,
                    p.estado_prestamo,
                    p.observaciones,
                    b.nombre as nombre_bien,
                    p.cve_persona_solicita,
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
                AND p.cve_persona_solicita = :id_persona
                ORDER BY p.fecha_solicitud DESC
            ");
            $stmt->execute([':id_persona' => $id_persona]);
            
            $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Logger::info("Listado de préstamos no devueltos por persona", ['id_persona' => $id_persona, 'total' => count($prestamos)]);

            Flight::json(ResponseFormatter::success($prestamos));

        } catch (\Exception $e) {
            Logger::error("Error al listar préstamos no devueltos", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    public static function listarPrestamosPendientesPorEncargado(int $id_profesor): void
    {
        try {
            $pdo = self::getDb();

            $stmt = $pdo->prepare("
                SELECT 
                    p.cve_prestamo,
                    p.cve_bien,
                    p.fecha_solicitud,
                    p.fecha_devolucion_pactada,
                    p.fecha_devolucion_real,
                    p.estado_prestamo,
                    p.observaciones,
                    b.nombre as nombre_bien,
                    p.cve_persona_solicita,
                    pe.nombre as persona_nombre,
                    pe.apellido_paterno,
                    pe.apellido_materno,
                    a.nombre as nombre_aula,
                    e.nombre as nombre_edificio
                FROM prestamos p
                JOIN bienes b ON p.cve_bien = b.cve_bien
                JOIN aula a ON b.cve_aula = a.cve_aula
                JOIN persona pe ON p.cve_persona_solicita = pe.cve_persona
                LEFT JOIN edificio e ON a.cve_edificio = e.cve_edificio
                WHERE a.cve_profesor = :id_profesor
                AND p.estado_prestamo = 'pendiente'
                ORDER BY p.fecha_solicitud DESC
            ");
            $stmt->execute([':id_profesor' => $id_profesor]);
            
            $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Logger::info("Listado de préstamos pendientes por encargado", ['id_profesor' => $id_profesor, 'total' => count($prestamos)]);

            Flight::json(ResponseFormatter::success($prestamos));

        } catch (\Exception $e) {
            Logger::error("Error al listar préstamos pendientes por encargado", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }
    public static function listarTodosLosPrestamosPendientes(): void
    {
        try {
            $pdo = self::getDb();

            $stmt = $pdo->prepare("
                SELECT 
                    p.cve_prestamo,
                    b.nombre as nombre_bien,
                    pe.nombre || ' ' || COALESCE(pe.apellido_paterno, '') || ' ' || COALESCE(pe.apellido_materno, '') as nombre_solicitante,
                    p.fecha_solicitud,
                    p.fecha_devolucion_pactada,
                    p.estado_prestamo,
                    p.observaciones
                FROM prestamos p
                JOIN bienes b ON p.cve_bien = b.cve_bien
                JOIN persona pe ON p.cve_persona_solicita = pe.cve_persona
                WHERE p.estado_prestamo = 'pendiente'
                ORDER BY p.fecha_solicitud DESC
            ");
            $stmt->execute();
            
            $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Logger::info("Listado simplificado general de préstamos pendientes", ['total' => count($prestamos)]);

            Flight::json(ResponseFormatter::success($prestamos));

        } catch (\Exception $e) {
            Logger::error("Error al listar todos los préstamos pendientes", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    public static function listarPrestamosPendientesPorPersona(int $id_persona): void
    {
        try {
            $pdo = self::getDb();

            $stmt = $pdo->prepare("
                SELECT 
                    p.cve_prestamo,
                    b.nombre as nombre_bien,
                    pe.nombre || ' ' || COALESCE(pe.apellido_paterno, '') || ' ' || COALESCE(pe.apellido_materno, '') as nombre_solicitante,
                    p.fecha_solicitud,
                    p.fecha_devolucion_pactada,
                    p.estado_prestamo,
                    p.observaciones
                FROM prestamos p
                JOIN bienes b ON p.cve_bien = b.cve_bien
                JOIN persona pe ON p.cve_persona_solicita = pe.cve_persona
                WHERE p.cve_persona_solicita = :id_persona
                AND p.estado_prestamo = 'pendiente'
                ORDER BY p.fecha_solicitud DESC
            ");
            $stmt->execute([':id_persona' => $id_persona]);
            
            $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Logger::info("Listado simplificado de préstamos pendientes por persona", ['id_persona' => $id_persona]);

            Flight::json(ResponseFormatter::success($prestamos));

        } catch (\Exception $e) {
            Logger::error("Error al listar préstamos pendientes por persona", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }
    public static function listarPrestamosAceptados(): void
    {
        try {
            $pdo = self::getDb();
            $stmt = $pdo->prepare("
                SELECT 
                    p.cve_prestamo,
                    b.nombre as nombre_bien,
                    pe.nombre as persona_nombre,
                    pe.apellido_paterno,
                    pe.apellido_materno,
                    p.fecha_solicitud,
                    p.fecha_devolucion_pactada,
                    p.fecha_devolucion_real,
                    p.estado_prestamo,
                    p.observaciones
                FROM prestamos p
                JOIN bienes b ON p.cve_bien = b.cve_bien
                JOIN persona pe ON p.cve_persona_solicita = pe.cve_persona
                WHERE p.estado_prestamo = 'aprobado'
                ORDER BY p.fecha_solicitud DESC
            ");
            $stmt->execute();
            
            $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            Logger::info("Listado simplificado de préstamos aceptados consultado", ['total' => count($prestamos)]);
            Flight::json(ResponseFormatter::success($prestamos));
        } catch (\Exception $e) {
            Logger::error("Error al listar préstamos aceptados", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    public static function listarPrestamosAceptadosPorPersona(int $id_persona): void
    {
        try {
            $pdo = self::getDb();
            $stmt = $pdo->prepare("
                SELECT 
                    p.cve_prestamo,
                    b.nombre as nombre_bien,
                    pe.nombre as persona_nombre,
                    pe.apellido_paterno,
                    pe.apellido_materno,
                    p.fecha_solicitud,
                    p.fecha_devolucion_pactada,
                    p.fecha_devolucion_real,
                    p.estado_prestamo,
                    p.observaciones
                FROM prestamos p
                JOIN bienes b ON p.cve_bien = b.cve_bien
                JOIN persona pe ON p.cve_persona_solicita = pe.cve_persona
                WHERE p.estado_prestamo = 'aprobado'
                AND p.cve_persona_solicita = :id_persona
                ORDER BY p.fecha_solicitud DESC
            ");
            $stmt->execute([':id_persona' => $id_persona]);
            
            $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            Logger::info("Listado simplificado de préstamos aceptados por persona", ['id_persona' => $id_persona]);
            Flight::json(ResponseFormatter::success($prestamos));
        } catch (\Exception $e) {
            Logger::error("Error al listar préstamos aceptados por persona", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    public static function listarDevolucionesPendientesPorEncargado(int $id_profesor): void
    {
        try {
            $pdo = self::getDb();

            $stmt = $pdo->prepare("
                SELECT 
                    p.cve_prestamo,
                    b.nombre as nombre_bien,
                    pe.nombre || ' ' || COALESCE(pe.apellido_paterno, '') || ' ' || COALESCE(pe.apellido_materno, '') as nombre_solicitante,
                    p.fecha_solicitud,
                    p.fecha_devolucion_pactada,
                    p.estado_prestamo,
                    p.observaciones
                FROM prestamos p
                JOIN bienes b ON p.cve_bien = b.cve_bien
                JOIN aula a ON b.cve_aula = a.cve_aula
                JOIN persona pe ON p.cve_persona_solicita = pe.cve_persona
                WHERE a.cve_profesor = :id_profesor
                AND p.estado_prestamo = 'por_devolver'
                ORDER BY p.fecha_solicitud DESC
            ");
            $stmt->execute([':id_profesor' => $id_profesor]);
            
            $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Logger::info("Listado de devoluciones pendientes por encargado", ['id_profesor' => $id_profesor]);

            Flight::json(ResponseFormatter::success($prestamos));

        } catch (\Exception $e) {
            Logger::error("Error al listar devoluciones pendientes por encargado", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    public static function listarTodasLasDevolucionesPendientes(): void
    {
        try {
            $pdo = self::getDb();

            $stmt = $pdo->prepare("
                SELECT 
                    p.cve_prestamo,
                    b.nombre as nombre_bien,
                    pe.nombre || ' ' || COALESCE(pe.apellido_paterno, '') || ' ' || COALESCE(pe.apellido_materno, '') as nombre_solicitante,
                    p.fecha_devolucion_pactada,
                    p.estado_prestamo,
                    p.observaciones
                FROM prestamos p
                JOIN bienes b ON p.cve_bien = b.cve_bien
                JOIN persona pe ON p.cve_persona_solicita = pe.cve_persona
                WHERE p.estado_prestamo = 'por_devolver'
                ORDER BY p.fecha_solicitud DESC
            ");
            $stmt->execute();
            
            $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Logger::info("Listado general de devoluciones pendientes", ['total' => count($prestamos)]);

            Flight::json(ResponseFormatter::success($prestamos));

        } catch (\Exception $e) {
            Logger::error("Error al listar todas las devoluciones pendientes", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }
}
