<?php
namespace App\Controllers;

use Flight;
use PDO;

class MatchController extends BaseController {

    /**
     * GET /api/v1/match/talento/@vacanteId
     * Algoritmo de coincidencia (Match) superior al 80%
     */
    public function matchTalento($vacanteId) {
        try {
            // 1. Obtener los requerimientos (benchmark) de la vacante
            $stmtVacante = $this->db->prepare("
                SELECT min_psicometrico, min_cognitivo, min_tecnico, min_proyectivo 
                FROM vacantes WHERE id_vacante = ?
            ");
            $stmtVacante->execute([$vacanteId]);
            $benchmark = $stmtVacante->fetch(PDO::FETCH_ASSOC);

            if (!$benchmark) {
                return Flight::json(['error' => 'Vacante no encontrada'], 404);
            }

            // 2. Obtener puntajes de todos los egresados que han hecho pruebas
            $stmtEgresados = $this->db->query("
                SELECT 
                    e.cve_alumno, e.nombre, e.apellido_paterno,
                    MAX(CASE WHEN t.nombre_tipo = 'Psicométrica' THEN r.puntaje ELSE 0 END) as psico,
                    MAX(CASE WHEN t.nombre_tipo = 'Cognitiva' THEN r.puntaje ELSE 0 END) as cog,
                    MAX(CASE WHEN t.nombre_tipo = 'Técnica' THEN r.puntaje ELSE 0 END) as tec,
                    MAX(CASE WHEN t.nombre_tipo = 'Proyectiva' THEN r.puntaje ELSE 0 END) as proy
                FROM egresados e
                JOIN resultados_evaluaciones r ON e.cve_alumno = r.cve_alumno
                JOIN tipos_evaluacion t ON r.id_tipo = t.id_tipo
                WHERE e.estado = TRUE
                GROUP BY e.cve_alumno, e.nombre, e.apellido_paterno
            ");
            $egresados = $stmtEgresados->fetchAll(PDO::FETCH_ASSOC);

            $finalistas = [];

            foreach ($egresados as $egre) {
                // Calcular porcentaje de coincidencia por cada pilar (evitando división por cero)
                $pPsico = ($benchmark['min_psicometrico'] > 0) ? ($egre['psico'] / $benchmark['min_psicometrico']) : 1;
                $pCog   = ($benchmark['min_cognitivo'] > 0) ? ($egre['cog'] / $benchmark['min_cognitivo']) : 1;
                $pTec   = ($benchmark['min_tecnico'] > 0) ? ($egre['tec'] / $benchmark['min_tecnico']) : 1;
                $pProy  = ($benchmark['min_proyectivo'] > 0) ? ($egre['proy'] / $benchmark['min_proyectivo']) : 1;

                // Promedio total de coincidencia (limitado a 1 para que no pase del 100% si el egresado es mejor)
                $totalMatch = (min($pPsico, 1) + min($pCog, 1) + min($pTec, 1) + min($pProy, 1)) / 4 * 100;

                if ($totalMatch >= 80) {
                    $finalistas[] = [
                        'cve_alumno' => $egre['cve_alumno'],
                        'nombre_completo' => $egre['nombre'] . ' ' . $egre['apellido_paterno'],
                        'coincidencia' => round($totalMatch, 2)
                    ];
                }
            }

            // Ordenar por mayor coincidencia
            usort($finalistas, function($a, $b) {
                return $b['coincidencia'] <=> $a['coincidencia'];
            });

            return Flight::json($finalistas, 200);

        } catch (\Exception $e) {
            return Flight::json(['error' => $e->getMessage()], 500);
        }
    }
}