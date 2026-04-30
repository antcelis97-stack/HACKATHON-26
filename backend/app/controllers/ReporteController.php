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
                (SELECT COUNT(*) FROM convenios c WHERE c.id_empresa = e.id_empresa AND c.estatus = 'activo') > 0 as tiene_convenio_activo,
                COUNT(v.id_vacante) as vacantes_publicadas,
                AVG(v.min_psicometrico) as promedio_demanda_psico,
                AVG(v.min_tecnico) as promedio_demanda_tecnica
            FROM empresas e
            LEFT JOIN vacantes v ON e.id_empresa = v.id_empresa
            WHERE e.estado = TRUE
            GROUP BY e.id_empresa, e.razon_social
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

    /**
     * Reporte de Inserción Laboral: Segmentado por carrera
     */
    public function obtenerInsercionLaboralPorCarrera() {
        try {
            $stmt = $this->db->query("
                SELECT 
                    c.nombre_carrera,
                    COUNT(DISTINCT e.cve_alumno) as total_egresados,
                    COUNT(DISTINCT CASE WHEN p.estatus = 'contratado' THEN e.cve_alumno END) as contratados,
                    ROUND(
                        COUNT(DISTINCT CASE WHEN p.estatus = 'contratado' THEN e.cve_alumno END)::numeric / 
                        NULLIF(COUNT(DISTINCT e.cve_alumno), 0) * 100, 2
                    ) as porcentaje_insercion
                FROM carreras c
                LEFT JOIN egresados e ON c.id_carrera = e.id_carrera
                LEFT JOIN postulaciones p ON e.cve_alumno = p.cve_alumno
                GROUP BY c.id_carrera, c.nombre_carrera
                ORDER BY porcentaje_insercion DESC
            ");
            return Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC), 200);
        } catch (\Exception $e) {
            return Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Monitoreo de Estatus de Convenios: Clasificación estratégica (Tabla Normalizada)
     */
    public function obtenerEstatusConvenios() {
        try {
            $stmt = $this->db->query("
                SELECT 
                    e.id_empresa,
                    e.razon_social,
                    c.url_archivo_drive,
                    c.fecha_vencimiento,
                    c.estatus as estatus_convenio,
                    c.comentarios
                FROM empresas e
                LEFT JOIN convenios c ON e.id_empresa = c.id_empresa
                WHERE e.estado = TRUE
                ORDER BY 
                    CASE c.estatus 
                        WHEN 'en_revision' THEN 1 
                        WHEN 'pendiente' THEN 2 
                        WHEN 'activo' THEN 3 
                        ELSE 4 
                    END
            ");
            return Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC), 200);
        } catch (\Exception $e) {
            return Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Reporte de Indicadores de Éxito Laboral: Métricas temporales y por carrera
     */
    public function obtenerEstadisticasContratacion() {
        try {
            $stmt = $this->db->query("
                SELECT 
                    -- Métricas Temporales
                    (SELECT COUNT(*) FROM contrataciones) as total_general,
                    (SELECT COUNT(*) FROM contrataciones WHERE fecha_contratacion >= CURRENT_DATE - INTERVAL '30 days') as este_mes,
                    (SELECT COUNT(*) FROM contrataciones WHERE fecha_contratacion >= DATE_TRUNC('year', CURRENT_DATE)) as este_anio,
                    
                    -- Desglose por Carrera
                    (
                        SELECT json_agg(carreras_stats)
                        FROM (
                            SELECT 
                                cr.nombre_carrera,
                                COUNT(cn.id_contratacion) as contrataciones_totales
                            FROM carreras cr
                            LEFT JOIN egresados eg ON cr.id_carrera = eg.id_carrera
                            LEFT JOIN contrataciones cn ON eg.cve_alumno = cn.cve_alumno
                            GROUP BY cr.nombre_carrera
                        ) carreras_stats
                    ) as desglose_por_carrera
            ");
            return Flight::json($stmt->fetch(PDO::FETCH_ASSOC), 200);
        } catch (\Exception $e) {
            return Flight::json(['error' => $e->getMessage()], 500);
        }
    }
}