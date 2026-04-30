<?php
namespace App\Controllers;

use Flight;
use PDO;

class EvaluacionController extends BaseController {
    /**
     * POST /api/v1/evaluaciones
     * Registrar un resultado de evaluación individual (Puntaje Total)
     */
    public function saveResultados() {
        $data = $this->getInput();
        
        if (!isset($data['cve_alumno'], $data['id_tipo'], $data['puntaje'])) {
            return Flight::json(['error' => 'cve_alumno, id_tipo y puntaje son requeridos'], 400);
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO resultados_evaluaciones (cve_alumno, id_tipo, puntaje) 
                VALUES (?, ?, ?)
                ON CONFLICT (cve_alumno, id_tipo) 
                DO UPDATE SET puntaje = EXCLUDED.puntaje, fecha_registro = CURRENT_DATE, hora_registro = CURRENT_TIME
            ");
            
            $stmt->execute([
                $data['cve_alumno'], 
                $data['id_tipo'], 
                $data['puntaje']
            ]);

            return Flight::json(['mensaje' => 'Evaluación registrada o actualizada con éxito'], 201);

        } catch (\Exception $e) {
            return Flight::json(['error' => 'Error al guardar resultado', 'detalle' => $e->getMessage()], 500);
        }
    }
}