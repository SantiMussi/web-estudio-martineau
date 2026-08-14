<?php
define('IS_API_ENDPOINT', true);
require_once __DIR__ . '/../admin/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$tipo = $_GET['tipo'] ?? '';
$id   = isset($_GET['id']) ? (int)$_GET['id'] : null;

try {
    switch ($tipo) {

        case 'productos':
            $stmt = $pdo->query('
                SELECT p.id, p.titulo, c.slug AS categoria, p.descripcion, 
                       p.imagen, p.imagenes, p.specs, p.destacar
                FROM productos p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                WHERE IFNULL(p.oculto, 0) = 0
                ORDER BY p.orden ASC, p.created_at DESC
            ');
            $data = $stmt->fetchAll();
            echo json_encode(formatearLista($data));
            break;

        case 'proyectos':
            $stmt = $pdo->query('
                SELECT p.id, p.titulo, c.slug AS categoria, p.ubicacion, p.anio,
                       p.descripcion, p.imagen, p.imagenes, p.specs, p.destacar
                FROM proyectos p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                WHERE IFNULL(p.oculto, 0) = 0
                ORDER BY p.orden ASC, p.created_at DESC
            ');
            $data = $stmt->fetchAll();
            echo json_encode(formatearLista($data));
            break;

        case 'productos_destacados':
            $stmt = $pdo->query('
                SELECT p.id, p.titulo, c.slug AS categoria, p.descripcion, 
                       p.imagen, p.imagenes, p.specs, p.destacar
                FROM productos p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                WHERE p.destacar = 1 AND IFNULL(p.oculto, 0) = 0
                ORDER BY p.orden ASC, p.created_at DESC
            ');
            $data = $stmt->fetchAll();
            echo json_encode(formatearLista($data));
            break;

        case 'proyectos_destacados':
            $stmt = $pdo->query('
                SELECT p.id, p.titulo, c.slug AS categoria, p.ubicacion, p.anio,
                       p.descripcion, p.imagen, p.imagenes, p.specs, p.destacar
                FROM proyectos p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                WHERE p.destacar = 1 AND IFNULL(p.oculto, 0) = 0
                ORDER BY p.orden ASC, p.created_at DESC
            ');
            $data = $stmt->fetchAll();
            echo json_encode(formatearLista($data));
            break;

        case 'producto':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'Se requiere parámetro id.']);
                break;
            }
            $stmt = $pdo->prepare('
                SELECT p.id, p.titulo, c.slug AS categoria, p.descripcion, 
                       p.imagen, p.imagenes, p.specs, p.destacar
                FROM productos p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                WHERE p.id = :id AND IFNULL(p.oculto, 0) = 0
            ');
            $stmt->execute(['id' => $id]);
            $item = $stmt->fetch();
            
            if (!$item) {
                http_response_code(404);
                echo json_encode(['error' => 'Producto no encontrado.']);
            } else {
                echo json_encode(formatearItem($item));
            }
            break;

        case 'proyecto':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'Se requiere parámetro id.']);
                break;
            }
            $stmt = $pdo->prepare('
                SELECT p.id, p.titulo, c.slug AS categoria, p.ubicacion, p.anio,
                       p.descripcion, p.imagen, p.imagenes, p.specs, p.destacar
                FROM proyectos p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                WHERE p.id = :id AND IFNULL(p.oculto, 0) = 0
            ');
            $stmt->execute(['id' => $id]);
            $item = $stmt->fetch();
            
            if (!$item) {
                http_response_code(404);
                echo json_encode(['error' => 'Proyecto no encontrado.']);
            } else {
                echo json_encode(formatearItem($item));
            }
            break;

        case 'categorias':
            $filtro = $_GET['filtro'] ?? '';
            if ($filtro && in_array($filtro, ['producto', 'proyecto'], true)) {
                $stmt = $pdo->prepare('SELECT id, nombre, slug, tipo FROM categorias WHERE tipo = :tipo ORDER BY nombre');
                $stmt->execute(['tipo' => $filtro]);
            } else {
                $stmt = $pdo->query('SELECT id, nombre, slug, tipo FROM categorias ORDER BY tipo, nombre');
            }
            echo json_encode($stmt->fetchAll());
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Parámetro "tipo" inválido.']);
    }

} catch (Exception $e) {
    error_log('[API datos.php] Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor.']);
}

function formatearLista(array $items): array {
    return array_map('formatearItem', $items);
}

function formatearItem(array $item): array {
    if (isset($item['imagenes']) && is_string($item['imagenes'])) {
        $decoded = json_decode($item['imagenes'], true);
        $item['imagenes'] = is_array($decoded) ? $decoded : [];
    } else {
        $item['imagenes'] = [];
    }

    if (isset($item['specs']) && is_string($item['specs'])) {
        $decoded = json_decode($item['specs'], true);
        $item['specs'] = is_array($decoded) ? $decoded : [];
    } else {
        $item['specs'] = [];
    }

    $item['destacar'] = (bool)($item['destacar'] ?? false);

    return $item;
}
