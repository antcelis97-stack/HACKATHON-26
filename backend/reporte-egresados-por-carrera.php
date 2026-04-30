<?php
/**
 * Entrada HTTP para el reporte "Egresados por carrera" (evita PATH_INFO).
 * Requiere tablas carreras / egresados según backend/db/bd.sql y .env con PG_*.
 */
declare(strict_types=1);

$dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$qs = (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '')
    ? ('?' . $_SERVER['QUERY_STRING'])
    : '';
$_SERVER['REQUEST_URI'] = ($dir === '' ? '' : $dir) . '/api/reportes/egresados-por-carrera' . $qs;

require __DIR__ . '/index-bolsa.php';
