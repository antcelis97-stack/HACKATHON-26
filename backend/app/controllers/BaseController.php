<?php
namespace App\Controllers;

use Flight;

class BaseController {
    protected $db;
    
    public function __construct() {
        // Obtenemos la conexión a PostgreSQL 16 registrada en el index.php
        $this->db = Flight::db(); 
    }

    // Helper para obtener el JSON del body (Flight lo extrae fácilmente)
    protected function getInput() {
        return json_decode(Flight::request()->getBody(), true);
    }
}