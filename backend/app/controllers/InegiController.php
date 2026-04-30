<?php
namespace App\Controllers;

use Flight;

/**
 * Proxy seguro hacia la API DENUE del INEGI (evita CORS del navegador).
 * Documentación: https://www.inegi.org.mx/servicios/api_denue.html
 */
class InegiController
{
    public function buscar(): void
    {
        $req = Flight::request();
        $keyword = trim((string) ($req->query->keyword ?? ''));
        if ($keyword === '') {
            Flight::json(['error' => 'Parámetro keyword requerido', 'source' => 'none'], 400);
            return;
        }

        $lat = preg_replace('/[^0-9.\-]/', '', (string) ($req->query->lat ?? '21.50')) ?: '21.50';
        $lon = preg_replace('/[^0-9.\-]/', '', (string) ($req->query->lon ?? '-104.89')) ?: '-104.89';
        $radio = preg_replace('/[^0-9]/', '', (string) ($req->query->radio ?? '5000')) ?: '5000';
        if ((int) $radio > 5000) {
            $radio = '5000';
        }

        $token = trim((string) ($_ENV['INEGI_DENUE_TOKEN'] ?? ''));
        if ($token === '' || $token === 'TU_TOKEN_AQUI') {
            Flight::json([
                'error' => 'Configura INEGI_DENUE_TOKEN en el archivo .env del backend (token del portal INEGI).',
                'source' => 'none',
            ], 503);
            return;
        }

        $condicion = rawurlencode($keyword);
        $url = "https://www.inegi.org.mx/app/api/denue/v1/consulta/Buscar/{$condicion}/{$lat},{$lon}/{$radio}/{$token}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0) {
            $det = function_exists('curl_strerror') ? curl_strerror($errno) : ('cURL errno ' . $errno);
            Flight::json([
                'error' => 'Error de red al contactar INEGI',
                'detalle' => $det,
                'source' => 'none',
            ], 502);
            return;
        }

        if ($http !== 200) {
            Flight::json([
                'error' => 'INEGI respondió con código HTTP distinto de 200',
                'http' => $http,
                'cuerpo' => is_string($body) ? substr($body, 0, 500) : null,
                'source' => 'none',
            ], 502);
            return;
        }

        $data = json_decode((string) $body, true);
        if (!is_array($data)) {
            Flight::json([
                'error' => 'La respuesta de INEGI no es JSON válido (revisa el token o la condición de búsqueda).',
                'source' => 'none',
            ], 502);
            return;
        }

        $normalized = [];
        foreach ($data as $row) {
            if (!is_array($row)) {
                continue;
            }
            $normalized[] = $this->normalizeDenueRow($row);
        }

        Flight::json([
            'source' => 'inegi',
            'results' => $normalized,
        ], 200);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, string>
     */
    private function normalizeDenueRow(array $row): array
    {
        $ubicacion = (string) ($row['Ubicacion'] ?? '');
        $municipio = '';
        $entidad = '';
        if ($ubicacion !== '') {
            $parts = array_map('trim', explode(',', $ubicacion));
            $n = count($parts);
            if ($n >= 2) {
                $entidad = $parts[$n - 1] ?? '';
                $municipio = $parts[$n - 2] ?? '';
            } else {
                $municipio = $ubicacion;
            }
        }

        return [
            'Nombre' => (string) ($row['Nombre'] ?? ''),
            'Clase_actividad' => (string) ($row['Clase_actividad'] ?? ''),
            'Estrato' => (string) ($row['Estrato'] ?? ''),
            'Municipio' => $municipio,
            'Entidad' => $entidad,
            'Ubicacion' => $ubicacion,
            'Latitud' => (string) ($row['Latitud'] ?? ''),
            'Longitud' => (string) ($row['Longitud'] ?? ''),
        ];
    }
}
