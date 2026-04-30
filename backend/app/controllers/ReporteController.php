<?php
namespace App\Controllers;

use Flight;
use PDO;

class ReporteController extends BaseController {

    // --- 1. GESTIÓN INSTITUCIONAL (ADMINISTRACIÓN UT) ---

    public function getInsercionLaboral() {
        // Porcentaje de contratados vs registrados segmentado por carrera.
        $stmt = $this->db->query("SELECT carrera, COUNT(*) as total, 
                SUM(CASE WHEN contratado = true THEN 1 ELSE 0 END) as contratados 
                FROM egresados GROUP BY carrera");
        return Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC), 200);
    }

    public function getMapaCalor() {
        // Concentración geográfica: Zona de influencia vs Nacional.
        $stmt = $this->db->query("SELECT ubicacion, COUNT(*) as empresas 
                FROM empresas WHERE convenio_activo = true GROUP BY ubicacion");
        return Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC), 200);
    }

    public function getRankingCompetencias() {
        // Habilidades técnicas y blandas solicitadas con mayor frecuencia.
        // Se asume que 'habilidades' es una columna tipo JSONB o texto.
        $stmt = $this->db->query("SELECT habilidad, COUNT(*) as demanda 
                FROM vacante_habilidades GROUP BY habilidad ORDER BY demanda DESC LIMIT 10");
        return Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC), 200);
    }

    public function getEstatusConvenios() {
        // Reporte administrativo de convenios y formalización vía API.
        $stmt = $this->db->query("SELECT nombre_empresa, fecha_vencimiento, 
                CASE WHEN origen_api = true THEN 'Pendiente Formalizar' ELSE 'Activo' END as estatus 
                FROM empresas WHERE convenio_activo = true");
        return Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC), 200);
    }

    // --- 2. REPORTES DE TALENTO (EGRESADO) ---

    public function getHistograma($egresadoId) {
        // Cuantificación de capacidades en las 4 categorías.
        $stmt = $this->db->prepare("SELECT psicometricas, cognitivas, tecnicas, proyectivas 
                FROM evaluaciones WHERE egresado_id = ?");
        $stmt->execute([$egresadoId]);
        return Flight::json($stmt->fetch(PDO::FETCH_ASSOC), 200);
    }

    public function getRadarComparativo($egresadoId, $vacanteId) {
        // Spider Chart: Perfil real vs Perfil ideal de la vacante.
        $stmt = $this->db->prepare("
            SELECT e.psicometricas as real_psi, e.tecnicas as real_tec, 
                   v.req_psicometricos as ideal_psi, v.req_tecnicos as ideal_tec
            FROM evaluaciones e, vacantes v 
            WHERE e.egresado_id = ? AND v.id = ?");
        $stmt->execute([$egresadoId, $vacanteId]);
        return Flight::json($stmt->fetch(PDO::FETCH_ASSOC), 200);
    }

    // --- 3. SECTOR EMPRESARIAL ---

    public function getAnaliticaVacantes($empresaId) {
        // Postulaciones y tiempo promedio de cobertura.
        $stmt = $this->db->prepare("SELECT titulo, COUNT(p.id) as postulaciones, 
                AVG(p.fecha_contratacion - v.fecha_publicacion) as dias_cobertura
                FROM vacantes v LEFT JOIN postulaciones p ON v.id = p.vacante_id 
                WHERE v.empresa_id = ? GROUP BY v.id");
        $stmt->execute([$empresaId]);
        return Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC), 200);
    }

    public function getPlantillaUT($empresaId) {
        // Listado de egresados laborando actualmente en la empresa.
        $stmt = $this->db->prepare("SELECT nombre, carrera, evaluacion_desempeno 
                FROM egresados WHERE empresa_actual_id = ?");
        $stmt->execute([$empresaId]);
        return Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC), 200);
    }
}