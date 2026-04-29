<?php
namespace App\Controllers;

use Flight;

class EmpresaController extends BaseController {
    public function createVacante() {
        $data = $this->getInput();
        
        if (!isset($data['empresa_id'], $data['titulo'], $data['req_psicometricos'], $data['req_tecnicos'])) {
            return Flight::json(['error' => 'Faltan datos obligatorios para la vacante'], 400);
        }

        $stmt = $this->db->prepare("INSERT INTO vacantes (empresa_id, titulo, perfil_idoneo, req_psicometricos, req_tecnicos, estatus) VALUES (?, ?, ?, ?, ?, 'activa')");
        
        // perfil_idoneo se guarda como JSON (ej. habilidades blandas)
        if ($stmt->execute([
            $data['empresa_id'], 
            $data['titulo'], 
            json_encode($data['perfil_idoneo'] ?? []), 
            $data['req_psicometricos'], 
            $data['req_tecnicos']
        ])) {
            return Flight::json(['message' => 'Vacante publicada exitosamente'], 201);
        }
        
        return Flight::json(['error' => 'Error al publicar la vacante'], 500);
    }
}