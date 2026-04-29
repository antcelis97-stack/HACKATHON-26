# Estándares de Código PHP - SIEst

## Naming Conventions

| Elemento | Convención | Ejemplo |
|----------|-----------|---------|
| Controllers | `NombreController.php` | `EmpleadoController.php` |
| Métodos | `camelCase` | `obtenerPorId()` |
| Variables | `$camelCase` | `$empleadoData` |
| Constantes | `UPPER_SNAKE_CASE` | `MAX_REINTENTOS` |
| Tablas SQL | `snake_case` | `empleados` |
| Columns SQL | `snake_case` | `numero_empleado` |
| Namespaces | `PascalCase` | `app\Controllers` |

## Reglas de Código

### 1. SIEMPRE Usar ResponseFormatter

```php
// ✅ Correcto
Flight::json(ResponseFormatter::success($data));

// ❌ Incorrecto
Flight::json(['success' => true, 'data' => $data]);
```

### 2. SIEMPRE Usar Logger para Acciones Importantes

```php
// ✅ Correcto
Logger::info("Empleado creado", ['id' => $nuevoId]);

// ❌ Incorrecto
error_log("Empleado creado");
```

### 3. SIEMPRE Usar Transacciones para Writes

```php
// ✅ Correcto
$pdo->beginTransaction();
try {
    // Operations...
    $pdo->commit();
} catch (\Exception $e) {
    $pdo->rollBack();
    throw $e;
}

// ❌ Incorrecto
$stmt->execute();
$stmt2->execute(); // Sin transacción
```

### 4. SIEMPRE Usar Prepared Statements

```php
// ✅ Correcto
$stmt = $pdo->prepare("SELECT * FROM empleados WHERE id = :id");
$stmt->execute([':id' => $id]);

// ❌ Incorrecto
$stmt = $pdo->query("SELECT * FROM empleados WHERE id = $id");
```

### 5. Usar Excepciones Custom

```php
// ✅ Correcto
throw new NotFoundException("Empleado no encontrado");

// ❌ Incorrecto
throw new Exception("No encontrado");
```

### 6. Incluir require_once para Database

```php
// ✅ Correcto
require_once __DIR__ . '/../../config/database.php';
$pdo = getPgConnection();

// ❌ Incorrecto
// Asumir que $pdo ya existe
```

### 7. Documentar Métodos con PHPDoc

```php
/**
 * Obtiene un empleado por su ID
 * 
 * @param int $id ID del empleado
 * @return array Datos del empleado
 * @throws NotFoundException Si el empleado no existe
 */
public static function getById(int $id): array
```

## Estructura de un Controller

```php
<?php
namespace app\controllers;

use Flight;
use PDO;
use app\Lib\ResponseFormatter;
use app\Lib\Logger;
use app\Lib\AuditLog;
use app\Lib\NotFoundException;
use app\Lib\ValidationException;

class EjemploController
{
    /**
     * Listar recursos
     */
    public static function listar(): void
    {
        try {
            // 1. Validar parámetros de entrada
            
            // 2. Obtener conexión a BD
            require_once __DIR__ . '/../../config/database.php';
            $pdo = getPgConnection();
            
            // 3. Lógica de negocio
            $stmt = $pdo->query("SELECT * FROM tabla");
            $datos = $stmt->fetchAll();
            
            // 4. Loguear
            Logger::info("Acción realizada");
            
            // 5. Responder
            Flight::json(ResponseFormatter::success($datos));
            
        } catch (\Exception $e) {
            throw $e; // Error handler global
        }
    }
    
    /**
     * Crear recurso
     */
    public static function crear(): void
    {
        try {
            // 1. Obtener y validar datos
            $data = json_decode(file_get_contents('php://input'), true);
            $errors = self::validar($data);
            if (!empty($errors)) {
                Flight::json(ResponseFormatter::validationError($errors), 400);
                return;
            }
            
            // 2. Conexión y transacción
            require_once __DIR__ . '/../../config/database.php';
            $pdo = getPgConnection();
            $pdo->beginTransaction();
            
            // 3. Insertar
            $stmt = $pdo->prepare("INSERT INTO tabla (...) VALUES (...)");
            $stmt->execute($data);
            
            $pdo->commit();
            
            // 4. Audit log
            AuditLog::create('tabla', $id, $data);
            
            // 5. Responder
            Flight::json(ResponseFormatter::created(['id' => $id]), 201);
            
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}
```

## Response Format Estándar

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
  },
  "meta": {
    "timestamp": "2024-04-16T10:30:00-06:00"
  }
}
```

## HTTP Status Codes

| Código | Uso |
|--------|-----|
| 200 | OK |
| 201 | Created |
| 400 | Bad Request (validación) |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 500 | Server Error |

## Checklist Antes de Commit

- [ ] ¿Usé ResponseFormatter en todas las respuestas?
- [ ] ¿Usé Logger para acciones importantes?
- [ ] ¿Usé transacciones para writes?
- [ ] ¿Usé prepared statements?
- [ ] ¿Validé los datos de entrada?
- [ ] ¿Documenté los métodos públicos?
- [ ] ¿La sintaxis es correcta? (`php -l archivo.php`)
