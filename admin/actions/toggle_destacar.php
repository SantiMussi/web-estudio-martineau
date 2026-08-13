<?php
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

$tipo = $_POST['tipo'] ?? '';
$id   = (int)($_POST['id'] ?? 0);

// Whitelist estricta de tablas permitidas (previene SQL injection)
$tablas_permitidas = ['producto' => 'productos', 'proyecto' => 'proyectos'];

if (!isset($tablas_permitidas[$tipo]) || $id <= 0) {
    $_SESSION['flash_msg'] = ['type' => 'error', 'text' => 'Parámetros inválidos.'];
    header('Location: ../index.php');
    exit();
}

$tabla = $tablas_permitidas[$tipo];

try {
    // Toggle: invertir valor de destacar
    $stmt = $pdo->prepare("UPDATE {$tabla} SET destacar = NOT destacar WHERE id = :id");
    $stmt->execute(['id' => $id]);

    $_SESSION['flash_msg'] = ['type' => 'success', 'text' => 'Estado actualizado.'];

} catch (Exception $e) {
    $_SESSION['flash_msg'] = ['type' => 'error', 'text' => 'Error al actualizar el estado.'];
}

// Redirigir a la página correcta
$redirect = $tipo === 'proyecto' ? '../proyectos.php' : '../index.php';
header('Location: ' . $redirect);
exit();
