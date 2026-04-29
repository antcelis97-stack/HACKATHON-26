# Documentación API — módulo profesor (para Frontend)

Documentación generada siguiendo la plantilla de equipo: **Endpoint**, **Request**, **Response** (éxito + error 400 o 500), **Nota técnica** y **código del endpoint** en Flight PHP.

**Contexto:** Backend Senior, API Flight PHP. Todas las rutas bajo `/api/v1/profesor/*` exigen header `Authorization: Bearer <token>` y rol **Profesor** (`ProfesorMiddleware::requireProfesorRole`). Sin token válido → **401**; token válido pero no es profesor → **403**.

**Base URL (ejemplo):** `https://tu-servidor` o `http://localhost:8081`

---

## 1. Perfil / contexto del profesor

**Endpoint:** `GET /api/v1/profesor/contexto`

**Código del endpoint:**

```php
Flight::route('GET /api/v1/profesor/contexto', function() {
    if (!\App\Lib\ProfesorMiddleware::requireProfesorRole()) return;
    \app\controllers\ProfesorController::contexto();
});
```

**Request:** No aplica cuerpo JSON. Sin query obligatoria.

**Response (éxito — 200):**

```json
{
  "success": true,
  "data": {
    "id": "2",
    "usuario": "profesor",
    "nombre": "Profesor de Ejemplo",
    "email": "profesor@universidad.edu",
    "activo": true,
    "created_at": "2026-04-17 10:00:00",
    "rol_id": "2",
    "rol_nombre": "Profesor",
    "rol_descripcion": "Acceso a módulos de profesor"
  },
  "meta": { "timestamp": "2026-04-17T19:00:00+00:00" }
}
```

**Response (error — 401 sin Bearer):**

```json
{
  "success": false,
  "error": {
    "code": "UNAUTHORIZED",
    "message": "Token requerido"
  },
  "meta": { "timestamp": "2026-04-17T19:00:00+00:00" }
}
```

**Nota técnica:** Lee `public.usuarios` unido a `public.roles` usando el `user_id` del JWT. Sirve para cabecera del módulo y comprobar sesión.

---

## 2. Listar préstamos (historial)

**Endpoint:** `GET /api/v1/profesor/prestamos`

**Código del endpoint:**

```php
Flight::route('GET /api/v1/profesor/prestamos', function() {
    if (!\App\Lib\ProfesorMiddleware::requireProfesorRole()) return;
    \app\controllers\ProfesorController::listarPrestamos();
});
```

**Request:** No hay body. Query opcional: `page`, `limit` (ej. `?page=1&limit=20`).

**Response (éxito — 200):**

```json
{
  "success": true,
  "data": [],
  "meta": {
    "timestamp": "2026-04-17T19:00:00+00:00",
    "pagination": {
      "page": 1,
      "limit": 20,
      "total": 0,
      "total_pages": 0
    }
  }
}
```

**Response (error — 500 fallo de base de datos):**

```json
{
  "success": false,
  "error": {
    "code": "ERROR",
    "message": "could not connect to server"
  },
  "meta": { "timestamp": "2026-04-17T19:00:00+00:00" }
}
```

**Nota técnica:** Devuelve lista paginada del historial del profesor. Hoy `data` puede ir vacío hasta existir tabla de préstamos y lógica de persistencia.

---

## 3. Solicitar préstamo

**Endpoint:** `POST /api/v1/profesor/prestamos`

**Código del endpoint:**

```php
Flight::route('POST /api/v1/profesor/prestamos', function() {
    if (!\App\Lib\ProfesorMiddleware::requireProfesorRole()) return;
    \app\controllers\ProfesorController::crearPrestamo();
});
```

**Request (ejemplo contrato; el backend validará campos cuando exista persistencia):**

```json
{
  "bien_id": 15,
  "motivo": "uso en laboratorio",
  "fecha_devolucion_prevista": "2026-06-30",
  "observaciones": ""
}
```

**Response (éxito futuro — 201):** *(aún no implementado; hoy se responde 501.)*

```json
{
  "success": true,
  "data": { "id": 101 },
  "meta": {
    "timestamp": "2026-04-17T19:00:00+00:00",
    "message": "Recurso creado exitosamente"
  }
}
```

