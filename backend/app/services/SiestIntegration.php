<?php
namespace App\Services;

class SiestIntegration {
    /**
     * Simula la validación de egresados mediante la API del SIEst 2.0.
     * Permite precargar datos cuantitativos oficiales para los reportes.
     */
    public function getDatosEgresado($matricula) {
        // Este Mock representa la respuesta que la DITI enviaría desde su base de datos escolar
        $db_siest = [
            'UT2022001' => [
                'nombre' => 'Antonio Salvador',
                'apellidos' => 'López Celis',
                'carrera' => 'Desarrollo de Software Multiplataforma',
                'estatus' => 'Egresado',
                'promedio_escolar' => 9.4
            ],
            'UT2022002' => [
                'nombre' => 'María José',
                'apellidos' => 'García Ruiz',
                'carrera' => 'Gastronomía',
                'estatus' => 'Titulado',
                'promedio_escolar' => 9.1
            ],
            'UT2022003' => [
                'nombre' => 'Juan Pedro',
                'apellidos' => 'Pérez Nayar',
                'carrera' => 'Acuicultura',
                'estatus' => 'Egresado',
                'promedio_escolar' => 8.9
            ],
            'UT2022004' => [
                'nombre' => 'Carla Estefanía',
                'apellidos' => 'Méndez Hurtado',
                'carrera' => 'Turismo',
                'estatus' => 'Pasante',
                'promedio_escolar' => 9.7
            ]
        ];

        $matriculaKey = strtoupper(trim($matricula));

        if (array_key_exists($matriculaKey, $db_siest)) {
            return [
                'success' => true,
                'source' => 'Sincronizado con SIEst 2.0',
                'data' => $db_siest[$matriculaKey]
            ];
        }

        return [
            'success' => false,
            'message' => 'Matrícula no encontrada en el padrón de la UT de la Costa'
        ];
    }
}