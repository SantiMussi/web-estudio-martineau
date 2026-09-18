<?php

require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../proyectos.php');
    exit();
}

verificar_csrf();

try {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        throw new Exception('Proyecto inválido.');
    }

    $stmt = $pdo->prepare('SELECT * FROM proyectos WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $original = $stmt->fetch();

    if (!$original) {
        throw new Exception('Proyecto no encontrado.');
    }

    $stmt = $pdo->prepare('
        INSERT INTO proyectos (titulo, categoria_id, ubicacion, anio, descripcion, specs, destacar)
        VALUES (:titulo, :categoria_id, :ubicacion, :anio, :descripcion, :specs, 0)
    ');
    $stmt->execute([
        'titulo'       => $original['titulo'] . ' (copia)',
        'categoria_id' => $original['categoria_id'],
        'ubicacion'    => $original['ubicacion'],
        'anio'         => $original['anio'],
        'descripcion'  => $original['descripcion'],
        'specs'        => $original['specs'] ?? '[]',
    ]);

    $_SESSION['flash_msg'] = [
        'type' => 'success',
        'text' => 'Proyecto duplicado como "' . $original['titulo'] . ' (copia)". Quedó sin imagen propia: subile una foto para completarlo.',
    ];

} catch (Exception $e) {
    $_SESSION['flash_msg'] = ['type' => 'error', 'text' => $e->getMessage()];
}

header('Location: ../proyectos.php');
exit();
