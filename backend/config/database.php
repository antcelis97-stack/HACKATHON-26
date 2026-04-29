<?php
/**
 * Configuración de Conexiones a Bases de Datos
 * 
 * SIEst usa DOS bases de datos:
 * 1. PostgreSQL 16 (local): Para módulos nuevos (CREATE, READ, UPDATE, DELETE)
 * 2. SQL Server: Para consultar datos legacy del SIEst viejo (SOLO READ)
 * 
 * Para producción, cambiar las variables de entorno en .env
 * para apuntar a Supabase (ver comentarios en .env).
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

/**
 * Helper: conectar con retry por circuit breaker (solo para Supabase remoto)
 */
function connectWithRetry(string $dsn, string $user, string $password, array $options = [], int $maxRetries = 3): PDO
{
    $delays = [2, 3, 5];

    for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
        try {
            return new PDO($dsn, $user, $password, $options);
        } catch (\PDOException $e) {
            if (strpos($e->getMessage(), 'ECIRCUITBREAKER') !== false && $attempt < $maxRetries - 1) {
                sleep($delays[$attempt]);
                continue;
            }
            throw $e;
        }
    }

    throw new \PDOException('No se pudo conectar después de ' . $maxRetries . ' intentos');
}

/**
 * Obtener conexión a PostgreSQL
 * 
 * @return PDO
 */
function getPgConnection(): PDO
{
    $host = $_ENV['PG_HOST'] ?? 'localhost';
    $port = $_ENV['PG_PORT'] ?? '5432';
    $dbname = $_ENV['PG_NAME'] ?? 'siest';
    $user = $_ENV['PG_USER'] ?? 'postgres';
    $password = $_ENV['PG_PASS'] ?? 'postgres123';

    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

    return connectWithRetry($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
}

/**
 * Obtener conexión a SQL Server
 * 
 * IMPORTANTE: Esta base es READ ONLY. NO escribas aquí.
 * 
 * @return PDO
 */
function getDBConnection(): PDO
{
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $port = $_ENV['DB_PORT'] ?? '1433';
    $dbname = $_ENV['DB_NAME'] ?? 'dexter';
    $user = $_ENV['DB_USER'] ?? 'sa';
    $password = $_ENV['DB_PASS'] ?? '';

    $os = PHP_OS_FAMILY;

    if ($os === "Windows") {
        $dsn = "sqlsrv:Server=$host,$port;Database=$dbname";
    } else {
        $dsn = "dblib:host=$host:$port;dbname=$dbname;charset=UTF-8";
    }

    return new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
}

/**
 * Obtener conexión a PostgreSQL 16 (inventario)
 * 
 * Default: localhost (desarrollo).
 * Para producción: configurar PG16_* en .env para apuntar a Supabase.
 * 
 * @return PDO
 */
function getPgInventarioConnection(): PDO
{
    $host = $_ENV['PG16_HOST'] ?? 'localhost';
    $port = $_ENV['PG16_PORT'] ?? '5432';
    $dbname = $_ENV['PG16_NAME'] ?? 'siest';
    $user = $_ENV['PG16_USER'] ?? 'postgres';
    $password = $_ENV['PG16_PASS'] ?? 'postgres123';

    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

    return connectWithRetry($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
}
