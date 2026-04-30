<?php
namespace App\Config;

use PDO;
use PDOException;

class Database {
    private string $host;
    private string $port;
    private string $dbname;
    private string $user;
    private string $password;

    public function __construct()
    {
        $this->host = $_ENV['PG_HOST'] ?? 'localhost';
        $this->port = $_ENV['PG_PORT'] ?? ($_ENV['PG16_PORT'] ?? '5432');
        $this->dbname = $_ENV['PG_NAME'] ?? ($_ENV['PG16_NAME'] ?? 'bolsa_trabajo_ut');
        $this->user = $_ENV['PG_USER'] ?? ($_ENV['PG16_USER'] ?? 'postgres');
        $this->password = $_ENV['PG_PASS'] ?? ($_ENV['PG16_PASS'] ?? 'tu_password');
    }

    public function connect() {
        try {
            $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->dbname}";
            $pdo = new PDO($dsn, $this->user, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            return $pdo;
        } catch (PDOException $e) {
            // Se detiene la ejecución y devuelve un JSON en caso de error crítico
            die(json_encode([
                "error" => "Error de conexión a PostgreSQL",
                "mensaje" => $e->getMessage()
            ]));
        }
    }
}