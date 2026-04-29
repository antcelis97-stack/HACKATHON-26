<?php
/**
 * Entry Point - SIEstSandboxBackend
 */

// =============================================================================
// CORS — Permitir peticiones desde el frontend Angular
// Debe ir ANTES de cargar Flight para garantizar headers en todas las respuestas
// =============================================================================
header('Access-Control-Allow-Origin: http://localhost:4200');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Max-Age: 86400');
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/routes/routes.php';
