<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../portada_imagenes.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../portada.php');
    exit();
}

verificar_csrf();

$id = (int)($_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT imagen, imagen_movil FROM portada WHERE id = :id');
$stmt->execute(['id' => $id]);
$foto = $stmt->fetch();

if ($foto) {
    $pdo->prepare('DELETE FROM portada WHERE id = :id')->execute(['id' => $id]);
    portada_borrar_archivos($foto);
    $_SESSION['flash_msg'] = ['type' => 'success', 'text' => 'Foto eliminada de la portada.'];
} else {
    $_SESSION['flash_msg'] = ['type' => 'error', 'text' => 'Foto no encontrada.'];
}

header('Location: ../portada.php');
exit();