**Response (error — 400 JSON inválido):**

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Datos inválidos",
    "details": [
      { "field": "body", "message": "JSON inválido" }
    ]
  },
  "meta": { "timestamp": "2026-04-17T19:00:00+00:00" }
}
```

**Response (error — 501 no implementado):**

```json
{
  "success": false,
  "error": {
    "code": "NOT_IMPLEMENTED",
    "message": "Persistencia de préstamos pendiente: agregar migración y tabla en PostgreSQL (ver docs/MODULO_PROFESOR_API.md)."
  },
  "meta": { "timestamp": "2026-04-17T19:00:00+00:00" }
}
```

**Nota técnica:** Recibe el cuerpo JSON de la solicitud. Mientras no haya migración de préstamos, responde **501** tras validar que el JSON sea parseable.

---

## 4. Listar aulas a cargo

**Endpoint:** `GET /api/v1/profesor/aulas`

**Código del endpoint:**

```php
Flight::route('GET /api/v1/profesor/aulas', function() {
    if (!\App\Lib\ProfesorMiddleware::requireProfesorRole()) return;
    \app\controllers\ProfesorController::listarAulas();
});
```

**Request:** Sin body. Query opcional: `page`, `limit`.

**Response (éxito — 200):** Misma forma que préstamos: `data` array + `meta.pagination`.

**Response (error — 500):** Igual estructura `{ "success": false, "error": { "code": "ERROR", "message": "..." } }`.

**Nota técnica:** Pensado para la vista “aulas encargadas”. Lista vacía hasta modelo de datos y consultas.

---

## 5. Listar inventario del profesor

**Endpoint:** `GET /api/v1/profesor/inventario`

**Código del endpoint:**

```php
Flight::route('GET /api/v1/profesor/inventario', function() {
    if (!\App\Lib\ProfesorMiddleware::requireProfesorRole()) return;
    \app\controllers\ProfesorController::listarInventario();
});
```

**Request:** Sin body. Query: `page`, `limit` opcionales.

**Response (éxito — 200):** Paginado; `data` vacío hasta integrar bienes/inventario.

**Response (error — 500):** Formato estándar `ResponseFormatter::error`.

**Nota técnica:** Tabla de inventario asociada al profesor en el front.

---

## 6. Listar inmobiliaria

**Endpoint:** `GET /api/v1/profesor/inmobiliaria`

**Código del endpoint:**

```php
Flight::route('GET /api/v1/profesor/inmobiliaria', function() {
    if (!\App\Lib\ProfesorMiddleware::requireProfesorRole()) return;
    \app\controllers\ProfesorController::listarInmobiliaria();
});
```

**Request:** Sin body. Query: `page`, `limit` opcionales.

**Response (éxito — 200):** Paginado; `data` vacío hasta migración.

**Response (error — 500):** Mismo patrón de error genérico.

**Nota técnica:** Registro de espacios o inmuebles bajo responsabilidad del profesor según negocio.

---

## 7. Listar notificaciones

**Endpoint:** `GET /api/v1/profesor/notificaciones`

**Código del endpoint:**

```php
Flight::route('GET /api/v1/profesor/notificaciones', function() {
    if (!\App\Lib\ProfesorMiddleware::requireProfesorRole()) return;
    \app\controllers\ProfesorController::listarNotificaciones();
});
```

**Request:** Sin body.

**Response (éxito — 200):**

```json
{
  "success": true,
  "data": {
    "items": [],
    "no_leidas": 0
  },
  "meta": { "timestamp": "2026-04-17T19:00:00+00:00" }
}
```

**Response (error — 500):**

```json
{
  "success": false,
  "error": {
    "code": "ERROR",
    "message": "Error interno al consultar notificaciones"
  },
  "meta": { "timestamp": "2026-04-17T19:00:00+00:00" }
}
```

**Nota técnica:** Alimenta campana/badge con `items` y `no_leidas`. Stub hasta tabla de notificaciones.

---

## 8. Marcar notificación como leída

**Endpoint:** `PATCH /api/v1/profesor/notificaciones/{id}/leida`  
*(En código Flight: `@id` → número en la URL, ej. `/notificaciones/8/leida`.)*

**Código del endpoint:**

```php
Flight::route('PATCH /api/v1/profesor/notificaciones/@id/leida', function($id) {
    if (!\App\Lib\ProfesorMiddleware::requireProfesorRole()) return;
    \app\controllers\ProfesorController::marcarNotificacionLeida((int) $id);
});
```

**Request (opcional):**

```json
{}
```

**Response (éxito futuro — 200):** *(pendiente de implementación; hoy 501 o 400 si id inválido.)*

```json
{
  "success": true,
  "data": { "id": 8, "leida": true },
  "meta": { "timestamp": "2026-04-17T19:00:00+00:00" }
}
```

**Response (error — 400 id inválido):**

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Datos inválidos",
    "details": [
      { "field": "id", "message": "Identificador inválido" }
    ]
  },
  "meta": { "timestamp": "2026-04-17T19:00:00+00:00" }
}
```

**Response (error — 501):**

```json
{
  "success": false,
  "error": {
    "code": "NOT_IMPLEMENTED",
    "message": "Marcar notificación pendiente de tabla de notificaciones (ver docs/MODULO_PROFESOR_API.md)."
  },
  "meta": { "timestamp": "2026-04-17T19:00:00+00:00" }
}
```

