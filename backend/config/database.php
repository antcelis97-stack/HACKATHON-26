<?php
namespace App\Config;

use PDO;
use PDOException;

class Database {
    private $host = 'localhost';
    private $port = '5432';
    private $dbname = 'bolsa_trabajo_ut'; // Cambia esto por el nombre real de tu BD
    private $user = 'postgres';
    private $password = 'tu_password'; // Cambia por tu contraseña local

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