<?php

require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../categorias.php');
    exit();
}

verificar_csrf();

try {
    $id           = (int)($_POST['id'] ?? 0);
    $confirmacion = trim($_POST['confirmacion'] ?? '');

    if ($id <= 0) {
        throw new Exception('Categoría inválida.');
    }

    $stmt = $pdo->prepare('SELECT * FROM categorias WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $categoria = $stmt->fetch();

    if (!$categoria) {
        throw new Exception('Categoría no encontrada.');
    }

    if ($confirmacion !== $categoria['nombre']) {
        throw new Exception('El texto de confirmación no coincide con el nombre de la categoría. No se borró nada.');
    }

    $stmt = $pdo->prepare('SELECT id FROM ' . papelera_tabla($categoria['tipo']) . ' WHERE categoria_id = :id AND eliminado_at IS NULL');
    $stmt->execute(['id' => $id]);
    $enviados = papelera_enviar($pdo, $categoria['tipo'], $stmt->fetchAll(PDO::FETCH_COLUMN));

    $_SESSION['flash_msg'] = [
        'type' => 'success',
        'text' => "Se mandaron $enviados ítem(s) de \"{$categoria['nombre']}\" a la papelera. La categoría sigue existiendo, ahora vacía.",
    ];

} catch (Exception $e) {
    $_SESSION['flash_msg'] = ['type' => 'error', 'text' => $e->getMessage()];
}

header('Location: ../categorias.php');
exit();
