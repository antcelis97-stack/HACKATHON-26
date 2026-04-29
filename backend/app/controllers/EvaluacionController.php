<?php
namespace App\Controllers;

use Flight;

class EvaluacionController extends BaseController {
    public function saveResultados() {
        $data = $this->getInput();
        
        $stmt = $this->db->prepare("INSERT INTO evaluaciones (egresado_id, psicometricas, cognitivas, tecnicas, proyectivas) VALUES (?, ?, ?, ?, ?)");
        
        if ($stmt->execute([
            $data['egresado_id'], 
            $data['psicometricas'], 
            $data['cognitivas'], 
            $data['tecnicas'], 
            $data['proyectivas']
        ])) {
            return Flight::json(['message' => 'Evaluación registrada con éxito'], 201);
        }
        
        return Flight::json(['error' => 'Error al guardar los resultados'], 500);
    }
}