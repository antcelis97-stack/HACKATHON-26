<?php
namespace App\Controllers;

use Flight;
use PDO;

class EgresadoController extends BaseController {
    
    /**
     * GET /api/egresados/{usuarioId}
     * Obtener perfil del egresado
     */
    public function getProfile($usuarioId) {
        try {
            // Consultar información del egresado con datos del usuario
            $stmt = $this->db->prepare("
                SELECT 
                    u.cve_usuario as usuario_id,
                    u.usuario,
                    u.email,
                    e.especialidad,
                    e.trayectoria,
                    e.cv_url,
                    u.nombre
                FROM usuarios u
                LEFT JOIN egresados e ON u.cve_usuario = e.usuario_id
                WHERE u.cve_usuario = ?
            ");
            
            $stmt->execute([$usuarioId]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$profile) {
                return Flight::json(['error' => 'Egresado no encontrado'], 404);
            }
            
            return Flight::json($profile, 200);
            
        } catch (\Exception $e) {
            return Flight::json([
                'error' => 'Error al obtener el perfil',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/egresados/update-profile
     * Actualizar perfil del egresado
     */
    public function updateProfile() {
        try {
            $data = $this->getInput();
            
            // Validación básica
            if (empty($data['usuario_id'])) {
                return Flight::json(['error' => 'usuario_id es requerido'], 400);
            }
            
            // Actualizar tabla usuarios con nombre
            if (!empty($data['nombre'])) {
                $stmtUser = $this->db->prepare("UPDATE usuarios SET nombre = ? WHERE cve_usuario = ?");
                $stmtUser->execute([$data['nombre'], $data['usuario_id']]);
            }
            
            // Actualizar tabla egresados con especialidad, trayectoria y cv_url
            $stmt = $this->db->prepare("
                UPDATE egresados 
                SET 
                    especialidad = ?,
                    trayectoria = ?,
                    cv_url = ?
                WHERE usuario_id = ?
            ");
            
            if ($stmt->execute([
                $data['especialidad'] ?? null,
                $data['trayectoria'] ?? null,
                $data['cv_url'] ?? null,
                $data['usuario_id']
            ])) {
                return Flight::json(['message' => 'Perfil actualizado correctamente'], 200);
            }
            
            return Flight::json(['error' => 'Error al actualizar perfil'], 500);
            
        } catch (\Exception $e) {
            return Flight::json([
                'error' => 'Error al actualizar perfil',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    public function searchVacantes() {
        // Obtenemos los query params (?ubicacion=X&especialidad=Y) usando Flight
        $request = Flight::request();
        $ubicacion = $request->query->ubicacion;
        $especialidad = $request->query->especialidad;

        $query = "SELECT * FROM vacantes WHERE estatus = 'activa'";
        $params = [];

        if ($ubicacion) {
            $query .= " AND ubicacion = ?";
            $params[] = $ubicacion;
        }
        if ($especialidad) {
            $query .= " AND especialidad = ?";
            $params[] = $especialidad;
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $vacantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return Flight::json($vacantes, 200);
    }
}