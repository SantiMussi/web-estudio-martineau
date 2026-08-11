<?php
/**
 * actions/eliminar_producto.php — Eliminar Producto
 * 
 * @security CSRF + PDO Prepared Statements + Limpieza de archivos
 */

require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit();
}

verificar_csrf();

try {
    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {
        throw new Exception('ID de producto inválido.');
    }

    // Obtener datos del producto para limpiar archivos
    $stmt = $pdo->prepare('SELECT imagen, imagenes FROM productos WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $producto = $stmt->fetch();

    if (!$producto) {
        throw new Exception('Producto no encontrado.');
    }

    // Eliminar imagen principal del disco
    if ($producto['imagen']) {
        eliminar_imagen($producto['imagen']);
    }

    // Eliminar imágenes de la galería del disco
    if ($producto['imagenes']) {
        $galeria = json_decode($producto['imagenes'], true);
        if (is_array($galeria)) {
            foreach ($galeria as $img) {
                eliminar_imagen($img);
            }
        }
    }

    // Eliminar registro de la BD
    $stmt = $pdo->prepare('DELETE FROM productos WHERE id = :id');
    $stmt->execute(['id' => $id]);

    $_SESSION['flash_msg'] = ['type' => 'success', 'text' => 'Producto eliminado correctamente.'];

} catch (Exception $e) {
    $_SESSION['flash_msg'] = ['type' => 'error', 'text' => $e->getMessage()];
}

header('Location: ../index.php');
exit();
