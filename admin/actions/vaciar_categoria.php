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

    $tabla = $categoria['tipo'] === 'producto' ? 'productos' : 'proyectos';

    $stmt = $pdo->prepare("SELECT id, imagen, imagenes FROM $tabla WHERE categoria_id = :id");
    $stmt->execute(['id' => $id]);
    $items = $stmt->fetchAll();

    foreach ($items as $item) {
        if ($item['imagen']) {
            eliminar_imagen($item['imagen']);
        }
        if ($item['imagenes']) {
            $galeria = json_decode($item['imagenes'], true);
            if (is_array($galeria)) {
                foreach ($galeria as $img) {
                    eliminar_imagen($img);
                }
            }
        }
    }

    $stmt = $pdo->prepare("DELETE FROM $tabla WHERE categoria_id = :id");
    $stmt->execute(['id' => $id]);
    $borrados = $stmt->rowCount();

    $_SESSION['flash_msg'] = [
        'type' => 'success',
        'text' => "Se eliminaron $borrados ítem(s) de \"{$categoria['nombre']}\" junto con sus imágenes. La categoría sigue existiendo, ahora vacía.",
    ];

} catch (Exception $e) {
    $_SESSION['flash_msg'] = ['type' => 'error', 'text' => $e->getMessage()];
}

header('Location: ../categorias.php');
exit();
