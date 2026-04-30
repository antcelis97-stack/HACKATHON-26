<?php
namespace App\Controllers;

use Flight;
use PDO;

class ReporteController extends BaseController {

    // --- 1. GESTIÓN INSTITUCIONAL (ADMINISTRACIÓN UT) ---

    public function getInsercionLaboral() {
        // Porcentaje de contratados vs registrados segmentado por carrera.
        $stmt = $this->db->query("SELECT c.nombre_carrera as carrera, COUNT(e.cve_alumno) as total, 
                SUM(CASE WHEN e.estatus_laboral = 'contratado' THEN 1 ELSE 0 END) as contratados 
                FROM carreras c
                LEFT JOIN egresados e ON c.id_carrera = e.id_carrera
                GROUP BY c.nombre_carrera");
        return Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC), 200);
    }

    public function getMapaCalor() {
        // Concentración geográfica: Zona de influencia vs Nacional.
        $stmt = $this->db->query("SELECT ubicacion, COUNT(*) as empresas 
                FROM empresas WHERE estado = true GROUP BY ubicacion");
        return Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC), 200);
    }

    public function getRankingCompetencias() {
        // Habilidades técnicas y blandas solicitadas con mayor frecuencia.
        $stmt = $this->db->query("SELECT titulo_puesto as habilidad, COUNT(*) as demanda 
                FROM vacantes GROUP BY titulo_puesto ORDER BY demanda DESC LIMIT 10");
        return Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC), 200);
    }

    public function getEstatusConvenios() {
        // Reporte administrativo de convenios.
        $stmt = $this->db->query("SELECT razon_social as nombre_empresa, 
                (SELECT fecha_vencimiento FROM convenios c WHERE c.id_empresa = e.id_empresa LIMIT 1) as fecha_vencimiento,
                (SELECT estatus FROM convenios c WHERE c.id_empresa = e.id_empresa LIMIT 1) as estatus
                FROM empresas e WHERE estado = true");
        return Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC), 200);
    }

    // --- 2. REPORTES DE TALENTO (EGRESADO) ---

    /**
     * GET /api/v1/reportes/encabezado/@id
     * Obtiene los datos básicos para el encabezado de un reporte de egresado
     */
    public function getEncabezadoReporte($id_usuario) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    (e.nombre || ' ' || e.apellidos) as nombre_completo,
                    e.foto_url,
                    c.nombre_carrera as carrera
                FROM egresados e
                JOIN carreras c ON e.id_carrera = c.id_carrera
                WHERE e.id_usuario = ?
            ");
            $stmt->execute([$id_usuario]);
            $datos = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$datos) {
                return Flight::json(['error' => 'Egresado no encontrado'], 404);
            }

            return Flight::json($datos, 200);
        } catch (\Exception $e) {
            return Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getHistograma($egresadoId) {
        // Cuantificación de capacidades en las 4 categorías.
        $stmt = $this->db->prepare("SELECT min_psicometrico as psicometricas, min_cognitivo as cognitivas, 
                min_tecnico as tecnicas, min_proyectivo as proyectivas 
                FROM egresados WHERE id_usuario = ?");
        $stmt->execute([$egresadoId]);
        return Flight::json($stmt->fetch(PDO::FETCH_ASSOC), 200);
    }

    public function getRadarCompetencias($egresadoId) {
        // Alias para compatibilidad con la ruta de la API
        return $this->getHistograma($egresadoId);
    }

    public function getRadarComparativo($egresadoId, $vacanteId) {
        // Spider Chart: Perfil real vs Perfil ideal de la vacante.
        $stmt = $this->db->prepare("
            SELECT e.min_psicometrico as real_psi, e.min_tecnico as real_tec, 
                   v.min_psicometrico as ideal_psi, v.min_tecnico as ideal_tec
            FROM egresados e, vacantes v 
            WHERE e.id_usuario = ? AND v.id_vacante = ?");
        $stmt->execute([$egresadoId, $vacanteId]);
        return Flight::json($stmt->fetch(PDO::FETCH_ASSOC), 200);
    }

    // --- 3. SECTOR EMPRESARIAL ---

    public function getAnaliticaVacantes($empresaId) {
        // Postulaciones y tiempo promedio de cobertura.
        $stmt = $this->db->prepare("SELECT titulo_puesto as titulo, COUNT(p.id_postulacion) as postulaciones
                FROM vacantes v LEFT JOIN postulaciones p ON v.id_vacante = p.id_vacante 
                WHERE v.id_empresa = ? GROUP BY v.id_vacante, v.titulo_puesto");
        $stmt->execute([$empresaId]);
        return Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC), 200);
    }

    public function getPlantillaUT($empresaId) {
        // Listado de egresados laborando actualmente en la empresa.
        $stmt = $this->db->prepare("SELECT nombre, apellidos, estatus_laboral 
                FROM egresados WHERE id_usuario IN (SELECT id_usuario FROM usuarios WHERE rol = 'egresado')");
        // Nota: Esta consulta debe ajustarse según cómo registres la relación empresa-egresado contratado
        $stmt->execute();
        return Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC), 200);
    }
}