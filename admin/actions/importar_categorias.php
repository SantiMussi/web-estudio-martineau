<?php

require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../importar.php');
    exit();
}

verificar_csrf();

$creadas = 0;
$existentes = 0;
$errores = [];

try {
    $texto = obtener_texto_importacion('archivo', 'datos');
    $filas = parsear_csv_texto($texto);

    if (empty($filas)) {
        throw new Exception('No se encontraron filas para importar. Verificá el archivo o el texto pegado.');
    }

    foreach ($filas as $i => $fila) {
        $num = $i + 2; // +1 por índice base 0, +1 por la fila de encabezado
        $nombre = trim($fila['nombre'] ?? '');
        $tipo = mb_strtolower(trim($fila['tipo'] ?? ''), 'UTF-8');

        if ($nombre === '') {
            $errores[] = "Fila $num: falta el nombre.";
            continue;
        }
        if (!in_array($tipo, ['producto', 'proyecto'], true)) {
            $errores[] = "Fila $num ('$nombre'): el tipo debe ser \"producto\" o \"proyecto\".";
            continue;
        }

        $stmt = $pdo->prepare('SELECT id FROM categorias WHERE LOWER(nombre) = LOWER(:nombre) AND tipo = :tipo');
        $stmt->execute(['nombre' => $nombre, 'tipo' => $tipo]);
        if ($stmt->fetch()) {
            $existentes++;
            continue;
        }

        $slug = slug_unico($pdo, $nombre);
        $stmt = $pdo->prepare('INSERT INTO categorias (nombre, slug, tipo) VALUES (:nombre, :slug, :tipo)');
        $stmt->execute(['nombre' => $nombre, 'slug' => $slug, 'tipo' => $tipo]);
        $creadas++;
    }

    $texto_resumen = "$creadas categoría(s) creada(s).";
    if ($existentes > 0) {
        $texto_resumen .= " $existentes ya existían y se omitieron.";
    }
    if (!empty($errores)) {
        $texto_resumen .= ' Errores: ' . implode(' ', array_slice($errores, 0, 10));
        if (count($errores) > 10) {
            $texto_resumen .= ' (y ' . (count($errores) - 10) . ' más)';
        }
    }

    $_SESSION['flash_msg'] = [
        'type' => $creadas > 0 ? ($errores ? 'warning' : 'success') : 'error',
        'text' => $texto_resumen,
    ];

} catch (Exception $e) {
    $_SESSION['flash_msg'] = ['type' => 'error', 'text' => $e->getMessage()];
}

header('Location: ../categorias.php');
exit();
