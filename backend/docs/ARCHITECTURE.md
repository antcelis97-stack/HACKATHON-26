# Arquitectura - SIEstSandboxBackend

## QUICKREF
- php, flight, postgresql, api, routes, controllers
- estructura carpetas, naming conventions, patrones
- sandbox, estudiantes, ejemplo CRUD

---

## Setup

### Requisitos
- PHP 8.1+
- Composer
- PostgreSQL 14+

### Instalación

```bash
# 1. Clonar o copiar este directorio
cd SIEstSandboxBackend

# 2. Instalar dependencias
composer install

# 3. Copiar y configurar variables de entorno
cp .env.example .env
# Editar .env con tus credenciales de PostgreSQL:
# DB_HOST=localhost
# DB_PORT=5432
# DB_NAME=siest_sandbox
# DB_USER=postgres
# DB_PASSWORD=tu_password

# 4. Crear base de datos
createdb siest_sandbox

# 5. Ejecutar schema
psql -h localhost -U postgres -d siest_sandbox -f db/migrations/001_schema_sandbox.sql

# 6. Insertar datos de prueba
psql -h localhost -U postgres -d siest_sandbox -f db/seeds/001_usuarios_prueba.sql

# 7. Iniciar servidor
php -S localhost:8080 -t .
```

### Verificar Instalación

```bash
# Health check (sin auth)
curl http://localhost:8080/api/v1/empleados

# Login
curl -X POST http://localhost:8080/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"usuario":"admin","contrasena":"password"}'
```

### Credenciales de Prueba

| Usuario  | Contraseña   | Rol          |
|----------|--------------|--------------|
| admin    | password     | Administrador|
| profesor | password     | Profesor     |
| alumno   | password     | Estudiante   |

---

## Estructura de Carpetas

```
SIEstSandboxBackend/
├── index.php                    # Entry point de la API
├── composer.json                # Dependencias PHP
├── .env                         # Variables de entorno
├── .env.example                 # Template de variables
│
├── routes/
│   └── routes.php              # Definición de todas las rutas API
│
├── config/
│   └── database.php            # Conexión PostgreSQL
│
├── app/
│   ├── controllers/             # Controladores (Flight)
│   │   ├── EjemploController.php
│   │   └── ProfesorController.php  # Módulo /api/v1/profesor/* (docs/MODULO_PROFESOR_API.md)
│   │
│   └── Lib/                    # Bibliotecas helper
│       ├── Exceptions.php       # Excepciones personalizadas
│       └── ProfesorMiddleware.php  # Rol Profesor + JWT para rutas /profesor
│
├── db/
│   ├── migrations/             # Scripts SQL de schema
│   │   └── 001_schema_sandbox.sql
│   └── seeds/                  # Datos de prueba
│       └── 001_usuarios_prueba.sql
│
├── docs/                       # Documentación
│   ├── ARCHITECTURE.md         # Este archivo
│   ├── WORKFLOW.md             # Protocolo de desarrollo
│   ├── AGENTS.md               # Agentes especializados
│   ├── CODIGO_EJEMPLO.md      # Explicación del código ejemplo
│   ├── MODULO_PROFESOR_API.md  # Contrato API módulo profesor / frontend
│   ├── PROFESOR_ENDPOINTS.md   # Lista + ejemplos JSON /api/v1/profesor/*
│   └── PROFESOR_DOCUMENTACION_FRONTEND.md  # Plantilla IA: endpoint, request, response, código
│
└── vendor/                     # Dependencias Composer
```

---

## Stack Tecnológico

| Componente | Tecnología | Notas |
|------------|------------|-------|
| Language   | PHP        | 8.1+  |
| Framework  | Flight     | 3.x - micro-framework ligero |
| Database   | PostgreSQL | 14+   |
| JWT        | firebase/php-jwt | 7.0+ |
| Env        | vlucas/phpdotenv | 5.x |

---

## Naming Conventions

| Elemento | Convención | Ejemplo |
|----------|-----------|---------|
| Controller | `{Nombre}Controller.php` | `EjemploController.php` |
| Método | `camelCase` | `listar()`, `obtenerPorId()` |
| Ruta API | `/api/v1/{modulo}/{recurso}` | `/api/v1/empleados` |
| Tabla PostgreSQL | `snake_case` | `empleados` |
| Variable | `$camelCase` | `$pdo`, `$empleados` |
| Constante | `UPPER_SNAKE_CASE` | `JWT_SECRET` |

---

## Patrones de Código

### Controller Structure

