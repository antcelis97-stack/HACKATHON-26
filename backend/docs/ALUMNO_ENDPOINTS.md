# Endpoints — módulo alumno (`/api/v1/alumno`)

**Base URL (ejemplo):** `http://localhost:8080` (o el host/puerto de tu API)

**Autenticación:** todas las rutas requieren header  
`Authorization: Bearer <token_jwt>`  
con usuario de rol **Estudiante** (nombre del rol en `public.roles`, usuario de prueba `alumno`). Si el token falta, es inválido o el rol no es Estudiante → **401** / **403**.  
El token se obtiene con `POST /api/v1/login` (ver `docs/ARCHITECTURE.md`).

**Nota de producto:** En la interfaz el módulo se llama “Alumno”; en el JWT y en base de datos el rol del sandbox es **Estudiante**.

**CORS / JSON:** según `routes/routes.php`; cuerpos con `Content-Type: application/json` cuando aplique.

---

## Lista de endpoints

| Método | Ruta | Para qué sirve |
|--------|------|----------------|
| `GET` | `/api/v1/alumno/contexto` | Perfil del usuario logueado (nombre, email, rol, etc.) desde PostgreSQL. Cabecera del módulo y comprobación de sesión. |
| `GET` | `/api/v1/alumno/prestamos` | **Historial de préstamo** del alumno: lista paginada. |
| `POST` | `/api/v1/alumno/prestamos` | **Solicitar préstamo** (cuerpo JSON según negocio). |

**Estado actual del backend:** `prestamos` devuelve lista vacía y `POST` responde **501** hasta existir migración y persistencia (mismo enfoque que el módulo profesor). `contexto` funciona cuando PostgreSQL está configurado.

En `routes/routes.php` los parámetros dinámicos de Flight usan `@id`; aquí no hay rutas con parámetro en el path.

---

## Formato de respuestas

Éxito: `{ "success": true, "data": …, "meta": { "timestamp": "…" } }`.  
Listas paginadas: `meta.pagination` con `page`, `limit`, `total`, `total_pages`.  
Error: `{ "success": false, "error": { "code": "…", "message": "…" }, "meta": { "timestamp": "…" } }`.  
Validación (400): `error.details` como arreglo de `{ "field", "message" }`.

---

### 1. `contexto` — perfil del alumno autenticado

1. **URL y método:** `GET /api/v1/alumno/contexto`
2. **Qué envían:** sin cuerpo.
3. **Qué respondemos (200):**

```json
{
  "success": true,
  "data": {
    "id": "3",
    "usuario": "alumno",
    "nombre": "Estudiante de Ejemplo",
    "email": "alumno@universidad.edu",
    "activo": true,
    "created_at": "2026-04-19 10:00:00",
    "rol_id": "3",
    "rol_nombre": "Estudiante",
    "rol_descripcion": "Acceso básico a módulos de estudiante"
  },
  "meta": {
    "timestamp": "2026-04-19T12:00:00+00:00"
  }
}
```

---

### 2. `listarPrestamos` — historial de préstamo (paginado)

1. **URL y método:** `GET /api/v1/alumno/prestamos?page=1&limit=20`
2. **Qué envían:** sin cuerpo. Query opcional: `page` (default 1), `limit` (default 20, máx. 100).
3. **Qué respondemos (200, lista vacía hasta migración):**

```json
{
  "success": true,
  "data": [],
  "meta": {
    "timestamp": "2026-04-19T12:00:00+00:00",
    "pagination": {
      "page": 1,
      "limit": 20,
      "total": 0,
      "total_pages": 0
    }
  }
}
```

*(Cuando exista persistencia, `data` será un arreglo de objetos con campos como `id`, `fecha_solicitud`, `estado`, `bien_id`, etc., filtrados por el usuario autenticado.)*

---

### 3. `crearPrestamo` — solicitar préstamo

1. **URL y método:** `POST /api/v1/alumno/prestamos`
2. **Qué envían (ejemplo de contrato futuro; hoy el backend responde 501):**

```json
{
  "bien_id": 15,
  "motivo": "proyecto de semestre",
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
    "message": "Persistencia de préstamos pendiente: agregar migración y tabla en PostgreSQL (ver docs/ALUMNO_ENDPOINTS.md)."
  },
  "meta": {
    "timestamp": "2026-04-19T12:00:00+00:00"
  }
}
```

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
  "meta": { "timestamp": "2026-04-19T12:00:00+00:00" }
}
```

*(Cuando esté implementado, éxito típico **201** con `data`: `{ "id": 123 }`.)*

---

## Errores de autorización

**401** — sin `Authorization`, token mal formado o JWT inválido/expirado (mismo comportamiento que el resto de la API).

**403** — token válido pero el claim `rol` del JWT no es `Estudiante` (p. ej. usuario Profesor o Administrador).

---

## Referencias

- Rutas: `routes/routes.php` (bloque **MÓDULO ALUMNO**).
- Controlador: `app/controllers/AlumnoController.php`.
- Middleware de rol: `App\Lib\AlumnoMiddleware::requireAlumnoRole()` en `app/Lib/AlumnoMiddleware.php`.
