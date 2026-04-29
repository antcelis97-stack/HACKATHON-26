# Endpoints — módulo profesor (`/api/v1/profesor`)

**Base URL (ejemplo):** `http://localhost:8081` (o el host/puerto de tu API)

**Autenticación:** todas las rutas requieren header  
`Authorization: Bearer <token_jwt>`  
con usuario de rol **Profesor** (si no, respuesta **403**).  
El token se obtiene con `POST /api/v1/login` (ver `docs/MODULO_PROFESOR_API.md`).

**CORS / JSON:** según `routes/routes.php`; cuerpos con `Content-Type: application/json` cuando aplique.

---

## Lista de endpoints

| Método | Ruta | Para qué sirve |
|--------|------|----------------|
| `GET` | `/api/v1/profesor/contexto` | Devuelve el perfil del usuario logueado (nombre, email, rol, etc.) desde PostgreSQL. Sirve para cabecera del módulo y validar que la sesión sigue viva. |
| `GET` | `/api/v1/profesor/prestamos` | Lista paginada del **historial de préstamos** del profesor. Cuando exista la tabla, alimenta la pantalla de historial. |
| `POST` | `/api/v1/profesor/prestamos` | **Solicitar un préstamo** nuevo (enviar JSON con los campos acordados). Equivale al formulario de solicitud. |
| `GET` | `/api/v1/profesor/aulas` | **Aulas o espacios** donde el profesor es responsable/encargado. Vista “aulas encargadas”. |
| `GET` | `/api/v1/profesor/inventario` | **Inventario** asociado al profesor (bienes por aula/área según reglas de negocio). Tabla tipo “inventario profesor”. |
| `GET` | `/api/v1/profesor/inmobiliaria` | **Registro de inmobiliaria** / espacios bajo su responsabilidad (según el modelo de datos del proyecto). |
| `GET` | `/api/v1/profesor/notificaciones` | **Notificaciones** (p. ej. préstamos pendientes de aprobar): lista + contador de no leídas para **campana / badge**. |
| `PATCH` | `/api/v1/profesor/notificaciones/{id}/leida` | **Marcar una notificación como leída** (baja el contador al abrir o descartar). |
| `GET` | `/api/v1/profesor/reportes/bien/{id}` | **Reporte o ficha** de un bien concreto por `id` (detalle individual, QR, etc.). |
| `GET` | `/api/v1/profesor/auditoria` | Registros de **`audit_log`** donde el usuario del token es el actor (`user_id`). Lista paginada para revisar acciones vinculadas a su cuenta. |
| `POST` | `/api/v1/profesor/auditoria/export` | Misma lógica de auditoría en **bloque JSON** (export / informe amplio sin paginar tanto como la lista). |

**Estado actual del backend:** varios listados siguen vacíos o con **501/404** hasta que existan migraciones (préstamos, bienes, notificaciones). `contexto` y `auditoria` usan tablas ya presentes en el sandbox cuando PostgreSQL está bien configurado.

**Nota:** En `routes/routes.php` las rutas con parámetro usan `@id` en Flight; en la URL real es el segmento numérico (ej. `.../notificaciones/5/leida`, `.../reportes/bien/12`).

---

## Documentación técnica breve (Frontend)

Formato común de **éxito**: `{ "success": true, "data": …, "meta": { "timestamp": "…" } }`.  
Listas **paginadas**: además `meta.pagination` con `page`, `limit`, `total`, `total_pages`.  
**Error**: `{ "success": false, "error": { "code": "…", "message": "…" }, "meta": { "timestamp": "…" } }`.  
Validación (400): `error.details` es un array de `{ "field", "message" }`.

Los ejemplos usan **campos en minúsculas**; fechas son ilustrativas (ISO 8601).

---

### 1. `contexto` — perfil del profesor autenticado

