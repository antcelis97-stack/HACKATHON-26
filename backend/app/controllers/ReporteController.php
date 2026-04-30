<?php
namespace App\Controllers;

use Flight;
use PDO;

class ReporteController extends BaseController {
    /**
     * Reporte 1 (bd.sql): egresados activos por carrera y avance de exámenes.
     * Ruta pública vía reporte-egresados-por-carrera.php; en producción conviene JWT solo admin.
     */
    public function getEgresadosPorCarrera(): void
    {
        $sql = <<<'SQL'
SELECT
    c.id_carrera,
    c.nombre_carrera AS carrera,
    COUNT(e.cve_alumno)::int AS total_egresados,
    COALESCE(SUM(CASE WHEN e.completo_examenes IS TRUE THEN 1 ELSE 0 END), 0)::int AS con_examenes_completos
FROM carreras c
LEFT JOIN egresados e ON e.id_carrera = c.id_carrera AND e.estado IS TRUE
WHERE c.estado IS TRUE
GROUP BY c.id_carrera, c.nombre_carrera
ORDER BY c.nombre_carrera
SQL;

        try {
            $stmt = $this->db->query($sql);
            $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            Flight::json([
                'reporte' => 'egresados_por_carrera',
                'titulo' => 'Egresados por carrera',
                'filas' => $filas,
            ], 200);
        } catch (\Throwable $e) {
            Flight::json([
                'error' => 'No se pudo ejecutar el reporte en la base de datos.',
                'detalle' => $e->getMessage(),
            ], 500);
        }
    }

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