<?php
/**
 * Punto de entrada API Bolsa de Trabajo (VinculaUT / rutas en routes/api.php).
 * No reemplaza index.php (sandbox SIEst). Usa este archivo cuando conectes el frontend.
 */
require __DIR__ . '/vendor/autoload.php';

use App\Config\Database;
use Flight;

Flight::route('OPTIONS *', function () {
    Flight::response()
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
        ->send();
    exit;
});

Flight::before('start', function () {
    Flight::response()
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
        ->header('Content-Type', 'application/json');
});

Flight::register('db', 'PDO', [], function () {
    $database = new Database();
    return $database->connect();
});

require_once __DIR__ . '/routes/api.php';

Flight::map('notFound', function () {
    Flight::json(['error' => 'Endpoint no encontrado en la API'], 404);
});

Flight::start();
