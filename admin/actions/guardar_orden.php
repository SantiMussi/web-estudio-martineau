<?php

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

// Leer payload JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || empty($data['tabla']) || !isset($data['orden']) || !is_array($data['orden'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Datos inválidos.']);
    exit;
}

$tabla = $data['tabla'];

// Solo permitir actualizar estas dos tablas
if (!in_array($tabla, ['productos', 'proyectos'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Tabla no permitida.']);
    exit;
}

$orden = $data['orden'];

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("UPDATE {$tabla} SET orden = :orden WHERE id = :id");

    foreach ($orden as $index => $id) {
        $stmt->execute([
            ':orden' => $index,
            ':id'    => (int)$id
        ]);
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Error actualizando orden: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error en la base de datos.']);
}
