<?php
namespace App\Controllers;

use Flight;
use PDO;

class MatchController extends BaseController {

    /**
     * GET /api/v1/match/ranking/@vacanteId
     * Ranking de candidatos con semáforo y diagnóstico de brechas.
     * Soporta filtro opcional ?semaforo=verde
     */
    public function getRankingByVacante($vacanteId) {
        $filtroSemaforo = Flight::request()->query['semaforo'] ?? null;

        try {
            // 1. Benchmark de la vacante
            $stmtVacante = $this->db->prepare("
                SELECT min_psicometrico, min_cognitivo, min_tecnico, min_proyectivo 
                FROM vacantes WHERE id_vacante = ?
            ");
            $stmtVacante->execute([$vacanteId]);
            $benchmark = $stmtVacante->fetch(PDO::FETCH_ASSOC);

            if (!$benchmark) return Flight::json(['error' => 'Vacante no encontrada'], 404);

            // 2. Puntajes de egresados
            $stmtEgresados = $this->db->query("
                SELECT 
                    e.cve_alumno, e.nombre, e.apellido_paterno, e.apellido_materno,
                    MAX(CASE WHEN t.nombre_tipo = 'Psicométrica' THEN r.puntaje ELSE 0 END) as psico,
                    MAX(CASE WHEN t.nombre_tipo = 'Cognitiva' THEN r.puntaje ELSE 0 END) as cog,
                    MAX(CASE WHEN t.nombre_tipo = 'Técnica' THEN r.puntaje ELSE 0 END) as tec,
                    MAX(CASE WHEN t.nombre_tipo = 'Proyectiva' THEN r.puntaje ELSE 0 END) as proy
                FROM egresados e
                JOIN resultados_evaluaciones r ON e.cve_alumno = r.cve_alumno
                JOIN tipos_evaluacion t ON r.id_tipo = t.id_tipo
                WHERE e.estado = TRUE
                GROUP BY e.cve_alumno, e.nombre, e.apellido_paterno, e.apellido_materno
            ");
            $egresados = $stmtEgresados->fetchAll(PDO::FETCH_ASSOC);

            $ranking = [];

            foreach ($egresados as $egre) {
                // Cálculo de match
                $pPsico = ($benchmark['min_psicometrico'] > 0) ? ($egre['psico'] / $benchmark['min_psicometrico']) : 1;
                $pCog   = ($benchmark['min_cognitivo'] > 0) ? ($egre['cog'] / $benchmark['min_cognitivo']) : 1;
                $pTec   = ($benchmark['min_tecnico'] > 0) ? ($egre['tec'] / $benchmark['min_tecnico']) : 1;
                $pProy  = ($benchmark['min_proyectivo'] > 0) ? ($egre['proy'] / $benchmark['min_proyectivo']) : 1;

                $totalMatch = (min($pPsico, 1) + min($pCog, 1) + min($pTec, 1) + min($pProy, 1)) / 4 * 100;
                $porcentaje = round($totalMatch, 2);

                // Lógica de Semáforo
                $semaforo = 'rojo';
                if ($porcentaje >= 80) $semaforo = 'verde';
                elseif ($porcentaje >= 60) $semaforo = 'amarillo';

                // Alertas de brecha técnica
                $alertas = [];
                if ($egre['tec'] < $benchmark['min_tecnico']) {
                    $alertas[] = "Brecha Técnica Detectada";
                }

                $candidato = [
                    'cve_alumno' => $egre['cve_alumno'],
                    'nombre_completo' => $egre['nombre'] . ' ' . $egre['apellido_paterno'] . ' ' . $egre['apellido_materno'],
                    'match_porcentaje' => $porcentaje,
                    'semaforo' => $semaforo,
                    'alertas' => $alertas,
                    'detalles' => [
                        'psicometrica' => $egre['psico'],
                        'cognitiva' => $egre['cog'],
                        'tecnica' => $egre['tec'],
                        'proyectiva' => $egre['proy']
                    ]
                ];

                // Aplicar filtro si existe
                if ($filtroSemaforo) {
                    if ($semaforo === strtolower($filtroSemaforo)) {
                        $ranking[] = $candidato;
                    }
                } else {
                    $ranking[] = $candidato;
                }
            }

            // Ordenar por ranking
            usort($ranking, function($a, $b) {
                return $b['match_porcentaje'] <=> $a['match_porcentaje'];
            });

            return Flight::json($ranking, 200);

        } catch (\Exception $e) {
            return Flight::json(['error' => $e->getMessage()], 500);
        }
    }
}