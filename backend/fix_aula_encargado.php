<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = getPgInventarioConnection();
    
    // Check if column exists first
    $stmt = $pdo->query("
        SELECT column_name 
        FROM information_schema.columns 
        WHERE table_name='aula' and column_name='encargado'
    ");
    
    if ($stmt->fetch()) {
        echo "La columna 'encargado' ya existe en la tabla aula.\n";
    } else {
        $pdo->exec("ALTER TABLE aula ADD COLUMN encargado INT REFERENCES persona(cve_persona)");
        echo "¡Columna 'encargado' agregada correctamente a la tabla aula en Supabase!\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
