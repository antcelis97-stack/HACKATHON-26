# Módulo Profesor — API y conexión con el frontend

## QUICKREF
- jwt, bearer, `/api/v1/profesor`, flight, postgresql
- contrato json, `ResponseFormatter`, rutas en `routes/routes.php`
- lectura/escritura según `docs/WORKFLOW.md` y `docs/ARCHITECTURE.md`

---

## Objetivo

Documentar cómo el **módulo de profesor en el frontend** obtiene datos del backend y envía datos al backend, **sin duplicar** la arquitectura general: esta guía complementa `ARCHITECTURE.md` y sigue el protocolo de `WORKFLOW.md` (nuevo endpoint → controller + ruta; nueva tabla → migración + seeds si aplica). Los estándares de código están en `STANDARDS.md` (raíz del repo).

---

## Autenticación (obligatoria para el módulo profesor)

1. **Login** (público): `POST /api/v1/login` con cuerpo JSON `usuario` y `contrasena` (ver `ARCHITECTURE.md` — credenciales de prueba; rol **Profesor** con usuario `profesor`).
2. Respuesta incluye `token` (JWT de acceso) y metadatos del usuario (incluye `rol`).
3. **Peticiones al módulo profesor**: header  
   `Authorization: Bearer <token>`  
   y `Content-Type: application/json` cuando el cuerpo sea JSON.

El JWT incluye entre otros: `sub` (id de usuario en PostgreSQL), `usuario`, `rol` (nombre del rol, p. ej. `Profesor`). El middleware de rutas protegidas valida el token antes de ejecutar el controller (patrón descrito en `routes/routes.php` y en `CODIGO_EJEMPLO.md`).

---

## CORS y origen del frontend

Las cabeceras CORS están configuradas en `routes/routes.php` (origen permitido para desarrollo, p. ej. `http://localhost:4200`). El equipo del frontend debe alinear la URL del cliente con lo definido ahí o ampliar CORS en el mismo archivo si el entorno cambia.

---

## Formato de respuestas JSON

Usar siempre el mismo criterio que el resto de la API (`App\Lib\ResponseFormatter` y `Flight::json()` en los controllers):

| Situación | Forma típica de respuesta |
|-----------|---------------------------|
| Éxito con datos | `{ "success": true, "data": { ... }, "meta": { "timestamp": "..." } }` |
| Lista paginada | `success`, `data` (array), `meta.pagination` (page, limit, total, total_pages) |
| Creado | HTTP 201, `success`, `data` (p. ej. `{ "id": ... }`) |
| Validación | HTTP 400, `success: false`, `error.code`: `VALIDATION_ERROR`, `error.details` |
| No autorizado | HTTP 401, token ausente o inválido |
| Prohibido | HTTP 403, usuario autenticado pero sin rol adecuado para el recurso |
| No encontrado | HTTP 404 |
| Error servidor | HTTP 500, `success: false`, `error.message` |

---

## Convención de rutas del módulo

Según `ARCHITECTURE.md` (naming: `/api/v1/{modulo}/{recurso}`), el prefijo recomendado es:

```text
/api/v1/profesor/...
```

Implementación: registrar cada ruta en `routes/routes.php` (bloque **MÓDULO PROFESOR**), la lógica en `app/controllers/ProfesorController.php` y el control de rol en `App\Lib\ProfesorMiddleware::requireProfesorRole()` (`app/Lib/ProfesorMiddleware.php`), con **try-catch**, **prepared statements** y **transacciones** en operaciones de escritura (`WORKFLOW.md`).

---

## Contrato de endpoints (especificación)

La siguiente tabla es el **contrato orientativo** para quien conecte el frontend. Los métodos y rutas deben implementarse en el backend según `WORKFLOW.md`; hasta que existan en `routes/routes.php`, el cliente recibirá 404.

