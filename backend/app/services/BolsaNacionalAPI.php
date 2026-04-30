<?php
namespace App\Services;

class BolsaNacionalAPI {
    // Adzuna es ideal porque cubre todos los sectores laborales
    private $appId = 'b86c4ec8';
    private $appKey = 'ebb6c2faf2b6801bc92eb6282a60a1c7';

    /**
     * Obtiene vacantes de diversas áreas para cubrir todas las carreras de la UT.
     * @param string $busqueda Palabra clave (ej. 'Contabilidad', 'Gastronomia', 'Acuicultura')
     */
    public function getVacantesNacionales($busqueda = 'empleo') {
        // Filtramos por México (mx) y permitimos búsqueda abierta
        $url = "https://api.adzuna.com/v1/api/jobs/mx/search/1?app_id={$this->appId}&app_key={$this->appKey}&results_per_page=15&what=" . urlencode($busqueda);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $data = json_decode($response, true);
        
        // ¡curl_close($ch); ha sido eliminado para compatibilidad limpia con PHP 8.5!

        if (!isset($data['results'])) return ['error' => 'No se encontraron vacantes'];

        return array_map(function($job) {
            return [
                'titulo' => $job['title'],
                'empresa' => $job['company']['display_name'] ?? 'Empresa Confidencial',
                'ubicacion' => $job['location']['display_name'],
                'descripcion' => $job['description'],
                'url' => $job['redirect_url'],
                'fuente' => 'Adzuna Nacional',
                // Intentamos identificar la categoría para el frontend
                'categoria' => $job['category']['label'] ?? 'General'
            ];
        }, $data['results']);
    }
}