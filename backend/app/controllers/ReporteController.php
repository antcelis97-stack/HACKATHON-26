<?php
namespace App\Controllers;

use Flight;
use PDO;

class ReporteController extends BaseController {
    public function getInsercionLaboral() {
        // Porcentaje de egresados contratados vs registrados, segmentado por carrera
        $stmt = $this->db->query("SELECT carrera, COUNT(*) as total_registrados, SUM(CASE WHEN estatus_laboral = 'contratado' THEN 1 ELSE 0 END) as contratados FROM egresados GROUP BY carrera");
        return Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC), 200);
    }

    public function getMapaCalor() {
        // Concentración geográfica de empresas con convenio
        $stmt = $this->db->query("SELECT ubicacion, COUNT(*) as cantidad_empresas FROM empresas WHERE convenio_activo = true GROUP BY ubicacion");
        return Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC), 200);
    }
    
    public function getRadarCompetencias($egresadoId) {
        // Datos para el Spider Chart (Perfil real vs Perfil ideal)
        $stmt = $this->db->prepare("SELECT psicometricas, cognitivas, tecnicas, proyectivas FROM evaluaciones WHERE egresado_id = ?");
        $stmt->execute([$egresadoId]);
        $resultados = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resultados) {
            return Flight::json($resultados, 200);
        }
        
        return Flight::json(['error' => 'No hay evaluaciones para este egresado'], 404);
    }
}