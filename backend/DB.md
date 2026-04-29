# Base de Datos - SIEst

## Arquitectura de Bases de Datos

SIEst utiliza **DOS bases de datos** por razones históricas:

```
┌─────────────────────────────────────────────────────────────────────┐
│                         SIEst Backend                                 │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│   PostgreSQL (siest)              SQL Server (dexter)             │
│   ═══════════════════════         ════════════════════════════       │
│                                                                      │
│   • MÓDULOS NUEVOS              • SOLO CONSULTA (READ ONLY)       │
│   • CREATE, READ, UPDATE       • Datos del SIEst viejo (JSP)     │
│   • DELETE                       • Usuarios, carreras, personas     │
│   • Students crean AQUÍ          • NO escribir aquí                │
│                                                                      │
│   getPgConnection()              getDBConnection()                  │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

## Regla Simple

| Necesito... | Usar |
|------------|------|
| Crear tablas para mi módulo | **PostgreSQL** |
| Guardar datos nuevos | **PostgreSQL** |
| Leer usuarios existentes | **SQL Server** |
| Leer carreras/periodos | **SQL Server** |
| Leer datos del checador | **PostgreSQL** |

---

## PostgreSQL (Tu Base de Datos)

### Propósito
Almacenar todos los datos de los módulos nuevos que los estudiantes van a crear.

### Conexión
```php
require_once __DIR__ . '/../../config/database.php';
$pdo = getPgConnection();
```

### Operaciones
- ✅ SELECT
- ✅ INSERT
- ✅ UPDATE
- ✅ DELETE
- ✅ CREATE TABLE (migrations)

### Schema del Sandbox

El sandbox usa el schema `public` con las siguientes tablas:

| Tabla | Descripción |
|-------|-------------|
| `roles` | Roles del sistema |
| `empleados` | Catálogo de empleados (ejemplo) |
| `usuarios` | Usuarios para autenticación |
| `logs` | Logs del sistema |
| `audit_log` | Auditoría de cambios |
| `refresh_tokens` | Tokens de refresco JWT |

---

## SQL Server (Datos Legacy)

### Propósito
Consultar datos que ya existen en el SIEst viejo (hecho en JSP). Estos datos son **SOLO LECTURA**.

### Conexión
```php
require_once __DIR__ . '/../../config/database.php';
$db = getDBConnection();  // ¡Ojo! Esta es getDBConnection, no getPgConnection
```

### Operaciones
- ✅ SELECT (leer datos)
- ❌ NO INSERT
- ❌ NO UPDATE
- ❌ NO DELETE

### Tablas Disponibles (solo lectura)

| Tabla | Descripción |
|-------|-------------|
| `usuario` | Usuarios del sistema |
| `rol` | Roles disponibles |
| `persona` | Datos de personas |
| `profesor` | Datos de profesores |
| `carrera` | Carreras/programas |
| `periodo` | Periodos escolares |
| `area` | Áreas/departamentos |

### Ejemplo: Consultar Usuarios del Sistema Viejo

```php
// Leer datos del SIEst viejo (SQL Server)
$db = getDBConnection();
$stmt = $db->prepare("SELECT * FROM persona WHERE cve_persona = ?");
$stmt->execute([$id]);
$persona = $stmt->fetch();
```

---

## Ejemplo: Flujo Completo

Imagina que quieres crear un módulo de "Reportes de Asistencia":

### Paso 1: Consultar datos del empleado (SQL Server)
```php
// Obtener datos del empleado desde el sistema viejo
$db = getDBConnection();
$stmt = $db->prepare("
    SELECT p.*, p.nombre + ' ' + p.apellido_paterno as nombre_completo
    FROM persona p
    WHERE p.cve_persona = ?
");
$stmt->execute([$empleadoId]);
$empleado = $stmt->fetch();
```

### Paso 2: Guardar el reporte (PostgreSQL)
```php
// Guardar el reporte en tu módulo nuevo
$pg = getPgConnection();
$stmt = $pg->prepare("
    INSERT INTO reportes_asistencia (empleado_id, fecha, observaciones)
    VALUES (?, ?, ?)
");
$stmt->execute([$empleadoId, $fecha, $observaciones]);
```

### Paso 3: Consultar tus reportes (PostgreSQL)
```php
// Leer reportes de tu módulo
$pg = getPgConnection();
$stmt = $pg->prepare("
    SELECT r.*, e.nombre_completo
    FROM reportes_asistencia r
    JOIN public.empleados e ON r.empleado_id = e.id
    WHERE r.fecha BETWEEN ? AND ?
");
$stmt->execute([$fechaInicio, $fechaFin]);
$reportes = $stmt->fetchAll();
```

---

## NO HACER

- ❌ **No escribir en SQL Server** (es solo lectura)
- ❌ **No intentar transacciones cruzadas** entre bases de datos
- ❌ **No asumir que las tablas existen en ambas bases de datos**
- ❌ **No hardcodear credenciales** - usar variables de entorno

## Configuración de Conexiones

Ver `.env.example` para las variables de entorno:

```bash
# PostgreSQL
PG_HOST=localhost
PG_PORT=5432
PG_NAME=siest
PG_USER=postgres
PG_PASS=tu_password

# SQL Server (legacy - solo lectura)
DB_HOST=localhost
DB_PORT=1433
DB_NAME=dexter
DB_USER=tu_usuario
DB_PASS=tu_password
```

---

## Comandos SQL Útiles

```bash
# Conectar a PostgreSQL
psql -h localhost -U postgres -d siest

# Ver tablas
\dt public.*

# Ver estructura de tabla
\d public.empleados

# Ejecutar script SQL
psql -h localhost -U postgres -d siest -f db/migrations/001_schema_sandbox.sql
```
