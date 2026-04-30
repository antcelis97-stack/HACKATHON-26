# Workflow - SIEstSandboxBackend

## QUICKREF
- php, flight, postgresql, api, desarrollo
- protocolo, docs, tareas
- sandbox, estudiantes, ejemplo CRUD

---

## Protocolo Obligatorio (cada tarea)

Este workflow debe seguirse **ANTES** de trabajar en cualquier tarea.

---

## Fase 1: Revisar Documentación

| Paso | Documento | Para qué |
|------|-----------|----------|
| 1.1 | `docs/ARCHITECTURE.md` | Estructura folders, naming, patrones Flight |
| 1.2 | `STANDARDS.md` | Estándares de código PHP |
| 1.3 | `API.md` | Endpoints existentes |
| 1.4 | `db/migrations/` | Schema de base de datos |

---

## Fase 2: Informar Plan

**Siempre** informar antes de ejecutar:

```markdown
## Plan de Trabajo

### Documentación a revisar:
- [x] docs/ARCHITECTURE.md - estructura y naming
- [x] STANDARDS.md - estándares PHP
- [ ] API.md - (si aplica)

### Approach:
[Breve resumen de cómo se abordará la tarea]

### Tareas identificadas:
1. **Tarea 1**: [descripción corta]
2. **Tarea 2**: [descripción]
```

---

## Fase 3: Ejecutar Tarea

### División de Tareas Complejas

Si la tarea es compleja, **DIVIDIR** en subtareas:

```
Tarea compleja → subtarea 1.1 → subtarea 1.2 → subtarea 2.1
```

**Reglas:**
- Cada subtarea debe poder completarse en una implementación
- Dividir hasta que sea manejable

---

## Variaciones por Tipo de Tarea

### 1. Nuevo Endpoint API
```
Docs: ARCHITECTURE + STANDARDS
Tareas: 1) Crear controller/método, 2) Agregar ruta en routes.php
```

### 2. Nueva Tabla
```
Docs: ARCHITECTURE
Tareas: 1) Crear SQL migration, 2) Agregar seeds si aplica
```

### 3. Fix/Bug
```
Docs: ARCHITECTURE + STANDARDS
Tareas: 1) Investigar causa, 2) Corregir, 3) Verificar
```

### 4. Feature Completa
```
Docs: TODOS
Tareas: Dividir en fases (explore → propose → spec → design → tasks → apply → verify)
```

---

## Checklist Antes de Commit

- [ ] ¿Seguí las naming conventions?
- [ ] ¿Usé try-catch en todos los métodos?
- [ ] ¿Usé prepared statements?
- [ ] ¿Usé transacciones para writes (INSERT/UPDATE/DELETE)?
- [ ] ¿Validé los datos de entrada?
- [ ] ¿La sintaxis es correcta? (`php -l archivo.php`)

---

## Patrones de Código Obligatorios

### 1. Try-Catch con Transacción

```php
$pdo->beginTransaction();
try {
    // operations...
    $pdo->commit();
} catch (\Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    throw $e;
}
```

### 2. Prepared Statements

```php
$stmt = $pdo->prepare("SELECT * FROM tabla WHERE id = :id");
$stmt->execute([':id' => $id]);
```

### 3. Respuesta JSON

```php
// Success
Flight::json(['success' => true, 'data' => $result], 200);

// Created
Flight::json(['success' => true, 'data' => ['id' => $id]], 201);

// Error
Flight::json(['success' => false, 'error' => $e->getMessage()], 500);
```

---

## Ejemplo Completo (lista formal)

```markdown
## Plan de Trabajo - Agregar filtro por área a empleados

### Documentación a revisar:
- [x] docs/ARCHITECTURE.md - estructura de controllers
- [x] STANDARDS.md - patrones PHP

### Approach:
Agregar parámetro opcional `area` al endpoint listar() para filtrar empleados por área.

### Tareas identificadas:
1. **Modificar listar()**: Agregar parámetro area en query string
2. **Construir WHERE dinámico**: Agregar condición si se pasa área
3. **Probar endpoint**: Verificar con curl

⚠️ IMPORTANTE: Mantener backward compatibility - area es opcional.
```

---

## Testing Manual

### Probar Endpoints con curl

```bash
# Login
curl -X POST http://localhost:8080/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"usuario":"admin","contrasena":"password"}'

# Listar (con token)
TOKEN="tu_token_aqui"
curl http://localhost:8080/api/v1/empleados \
  -H "Authorization: Bearer $TOKEN"

# Crear
curl -X POST http://localhost:8080/api/v1/empleados \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"nombre":"Juan Pérez","email":"juan@test.com"}'
```

---

## Recursos

- [Flight Framework Docs](https://flightphp.com/docs)
- [PHP Documentation](https://www.php.net/docs.php)
- [PostgreSQL PDO](https://www.php.net/manual/es/ref.pdo-pgsql.php)

---

## Relación con Otros Docs

| Documento | Relación |
|-----------|----------|
| `docs/ARCHITECTURE.md` | Estructura del proyecto |
| `docs/AGENTS.md` | Agentes especializados |
| `STANDARDS.md` | Estándares de código |
| `CODIGO_EJEMPLO.md` | Código ejemplo explicado |

---

## Actualizaciones

| Fecha | Cambio |
|-------|--------|
| 2026-04-16 | Versión inicial - Workflow del sandbox backend |
