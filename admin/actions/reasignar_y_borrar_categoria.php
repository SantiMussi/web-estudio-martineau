<?php

require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../categorias.php');
    exit();
}

verificar_csrf();

try {
    $id           = (int)($_POST['id'] ?? 0);
    $idDestino    = (int)($_POST['categoria_destino_id'] ?? 0);
    $confirmacion = trim($_POST['confirmacion'] ?? '');

    if ($id <= 0) {
        throw new Exception('Categoría inválida.');
    }

    $stmt = $pdo->prepare('SELECT * FROM categorias WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $origen = $stmt->fetch();

    if (!$origen) {
        throw new Exception('Categoría no encontrada.');
    }

    if ($confirmacion !== $origen['nombre']) {
        throw new Exception('El texto de confirmación no coincide con el nombre de la categoría. No se hizo ningún cambio.');
    }

    if ($idDestino <= 0) {
        throw new Exception('Elegí a qué categoría mover los ítems.');
    }

    if ($idDestino === $id) {
        throw new Exception('La categoría de destino tiene que ser distinta de la que estás borrando.');
    }

    $stmt = $pdo->prepare('SELECT * FROM categorias WHERE id = :id');
    $stmt->execute(['id' => $idDestino]);
    $destino = $stmt->fetch();

    if (!$destino) {
        throw new Exception('Categoría de destino no encontrada.');
    }

    if ($destino['tipo'] !== $origen['tipo']) {
        throw new Exception('La categoría de destino tiene que ser del mismo tipo (producto o proyecto).');
    }

    $tabla = $origen['tipo'] === 'producto' ? 'productos' : 'proyectos';

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("UPDATE $tabla SET categoria_id = :destino WHERE categoria_id = :origen");
    $stmt->execute(['destino' => $idDestino, 'origen' => $id]);
    $reasignados = $stmt->rowCount();

    $stmt = $pdo->prepare('DELETE FROM categorias WHERE id = :id');
    $stmt->execute(['id' => $id]);

    $pdo->commit();

    $_SESSION['flash_msg'] = [
        'type' => 'success',
        'text' => "Se reasignaron $reasignados ítem(s) a \"{$destino['nombre']}\" y se eliminó la categoría \"{$origen['nombre']}\".",
    ];

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash_msg'] = ['type' => 'error', 'text' => $e->getMessage()];
}

header('Location: ../categorias.php');
exit();
