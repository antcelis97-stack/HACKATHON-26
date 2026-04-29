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

class InformacionController
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
     * GET /api/v1/aulas/edificio/@cve_edificio
     * 
     * Obtiene solo el ID y el nombre de las aulas pertenecientes a un edificio.
     */
    public static function getAulasPorEdificio(int $cve_edificio): void
    {
        try {
            $pdo = self::getDb();

            $stmt = $pdo->prepare("
                SELECT cve_aula, nombre 
                FROM aula 
                WHERE cve_edificio = :edificio
                ORDER BY nombre ASC
            ");

            $stmt->execute([':edificio' => $cve_edificio]);
            $aulas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($aulas)) {
                Flight::json(ResponseFormatter::success([], 200, "No se encontraron aulas para el edificio especificado"));
                return;
            }

            Flight::json(ResponseFormatter::success($aulas));

        } catch (\Exception $e) {
            Logger::error("Error al obtener aulas por edificio", [
                'cve_edificio' => $cve_edificio,
                'error' => $e->getMessage()
            ]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/informacion/aulas
     * 
     * Obtiene todas las aulas con el nombre del edificio.
     */
    public static function getAulas(): void
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
                    //Filtrar aulas por esaAdscripción
                    $filtroAdscripcion = "WHERE a.cve_adscripcion = :cve_adscripcion";
                    $params[':cve_adscripcion'] = $cve_adscripcion;
                }
            }

            $sql = "
                SELECT 
                    a.cve_aula,
                    a.nombre,
                    e.nombre as nombre_edificio,
                    COALESCE(ad.nombre_adscripcion, 'Sin asignar') as nombre_adscripcion
                FROM aula a
                JOIN edificio e ON a.cve_edificio = e.cve_edificio
                LEFT JOIN adscripcion ad ON a.cve_adscripcion = ad.cve_adscripcion
                $filtroAdscripcion
                ORDER BY e.nombre ASC, a.nombre ASC
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $aulas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($aulas)) {
                Flight::json(ResponseFormatter::success([], 200, "No se encontraron aulas registradas"));
                return;
            }

            Flight::json(ResponseFormatter::success($aulas));

        } catch (\Exception $e) {
            Logger::error("Error al obtener todas las aulas", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/informacion/personal/carrera/@cve_carrera
     * 
     * Obtiene el listado de personas (ID y nombre completo) filtrado por carrera.
     */
    public static function getPersonalPorCarrera(int $cve_carrera): void
    {
        try {
            $pdo = self::getDb();

            $stmt = $pdo->prepare("
                SELECT cve_persona, 
                       nombre || ' ' || COALESCE(apellido_paterno, '') || ' ' || COALESCE(apellido_materno, '') AS nombre_completo
                FROM persona 
                WHERE cve_carrera = :carrera
                ORDER BY nombre_completo ASC
            ");

            $stmt->execute([':carrera' => $cve_carrera]);
            $personas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($personas)) {
                Flight::json(ResponseFormatter::success([], 200, "No se encontró personal para la carrera especificada"));
                return;
            }

            Flight::json(ResponseFormatter::success($personas));

        } catch (\Exception $e) {
            Logger::error("Error al obtener personal por carrera", [
                'cve_carrera' => $cve_carrera,
                'error' => $e->getMessage()
            ]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/informacion/profesores
     * 
     * Obtiene el listado de todos los profesores (ID y nombre completo).
     */
    public static function getProfesores(): void
    {
        try {
            $pdo = self::getDb();

            $stmt = $pdo->prepare("
                SELECT p.cve_persona as cve, 
                       p.nombre || ' ' || COALESCE(p.apellido_paterno, '') || ' ' || COALESCE(p.apellido_materno, '') AS nombre
                FROM persona p
                INNER JOIN profesor prof ON p.cve_persona = prof.cve_persona
                ORDER BY nombre ASC
            ");

            $stmt->execute();
            $profesores = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($profesores)) {
                Flight::json(ResponseFormatter::success([], 200, "No se encontraron profesores registrados"));
                return;
            }

            Flight::json(ResponseFormatter::success($profesores));

        } catch (\Exception $e) {
            Logger::error("Error al obtener lista de profesores", [
                'error' => $e->getMessage()
            ]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/informacion/bienes
     * 
     * Obtiene el listado de todos los bienes (ID y nombre) para selectores/dropdowns.
     * Puede filtrar por id_aula si se proporciona como parámetro.
     */
    public static function getBienesCatalogo(?int $id_aula = null): void
    {
        try {
            $pdo = self::getDb();
            
            // Si no se pasa como argumento, intentar obtener de query params
            $id_aula = $id_aula ?? (isset($_GET['id_aula']) ? (int)$_GET['id_aula'] : null);

            $sql = "SELECT cve_bien, nombre FROM bienes";
            $params = [];

            if ($id_aula) {
                $sql .= " WHERE cve_aula = :id_aula";
                $params[':id_aula'] = $id_aula;
            }

            $sql .= " ORDER BY nombre ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $bienes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Flight::json(ResponseFormatter::success($bienes));

        } catch (\Exception $e) {
            Logger::error("Error al obtener catálogo de bienes", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/informacion/bienes/disponibles
     * 
     * Obtiene el listado de bienes que no tienen un préstamo activo.
     * Filtra por estado_prestamo = 'disponible'.
     */
    public static function getBienesDisponibles(): void
    {
        try {
            $pdo = self::getDb();
            
            // Obtener id_aula opcional de los query params (?id_aula=X)
            $id_aula = Flight::request()->query->id_aula;

            $sql = "
                SELECT 
                    b.cve_bien, 
                    b.no_serie, 
                    b.nombre,
                    b.codigo_qr,
                    m.nombre_modelo,
                    mar.nombre_marca
                FROM bienes b
                LEFT JOIN modelos m ON b.cve_modelo = m.cve_modelo
                LEFT JOIN marcas mar ON m.cve_marca = mar.cve_marca
                WHERE b.estado_prestamo = 'disponible' 
                AND b.activo = true
            ";

            $params = [];
            if ($id_aula) {
                $sql .= " AND cve_aula = :id_aula";
                $params[':id_aula'] = (int)$id_aula;
            }

            $sql .= " ORDER BY nombre ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $bienes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Flight::json(ResponseFormatter::success($bienes));

        } catch (\Exception $e) {
            Logger::error("Error al obtener bienes disponibles", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }
    /**
     * GET /api/v1/informacion/tipos-bien
     * 
     * Obtiene el catálogo de tipos de bien.
     */
    public static function getTiposBien(): void
    {
        try {
            $pdo = self::getDb();
            $stmt = $pdo->query("SELECT cve_tipo, clave, nombre FROM tipos_bien ORDER BY nombre ASC");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            Flight::json(ResponseFormatter::success($data));
        } catch (\Exception $e) {
            Logger::error("Error al obtener tipos de bien", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/informacion/familias/tipo/@cve_tipo
     * 
     * Obtiene las familias de artículos pertenecientes a un tipo de bien.
     */
    public static function getFamiliasPorTipo(int $cve_tipo): void
    {
        try {
            $pdo = self::getDb();
            $stmt = $pdo->prepare("SELECT cve_familia, clave, nombre, cve_tipo FROM familias_articulos WHERE cve_tipo = :tipo ORDER BY nombre ASC");
            $stmt->execute([':tipo' => $cve_tipo]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            Flight::json(ResponseFormatter::success($data));
        } catch (\Exception $e) {
            Logger::error("Error al obtener familias por tipo", ['cve_tipo' => $cve_tipo, 'error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/informacion/articulos/familia/@cve_familia
     * 
     * Obtiene los artículos pertenecientes a una familia específica.
     */
    public static function getArticulosPorFamilia(int $cve_familia): void
    {
        try {
            $pdo = self::getDb();
            $stmt = $pdo->prepare("SELECT cve_articulo, clave, nombre, cve_familia FROM articulos WHERE cve_familia = :familia ORDER BY nombre ASC");
            $stmt->execute([':familia' => $cve_familia]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            Flight::json(ResponseFormatter::success($data));
        } catch (\Exception $e) {
            Logger::error("Error al obtener artículos por familia", ['cve_familia' => $cve_familia, 'error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/informacion/edificios
     * 
     * Obtiene el catálogo de edificios activos.
     */
    public static function getEdificios(): void
    {
        try {
            $pdo = self::getDb();
            $stmt = $pdo->query("SELECT cve_edificio, nombre, abreviatura FROM edificio WHERE activo = true ORDER BY nombre ASC");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            Flight::json(ResponseFormatter::success($data));
        } catch (\Exception $e) {
            Logger::error("Error al obtener edificios", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/informacion/tipos-aula
     * 
     * Obtiene el catálogo de tipos de aula.
     */
    public static function getTiposAula(): void
    {
        try {
            $pdo = self::getDb();
            $stmt = $pdo->query("SELECT cve_tipo_aula, nombre FROM tipo_aula ORDER BY nombre ASC");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            Flight::json(ResponseFormatter::success($data));
        } catch (\Exception $e) {
            Logger::error("Error al obtener tipos de aula", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/informacion/marcas
     * 
     * Obtiene el catálogo de marcas.
     */
    public static function getMarcas(): void
    {
        try {
            $pdo = self::getDb();
            $stmt = $pdo->query("SELECT cve_marca, nombre_marca FROM marcas ORDER BY nombre_marca ASC");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            Flight::json(ResponseFormatter::success($data));
        } catch (\Exception $e) {
            Logger::error("Error al obtener marcas", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/informacion/modelos
     * 
     * Obtiene el catálogo de modelos.
     */
    public static function getModelos(): void
    {
        try {
            $pdo = self::getDb();
            $stmt = $pdo->query("SELECT cve_modelo, nombre_modelo, cve_marca, descripcion FROM modelos ORDER BY nombre_modelo ASC");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            Flight::json(ResponseFormatter::success($data));
        } catch (\Exception $e) {
            Logger::error("Error al obtener modelos", ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/informacion/bien/qr/@id
     * 
     * Obtiene el código QR de un bien por su ID.
     */
    public static function getQRByBienId(int $id): void
    {
        try {
            $pdo = self::getDb();
            $stmt = $pdo->prepare("SELECT codigo_qr FROM bienes WHERE cve_bien = :id");
            $stmt->execute([':id' => $id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$data) {
                Flight::json(ResponseFormatter::error("No se encontró el bien con ID: $id", 404), 404);
                return;
            }

            Flight::json(ResponseFormatter::success($data));
        } catch (\Exception $e) {
            Logger::error("Error al obtener QR por ID de bien", ['cve_bien' => $id, 'error' => $e->getMessage()]);
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
}