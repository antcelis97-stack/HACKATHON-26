<?php
namespace App\Controllers;

use Flight;
use PDO;

class MatchController extends BaseController {
    public function getCandidatosIdoneos($vacanteId) {
        // Consultamos los requerimientos de la vacante (benchmark)
        $stmtVacante = $this->db->prepare("SELECT req_psicometricos, req_tecnicos FROM vacantes WHERE id = ?");
        $stmtVacante->execute([$vacanteId]);
        $vacante = $stmtVacante->fetch(PDO::FETCH_ASSOC);

        if (!$vacante) {
            return Flight::json(['error' => 'Vacante no encontrada'], 404);
        }

        // Consultamos las evaluaciones de todos los egresados
        $stmtEgresados = $this->db->query("SELECT egresado_id, psicometricas, tecnicas FROM evaluaciones");
        $egresados = $stmtEgresados->fetchAll(PDO::FETCH_ASSOC);

        $candidatosIdoneos = [];

        foreach ($egresados as $egresado) {
            // Cálculos basados en los datos cuantitativos
            $coincidenciaTecnica = ($egresado['tecnicas'] / $vacante['req_tecnicos']) * 100;
            $coincidenciaPsicometrica = ($egresado['psicometricas'] / $vacante['req_psicometricos']) * 100;
            
            $promedioCoincidencia = ($coincidenciaTecnica + $coincidenciaPsicometrica) / 2;

            if ($promedioCoincidencia >= 80) {
                $candidatosIdoneos[] = [
                    'egresado_id' => $egresado['egresado_id'],
                    'coincidencia_porcentaje' => round($promedioCoincidencia, 2)
                ];
            }
        }

        // Devolvemos el array filtrado directamente con Flight
        return Flight::json($candidatosIdoneos, 200);
    }
}