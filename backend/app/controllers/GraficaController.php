<?php
namespace App\Controllers;

use Flight;
use App\Lib\ResponseFormatter;
use App\Lib\UnauthorizedException;

class GraficaController extends BaseController {

    /**
     * GET /api/v1/graficas/radar/@usuarioId
     * Genera la estructura JSON para el Spider Chart de Angular
     */
    public function generateSkillGraph($usuarioId) {
        try {
            // Consultamos los resultados del egresado cruzando con los nombres de las pruebas
            $stmt = $this->db->prepare("
                SELECT 
                    t.nombre_tipo as habilidad,
                    r.puntaje
                FROM resultados_evaluaciones r
                JOIN tipos_evaluacion t ON r.id_tipo = t.id_tipo
                JOIN egresados e ON r.cve_alumno = e.cve_alumno
                WHERE e.id_usuario = ? AND r.estado = TRUE
            ");
            
            $stmt->execute([$usuarioId]);
            $resultados = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (!$resultados) {
                return Flight::json(['error' => 'No hay datos de evaluación para este usuario'], 404);
            }

            // Formateamos la respuesta para que Angular la consuma directamente
            $labels = [];
            $data = [];

            foreach ($resultados as $row) {
                $labels[] = $row['habilidad'];
                $data[] = (float)$row['puntaje'];
            }

            return Flight::json([
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Perfil del Egresado',
                        'data' => $data
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return Flight::json(['error' => $e->getMessage()], 500);
        }
    }
}