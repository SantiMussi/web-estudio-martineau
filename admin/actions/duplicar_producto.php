<?php

require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit();
}

verificar_csrf();

try {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        throw new Exception('Producto inválido.');
    }

    $stmt = $pdo->prepare('SELECT * FROM productos WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $original = $stmt->fetch();

    if (!$original) {
        throw new Exception('Producto no encontrado.');
    }

    $stmt = $pdo->prepare('
        INSERT INTO productos (titulo, categoria_id, descripcion, specs, destacar, oculto)
        VALUES (:titulo, :categoria_id, :descripcion, :specs, 0, 1)
    ');
    $stmt->execute([
        'titulo'       => $original['titulo'] . ' (copia)',
        'categoria_id' => $original['categoria_id'],
        'descripcion'  => $original['descripcion'],
        'specs'        => $original['specs'] ?? '[]',
    ]);

    $_SESSION['flash_msg'] = [
        'type' => 'success',
        'text' => 'Producto duplicado como "' . $original['titulo'] . ' (copia)". Quedó oculto y sin imagen propia: subile una foto y sacale el "oculto" cuando esté lista la ficha.',
    ];

} catch (Exception $e) {
    $_SESSION['flash_msg'] = ['type' => 'error', 'text' => $e->getMessage()];
}

header('Location: ../index.php');
exit();
