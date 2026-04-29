# Workflow Backend - SIEst

## Stack Tecnológico

- **PHP** 8.2+
- **Flight Framework** 3.15 (micro-framework)
- **PostgreSQL** (datos nuevos)
- **SQL Server** (datos legacy - solo lectura)
- **Composer** (gestión de dependencias)

## Estructura de Carpetas

```
SIEstSandboxBackend/
├── app/
│   └── controllers/           # Tus controllers aquí
├── config/
│   └── database.php         # Conexiones a bases de datos
├── routes/
│   └── routes.php           # Definición de rutas
├── db/
│   ├── migrations/          # Scripts SQL de schema
│   └── seeds/               # Datos de prueba
└── vendor/                  # Dependencias Composer
```

## Patrones de Código

### 1. Crear un Controller

Crea un archivo en `app/controllers/` siguiendo este patrón:

```php
<?php
namespace app\controllers;

use Flight;
use PDO;
use app\Lib\ResponseFormatter;  // SIEMPRE usar
use app\Lib\Logger;              // Para logging
use app\Lib\NotFoundException;   // Excepciones custom
use app\Lib\ValidationException;

class TuController
{
    /**
     * GET /api/v1/tu-recurso
     */
    public static function listar(): void
    {
        try {
            // 1. Obtener conexión a PostgreSQL
            require_once __DIR__ . '/../../config/database.php';
            $pdo = getPgConnection();

            // 2. Tu lógica...
            $stmt = $pdo->query("SELECT * FROM public.tu_tabla");
            $datos = $stmt->fetchAll();

            // 3. Loguear acción
            Logger::info("Acción realizada", ['count' => count($datos)]);

            // 4. Responder con ResponseFormatter
            Flight::json(ResponseFormatter::success($datos));

        } catch (\Exception $e) {
            // El manejo de errores global captura esto
            throw $e;
        }
    }
}
```

### 2. Agregar Rutas

En `routes/routes.php`:

```php
// Rutas públicas (sin autenticación)
Flight::route('GET /api/v1/tu-recurso', function() {
    \app\controllers\TuController::listar();
});

// Rutas protegidas (con autenticación)
Flight::route('POST /api/v1/tu-recurso', function() {
    if (!authMiddleware()) return;  // Verifica JWT
    \app\controllers\TuController::crear();
});
```

### 3. ResponseFormatter - SIEMPRE

Nunca uses `Flight::json()` directamente. Siempre usa ResponseFormatter:

```php
// ✅ CORRECTO
Flight::json(ResponseFormatter::success($data));
Flight::json(ResponseFormatter::created(['id' => 1]));
Flight::json(ResponseFormatter::paginated($items, $page, $limit, $total));
Flight::json(ResponseFormatter::error("Error", "CODIGO"));
Flight::json(ResponseFormatter::validationError($errors), 400);
Flight::json(ResponseFormatter::notFound("Empleado"), 404);

// ❌ INCORRECTO
Flight::json(['success' => true, 'data' => $data]);
Flight::json(['error' => 'mensaje']);
```

### 4. Logger - Para Acciones Importantes

```php
Logger::debug("Mensaje de debug", ['detalle' => 'valor']);
Logger::info("Acción realizada", ['user_id' => 1]);
Logger::warning("Algo raro pasó", ['ip' => '192.168.1.1']);
Logger::error("Error en proceso", ['error' => $e->getMessage()]);
Logger::critical("Fallo crítico", ['sistema' => 'pago']);
```

### 5. Transacciones para Writes

```php
$pdo->beginTransaction();
try {
    // Operaciones de escritura...
    $pdo->commit();
} catch (\Exception $e) {
    $pdo->rollBack();
    throw $e;
}
```

### 6. Excepciones Personalizadas

```php
throw new NotFoundException("Empleado no encontrado");
throw new ValidationException("Datos inválidos", [
    ['field' => 'email', 'message' => 'Formato inválido']
]);
throw new UnauthorizedException("Token expirado");
```

### 7. Consultar SQL Server (Legacy)

```php
// ¡Ojo! Usar getDBConnection, no getPgConnection
$db = getDBConnection();
$stmt = $db->prepare("SELECT * FROM persona WHERE cve_persona = ?");
$stmt->execute([$id]);
$datos = $stmt->fetch();
```

## Comandos Útiles

```bash
# Verificar sintaxis PHP
php -l app/controllers/TuController.php

# Iniciar servidor de desarrollo
php -S localhost:8080 -t .

# Instalar dependencias
composer install

# Actualizar autoload después de agregar clases
composer dump-autoload
```

## Crear un Nuevo Módulo

### Paso 1: Crear el Controller

```bash
# Crear archivo en app/controllers/
touch app/controllers/MiModuloController.php
```

### Paso 2: Implementar los métodos CRUD

Ver `app/controllers/EjemploController.php` para un ejemplo completo.

### Paso 3: Agregar las rutas

En `routes/routes.php`:

```php
Flight::route('GET /api/v1/mi-modulo', function() {
    \app\controllers\MiModuloController::listar();
});
Flight::route('POST /api/v1/mi-modulo', function() {
    if (!authMiddleware()) return;
    \app\controllers\MiModuloController::crear();
});
```

### Paso 4: Crear las tablas (si es necesario)

```sql
-- En db/migrations/
CREATE TABLE public.mi_modulo (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Paso 5: Probar

```bash
# Listar
curl http://localhost:8080/api/v1/mi-modulo

# Crear (con token)
curl -X POST http://localhost:8080/api/v1/mi-modulo \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"nombre": "Ejemplo", "descripcion": "Descripción"}'
```

## Recursos

- [Flight Framework](https://flightphp.com/)
- [PHP Documentation](https://www.php.net/manual/es/)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
