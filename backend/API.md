# API Reference - SIEst Sandbox

## Base URL

```
Desarrollo: http://localhost:8080
```

## Authentication

### Login
```
POST /api/v1/login
Content-Type: application/json

{
  "usuario": "admin",
  "contrasena": "password"
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIs...",
    "refresh_token": "abc123...",
    "expires_in": 3600,
    "usuario": {
      "id": 1,
      "usuario": "admin",
      "nombre": "Administrador del Sistema",
      "rol": "Administrador"
    }
  },
  "meta": {
    "timestamp": "2024-04-16T10:30:00-06:00"
  }
}
```

### Refresh Token
```
POST /api/v1/refresh-token
Content-Type: application/json

{
  "refresh_token": "abc123..."
}
```

---

## Endpoints

### Health Check
```
GET /
```
Sin autenticación.

**Response (200):**
```json
{
  "success": true,
  "data": {
    "message": "SIEst Sandbox API",
    "version": "1.0.0"
  }
}
```

---

### Listar Empleados
```
GET /api/v1/empleados
```

Sin autenticación. Soporta paginación y filtros.

**Query Parameters:**
| Parámetro | Tipo | Default | Descripción |
|-----------|------|---------|-------------|
| `page` | int | 1 | Número de página |
| `limit` | int | 20 | Items por página (max: 100) |
| `search` | string | - | Buscar por nombre o número |
| `area` | string | - | Filtrar por área |
| `activo` | bool | - | Filtrar por estado |

**Ejemplo:**
```
GET /api/v1/empleados?page=1&limit=10&area=Inform%C3%A1tica
```

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "numero_empleado": "EMP001",
      "nombre_completo": "Juan Pérez García",
      "email": "juan.perez@universidad.edu",
      "area": "Informática",
      "activo": true
    }
  ],
  "meta": {
    "timestamp": "2024-04-16T10:30:00-06:00",
    "pagination": {
      "page": 1,
      "limit": 10,
      "total": 50,
      "total_pages": 5
    }
  }
}
```

---

### Ver Empleado
```
GET /api/v1/empleados/{id}
```

Sin autenticación.

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "numero_empleado": "EMP001",
    "nombre_completo": "Juan Pérez García",
    "email": "juan.perez@universidad.edu",
    "telefono": "5512345678",
    "area": "Informática",
    "puesto": "Director",
    "activo": true,
    "created_at": "2024-01-01T00:00:00",
    "updated_at": "2024-01-01T00:00:00"
  }
}
```

**Response (404):**
```json
{
  "success": false,
  "error": {
    "code": "NOT_FOUND",
    "message": "Empleado no encontrado"
  }
}
```

---

### Crear Empleado
```
POST /api/v1/empleados
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "numero_empleado": "EMP009",
  "nombre_completo": "Nuevo Empleado",
  "email": "nuevo@universidad.edu",
  "telefono": "5512345686",
  "area": "Administración",
  "puesto": "Auxiliar",
  "activo": true
}
```

**Campos requeridos:** `numero_empleado`, `nombre_completo`

**Response (201):**
```json
{
  "success": true,
  "data": {
    "id": 9
  },
  "meta": {
    "timestamp": "2024-04-16T10:30:00-06:00",
    "message": "Recurso creado exitosamente"
  }
}
```

**Response (400) - Validación:**
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Datos inválidos",
    "details": [
      {"field": "numero_empleado", "message": "Número de empleado requerido"}
    ]
  }
}
```

---

### Actualizar Empleado
```
PUT /api/v1/empleados/{id}
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "nombre_completo": "Nombre Actualizado",
  "area": "Nueva Área"
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1
  },
  "meta": {
    "timestamp": "2024-04-16T10:30:00-06:00"
  }
}
```

---

### Eliminar Empleado
```
DELETE /api/v1/empleados/{id}
Authorization: Bearer {token}
```

Soft delete: cambia `activo` a `false`.

**Response (204):**
Sin body.

---

### Generar Barcode (3 campos)
```
POST /api/v1/barcodes/generar
Authorization: Bearer {token}
Content-Type: application/json
```

Genera el texto base de barcode con formato `TIPO-FAMILIA-ARTICULO` usando claves de catálogos e inserta el registro en `bienes`.

**Request Body:**
```json
{
  "nombre": "Laptop Dell de Juan",
  "cve_tipo": 1,
  "cve_familia": 1,
  "cve_articulo": 1
}
```

**Response (201):**
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

### Obtener Imagen Barcode (3 campos)
```
GET /api/v1/barcodes/imagen?cve_tipo=1&cve_familia=1&cve_articulo=1
Authorization: Bearer {token}
```

Devuelve directamente la imagen PNG del código de barras construido con formato `TIPO-FAMILIA-ARTICULO`.

**Response (200):**
- `Content-Type: image/png`
- Body binario con la imagen.

---

## Response Format

### Success
```json
{
  "success": true,
  "data": { ... },
  "meta": {
    "timestamp": "ISO 8601"
  }
}
```

### Error
```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Mensaje descriptivo",
    "details": []
  },
  "meta": {
    "timestamp": "ISO 8601"
  }
}
```

## Error Codes

| Code | HTTP | Descripción |
|------|------|-------------|
| `VALIDATION_ERROR` | 400 | Datos de entrada inválidos |
| `UNAUTHORIZED` | 401 | Token requerido o inválido |
| `FORBIDDEN` | 403 | Sin permisos |
| `NOT_FOUND` | 404 | Recurso no encontrado |
| `ERROR` | 500 | Error interno |

## Status Codes

| Código | Descripción |
|--------|-------------|
| 200 | OK |
| 201 | Creado |
| 204 | Sin contenido |
| 400 | Bad Request |
| 401 | Unauthorized |
| 404 | Not Found |
| 500 | Server Error |

---

## Ejemplos con cURL

### Login
```bash
curl -X POST http://localhost:8080/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"usuario":"admin","contrasena":"password"}'
```

### Listar Empleados
```bash
curl http://localhost:8080/api/v1/empleados
```

### Crear Empleado (con auth)
```bash
curl -X POST http://localhost:8080/api/v1/empleados \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIs..." \
  -H "Content-Type: application/json" \
  -d '{"numero_empleado":"EMP010","nombre_completo":"Test User"}'
```

### Actualizar Empleado
```bash
curl -X PUT http://localhost:8080/api/v1/empleados/1 \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIs..." \
  -H "Content-Type: application/json" \
  -d '{"area":"Sistemas"}'
```

### Eliminar Empleado
```bash
curl -X DELETE http://localhost:8080/api/v1/empleados/1 \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIs..."
```
