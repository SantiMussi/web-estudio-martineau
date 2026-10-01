<?php
require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../resenas.php');
    exit();
}

verificar_csrf();

$id = (int)($_POST['id'] ?? 0);
$stmt = $pdo->prepare('DELETE FROM resenas WHERE id = :id');
$stmt->execute(['id' => $id]);

$_SESSION['flash_msg'] = $stmt->rowCount() > 0
    ? ['type' => 'success', 'text' => 'Reseña eliminada.']
    : ['type' => 'error', 'text' => 'Reseña no encontrada.'];

header('Location: ../resenas.php');
exit();
