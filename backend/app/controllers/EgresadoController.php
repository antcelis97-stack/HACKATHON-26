<?php
namespace App\Controllers;

use Flight;
use PDO;

class EgresadoController extends BaseController {
    public function searchVacantes() {
        // Obtenemos los query params (?ubicacion=X&especialidad=Y) usando Flight
        $request = Flight::request();
        $ubicacion = $request->query->ubicacion;
        $especialidad = $request->query->especialidad;

        $query = "SELECT * FROM vacantes WHERE estatus = 'activa'";
        $params = [];

        if ($ubicacion) {
            $query .= " AND ubicacion = ?";
            $params[] = $ubicacion;
        }
        if ($especialidad) {
            $query .= " AND especialidad = ?";
            $params[] = $especialidad;
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $vacantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return Flight::json($vacantes, 200);
    }
}