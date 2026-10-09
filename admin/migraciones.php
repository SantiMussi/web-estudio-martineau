<?php

const ESQUEMA_VERSION = 5;

function migrar_base(PDO $pdo): void
{
    if (($_SESSION['esquema_version'] ?? 0) === ESQUEMA_VERSION) return;

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS ajustes (
            clave VARCHAR(50) PRIMARY KEY,
            valor TEXT NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ');

    foreach (['productos', 'proyectos'] as $tabla) {
        if (!migracion_columna_existe($pdo, $tabla, 'eliminado_at')) {
            $pdo->exec("ALTER TABLE $tabla ADD COLUMN eliminado_at DATETIME NULL DEFAULT NULL");
        }
        if (!migracion_columna_existe($pdo, $tabla, 'oculto_antes')) {
            $pdo->exec("ALTER TABLE $tabla ADD COLUMN oculto_antes TINYINT(1) NOT NULL DEFAULT 0");
        }
    }

    if (!migracion_tabla_existe($pdo, 'resenas')) {
        $pdo->exec('
            CREATE TABLE resenas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(120) NOT NULL,
                detalle VARCHAR(120) NULL,
                texto TEXT NOT NULL,
                oculto TINYINT(1) NOT NULL DEFAULT 0,
                orden INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ');
        $stmt = $pdo->prepare('INSERT INTO resenas (nombre, detalle, texto, orden) VALUES (:nombre, :detalle, :texto, :orden)');
        $stmt->execute([
            'nombre' => 'José María Del Bonnis',
            'detalle' => 'Arquitecto',
            'texto' => 'Excelente terminación y calidad de los productos. Las piezas de piedra pueden ser usadas en el exterior bajo cualquier clima.',
            'orden' => 0,
        ]);
        $stmt->execute([
            'nombre' => 'Agustín Sacco',
            'detalle' => 'Particular',
            'texto' => 'Navegando por internet encontré esta empresa que me aconsejó en el tipo de producto. No tengo otra cosa que palabras de agradecimiento por el asesoramiento.',
            'orden' => 1,
        ]);
    }

    foreach (['productos', 'proyectos'] as $tabla) {
        if (!migracion_columna_existe($pdo, $tabla, 'slug')) {
            $pdo->exec("ALTER TABLE $tabla ADD COLUMN slug VARCHAR(100) NULL DEFAULT NULL, ADD INDEX idx_{$tabla}_slug (slug)");
        }
    }
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS redirecciones_slug (
            tipo VARCHAR(10) NOT NULL,
            slug VARCHAR(100) NOT NULL,
            item_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (tipo, slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ');

    if (!migracion_tabla_existe($pdo, 'portada')) {
        $pdo->exec('
            CREATE TABLE portada (
                id INT AUTO_INCREMENT PRIMARY KEY,
                imagen VARCHAR(255) NOT NULL,
                imagen_movil VARCHAR(255) NULL,
                oculto TINYINT(1) NOT NULL DEFAULT 0,
                orden INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ');
        $stmt = $pdo->prepare('INSERT INTO portada (imagen, imagen_movil, orden) VALUES (:imagen, :movil, :orden)');
        foreach ([1, 2, 4, 5] as $orden => $n) {
            $stmt->execute([
                'imagen' => "assets/ImgsPrincipales/portada/hero-portada-$n.webp",
                'movil'  => "assets/ImgsPrincipales/portada/hero-portada-$n-movil.webp",
                'orden'  => $orden,
            ]);
        }
    }

    if (!migracion_columna_existe($pdo, 'portada', 'imagen_original')) {
        $pdo->exec('ALTER TABLE portada ADD COLUMN imagen_original VARCHAR(255) NULL DEFAULT NULL');
    }
    if (!migracion_columna_existe($pdo, 'portada', 'ajustes')) {
        $pdo->exec('ALTER TABLE portada ADD COLUMN ajustes TEXT NULL');
    }

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS disenos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(120) NOT NULL,
            original VARCHAR(255) NOT NULL,
            miniatura VARCHAR(255) NULL,
            datos LONGTEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ');

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

function migracion_tabla_existe(PDO $pdo, string $tabla): bool
{
    $stmt = $pdo->prepare('
        SELECT COUNT(*) FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tabla
    ');
    $stmt->execute(['tabla' => $tabla]);
    return (int)$stmt->fetchColumn() > 0;
}
