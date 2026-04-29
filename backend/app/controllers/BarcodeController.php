<?php

namespace app\controllers;

use App\Lib\Logger;
use App\Lib\ResponseFormatter;
use Flight;
use PDO;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Picqer\Barcode\BarcodeGenerator;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;

/**
 * BarcodeController
 *
 * Endpoints:
 *   GET  /api/v1/barcodes/imagen              → imagen PNG del barcode (local)
 *   GET  /api/v1/barcodes/buscar/{codigo}      → JSON con datos completos del bien
 *   GET  /api/v1/barcodes/etiquetas/datos      → JSON con imagen base64 lista para el frontend
 *   GET  /api/v1/barcodes/etiquetas/datos/aula/{cve_aula} → Etiquetas barcode por aula
 *   GET  /api/v1/barcodes/etiquetas/qr/datos/aula/{cve_aula} → Etiquetas QR por aula
 */
class BarcodeController
{
    // -------------------------------------------------------------------------
    // Helpers privados
    // -------------------------------------------------------------------------

    /**
     * Escapa un valor para salida HTML.
     */
    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Genera imagen de código de barras en formato PNG base64.
     */
    private static function generarBarcodeBase64(string $codigo): string
    {
        if (!class_exists('Picqer\Barcode\BarcodeGeneratorSVG')) {
            throw new \Exception("La librería Picqer\Barcode no está instalada. Ejecuta 'composer require picqer/php-barcode-generator'");
        }
        $generator = new \Picqer\Barcode\BarcodeGeneratorSVG();
        $barcode = $generator->getBarcode($codigo, \Picqer\Barcode\BarcodeGenerator::TYPE_CODE_128, 2, 50);
        return 'data:image/svg+xml;base64,' . base64_encode($barcode);
    }

    /**
     * Genera imagen de código QR en formato SVG base64 (no requiere extensión GD).
     */
    private static function generarQRBase64(string $contenido): string
    {
        if (!class_exists('Endroid\QrCode\Builder\Builder')) {
            throw new \Exception("La librería Endroid\QrCode no está instalada. Ejecuta 'composer require endroid/qr-code'");
        }
        // En versiones >= 5.0 de endroid/qr-code, se usan argumentos con nombre en el constructor
        $builder = new \Endroid\QrCode\Builder\Builder(
            writer: new \Endroid\QrCode\Writer\SvgWriter(),
            data: $contenido,
            size: 200,
            margin: 10
        );
        $result = $builder->build();
        return 'data:image/svg+xml;base64,' . base64_encode($result->getString());
    }

