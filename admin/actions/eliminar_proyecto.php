<?php
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../proyectos.php');
    exit();
}

verificar_csrf();

try {
    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {
        throw new Exception('ID de proyecto inválido.');
    }

    $stmt = $pdo->prepare('SELECT imagen, imagenes FROM proyectos WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $proyecto = $stmt->fetch();

    if (!$proyecto) {
        throw new Exception('Proyecto no encontrado.');
    }

    if ($proyecto['imagen']) {
        eliminar_imagen($proyecto['imagen']);
    }

    if ($proyecto['imagenes']) {
        $galeria = json_decode($proyecto['imagenes'], true);
        if (is_array($galeria)) {
            foreach ($galeria as $img) {
                eliminar_imagen($img);
            }
        }
    }

    $stmt = $pdo->prepare('DELETE FROM proyectos WHERE id = :id');
    $stmt->execute(['id' => $id]);

    $_SESSION['flash_msg'] = ['type' => 'success', 'text' => 'Proyecto eliminado correctamente.'];

} catch (Exception $e) {
    $_SESSION['flash_msg'] = ['type' => 'error', 'text' => $e->getMessage()];
}

header('Location: ../proyectos.php');
exit();
