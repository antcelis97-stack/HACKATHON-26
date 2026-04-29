<?php
require 'vendor/autoload.php';

use App\Config\Database;

// 1. Configuración estricta de CORS para compatibilidad con Angular
Flight::route('OPTIONS *', function() {
    Flight::response()
        ->header('Access-Control-Allow-Origin', '*') // En producción cambiar por la URL de Angular
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
        ->send();
    exit;
});

// 2. Middleware para parsear todo a JSON automáticamente
Flight::before('start', function() {
    Flight::response()
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
        ->header('Content-Type', 'application/json');
});

// 3. Inyección de dependencias: Registrar la conexión a PostgreSQL
Flight::register('db', 'PDO', array(), function() {
    $database = new Database();
    return $database->connect();
});

// 4. Importar el manejador central de rutas
require_once 'routes/api.php';
use Flight;

// 5. Interceptar errores 404 para no devolver HTML
Flight::map('notFound', function(){
    Flight::json(['error' => 'Endpoint no encontrado en la API'], 404);
});

// Arrancar el motor de Flight
Flight::start();