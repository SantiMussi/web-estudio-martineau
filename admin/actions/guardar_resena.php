<?php
require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../resenas.php');
    exit();
}

verificar_csrf();

try {
    $id      = (int)($_POST['id'] ?? 0);
    $nombre  = trim($_POST['nombre'] ?? '');
    $detalle = trim($_POST['detalle'] ?? '');
    $texto   = trim(preg_replace('/\s+/u', ' ', $_POST['texto'] ?? ''));
    $oculto  = isset($_POST['oculto']) ? 1 : 0;

    if ($nombre === '' || $texto === '') {
        throw new Exception('Completá el nombre y el texto de la reseña.');
    }
    if (mb_strlen($nombre) > 120 || mb_strlen($detalle) > 120) {
        throw new Exception('El nombre y el detalle pueden tener hasta 120 caracteres.');
    }

    $datos = [
        'nombre'  => $nombre,
        'detalle' => $detalle !== '' ? $detalle : null,
        'texto'   => $texto,
        'oculto'  => $oculto,
    ];

    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE resenas SET nombre = :nombre, detalle = :detalle, texto = :texto, oculto = :oculto WHERE id = :id');
        $stmt->execute($datos + ['id' => $id]);
        $_SESSION['flash_msg'] = ['type' => 'success', 'text' => 'Reseña actualizada.'];
    } else {
        $orden = (int)$pdo->query('SELECT IFNULL(MAX(orden), -1) + 1 FROM resenas')->fetchColumn();
        $stmt = $pdo->prepare('INSERT INTO resenas (nombre, detalle, texto, oculto, orden) VALUES (:nombre, :detalle, :texto, :oculto, :orden)');
        $stmt->execute($datos + ['orden' => $orden]);
        $_SESSION['flash_msg'] = ['type' => 'success', 'text' => $oculto ? 'Reseña guardada (oculta).' : 'Reseña agregada. Ya se ve en la página Nosotros.'];
    }

} catch (Exception $e) {
    $_SESSION['flash_msg'] = ['type' => 'error', 'text' => $e->getMessage()];
}

header('Location: ../resenas.php');
exit();
