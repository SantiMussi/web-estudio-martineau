<?php
require_once __DIR__ . '/../auth.php';

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

    $tabla = $cat['tipo'] === 'producto' ? 'productos' : 'proyectos';

    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM $tabla WHERE categoria_id = :id AND eliminado_at IS NULL");
    $stmt->execute(['id' => $id]);
    $count = $stmt->fetch();

    if ($count['total'] > 0) {
        throw new Exception('No se puede eliminar: tiene ' . $count['total'] . ' ítem(s) asociado(s). Eliminá o reasigná los ítems primero.');
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("UPDATE $tabla SET categoria_id = NULL WHERE categoria_id = :id");
    $stmt->execute(['id' => $id]);

    $stmt = $pdo->prepare('DELETE FROM categorias WHERE id = :id');
    $stmt->execute(['id' => $id]);

    $pdo->commit();

    $_SESSION['flash_msg'] = ['type' => 'success', 'text' => 'Categoría eliminada correctamente.'];

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['flash_msg'] = ['type' => 'error', 'text' => $e->getMessage()];
}

header('Location: ../categorias.php');
exit();