| Método | Ruta (propuesta) | Propósito | Auth |
|--------|------------------|------------|------|
| GET | `/api/v1/profesor/contexto` | Datos del profesor autenticado (p. ej. perfil desde `public.usuarios` + `public.roles` usando `sub` del JWT) | Sí; solo rol Profesor |
| GET | `/api/v1/profesor/prestamos` | Listado / historial de préstamos del profesor | Sí |
| POST | `/api/v1/profesor/prestamos` | Solicitar préstamo | Sí |
| GET | `/api/v1/profesor/aulas` | Aulas a cargo | Sí |
| GET | `/api/v1/profesor/inventario` | Vista de inventario asociada al profesor | Sí |
| GET | `/api/v1/profesor/inmobiliaria` | Registro de inmobiliaria / espacios a cargo (según modelo de datos acordado) | Sí |
| GET | `/api/v1/profesor/notificaciones` | Notificaciones (p. ej. aprobación de préstamos); soporte a “no leídas” para badge / campana | Sí |
| PATCH | `/api/v1/profesor/notificaciones/@id/leida` | Marcar notificación como leída | Sí |
| GET | `/api/v1/profesor/reportes/bien/@id` | Reporte individual de un bien | Sí |
| POST | `/api/v1/profesor/auditoria/export` o GET con filtros | Generar o listar datos de auditoría según reglas del negocio | Sí (puede restringirse a coordinación/admin según política) |

**Aprobación / rechazo de préstamos** (botones en UI): suelen ser acciones de **administrador** u otro rol; si el backend las expone, conviene rutas separadas, p. ej. `PATCH /api/v1/admin/prestamos/@id` con cuerpo `{ "estado": "aprobado" | "rechazado" }`, y que al cambiar el estado se genere notificación al profesor y registro en `public.audit_log` cuando aplique.

---

## Base de datos (PostgreSQL — módulos nuevos)

- **Ya disponible** en `db/migrations/001_schema_sandbox.sql`: `roles`, `usuarios`, `audit_log`, `logs`, etc. Útiles para identificar al profesor (`usuarios` + `rol_id` / nombre de rol **Profesor**) y para auditoría.
- **Tablas específicas** (préstamos, aulas, bienes, inmobiliaria, notificaciones): deben definirse en **nuevas migraciones** bajo `db/migrations/` y, si aplica, seeds en `db/seeds/`, como indica `WORKFLOW.md`.

Si el proyecto también lee datos legacy en SQL Server (tabla `profesor`, etc.), la política general está en `DB.md` en la raíz: **solo lectura** en SQL Server; persistencia del módulo nuevo en PostgreSQL.

---

## Ejemplo mínimo de consumo (frontend → backend)

**1. Obtener token**

```bash
curl -s -X POST http://localhost:8080/api/v1/login \
  -H "Content-Type: application/json" \
  -d "{\"usuario\":\"profesor\",\"contrasena\":\"password\"}"
```

**2. Llamar a un endpoint del módulo (cuando esté implementado)**

```bash
curl -s http://localhost:8080/api/v1/profesor/contexto \
  -H "Authorization: Bearer <TOKEN_AQUI>" \
  -H "Content-Type: application/json"
```

**3. Enviar datos (POST de ejemplo, cuando exista el recurso)**

```bash
curl -s -X POST http://localhost:8080/api/v1/profesor/prestamos \
  -H "Authorization: Bearer <TOKEN_AQUI>" \
  -H "Content-Type: application/json" \
  -d "{\"ejemplo_campo\":\"valor\"}"
```

Los cuerpos JSON reales dependen del modelo acordado en migraciones y validación en el controller.

---

## Relación con otros documentos

| Documento | Uso para este módulo |
|-----------|----------------------|
| `docs/ARCHITECTURE.md` | Estructura, naming, stack, setup |
| `docs/WORKFLOW.md` | Pasos antes de codificar; checklist y transacciones |
| `docs/AGENTS.md` | Prompts para controllers / depuración |
| `docs/CODIGO_EJEMPLO.md` | Patrón del `EjemploController` (login, CRUD, JWT) |
| `STANDARDS.md` | Estándares PHP del repo |

---

## Historial de este documento

| Fecha | Cambio |
|-------|--------|
| 2026-04-17 | Versión inicial — contrato API módulo profesor |
| 2026-04-17 | Rutas en `routes/routes.php`; código bajo `app/controllers/` y `app/Lib/` |
| 2026-04-17 | Módulo profesor: `ProfesorController.php` + `ProfesorMiddleware.php` |