**Nota técnica:** Actualiza estado de lectura para bajar contador de no leídas en UI.

---

## 9. Reporte individual de un bien

**Endpoint:** `GET /api/v1/profesor/reportes/bien/{id}`

**Código del endpoint:**

```php
Flight::route('GET /api/v1/profesor/reportes/bien/@id', function($id) {
    if (!\App\Lib\ProfesorMiddleware::requireProfesorRole()) return;
    \app\controllers\ProfesorController::reporteBien((int) $id);
});
```

**Request:** Sin body.

**Response (éxito futuro — 200):**

```json
{
  "success": true,
  "data": {
    "id": 12,
    "nombre": "proyector",
    "numero_inventario": "INV-012",
    "estado_fisico": "bueno",
    "aula_id": 3
  },
  "meta": { "timestamp": "2026-04-17T19:00:00+00:00" }
}
```

**Response (error — 404 sin catálogo / no encontrado):**

```json
{
  "success": false,
  "error": {
    "code": "NOT_FOUND",
    "message": "Bien no encontrado"
  },
  "meta": { "timestamp": "2026-04-17T19:00:00+00:00" }
}
```

**Nota técnica:** Ficha o reporte de un bien por identificador. Hoy responde **404** hasta existir catálogo de bienes.

---

## 10. Listar auditoría del usuario

**Endpoint:** `GET /api/v1/profesor/auditoria`

**Código del endpoint:**

```php
Flight::route('GET /api/v1/profesor/auditoria', function() {
    if (!\App\Lib\ProfesorMiddleware::requireProfesorRole()) return;
    \app\controllers\ProfesorController::listarAuditoria();
});
```

**Request:** Sin body. Query: `page`, `limit` opcionales.

**Response (éxito — 200):**

```json
{
  "success": true,
  "data": [
    {
      "id": "1",
      "user_id": "2",
      "accion": "create",
      "tabla": "empleados",
      "registro_id": "5",
      "datos_anteriores": null,
      "datos_nuevos": { "nombre": "ejemplo" },
      "created_at": "2026-04-17 12:30:00"
    }
  ],
  "meta": {
    "timestamp": "2026-04-17T19:00:00+00:00",
    "pagination": {
      "page": 1,
      "limit": 20,
      "total": 1,
      "total_pages": 1
    }
  }
}
```

**Response (error — 500):**

```json
{
  "success": false,
  "error": {
    "code": "ERROR",
    "message": "SQLSTATE[08006] ..."
  },
  "meta": { "timestamp": "2026-04-17T19:00:00+00:00" }
}
```

**Nota técnica:** Consulta `public.audit_log` filtrando por `user_id` del token. Paginado estándar del proyecto.

---

## 11. Exportar auditoría (JSON)

**Endpoint:** `POST /api/v1/profesor/auditoria/export`  
*(Opcional en query: `limit` entre 1 y 1000, default 500.)*

**Código del endpoint:**

```php
Flight::route('POST /api/v1/profesor/auditoria/export', function() {
    if (!\App\Lib\ProfesorMiddleware::requireProfesorRole()) return;
    \app\controllers\ProfesorController::exportarAuditoria();
});
```

**Request:**

```json
{}
```

**Response (éxito — 200):**

```json
{
  "success": true,
  "data": {
    "formato": "json",
    "registros": [
      {
        "id": "1",
        "user_id": "2",
        "accion": "create",
        "tabla": "empleados",
        "registro_id": "5",
        "datos_anteriores": null,
        "datos_nuevos": {},
        "created_at": "2026-04-17 12:30:00"
      }
    ],
    "total_devuelto": 1
  },
  "meta": { "timestamp": "2026-04-17T19:00:00+00:00" }
}
```

**Response (error — 500):**

```json
{
  "success": false,
  "error": {
    "code": "ERROR",
    "message": "Error al ejecutar consulta"
  },
  "meta": { "timestamp": "2026-04-17T19:00:00+00:00" }
}
```

**Nota técnica:** Devuelve un bloque amplio de filas de auditoría del mismo usuario; pensado para export / informe.

---

## Rol no autorizado (403) — común a todas

Si el JWT es válido pero el usuario **no** tiene rol `Profesor`:

```json
{
  "success": false,
  "error": {
    "code": "FORBIDDEN",
    "message": "Solo el rol Profesor puede acceder a este módulo"
  },
  "meta": { "timestamp": "2026-04-17T19:00:00+00:00" }
}
```

---

## Referencias en el repo

| Recurso | Ubicación |
|---------|-----------|
| Rutas Flight | `routes/routes.php` |
| Controlador | `app/controllers/ProfesorController.php` |
| Middleware rol | `app/Lib/ProfesorMiddleware.php` |
| Tabla resumen + ejemplos extendidos | `docs/PROFESOR_ENDPOINTS.md` |
