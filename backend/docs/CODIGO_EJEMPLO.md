# Explicación del Código Ejemplo

Este documento explica línea por línea el código del módulo de ejemplo.

## Controller: `app/controllers/EjemploController.php`

### Estructura General

El controller está organizado en secciones:

1. **Constantes de clase** - Configuración JWT
2. **Autenticación** - Login, refresh token
3. **CRUD Empleados** - Listar, ver, crear, actualizar, eliminar
4. **Métodos auxiliares** - Token, validación

---

## Autenticación

### `login()` - Líneas 43-95

```php
public static function login(): void
{
    try {
        // 1. Obtener datos del body
        $data = json_decode(file_get_contents('php://input'), true);

        // 2. Validar campos requeridos
        if (empty($data['usuario']) || empty($data['contrasena'])) {
            // Retornar error de validación
            Flight::json(ResponseFormatter::validationError([...]), 400);
            return;
        }

        // 3. Conectar a PostgreSQL
        require_once __DIR__ . '/../../config/database.php';
        $pdo = getPgConnection();

        // 4. Buscar usuario en la BD
        $stmt = $pdo->prepare("SELECT ... FROM public.usuarios ...");
        $stmt->execute([':usuario' => $data['usuario']]);
        $user = $stmt->fetch();

        // 5. Verificar que existe
        if (!$user) {
            throw new UnauthorizedException("Credenciales incorrectas");
        }

        // 6. Verificar contraseña (bcrypt)
        if (!password_verify($data['contrasena'], $user['contrasena_hash'])) {
            throw new UnauthorizedException("Credenciales incorrectas");
        }

        // 7. Generar JWT
        $token = self::generarToken($user);

        // 8. Generar refresh token
        $refreshToken = bin2hex(random_bytes(32));
        // ... guardar en BD ...

        // 9. Loguear éxito
        Logger::info("Login exitoso", ['usuario' => $user['usuario']]);

        // 10. Responder
        Flight::json(ResponseFormatter::success([...]));

    } catch (UnauthorizedException $e) {
        // Manejar error específico
        Logger::warning("Login fallido", [...]);
        Flight::json(ResponseFormatter::unauthorized($e->getMessage()), 401);
    } catch (\Exception $e) {
        // Error genérico
        Logger::error("Error en login", [...]);
        Flight::json(ResponseFormatter::error($e->getMessage()), 500);
    }
}
```

**Puntos clave:**
- Siempre validar inputs antes de procesar
- Usar prepared statements para evitar SQL injection
- Loguear tanto éxito como errores
- Usar excepciones específicas para errores específicos

---

## CRUD - Listar

### `listar()` - Líneas 104-159

```php
public static function listar(): void
{
    try {
        // 1. Conexión
        require_once __DIR__ . '/../../config/database.php';
        $pdo = getPgConnection();

        // 2. Parámetros de paginación
        $page = max(1, (int)($_GET['page'] ?? 1));      // Default 1, mínimo 1
        $limit = min(100, max(1, (int)($_GET['limit'] ?? 20))); // Default 20, máx 100
        $offset = ($page - 1) * $limit;

        // 3. Construir filtros dinámicamente
        $where = [];
        $params = [];

        if (!empty($_GET['area'])) {
            $where[] = "area ILIKE :area";  // ILIKE = case insensitive
            $params[':area'] = '%' . $_GET['area'] . '%';
        }

        if (isset($_GET['activo'])) {
            $where[] = "activo = :activo";
            $params[':activo'] = $_GET['activo'] === 'true';
        }

        // 4. Query con LIMIT/OFFSET para paginación
        $sql = "SELECT * FROM public.empleados $whereSql LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $empleados = $stmt->fetchAll();

        // 5. Count para saber total de páginas
        $countSql = "SELECT COUNT(*) FROM public.empleados $whereSql";
        $total = (int)$countStmt->fetchColumn();

        // 6. Responder con paginación
        Flight::json(ResponseFormatter::paginated($empleados, $page, $limit, $total));
```

**Puntos clave:**
- Sanitizar parámetros de paginación
- Filtros opcionales con valores por defecto
- Usar `ILIKE` para búsqueda case-insensitive
- Retornar metadata de paginación

---

## CRUD - Crear

### `crear()` - Líneas 168-222

