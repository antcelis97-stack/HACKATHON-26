# Endpoints — módulo barcode (`/api/v1/barcodes`)

**Base URL (ejemplo):** `http://localhost:8080` (o el host/puerto de tu API)

**Autenticación:** requiere header  
`Authorization: Bearer <token_jwt>`  
porque usa `authMiddleware()` en rutas protegidas.

**Alcance actual:** generación e inserción del bien con código de barras en BD usando 3 segmentos:
`TIPO-FAMILIA-ARTICULO`.

**Tabla `bienes` (requisito de barcode):**
- `codigo_barras varchar(50) unique`
- `nfc varchar(50) unique`
- `codigo_qr varchar(255) unique`
- `no_serie varchar(50) unique`
- `no_factura varchar(50) unique`

---

## Lista de endpoints

| Método | Ruta | Para qué sirve |
|--------|------|----------------|
| `POST` | `/api/v1/barcodes/generar` | Genera barcode y crea un registro en `bienes` con `codigo_barras`. |
| `GET` | `/api/v1/barcodes/imagen` | Devuelve la imagen PNG del barcode usando `cve_tipo`, `cve_familia` y `cve_articulo` por query params. |
| `GET` | `/api/v1/barcodes/etiquetas` | Devuelve una vista HTML de etiquetas en blanco/negro lista para impresión. |

---

## Endpoint disponible

### 1. `generar` — crear bien con código de barras

1. **URL y método:** `POST /api/v1/barcodes/generar`
2. **Qué envían (JSON):**

```json
{
  "nombre": "Laptop Dell de Juan",
  "cve_tipo": 1,
  "cve_familia": 1,
  "cve_articulo": 1
}
```

3. **Regla aplicada en backend:**
- Se resuelven las claves en catálogos:
  - `tipos_bien.clave` (ej. `MC`)
  - `familias_articulos.clave` (ej. `EQ`)
  - `articulos.clave` (ej. `LAP`)
- Se construye: `MC-EQ-LAP`

4. **Qué respondemos (201):**

```json
{
  "success": true,
  "data": {
    "cve_bien": 25,
    "barcode_text": "MC-EQ-LAP",
    "barcode_url": "https://barcode.tec-it.com/barcode.ashx?data=MC-EQ-LAP&code=Code128",
    "segments": {
      "tipo": "MC",
      "familia": "EQ",
      "articulo": "LAP"
    }
  },
  "meta": {
    "timestamp": "2026-04-21T12:00:00+00:00",
    "message": "Recurso creado exitosamente"
  }
}
```

---

## Validaciones y errores

**Validación (400):**
- `nombre` es requerido (máx. 100 caracteres).
- `cve_tipo`, `cve_familia`, `cve_articulo` deben ser enteros positivos.
- La combinación debe existir y ser consistente en base de datos.

Ejemplo:

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Datos inválidos",
    "details": [
      {
        "field": "relacion",
        "message": "La combinacion cve_tipo/cve_familia/cve_articulo no existe o no es consistente"
      }
    ]
  },
  "meta": {
    "timestamp": "2026-04-21T12:00:00+00:00"
  }
}
```

**Autorización:**
- **401** si falta token o es inválido.

**Conflicto (409):**
- si `codigo_barras` ya existe en `bienes` (columna única).

---

### 2. `imagen` — devolver imagen lista para frontend

1. **URL y método:**  
   `GET /api/v1/barcodes/imagen?cve_tipo=1&cve_familia=1&cve_articulo=1`
2. **Qué responde (200):** binario PNG (`Content-Type: image/png`)
3. **Uso en frontend:** se puede asignar directo a `src` de una etiqueta `<img>`.

Ejemplo:

```html
<img src="/api/v1/barcodes/imagen?cve_tipo=1&cve_familia=1&cve_articulo=1" alt="Codigo de barras">
```

**Errores comunes:**
- **400** si faltan parámetros o son inválidos.
- **401** si no se envía token válido.
- **502** si falla la respuesta del proveedor externo.

---

### 3. `etiquetas` — vista de tarjetas para impresión

1. **URL y método:**  
   `GET /api/v1/barcodes/etiquetas?limit=12`
2. **Qué hace:** genera una cuadrícula HTML de etiquetas en escala de grises, con:
   - encabezado principal sin colores,
   - nombre del bien,
   - subtítulo del artículo,
   - imagen de código de barras,
   - texto del código.
3. **Fuente de datos:** registros activos de `bienes` (con joins a catálogos para texto complementario).
4. **Salida:** `Content-Type: text/html; charset=UTF-8`.

**Notas:**
- `limit` es opcional (por defecto 12, máximo 60).
- Incluye botón `Imprimir etiquetas` (`window.print()`).

---

## Próxima fase recomendada

Agregar sufijo de 4 dígitos para unicidad:
- Formato futuro: `TIPO-FAMILIA-ARTICULO-0001`
- Manteniendo el mismo endpoint y contrato de salida.
