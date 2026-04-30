# Endpoints PHP para Impresión de Etiquetas

Agregar estos endpoints al backend existente.

---

## 1. InformacionController.php - Agregar método getAulas()

Agregar este método a `app/controllers/InformacionController.php`:

```php
/**
 * GET /api/v1/aulas
 * 
 * Obtiene todas las aulas con el nombre del edificio.
 */
public static function getAulas(): void
{
    try {
        $pdo = self::getDb();

        $stmt = $pdo->query("
            SELECT 
                a.cve_aula,
                a.nombre,
                e.nombre as nombre_edificio
            FROM aula a
            JOIN edificio e ON a.cve_edificio = e.cve_edificio
            ORDER BY e.nombre ASC, a.nombre ASC
        ");

        $aulas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        Flight::json(ResponseFormatter::success($aulas));

    } catch (\Exception $e) {
        Logger::error("Error al obtener todas las aulas", ['error' => $e->getMessage()]);
        Flight::json(ResponseFormatter::error($e->getMessage()), 500);
    }
}
```

---

## 2. BarcodesController.php - Nuevo archivo

Crear nuevo archivo en `app/controllers/BarcodesController.php`:

```php
<?php

namespace app\controllers;

use Flight;
use PDO;
use App\Lib\ResponseFormatter;
use App\Lib\Logger;

class BarcodesController
{
    private static function getDb(): \PDO
    {
        require_once __DIR__ . '/../../config/database.php';
        return getPgInventarioConnection();
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
            $pdo = self::getDb();

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
                LEFT JOIN persona p ON b.cve_encargado = p.cve_persona
                LEFT JOIN adscripcion_persona ap ON p.cve_persona = ap.cve_persona
                LEFT JOIN adscripcion ad ON ap.cve_adscripcion = ad.cve_adscripcion
                WHERE b.cve_aula = :cve_aula
                AND b.activo = true
                ORDER BY b.nombre ASC
            ");

            $stmt->execute([':cve_aula' => $cve_aula]);
            $bienes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Generar imágenes de código de barras en base64
            foreach ($bienes as &$bien) {
                if (!empty($bien['codigo_barras'])) {
                    $bien['barcode_imagen_base64'] = self::generarBarcodeBase64($bien['codigo_barras']);
                } else {
                    // Si no tiene código de barras, generar uno basado en cve_bien
                    $codigoGenerado = 'BIEN-' . str_pad($bien['cve_bien'], 6, '0', STR_PAD_LEFT);
                    $bien['barcode_imagen_base64'] = self::generarBarcodeBase64($codigoGenerado);
                    $bien['codigo_barras'] = $codigoGenerado;
                }
            }

            Flight::json(ResponseFormatter::success($bienes));

        } catch (\Exception $e) {
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
            $pdo = self::getDb();

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

            // Generar imágenes QR en base64
            foreach ($bienes as &$bien) {
                // Usar código QR existente o generar uno nuevo
                $contenidoQR = $bien['codigo_qr'] ?? 'http://localhost:4200/bien/' . $bien['cve_bien'];
                $bien['qr_imagen_base64'] = self::generarQRBase64($contenidoQR);
                
                // Texto institucional
                $bien['gobierno_texto'] = 'GOBIERNO DEL ESTADO DE NAYARIT';
                
                // Detalles del modelo para la etiqueta
                $bien['detalles_modelo'] = self::formatearDetallesModelo($bien);
            }

            Flight::json(ResponseFormatter::success($bienes));

        } catch (\Exception $e) {
            Logger::error("Error al obtener etiquetas QR por aula", [
                'cve_aula' => $cve_aula,
                'error' => $e->getMessage()
            ]);
            Flight::json(ResponseFormatter::error($e->getMessage()), 500);
        }
    }

    /**
     * Genera imagen de código de barras en formato PNG base64
     * Usa la librería picqer/php-barcode-generator
     */
    private static function generarBarcodeBase64(string $codigo): string
    {
        try {
            $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
            $barcode = $generator->getBarcode($codigo, \Picqer\Barcode\BarcodeGenerator::TYPE_CODE_128, 2, 50);
            return 'data:image/png;base64,' . base64_encode($barcode);
        } catch (\Exception $e) {
            Logger::error("Error al generar barcode", ['codigo' => $codigo, 'error' => $e->getMessage()]);
            return '';
        }
    }

    /**
     * Genera imagen de código QR en formato PNG base64
     * Usa la librería endroid/qr-code
     */
    private static function generarQRBase64(string $contenido): string
    {
        try {
            $qrCode = \Endroid\QrCode\Builder\Builder::create()
                ->writer(new \Endroid\QrCode.Writer\PngWriter())
                ->data($contenido)
                ->size(200)
                ->margin(10)
                ->build();
            return 'data:image/png;base64,' . base64_encode($qrCode->getString());
        } catch (\Exception $e) {
            Logger::error("Error al generar QR", ['contenido' => $contenido, 'error' => $e->getMessage()]);
            return '';
        }
    }

    /**
     * Formatea los detalles del modelo para la etiqueta QR
     */
    private static function formatearDetallesModelo(array $bien): string
    {
        $partes = [];
        
        if (!empty($bien['nombre_marca']) && $bien['nombre_marca'] !== 'N/A') {
            $partes[] = $bien['nombre_marca'];
        }
        
        if (!empty($bien['nombre_modelo']) && $bien['nombre_modelo'] !== 'N/A') {
            $partes[] = $bien['nombre_modelo'];
        }
        
        return empty($partes) ? 'Genérico' : implode(' / ', $partes);
    }
}
```

