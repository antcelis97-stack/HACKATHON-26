<?php
namespace App\Controllers;

use Flight;
use PDO;

class ReporteController extends BaseController {
    /**
     * Reporte de Egresados: Aptitudes y capacidades predominantes (promedios globales)
     */
    public function obtenerAptitudesPredominantes() {
        $stmt = $this->db->query("
            SELECT 
                t.nombre_tipo,
                AVG(r.puntaje) as promedio_puntaje,
                COUNT(r.id_resultado) as total_evaluaciones
            FROM resultados_evaluaciones r
            JOIN tipos_evaluacion t ON r.id_tipo = t.id_tipo
            WHERE r.estado = TRUE
            GROUP BY t.nombre_tipo
        ");
        return Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC), 200);
    }

    /**
     * Reporte Empresarial: Estadísticas de vacantes y tasa de inserción laboral
     */
    public function obtenerEstadisticasEmpresariales() {
        $stmt = $this->db->query("
            SELECT 
                (SELECT COUNT(*) FROM vacantes WHERE estado = TRUE) as vacantes_activas,
                (SELECT COUNT(*) FROM postulaciones WHERE estatus = 'contratado') as egresados_contratados,
                (SELECT COUNT(*) FROM postulaciones) as total_postulaciones,
                ROUND(
                    (SELECT COUNT(*)::numeric FROM postulaciones WHERE estatus = 'contratado') / 
                    NULLIF((SELECT COUNT(*) FROM postulaciones), 0) * 100, 2
                ) as tasa_insercion_porcentaje
        ");
        return Flight::json($stmt->fetch(PDO::FETCH_ASSOC), 200);
    }

    /**
     * Monitoreo Institucional: Empresas con convenio y demanda de perfiles
     */
    public function obtenerMonitoreoInstitucional() {
        $stmt = $this->db->query("
            SELECT 
                e.razon_social,
                e.url_convenio_drive IS NOT NULL as tiene_convenio,
                COUNT(v.id_vacante) as vacantes_publicadas,
                AVG(v.min_psicometrico) as promedio_demanda_psico,
                AVG(v.min_tecnico) as promedio_demanda_tecnica
            FROM empresas e
            LEFT JOIN vacantes v ON e.id_empresa = v.id_empresa
            WHERE e.estado = TRUE
            GROUP BY e.id_empresa, e.razon_social, e.url_convenio_drive
        ");
        return Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC), 200);
    }

    /**
     * Radar individual para Egresado
     */
    public function getRadarCompetencias($usuarioId) {
        $stmt = $this->db->prepare("
            SELECT 
                t.nombre_tipo as competencia,
                r.puntaje
            FROM resultados_evaluaciones r
            JOIN tipos_evaluacion t ON r.id_tipo = t.id_tipo
            JOIN egresados e ON r.cve_alumno = e.cve_alumno
            WHERE e.id_usuario = ? AND r.estado = TRUE
        ");
        $stmt->execute([$usuarioId]);
        return Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC), 200);
    }
        
        return Flight::json(['error' => 'No hay evaluaciones para este egresado'], 404);
    }
}