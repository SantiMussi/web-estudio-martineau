<?php
/**
 * admin/migrate_oculto.php — Script de migración
 * 
 * Agrega la columna "oculto" a las tablas "productos" y "proyectos".
 * Eliminar este archivo luego de ejecutarlo.
 */

require_once __DIR__ . '/config.php';

try {
    $pdo->exec("ALTER TABLE productos ADD COLUMN oculto TINYINT(1) NOT NULL DEFAULT 0;");
    echo "Columna 'oculto' agregada a la tabla 'productos' correctamente.<br>";
} catch (Exception $e) {
    echo "Error agregando columna 'oculto' a 'productos': " . $e->getMessage() . "<br>";
}

try {
    $pdo->exec("ALTER TABLE proyectos ADD COLUMN oculto TINYINT(1) NOT NULL DEFAULT 0;");
    echo "Columna 'oculto' agregada a la tabla 'proyectos' correctamente.<br>";
} catch (Exception $e) {
    echo "Error agregando columna 'oculto' a 'proyectos': " . $e->getMessage() . "<br>";
}

echo "<br><b>Migración finalizada. ¡Por favor eliminá este archivo (migrate_oculto.php) por seguridad!</b>";
