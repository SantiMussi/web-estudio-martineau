<?php
/*
 * Cambios en la estructura de la base que necesitan las funciones nuevas del panel.
 *
 * Se aplican solos la primera vez que se entra al panel después de actualizar el
 * código (auth.php llama a migrar_base() una vez por sesión), así no hay que correr
 * nada a mano en el servidor. Cada paso se fija si ya está hecho antes de aplicarse.
 *
 * Al agregar un paso nuevo, subir ESQUEMA_VERSION para que se vuelva a revisar.
 */

const ESQUEMA_VERSION = 1;

function migrar_base(PDO $pdo): void
{
    if (($_SESSION['esquema_version'] ?? 0) === ESQUEMA_VERSION) return;

    // Datos de contacto editables (admin/ajustes.php)
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS ajustes (
            clave VARCHAR(50) PRIMARY KEY,
            valor TEXT NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ');

    // Papelera: al eliminar, el ítem queda oculto con la fecha de borrado y se puede restaurar
    foreach (['productos', 'proyectos'] as $tabla) {
        if (!migracion_columna_existe($pdo, $tabla, 'eliminado_at')) {
            $pdo->exec("ALTER TABLE $tabla ADD COLUMN eliminado_at DATETIME NULL DEFAULT NULL");
        }
        if (!migracion_columna_existe($pdo, $tabla, 'oculto_antes')) {
            // Si estaba oculto antes de ir a la papelera, al restaurarlo vuelve oculto
            $pdo->exec("ALTER TABLE $tabla ADD COLUMN oculto_antes TINYINT(1) NOT NULL DEFAULT 0");
        }
    }

    $_SESSION['esquema_version'] = ESQUEMA_VERSION;
}

function migracion_columna_existe(PDO $pdo, string $tabla, string $columna): bool
{
    $stmt = $pdo->prepare('
        SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tabla AND COLUMN_NAME = :columna
    ');
    $stmt->execute(['tabla' => $tabla, 'columna' => $columna]);
    return (int)$stmt->fetchColumn() > 0;
}
