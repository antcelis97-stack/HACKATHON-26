<?php
namespace App\Controllers;

use Flight;

class GoogleDriveController extends BaseController {
    public function uploadCV() {
        $data = $this->getInput();
        
        if(empty($data['file_base64'])) {
            return Flight::json(['error' => 'El archivo es requerido'], 400);
        }
        
        // Aquí iría la lógica real de subida a Google Drive o al servidor local de la UT
        // Por ahora simulamos la respuesta exitosa
        $mockUrl = "https://drive.google.com/file/d/ejemplo_id_cv_egresado/view";
        
        return Flight::json([
            'message' => 'CV subido correctamente', 
            'cv_url' => $mockUrl
        ], 200);
    }
}