---

## 3. routes.php - Agregar rutas

Agregar al archivo `routes/routes.php`:

### Require del nuevo controlador (al inicio con los demás):
```php
require_once __DIR__ . '/../app/controllers/BarcodesController.php';
```

### Agregar las rutas (después de las rutas de Información):

```php
// =============================================================================
// ETIQUETAS PARA IMPRESIÓN
// =============================================================================

// Obtener todas las aulas
Flight::route('GET /api/v1/aulas', function() {
    \app\controllers\InformacionController::getAulas();
});

// Etiquetas de código de barras por aula
Flight::route('GET /api/v1/barcodes/etiquetas/datos/aula/@cve_aula', function($cve_aula) {
    //if (!authMiddleware()) return;
    \app\controllers\BarcodesController::getEtiquetasBarcodePorAula((int)$cve_aula);
});

// Etiquetas de código QR por aula
Flight::route('GET /api/v1/barcodes/etiquetas/qr/datos/aula/@cve_aula', function($cve_aula) {
    //if (!authMiddleware()) return;
    \app\controllers\BarcodesController::getEtiquetasQRPorAula((int)$cve_aula);
});
```

---

## 4. Dependencias necesarias

```bash
composer require picqer/php-barcode-generator
composer require endroid/qr-code
```

---

## 5. Resumen de archivos a modificar

| Archivo | Acción |
|---------|--------|
| `app/controllers/InformacionController.php` | Agregar método `getAulas()` |
| `app/controllers/BarcodesController.php` | **Crear nuevo archivo** |
| `routes/routes.php` | Agregar 3 rutas y require |

---

## 6. Consultas SQL de verificación

```sql
-- Todas las aulas con edificio
SELECT 
    a.cve_aula,
    a.nombre,
    e.nombre as nombre_edificio
FROM aula a
JOIN edificio e ON a.cve_edificio = e.cve_edificio
ORDER BY e.nombre, a.nombre;

-- Bienes por aula (ejemplo aula 1)
SELECT 
    b.cve_bien,
    b.nombre,
    b.codigo_barras,
    COALESCE(m.nombre_modelo, 'N/A') as nombre_modelo,
    COALESCE(ma.nombre_marca, 'N/A') as nombre_marca,
    a.nombre as nombre_aula,
    e.nombre as nombre_edificio
FROM bienes b
LEFT JOIN modelos m ON b.cve_modelo = m.cve_modelo
LEFT JOIN marcas ma ON m.cve_marca = ma.cve_marca
LEFT JOIN aula a ON b.cve_aula = a.cve_aula
LEFT JOIN edificio e ON a.cve_edificio = e.cve_edificio
WHERE b.cve_aula = 1
AND b.activo = true;
```