    /**
     * Resuelve claves de catálogos y construye el texto de barcode.
     * Usado internamente por imagen().
     */
    private static function resolveBarcodeData(int $cveTipo, int $cveFamilia, int $cveArticulo): ?array
    {
        require_once __DIR__ . '/../../config/database.php';
        $pdo = getPgInventarioConnection();

        $stmt = $pdo->prepare("
            SELECT
                tb.clave AS tipo_clave,
                fa.clave AS familia_clave,
                ar.clave AS articulo_clave
            FROM public.articulos ar
            INNER JOIN public.familias_articulos fa ON fa.cve_familia  = ar.cve_familia
            INNER JOIN public.tipos_bien tb         ON tb.cve_tipo     = fa.cve_tipo
            WHERE tb.cve_tipo     = :cve_tipo
              AND fa.cve_familia  = :cve_familia
              AND ar.cve_articulo = :cve_articulo
            LIMIT 1
        ");
        $stmt->bindValue(':cve_tipo',     $cveTipo,     PDO::PARAM_INT);
        $stmt->bindValue(':cve_familia',  $cveFamilia,  PDO::PARAM_INT);
        $stmt->bindValue(':cve_articulo', $cveArticulo, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            Flight::json(ResponseFormatter::validationError([[
                'field'   => 'relacion',
                'message' => 'La combinacion cve_tipo/cve_familia/cve_articulo no existe o no es consistente',
            ]]), 400);
            return null;
        }

        $barcodeText = sprintf(
            '%s-%s-%s',
            strtoupper((string) $row['tipo_clave']),
            strtoupper((string) $row['familia_clave']),
            strtoupper((string) $row['articulo_clave'])
        );

        return [
            'barcodeText' => $barcodeText,
            'tipo'        => (string) $row['tipo_clave'],
            'familia'     => (string) $row['familia_clave'],
            'articulo'    => (string) $row['articulo_clave'],
        ];
    }

    /**
     * POST /api/v1/barcodes/generar
     *
     * Genera un código de barras a partir de tipo/familia/artículo
     * y crea el registro del bien en la base de datos.
     */
    public static function generar(): void
    {
        try {
            $body = Flight::request()->getBody();
            $data = json_decode($body, true) ?? [];

            $cveTipo     = (int) ($data['cve_tipo'] ?? 0);
            $cveFamilia  = (int) ($data['cve_familia'] ?? 0);
            $cveArticulo = (int) ($data['cve_articulo'] ?? 0);
            $identificador = trim($data['identificador'] ?? '');
            $cveAula     = (int) ($data['cve_aula'] ?? 0);

            $errors = [];
            if ($cveTipo < 1) $errors[] = ['field' => 'cve_tipo', 'message' => 'cve_tipo es requerido'];
            if ($cveFamilia < 1) $errors[] = ['field' => 'cve_familia', 'message' => 'cve_familia es requerido'];
            if ($cveArticulo < 1) $errors[] = ['field' => 'cve_articulo', 'message' => 'cve_articulo es requerido'];
            if ($identificador === '') $errors[] = ['field' => 'identificador', 'message' => 'identificador es requerido'];
            if ($cveAula < 1) $errors[] = ['field' => 'cve_aula', 'message' => 'cve_aula es requerido'];

            if (!empty($errors)) {
                Flight::json(ResponseFormatter::validationError($errors), 400);
                return;
            }

            $barcode = self::resolveBarcodeData($cveTipo, $cveFamilia, $cveArticulo);
            if ($barcode === null) {
                return;
            }

            $codigoBarras = sprintf(
                '%s-%s-%s',
                $barcode['tipo'],
                $barcode['familia'],
                $barcode['articulo']
            );

            require_once __DIR__ . '/../../config/database.php';
            $pdo = getPgInventarioConnection();

            $stmt = $pdo->prepare("
                INSERT INTO bienes (nombre, codigo_barras, cve_aula, cve_articulo, activo, estado_prestamo, estado_fisico)
                VALUES (:nombre, :codigo_barras, :cve_aula, :cve_articulo, true, 'disponible', 'NUEVO')
                RETURNING cve_bien
            ");
            $stmt->execute([
                ':nombre' => $codigoBarras,
                ':codigo_barras' => $codigoBarras,
                ':cve_aula' => $cveAula,
                ':cve_articulo' => $cveArticulo
            ]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            Logger::info('Código de barras generado', ['cve_bien' => $row['cve_bien'], 'codigo' => $codigoBarras]);
            Flight::json(ResponseFormatter::success([
                'cve_bien' => (int) $row['cve_bien'],
                'codigo_barras' => $codigoBarras
            ], 200, 'Código de barras generado correctamente'));

        } catch (\Throwable $e) {
            Logger::error('Barcode generar', ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    // -------------------------------------------------------------------------
    // Endpoints públicos
    // -------------------------------------------------------------------------

    /**
     * GET /api/v1/barcodes/buscar/{codigo}
     *
     * Busca un bien por su código de barras y devuelve información completa.
     * Query param opcional: ?cve_aula=N para validar si el bien está en el aula correcta.
     */
    public static function buscarPorCodigo(string $codigo): void
    {
        try {
            require_once __DIR__ . '/../../config/database.php';
            $pdo = getPgInventarioConnection();

            $stmt = $pdo->prepare("
                SELECT
                    b.cve_bien, b.nombre, b.codigo_barras, b.codigo_qr,
                    b.no_serie, b.no_factura, b.descripcion, b.costo_unitario,
                    b.estado_fisico, b.estado_prestamo, b.activo, b.fecha_registro,
                    b.cve_aula, b.foto_url, b.foto_drive_id,
                    m.nombre_modelo,
                    mar.nombre_marca,
                    a.nombre  AS nombre_aula,
                    e.nombre  AS nombre_edificio,
                    tb.nombre AS tipo_nombre,
                    fa.nombre AS familia_nombre,
                    ar.nombre AS articulo_nombre
                FROM public.bienes b
                LEFT JOIN public.articulos ar          ON ar.cve_articulo  = b.cve_articulo
                LEFT JOIN public.familias_articulos fa ON fa.cve_familia   = ar.cve_familia
                LEFT JOIN public.tipos_bien tb         ON tb.cve_tipo      = fa.cve_tipo
                LEFT JOIN public.modelos m             ON m.cve_modelo     = b.cve_modelo
                LEFT JOIN public.marcas mar            ON mar.cve_marca    = m.cve_marca
                LEFT JOIN public.aula a                ON a.cve_aula       = b.cve_aula
                LEFT JOIN public.edificio e            ON e.cve_edificio   = a.cve_edificio
                WHERE b.codigo_barras = :codigo
                LIMIT 1
            ");
            $stmt->execute([':codigo' => $codigo]);
            $bien = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$bien) {
                Flight::json(ResponseFormatter::error("No se encontró ningún bien con el código: $codigo", 404), 404);
                return;
            }

            $cveAulaEscaneada  = Flight::request()->query->cve_aula;
            $ubicacionCorrecta = $cveAulaEscaneada !== null
                ? ((int) $cveAulaEscaneada === (int) $bien['cve_aula'])
                : false;

            Logger::info('Búsqueda por código de barras exitosa', ['codigo' => $codigo]);
            Flight::json(ResponseFormatter::success(array_merge($bien, [
                'ubicacion_correcta' => $ubicacionCorrecta,
                'url_reporte'        => '/api/v1/reportes/detalle-bien/' . $bien['cve_bien'],
            ])));

        } catch (\Throwable $e) {
            Logger::error('Barcode buscarPorCodigo', ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * Genera el código de barras interno (sin guiones) para uso desde otros controladores.
     * Formato: TIPO + FAMILIA + ARTICULO + IDENTIFICADOR
     */
    public static function generarCodigoBarrasInterno(
        int $cveTipo,
        int $cveFamilia,
        int $cveArticulo,
        string $identificador
    ): string {
        require_once __DIR__ . '/../../config/database.php';
        $pdo = getPgInventarioConnection();

        $stmt = $pdo->prepare("
            SELECT
                tb.clave AS tipo_clave,
                fa.clave AS familia_clave,
                ar.clave AS articulo_clave
            FROM public.articulos ar
            INNER JOIN public.familias_articulos fa ON fa.cve_familia  = ar.cve_familia
            INNER JOIN public.tipos_bien tb         ON tb.cve_tipo     = fa.cve_tipo
            WHERE tb.cve_tipo     = :cve_tipo
              AND fa.cve_familia  = :cve_familia
              AND ar.cve_articulo = :cve_articulo
            LIMIT 1
        ");
        $stmt->bindValue(':cve_tipo',     $cveTipo,     PDO::PARAM_INT);
        $stmt->bindValue(':cve_familia',  $cveFamilia,  PDO::PARAM_INT);
        $stmt->bindValue(':cve_articulo', $cveArticulo, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new \Exception(
                "No se encontró la configuración del catálogo para IDs: " .
                "Tipo $cveTipo, Familia $cveFamilia, Articulo $cveArticulo"
            );
        }

        return sprintf(
            '%s%s%s%s',
            strtoupper(trim((string) $row['tipo_clave'])),
            strtoupper(trim((string) $row['familia_clave'])),
            strtoupper(trim((string) $row['articulo_clave'])),
            strtoupper(trim($identificador))
        );
    }

    /**
     * GET /api/v1/barcodes/imagen?cve_tipo=1&cve_familia=1&cve_articulo=1
     *
     * Descarga y devuelve la imagen PNG del código de barras desde el proveedor externo.
     */
    public static function imagen(): void
    {
        try {
            $cveTipo     = (int) ($_GET['cve_tipo']     ?? 0);
            $cveFamilia  = (int) ($_GET['cve_familia']  ?? 0);
            $cveArticulo = (int) ($_GET['cve_articulo'] ?? 0);

            $errors = [];
            if ($cveTipo     < 1) $errors[] = ['field' => 'cve_tipo',     'message' => 'cve_tipo es requerido y debe ser entero positivo'];
            if ($cveFamilia  < 1) $errors[] = ['field' => 'cve_familia',  'message' => 'cve_familia es requerido y debe ser entero positivo'];
            if ($cveArticulo < 1) $errors[] = ['field' => 'cve_articulo', 'message' => 'cve_articulo es requerido y debe ser entero positivo'];

            if (!empty($errors)) {
                Flight::json(ResponseFormatter::validationError($errors), 400);
                return;
            }

            $barcode = self::resolveBarcodeData($cveTipo, $cveFamilia, $cveArticulo);
            if ($barcode === null) {
                return;
            }

            $imageBytes = null;
            if ($barcode !== null) {
                $generator = new BarcodeGeneratorPNG();
                $imageBytes = $generator->getBarcode($barcode['barcodeText'], BarcodeGenerator::TYPE_CODE_128, 2, 50);
            }

            if ($imageBytes === null || $imageBytes === '') {
                Flight::json(ResponseFormatter::error(
                    'No fue posible generar la imagen del codigo de barras localmente',
                    'BARCODE_GENERATION_ERROR'
                ), 500);
                return;
            }

            header('Content-Type: image/png');
            header('Cache-Control: public, max-age=300');
            echo $imageBytes;

        } catch (\Throwable $e) {
            Logger::error('Barcode imagen', ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/barcodes/etiquetas/datos?limit=12
     *
     * Devuelve en JSON los datos de cada etiqueta listos para el frontend.
     * El campo `barcode_imagen_base64` contiene la imagen PNG en data URI
     * (data:image/png;base64,...) para usar directamente en <img src="...">.
     */
    public static function etiquetasDatos(int $id): void
    {
        try {
            require_once __DIR__ . '/../../config/database.php';
            $pdo = getPgInventarioConnection();

            $sql = "
                SELECT
                    b.cve_bien,
                    b.nombre,
                    b.codigo_barras,
                    COALESCE(b.no_serie, '')            AS no_serie,
                    COALESCE(m.nombre_modelo, '')        AS nombre_modelo,
                    COALESCE(mar.nombre_marca, '')       AS nombre_marca,
                    COALESCE(au.nombre, '')              AS nombre_aula,
                    COALESCE(e.nombre, '')               AS nombre_edificio,
                    COALESCE(ads.clave_adscripcion, '')  AS clave_adscripcion,
                    COALESCE(ads.nombre_adscripcion, '') AS nombre_adscripcion
                FROM public.bienes b
                LEFT JOIN public.modelos m              ON m.cve_modelo        = b.cve_modelo
                LEFT JOIN public.marcas mar             ON mar.cve_marca       = m.cve_marca
                LEFT JOIN public.aula au                ON au.cve_aula         = b.cve_aula
                LEFT JOIN public.edificio e             ON e.cve_edificio      = au.cve_edificio
                LEFT JOIN public.profesor prof          ON prof.cve_profesor   = au.cve_profesor
                LEFT JOIN public.adscripcion_persona ap
                    ON ap.cve_persona = prof.cve_persona AND ap.activo = true
                LEFT JOIN public.adscripcion ads        ON ads.cve_adscripcion = ap.cve_adscripcion
                WHERE b.cve_bien = :id
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Contexto HTTP para descargar imagen del proveedor externo
            $ctx = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 8, 'ignore_errors' => true]]);

            $etiquetas = array_map(function (array $row) use ($ctx): array {
                $codigo = trim((string) ($row['codigo_barras'] ?? ''));

                // Generar imagen y convertir a base64 si hay código de barras
                $imagenBase64 = null;
                if ($codigo !== '') {
                    $imagenBase64 = self::generarBarcodeBase64($codigo);
                }

                $str = fn(string $k) => $row[$k] !== '' ? $row[$k] : null;

                return [
                    'cve_bien'              => (int) $row['cve_bien'],
                    'nombre'                => $row['nombre'],
                    'codigo_barras'         => $codigo !== '' ? $codigo : null,
                    'barcode_imagen_base64' => $imagenBase64,
                    'no_serie'              => $str('no_serie'),
                    'nombre_modelo'         => $str('nombre_modelo'),
                    'nombre_marca'          => $str('nombre_marca'),
                    'nombre_aula'           => $str('nombre_aula'),
                    'nombre_edificio'       => $str('nombre_edificio'),
                    'clave_adscripcion'     => $str('clave_adscripcion'),
                    'nombre_adscripcion'    => $str('nombre_adscripcion'),
                ];
            }, $rows);

            Logger::info('Etiquetas datos devueltos', ['total' => count($etiquetas)]);
            Flight::json(ResponseFormatter::success($etiquetas));

        } catch (\Throwable $e) {
            Logger::error('Barcode etiquetasDatos', ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * Construye la URL de imagen QR.
     */
    private static function qrUrl(string $data): string
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . rawurlencode($data);
    }

    /**
     * GET /api/v1/barcodes/etiquetas/qr/datos?limit=12
     *
     * Devuelve en JSON los datos de cada etiqueta con QR listos para el frontend.
     */
    public static function qrEtiquetasDatos(int $id): void
    {
        try {
            require_once __DIR__ . '/../../config/database.php';
            $pdo = getPgInventarioConnection();

            $sql = "
                SELECT
                    b.cve_bien,
                    b.nombre,
                    b.codigo_qr,
                    COALESCE(b.descripcion, '')           AS descripcion,
                    COALESCE(m.nombre_modelo, '')        AS nombre_modelo,
                    COALESCE(mar.nombre_marca, '')       AS nombre_marca
                FROM public.bienes b
                LEFT JOIN public.modelos m              ON m.cve_modelo        = b.cve_modelo
                LEFT JOIN public.marcas mar             ON mar.cve_marca       = m.cve_marca
                WHERE b.cve_bien = :id
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $ctx = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 8, 'ignore_errors' => true]]);

            $etiquetas = array_map(function (array $row) use ($ctx): array {
                $idFormateado = str_pad((string)$row['cve_bien'], 7, '0', STR_PAD_LEFT);
                
                // Si no hay codigo_qr guardado, generamos uno por defecto apuntando al frontend
                $qrData = !empty($row['codigo_qr']) ? $row['codigo_qr'] : "http://localhost:4200/bien/" . $row['cve_bien'];
                
                // Generar QR en base64
                $imagenBase64 = self::generarQRBase64($qrData);

                return [
                    'cve_bien'            => (int) $row['cve_bien'],
                    'cve_bien_formateado' => $idFormateado,
                    'qr_imagen_base64'    => $imagenBase64,
                    'gobierno_texto'      => 'GOBIERNO DEL ESTADO DE NAYARIT',
                    'nombre'              => $row['nombre'],
                    'descripcion'         => $row['descripcion'],
                    'detalles_modelo'     => trim(($row['nombre_marca'] ?: 'S/M') . ' / ' . ($row['nombre_modelo'] ?: 'S/M'))
                ];
            }, $rows);

            Flight::json(ResponseFormatter::success($etiquetas));

        } catch (\Throwable $e) {
            Logger::error('Barcode qrEtiquetasDatos', ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * POST /api/v1/barcodes/generar/aula/@id
     * 
     * Genera el string del QR para un aula y lo guarda en la base de datos.
     */
    public static function generarQRAula(int $id): void
    {
        try {
            require_once __DIR__ . '/../../config/database.php';
            $pdo = getPgInventarioConnection();

            // 1. Verificar si el aula existe
            $stmtCheck = $pdo->prepare("SELECT cve_aula FROM aula WHERE cve_aula = :id");
            $stmtCheck->execute([':id' => $id]);
            if (!$stmtCheck->fetch()) {
                Flight::json(ResponseFormatter::error("No se encontró el aula con ID: $id", 404), 404);
                return;
            }

            // 2. Generar el dato del QR (localhost)
            $qrData = "http://localhost:4200/aula/" . $id;

            // 3. Guardar en la base de datos
            $stmtUpdate = $pdo->prepare("
                UPDATE aula 
                SET codigo_qr = :qr 
                WHERE cve_aula = :id
            ");
            $stmtUpdate->execute([
                ':qr' => $qrData,
                ':id' => $id
            ]);

            Logger::info("QR generado y guardado para el aula", ['cve_aula' => $id, 'qr' => $qrData]);

            Flight::json(ResponseFormatter::success(['codigo_qr' => $qrData], 200, "Código QR generado y guardado correctamente"));

        } catch (\Throwable $e) {
            Logger::error('Barcode generarQRAula', ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/barcodes/etiquetas/qr/aula/@id
     * 
     * Devuelve en JSON los datos de la etiqueta con QR para un aula.
     */
    public static function qrAulaEtiquetaDatos(int $id): void
    {
        try {
            require_once __DIR__ . '/../../config/database.php';
            $pdo = getPgInventarioConnection();

            $sql = "
                SELECT
                    a.cve_aula,
                    a.nombre,
                    a.codigo_qr,
                    e.nombre AS nombre_edificio,
                    ta.nombre AS tipo_aula
                FROM public.aula a
                LEFT JOIN public.edificio e ON a.cve_edificio = e.cve_edificio
                LEFT JOIN public.tipo_aula ta ON a.cve_tipo_aula = ta.cve_tipo_aula
                WHERE a.cve_aula = :id
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                Flight::json(ResponseFormatter::error("No se encontró el aula con ID: $id", 404), 404);
                return;
            }

            $ctx = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 8, 'ignore_errors' => true]]);

            $idFormateado = str_pad((string)$row['cve_aula'], 5, '0', STR_PAD_LEFT);
            $qrData = !empty($row['codigo_qr']) ? $row['codigo_qr'] : "http://localhost:4200/aula/" . $row['cve_aula'];

            // Generar QR en base64
            $imagenBase64 = self::generarQRBase64($qrData);

            $data = [
                'cve_aula'            => (int) $row['cve_aula'],
                'cve_aula_formateado' => $idFormateado,
                'qr_imagen_base64'    => $imagenBase64,
                'nombre'              => $row['nombre'],
                'nombre_edificio'     => $row['nombre_edificio'],
                'tipo_aula'           => $row['tipo_aula']
            ];

            Flight::json(ResponseFormatter::success($data));

        } catch (\Throwable $e) {
            Logger::error('Barcode qrAulaEtiquetaDatos', ['error' => $e->getMessage()]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/barcodes/etiquetas/datos/aula/@cve_aula
     * 
     * Obtiene los datos para generar etiquetas de código de barras
     * para todos los bienes de un aula específica.
     */
    public static function getEtiquetasBarcodePorAula(int $cve_aula): void
    {
        try {
            require_once __DIR__ . '/../../config/database.php';
            $pdo = getPgInventarioConnection();

            $stmt = $pdo->prepare("
                SELECT 
                    b.cve_bien,
                    b.nombre,
                    b.codigo_barras,
                    b.no_serie,
                    COALESCE(m.nombre_modelo, 'N/A') as nombre_modelo,
                    COALESCE(ma.nombre_marca, 'N/A') as nombre_marca,
                    a.nombre as nombre_aula,
                    e.nombre as nombre_edificio,
                    COALESCE(ad.clave_adscripcion, 'N/A') as clave_adscripcion,
                    COALESCE(ad.nombre_adscripcion, 'Sistemas') as nombre_adscripcion
                FROM bienes b
                LEFT JOIN modelos m ON b.cve_modelo = m.cve_modelo
                LEFT JOIN marcas ma ON m.cve_marca = ma.cve_marca
                LEFT JOIN aula a ON b.cve_aula = a.cve_aula
                LEFT JOIN edificio e ON a.cve_edificio = e.cve_edificio
                LEFT JOIN profesor pr ON a.cve_profesor = pr.cve_profesor
                LEFT JOIN persona p ON pr.cve_persona = p.cve_persona
                LEFT JOIN adscripcion_persona ap ON p.cve_persona = ap.cve_persona
                LEFT JOIN adscripcion ad ON ap.cve_adscripcion = ad.cve_adscripcion
                WHERE b.cve_aula = :cve_aula
                AND b.activo = true
                ORDER BY b.nombre ASC
            ");

            $stmt->execute([':cve_aula' => $cve_aula]);
            $bienes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($bienes as &$bien) {
                if (!empty($bien['codigo_barras'])) {
                    $bien['barcode_imagen_base64'] = self::generarBarcodeBase64($bien['codigo_barras']);
                } else {
                    $codigoGenerado = 'BIEN-' . str_pad($bien['cve_bien'], 6, '0', STR_PAD_LEFT);
                    $bien['barcode_imagen_base64'] = self::generarBarcodeBase64($codigoGenerado);
                    $bien['codigo_barras'] = $codigoGenerado;
                }
            }

            Flight::json(ResponseFormatter::success($bienes));

        } catch (\Throwable $e) {
            Logger::error("Error al obtener etiquetas barcode por aula", [
                'cve_aula' => $cve_aula,
                'error' => $e->getMessage()
            ]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * GET /api/v1/barcodes/etiquetas/qr/datos/aula/@cve_aula
     * 
     * Obtiene los datos para generar etiquetas de código QR
     * para todos los bienes de un aula específica.
     */
    public static function getEtiquetasQRPorAula(int $cve_aula): void
    {
        try {
            require_once __DIR__ . '/../../config/database.php';
            $pdo = getPgInventarioConnection();

            $stmt = $pdo->prepare("
                SELECT 
                    b.cve_bien,
                    LPAD(b.cve_bien::TEXT, 7, '0') as cve_bien_formateado,
                    b.codigo_qr,
                    b.nombre,
                    b.descripcion,
                    COALESCE(m.nombre_modelo, 'N/A') as nombre_modelo,
                    COALESCE(ma.nombre_marca, 'N/A') as nombre_marca
                FROM bienes b
                LEFT JOIN modelos m ON b.cve_modelo = m.cve_modelo
                LEFT JOIN marcas ma ON m.cve_marca = ma.cve_marca
                WHERE b.cve_aula = :cve_aula
                AND b.activo = true
                ORDER BY b.nombre ASC
            ");

            $stmt->execute([':cve_aula' => $cve_aula]);
            $bienes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($bienes as &$bien) {
                $qrData = !empty($bien['codigo_qr']) ? $bien['codigo_qr'] : "http://localhost:4200/bien/" . $bien['cve_bien'];
                $bien['qr_imagen_base64'] = self::generarQRBase64($qrData);
                $bien['gobierno_texto'] = 'GOBIERNO DEL ESTADO DE NAYARIT';
                $bien['detalles_modelo'] = trim(($bien['nombre_marca'] !== 'N/A' ? $bien['nombre_marca'] : 'S/M') . ' / ' . ($bien['nombre_modelo'] !== 'N/A' ? $bien['nombre_modelo'] : 'S/M'));
            }

            Flight::json(ResponseFormatter::success($bienes));

        } catch (\Throwable $e) {
            Logger::error("Error al obtener etiquetas QR por aula", [
                'cve_aula' => $cve_aula,
                'error' => $e->getMessage()
            ]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }
}

