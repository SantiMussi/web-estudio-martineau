<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../inc/ajustes.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../ajustes.php');
    exit();
}

verificar_csrf();

$valores = [];
foreach (array_keys(AJUSTES_DEFECTO) as $clave) {
    $valores[$clave] = trim((string)($_POST[$clave] ?? ''));
}
$valores['whatsapp'] = preg_replace('/\D/', '', $valores['whatsapp']);
$valores['horario'] = implode("\n", contacto_lineas($valores['horario']));

$error = null;
if (in_array('', $valores, true)) {
    $error = 'Completá todos los campos.';
} elseif (strlen($valores['whatsapp']) < 10 || strlen($valores['whatsapp']) > 15) {
    $error = 'El número de WhatsApp tiene que tener entre 10 y 15 dígitos, con el código de país (ej: 5491131917014).';
} elseif (!filter_var($valores['email'], FILTER_VALIDATE_EMAIL)) {
    $error = 'El mail no es válido.';
} elseif (!preg_match('#^https://#i', $valores['instagram']) || !filter_var($valores['instagram'], FILTER_VALIDATE_URL)) {
    $error = 'El link de Instagram tiene que ser la dirección completa, empezando con https://';
}

if ($error) {
    $_SESSION['ajustes_borrador'] = $valores;
    $_SESSION['flash_msg'] = ['type' => 'error', 'text' => $error];
    header('Location: ../ajustes.php');
    exit();
}

try {
    ajustes_guardar($pdo, $valores);
    $_SESSION['flash_msg'] = ['type' => 'success', 'text' => 'Datos de contacto guardados.'];
} catch (Exception $e) {
    error_log('[guardar_ajustes.php] ' . $e->getMessage());
    $_SESSION['ajustes_borrador'] = $valores;
    $_SESSION['flash_msg'] = ['type' => 'error', 'text' => 'No se pudieron guardar los datos. Probá de nuevo.'];
}

header('Location: ../ajustes.php');
exit();
