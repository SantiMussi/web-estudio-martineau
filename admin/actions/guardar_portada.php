<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../portada_imagenes.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../portada.php');
    exit();
}

verificar_csrf();
set_time_limit(120);

$archivos = $_FILES['fotos'] ?? null;
$total = $archivos ? count((array)$archivos['name']) : 0;

if ($total === 0 || ($total === 1 && $archivos['error'][0] === UPLOAD_ERR_NO_FILE)) {
    $_SESSION['flash_msg'] = ['type' => 'error', 'text' => 'Elegí al menos una foto.'];
    header('Location: ../portada.php');
    exit();
}

$subidas = 0;
$errores = [];
// Las nuevas van al final del slideshow
$orden = (int)$pdo->query('SELECT IFNULL(MAX(orden), -1) + 1 FROM portada')->fetchColumn();
// La versión grande queda también como original: el editor siempre parte de ella
$stmt = $pdo->prepare('INSERT INTO portada (imagen, imagen_movil, imagen_original, orden) VALUES (:imagen, :movil, :original, :orden)');

for ($i = 0; $i < $total; $i++) {
    $archivo = [
        'name'     => $archivos['name'][$i],
        'tmp_name' => $archivos['tmp_name'][$i],
        'error'    => $archivos['error'][$i],
        'size'     => $archivos['size'][$i],
    ];
    try {
        $rutas = portada_procesar($archivo);
        $stmt->execute(['imagen' => $rutas['imagen'], 'movil' => $rutas['imagen_movil'], 'original' => $rutas['imagen'], 'orden' => $orden++]);
        $subidas++;
    } catch (Exception $e) {
        $errores[] = $e->getMessage();
    }
}

if ($errores) {
    $texto = ($subidas ? "Se agregaron $subidas foto(s). " : '') . 'No se pudieron subir: ' . implode(' ', $errores);
    $_SESSION['flash_msg'] = ['type' => 'error', 'text' => $texto];
} else {
    $_SESSION['flash_msg'] = ['type' => 'success', 'text' => $subidas === 1
        ? 'Foto agregada al final de la portada.'
        : "$subidas fotos agregadas al final de la portada."];
}

header('Location: ../portada.php');
exit();
