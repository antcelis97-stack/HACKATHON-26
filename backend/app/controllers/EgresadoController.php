<?php
namespace App\Controllers;

use Flight;
use PDO;

class EgresadoController extends BaseController {
    public function updateProfile() {
        $data = $this->getInput();
        
        // Registro de CV y trayectoria académica
        $stmt = $this->db->prepare("UPDATE egresados SET trayectoria = ?, cv_url = ? WHERE usuario_id = ?");
        
        if ($stmt->execute([$data['trayectoria'], $data['cv_url'], $data['usuario_id']])) {
            return Flight::json(['message' => 'Perfil actualizado correctamente'], 200);
        }
        
        return Flight::json(['error' => 'Error al actualizar perfil'], 500);
    }

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