```php
<?php
namespace app\controllers;

use Flight;
use PDO;
use Exception;

class EjemploController {
    
    /**
     * Listar empleados con paginación
     */
    public static function listar(): void
    {
        try {
            // 1. Obtener parámetros
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;
            
            // 2. Conexión DB
            require_once __DIR__ . '/../../config/database.php';
            $pdo = getPgConnection();
            
            // 3. Query
            $stmt = $pdo->prepare("SELECT * FROM empleados LIMIT :limit OFFSET :offset");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $empleados = $stmt->fetchAll();
            
            // 4. Respuesta
            Flight::json([
                'success' => true,
                'data' => $empleados,
                'meta' => ['page' => $page, 'limit' => $limit]
            ]);
            
        } catch (Exception $e) {
            Flight::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Crear empleado
     */
    public static function crear(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validación
            if (empty($data['nombre'])) {
                Flight::json([
                    'success' => false,
                    'error' => ['code' => 'VALIDATION', 'message' => 'Nombre requerido']
                ], 400);
                return;
            }
            
            require_once __DIR__ . '/../../config/database.php';
            $pdo = getPgConnection();
            
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("INSERT INTO empleados (nombre) VALUES (:nombre) RETURNING id");
                $stmt->execute([':nombre' => $data['nombre']]);
                $id = $stmt->fetch()['id'];
                
                $pdo->commit();
                
                Flight::json(['success' => true, 'data' => ['id' => $id]], 201);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            
        } catch (Exception $e) {
            Flight::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
```

### Route Registration

```php
// routes/routes.php

// GET pública
Flight::route('GET /api/v1/empleados', function() {
    EjemploController::listar();
});

// GET con parámetro
Flight::route('GET /api/v1/empleados/@id', function($id) {
    EjemploController::obtener($id);
});

// POST (crear)
Flight::route('POST /api/v1/empleados', function() {
    EjemploController::crear();
});

// PUT (actualizar)
Flight::route('PUT /api/v1/empleados/@id', function($id) {
    EjemploController::actualizar($id);
});

// DELETE
Flight::route('DELETE /api/v1/empleados/@id', function($id) {
    EjemploController::eliminar($id);
});
```

### Route con Autenticación

```php
// Rutas protegidas con middleware
Flight::route('GET /api/v1/empleados*', function() {
    authMiddleware(); // Verifica JWT
    EjemploController::listar();
})->addMiddleware('auth');
```

---

## Conexión a Base de Datos

```php
// config/database.php

function getPgConnection(): PDO {
    $host = getenv('DB_HOST') ?: 'localhost';
    $port = getenv('DB_PORT') ?: '5432';
    $dbname = getenv('DB_NAME') ?: 'siest_sandbox';
    $user = getenv('DB_USER') ?: 'postgres';
    $password = getenv('DB_PASSWORD') ?: '';
    
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    return new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}
```

---

## Respuestas API

### Success

```json
{
  "success": true,
  "data": { ... },
  "meta": {
    "timestamp": "2024-04-16T10:30:00-06:00"
  }
}
```

### Error

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Datos inválidos",
    "details": [
      {"field": "email", "message": "Formato inválido"}
    ]
  }
}
```

### Códigos de Estado HTTP

| Código | Uso |
|--------|-----|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request (validación) |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 500 | Server Error |

---

## Middleware de Autenticación

```php
// routes/routes.php - ejemplo de middleware

function authMiddleware(): void {
    $headers = getallheaders();
    $auth = $headers['Authorization'] ?? '';
    
    if (!preg_match('/^Bearer\s+(.+)$/', $auth, $matches)) {
        Flight::json(['success' => false, 'error' => 'Token requerido'], 401);
        return;
    }
    
    $token = $matches[1];
    
    try {
        $decoded = JWT::decode($token, new Key($_ENV['API_KEY'], 'HS256'));
        // Guardar usuario en contexto
        Flight::set('user', $decoded);
    } catch (Exception $e) {
        Flight::json(['success' => false, 'error' => 'Token inválido'], 401);
    }
}
```

---

## Módulo Ejemplo: Empleados

El sandbox incluye un módulo CRUD completo de ejemplo:

| Archivo | Propósito |
|---------|-----------|
| `app/controllers/EjemploController.php` | Controller con CRUD completo |
| `db/migrations/001_schema_sandbox.sql` | Schema con tablas: roles, empleados, usuarios, logs |
| `docs/CODIGO_EJEMPLO.md` | Explicación línea por línea del código |

### Tablas del Schema

```
public/
├── roles              # Catálogo de roles (admin, profesor, alumno)
├── empleados           # Catálogo de empleados
├── usuarios            # Usuarios del sistema (login)
├── logs               # Logs de actividad
├── audit_log          # Auditoría de cambios
└── refresh_tokens     # Tokens de refresh JWT
```

---

## Relación con Otros Docs

| Documento | Relación |
|-----------|----------|
| `WORKFLOW.md` | Protocolo de desarrollo |
| `AGENTS.md` | Agentes especializados |
| `CODIGO_EJEMPLO.md` | Explicación del código ejemplo |
| `STANDARDS.md` | Estándares de código PHP |
| `API.md` | Referencia de endpoints |

---

## Actualizaciones

| Fecha | Cambio |
|-------|--------|
| 2026-04-16 | Versión inicial - Arquitectura del sandbox backend |
