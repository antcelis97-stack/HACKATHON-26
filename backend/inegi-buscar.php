<?php
/**
 * Entrada estable para la búsqueda INEGI (evita PATH_INFO en index-bolsa.php).
 * URL: .../backend/inegi-buscar.php?keyword=...&lat=...&lon=...&radio=...
 */
declare(strict_types=1);

$dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$qs = (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '')
    ? ('?' . $_SERVER['QUERY_STRING'])
    : '';
$_SERVER['REQUEST_URI'] = ($dir === '' ? '' : $dir) . '/api/inegi/buscar' . $qs;

require __DIR__ . '/index-bolsa.php';
