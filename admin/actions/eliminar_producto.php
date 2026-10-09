<?php
require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit();
}

verificar_csrf();

try {
    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0 || papelera_enviar($pdo, 'producto', [$id]) === 0) {
        throw new Exception('Producto no encontrado.');
    }

    $_SESSION['flash_msg'] = ['type' => 'success', 'text' => 'Producto enviado a la papelera. Lo podés restaurar desde ahí durante ' . PAPELERA_DIAS . ' días.'];

} catch (Exception $e) {
    $_SESSION['flash_msg'] = ['type' => 'error', 'text' => $e->getMessage()];
}

header('Location: ../index.php');
exit();