1. **URL y método:** `GET /api/v1/profesor/contexto`
2. **Qué envían:** sin cuerpo JSON. Opcional en query: nada requerido.
3. **Qué respondemos (200):**

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
  "meta": {
    "timestamp": "2026-04-17T19:00:00+00:00"
  }
}
```

---

### 2. `listarPrestamos` — historial de préstamos (paginado)

1. **URL y método:** `GET /api/v1/profesor/prestamos?page=1&limit=20`
2. **Qué envían:** sin cuerpo. Query opcional: `page` (default 1), `limit` (default 20, máx. 100).
3. **Qué respondemos (200, lista vacía hoy):**

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

*(Cuando exista persistencia, `data` será un arreglo de objetos con campos como `id`, `fecha_solicitud`, `estado`, `bien_id`, etc., acordados con backend.)*

---

### 3. `crearPrestamo` — solicitar préstamo

1. **URL y método:** `POST /api/v1/profesor/prestamos`
2. **Qué envían (ejemplo contrato futuro; hoy el backend responde 501 hasta migración):**

```json
{
  "bien_id": 15,
  "motivo": "curso de laboratorio semestre actual",
  "fecha_devolucion_prevista": "2026-06-30",
  "observaciones": "opcional"
}
```

3. **Qué respondemos hoy (501):**

```json
{
  "success": false,
  "error": {
    "code": "NOT_IMPLEMENTED",
    "message": "Persistencia de préstamos pendiente: agregar migración y tabla en PostgreSQL (ver docs/MODULO_PROFESOR_API.md)."
  },
  "meta": {
    "timestamp": "2026-04-17T19:00:00+00:00"
  }
}
```

*(Cuando esté implementado, éxito típico 201 con `data`: `{ "id": 123 }`.)*

**JSON inválido (400):**

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

---

### 4. `listarAulas` — aulas encargadas

1. **URL y método:** `GET /api/v1/profesor/aulas?page=1&limit=20`
2. **Qué envían:** sin cuerpo. Query: `page`, `limit` (opcionales).
3. **Qué respondemos (200):** mismo esquema que préstamos: `data` array (vacío por ahora) + `meta.pagination`.

---

### 5. `listarInventario` — inventario del profesor

1. **URL y método:** `GET /api/v1/profesor/inventario?page=1&limit=20`
2. **Qué envían:** sin cuerpo. Query: `page`, `limit` (opcionales).
3. **Qué respondemos (200):** paginado; `data` vacío hasta migración.

---

### 6. `listarInmobiliaria` — registro inmobiliaria

1. **URL y método:** `GET /api/v1/profesor/inmobiliaria?page=1&limit=20`
2. **Qué envían:** sin cuerpo. Query: `page`, `limit` (opcionales).
3. **Qué respondemos (200):** paginado; `data` vacío hasta migración.

---

### 7. `listarNotificaciones` — campana / badge

1. **URL y método:** `GET /api/v1/profesor/notificaciones`
2. **Qué envían:** sin cuerpo.
3. **Qué respondemos (200):**

```json
{
  "success": true,
  "data": {
    "items": [],
    "no_leidas": 0
  },
  "meta": {
    "timestamp": "2026-04-17T19:00:00+00:00"
  }
}
```

*(Cuando exista tabla, `items` tendrá objetos como `id`, `tipo`, `mensaje`, `leida`, `creado_en`, `prestamo_id`, etc.)*

---

### 8. `marcarNotificacionLeida`

1. **URL y método:** `PATCH /api/v1/profesor/notificaciones/{id}/leida` (ej. `.../notificaciones/8/leida`)
2. **Qué envían:** cuerpo vacío u objeto vacío aceptable:

```json
{}
```

3. **Qué respondemos hoy (501):**

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

**`id` inválido (400):** `error.code` `VALIDATION_ERROR`, `details` con `field: "id"`.

---

### 9. `reporteBien` — ficha de un bien

1. **URL y método:** `GET /api/v1/profesor/reportes/bien/{id}` (ej. `.../reportes/bien/12`)
2. **Qué envían:** sin cuerpo.
3. **Qué respondemos hoy (404):**

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

*(Con catálogo implementado, 200 con `data` tipo `{ "id": 12, "nombre": "…", "estado_fisico": "…", "aula_id": 3 }`.)*

---

### 10. `listarAuditoria` — bitácora de auditoría del usuario

1. **URL y método:** `GET /api/v1/profesor/auditoria?page=1&limit=20`
2. **Qué envían:** sin cuerpo. Query: `page`, `limit` (opcionales).
3. **Qué respondemos (200, ejemplo con un registro):**

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

---

### 11. `exportarAuditoria` — export JSON de auditoría

1. **URL y método:** `POST /api/v1/profesor/auditoria/export?limit=500`  
   *(El backend lee `limit` desde query; máximo 1000; default 500.)*
2. **Qué envían:** cuerpo puede ir vacío:

```json
{}
```

3. **Qué respondemos (200):**

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
  "meta": {
    "timestamp": "2026-04-17T19:00:00+00:00"
  }
}
```

---

## Referencia

- Contrato general: `docs/MODULO_PROFESOR_API.md`
- Registro en código: `routes/routes.php` (bloque **MÓDULO PROFESOR**)
- Handlers: `app/controllers/ProfesorController.php`