```php
public static function crear(): void
{
    try {
        // 1. Obtener y validar datos
        $data = json_decode(file_get_contents('php://input'), true);
        $errors = self::validarEmpleado($data);
        if (!empty($errors)) {
            Flight::json(ResponseFormatter::validationError($errors), 400);
            return;
        }

        // 2. Conexión
        $pdo = getPgConnection();

        // 3. INICIAR TRANSACCIÓN
        $pdo->beginTransaction();

        // 4. Insertar
        $stmt = $pdo->prepare("
            INSERT INTO public.empleados 
            (numero_empleado, nombre_completo, ...)
            VALUES (:num, :nombre, ...)
            RETURNING id  -- PostgreSQL: retorna el ID insertado
        ");
        $stmt->execute([...]);
        $nuevoId = $stmt->fetch()['id'];

        // 5. CONFIRMAR TRANSACCIÓN
        $pdo->commit();

        // 6. Auditoría
        AuditLog::create('empleados', $nuevoId, $data, self::getUserIdFromToken());

        // 7. Responder con 201 Created
        Flight::json(ResponseFormatter::created(['id' => $nuevoId]), 201);

    } catch (\Exception $e) {
        // 8. En caso de error: ROLLBACK
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
```

**Puntos clave:**
- Siempre usar transacciones para writes
- `beginTransaction()` → operaciones → `commit()`
- Si falla, `rollBack()` y relanzar excepción
- Usar `RETURNING id` para obtener el ID insertado
- Registrar en audit_log para trazabilidad

---

## Validación

### `validarEmpleado()` - Líneas 412-434

```php
private static function validarEmpleado(array $data): array
{
    $errors = [];

    // Número de empleado requerido
    if (empty($data['numero_empleado'])) {
        $errors[] = [
            'field' => 'numero_empleado',
            'message' => 'Número de empleado requerido'
        ];
    }

    // Longitud máxima
    if (strlen($data['numero_empleado']) > 20) {
        $errors[] = [
            'field' => 'numero_empleado',
            'message' => 'Máximo 20 caracteres'
        ];
    }

    // Formato de email
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = [
            'field' => 'email',
            'message' => 'Formato de email inválido'
        ];
    }

    return $errors;
}
```

**Puntos clave:**
- Retornar array de errores con estructura: `[{field, message}, ...]`
- Validar tipo de datos (strings, números)
- Validar formatos (email, teléfono, etc.)
- Validar longitud máxima

---

## JWT

### `generarToken()` - Líneas 437-451

```php
private static function generarToken(array $user): string
{
    self::$jwtSecret = $_ENV['API_KEY'] ?? 'clave_default_32_chars';

    $payload = [
        'sub' => $user['id'],           // Subject (user ID)
        'usuario' => $user['usuario'],
        'rol' => $user['rol_nombre'] ?? 'Sin rol',
        'iat' => time(),                // Issued at
        'exp' => time() + self::$jwtExpire  // Expiration
    ];

    return JWT::encode($payload, self::$jwtSecret, 'HS256');
}
```

**Puntos clave:**
- Usar secret desde variable de entorno
- Incluir datos relevantes en el payload
- Token expira en 1 hora (3600 segundos)

---

## Patrones Clave

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

### 3. ResponseFormatter
```php
Flight::json(ResponseFormatter::success($data));
Flight::json(ResponseFormatter::error("msg", "CODE"), 500);
```

### 4. Logging
```php
Logger::info("Mensaje", ['key' => $value]);
Logger::error("Error", ['error' => $e->getMessage()]);
```

---

## Flujo Completo de una Request

```
Request HTTP
    │
    ▼
routes/routes.php
    │
    ▼
authMiddleware() (si es ruta protegida)
    │
    ▼
Controller::method()
    │
    ├── require_once database.php
    ├── $pdo = getPgConnection()
    │
    ├── Validar inputs
    │
    ├── Query a BD
    │
    ├── Logger::info()
    │
    └── Flight::json(ResponseFormatter::success())
            │
            ▼
        Response JSON
```

---

## Recursos Adicionales

- [Flight Framework](https://flightphp.com/docs)
- [JWT Library](https://github.com/firebase/php-jwt)
- [PostgreSQL PDO](https://www.php.net/manual/es/ref.pdo-pgsql.php)
