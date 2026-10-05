<?php
/*
 * Guarda una foto de la portada editada en el editor del panel (admin/portada-editor.js).
 * El navegador aplica brillo/contraste/saturación y el recorte para celular sobre la
 * foto ORIGINAL y manda las dos versiones; acá se validan, se guardan como WebP y se
 * reemplazan las anteriores. La original no se toca: se puede volver a editar o
 * restablecer las veces que haga falta sin perder calidad.
 */
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../portada_imagenes.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido.']);
    exit();
}

verificar_csrf();

$nuevas = [];
try {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM portada WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $foto = $stmt->fetch();
    if (!$foto) {
        throw new Exception('La foto ya no existe. Recargá la página.');
    }

    // Solo se guardan los ajustes conocidos, con sus rangos
    $pedido = json_decode((string)($_POST['ajustes'] ?? ''), true);
    $pedido = is_array($pedido) ? $pedido : [];
    $limitar = fn($v, $min, $max) => max($min, min($max, round((float)$v, 3)));
    $ajustes = [
        'brillo'     => $limitar($pedido['brillo'] ?? 0, -50, 50),
        'contraste'  => $limitar($pedido['contraste'] ?? 0, -50, 50),
        'saturacion' => $limitar($pedido['saturacion'] ?? 0, -100, 50),
        'foco_x'     => $limitar($pedido['foco_x'] ?? 0.5, 0, 1),
        'foco_y'     => $limitar($pedido['foco_y'] ?? 0.5, 0, 1),
    ];

    $nuevas['imagen'] = portada_guardar_editada($_FILES['grande'] ?? [], PORTADA_ANCHO_MAX, null, 80, '');
    $nuevas['imagen_movil'] = portada_guardar_editada($_FILES['movil'] ?? [], PORTADA_MOVIL_ANCHO, PORTADA_MOVIL_ALTO, 74, '-movil');

    // La original es la que había antes de la primera edición
    $original = $foto['imagen_original'] ?: $foto['imagen'];

    $pdo->prepare('
        UPDATE portada SET imagen = :imagen, imagen_movil = :movil, imagen_original = :original, ajustes = :ajustes
        WHERE id = :id
    ')->execute([
        'imagen'   => $nuevas['imagen'],
        'movil'    => $nuevas['imagen_movil'],
        'original' => $original,
        'ajustes'  => json_encode($ajustes),
        'id'       => $id,
    ]);

    // Las versiones editadas anteriores ya no se usan (la original se conserva)
    portada_borrar_archivos(['imagen' => $foto['imagen'], 'imagen_movil' => $foto['imagen_movil']], [$original]);

    $_SESSION['flash_msg'] = ['type' => 'success', 'text' => 'Foto de la portada actualizada.'];
    echo json_encode(['ok' => true]);

} catch (Exception $e) {
    // Si se llegó a guardar alguna versión nueva, no queda suelta
    portada_borrar_archivos($nuevas);
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
