<?php
require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit();
}

verificar_csrf();

$tipo = $_POST['tipo'] ?? '';
$id   = (int)($_POST['id'] ?? 0);

$tablas_permitidas = ['producto' => 'productos', 'proyecto' => 'proyectos', 'resena' => 'resenas', 'portada' => 'portada'];

if (!isset($tablas_permitidas[$tipo]) || $id <= 0) {
    $_SESSION['flash_msg'] = ['type' => 'error', 'text' => 'Parámetros inválidos.'];
    header('Location: ../index.php');
    exit();
}

$tabla = $tablas_permitidas[$tipo];

try {
    $stmt = $pdo->prepare("UPDATE {$tabla} SET oculto = NOT oculto WHERE id = :id");
    $stmt->execute(['id' => $id]);

    $_SESSION['flash_msg'] = ['type' => 'success', 'text' => 'Visibilidad actualizada.'];

} catch (Exception $e) {
    $_SESSION['flash_msg'] = ['type' => 'error', 'text' => 'Error al actualizar la visibilidad. Asegurate de haber agregado la columna "oculto" a la tabla.'];
}

$redirect = ['producto' => '../index.php', 'proyecto' => '../proyectos.php', 'resena' => '../resenas.php', 'portada' => '../portada.php'][$tipo];
header('Location: ' . $redirect);
exit();
