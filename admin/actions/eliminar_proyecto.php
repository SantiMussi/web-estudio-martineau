<?php
require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../proyectos.php');
    exit();
}

verificar_csrf();

try {
    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0 || papelera_enviar($pdo, 'proyecto', [$id]) === 0) {
        throw new Exception('Proyecto no encontrado.');
    }

    $_SESSION['flash_msg'] = ['type' => 'success', 'text' => 'Proyecto enviado a la papelera. Lo podés restaurar desde ahí durante ' . PAPELERA_DIAS . ' días.'];

} catch (Exception $e) {
    $_SESSION['flash_msg'] = ['type' => 'error', 'text' => $e->getMessage()];
}

header('Location: ../proyectos.php');
exit();
