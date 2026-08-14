<?php
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../categorias.php');
    exit();
}

verificar_csrf();

try {
    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {
        throw new Exception('ID de categoría inválido.');
    }

    $stmt = $pdo->prepare('SELECT tipo FROM categorias WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $cat = $stmt->fetch();

    if (!$cat) {
        throw new Exception('Categoría no encontrada.');
    }

    if ($cat['tipo'] === 'producto') {
        $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM productos WHERE categoria_id = :id');
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM proyectos WHERE categoria_id = :id');
    }
    $stmt->execute(['id' => $id]);
    $count = $stmt->fetch();

    if ($count['total'] > 0) {
        throw new Exception('No se puede eliminar: tiene ' . $count['total'] . ' ítem(s) asociado(s). Eliminá o reasigná los ítems primero.');
    }

    $stmt = $pdo->prepare('DELETE FROM categorias WHERE id = :id');
    $stmt->execute(['id' => $id]);

    $_SESSION['flash_msg'] = ['type' => 'success', 'text' => 'Categoría eliminada correctamente.'];

} catch (Exception $e) {
    $_SESSION['flash_msg'] = ['type' => 'error', 'text' => $e->getMessage()];
}

header('Location: ../categorias.php');
exit();
