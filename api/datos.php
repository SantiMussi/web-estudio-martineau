<?php
/**
 * api/datos.php — API JSON Pública
 * 
 * Endpoint que alimenta el frontend con datos dinámicos de la BD.
 * NO requiere autenticación (es público).
 * 
 * Parámetros GET:
 *   ?tipo=productos                → Todos los productos con categoría
 *   ?tipo=proyectos                → Todos los proyectos con categoría
 *   ?tipo=productos_destacados     → Solo productos con destacar=1
 *   ?tipo=proyectos_destacados     → Solo proyectos con destacar=1
 *   ?tipo=producto&id=X            → Detalle de un producto
 *   ?tipo=proyecto&id=X            → Detalle de un proyecto
 *   ?tipo=categorias&filtro=X      → Categorías filtradas por tipo (producto/proyecto)
 * 
 * @security PDO Prepared Statements. Sin datos sensibles expuestos.
 */

// ─── Conexión a BD ───
// Importamos la conexión centralizada (esto protege las credenciales de Git ya que config.php está en .gitignore)
require_once __DIR__ . '/../admin/config.php';

// ─── Headers de respuesta ───
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('X-Content-Type-Options: nosniff');

$tipo = $_GET['tipo'] ?? '';
$id   = isset($_GET['id']) ? (int)$_GET['id'] : null;

try {
    switch ($tipo) {

        // ─── Todos los productos ───
        case 'productos':
            $stmt = $pdo->query('
                SELECT p.id, p.titulo, c.slug AS categoria, p.descripcion, 
                       p.imagen, p.imagenes, p.specs, p.destacar
                FROM productos p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                ORDER BY p.orden ASC, p.created_at DESC
            ');
            $data = $stmt->fetchAll();
            echo json_encode(formatearLista($data));
            break;

        // ─── Todos los proyectos ───
        case 'proyectos':
            $stmt = $pdo->query('
                SELECT p.id, p.titulo, c.slug AS categoria, p.ubicacion, p.anio,
                       p.descripcion, p.imagen, p.imagenes, p.specs, p.destacar
                FROM proyectos p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                ORDER BY p.orden ASC, p.created_at DESC
            ');
            $data = $stmt->fetchAll();
            echo json_encode(formatearLista($data));
            break;

        // ─── Productos destacados ───
        case 'productos_destacados':
            $stmt = $pdo->query('
                SELECT p.id, p.titulo, c.slug AS categoria, p.descripcion, 
                       p.imagen, p.imagenes, p.specs, p.destacar
                FROM productos p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                WHERE p.destacar = 1
                ORDER BY p.orden ASC, p.created_at DESC
            ');
            $data = $stmt->fetchAll();
            echo json_encode(formatearLista($data));
            break;

        // ─── Proyectos destacados ───
        case 'proyectos_destacados':
            $stmt = $pdo->query('
                SELECT p.id, p.titulo, c.slug AS categoria, p.ubicacion, p.anio,
                       p.descripcion, p.imagen, p.imagenes, p.specs, p.destacar
                FROM proyectos p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                WHERE p.destacar = 1
                ORDER BY p.orden ASC, p.created_at DESC
            ');
            $data = $stmt->fetchAll();
            echo json_encode(formatearLista($data));
            break;

        // ─── Detalle de producto ───
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
                WHERE p.id = :id
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

        // ─── Detalle de proyecto ───
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
                WHERE p.id = :id
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

        // ─── Categorías ───
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
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor.']);
}

// ═══════════════════════════════════════════════
// ─── FUNCIONES HELPER ───
// ═══════════════════════════════════════════════

/**
 * Formatea una lista de ítems: deserializa campos JSON.
 */
function formatearLista(array $items): array {
    return array_map('formatearItem', $items);
}

/**
 * Formatea un ítem individual: deserializa imagenes y specs desde JSON.
 * Convierte el campo 'destacar' a booleano para compatibilidad con el frontend JS.
 */
function formatearItem(array $item): array {
    // Deserializar galería de imágenes
    if (isset($item['imagenes']) && is_string($item['imagenes'])) {
        $decoded = json_decode($item['imagenes'], true);
        $item['imagenes'] = is_array($decoded) ? $decoded : [];
    } else {
        $item['imagenes'] = [];
    }

    // Deserializar especificaciones
    if (isset($item['specs']) && is_string($item['specs'])) {
        $decoded = json_decode($item['specs'], true);
        $item['specs'] = is_array($decoded) ? $decoded : [];
    } else {
        $item['specs'] = [];
    }

    // Convertir destacar a booleano (el frontend JS espera true/false)
    $item['destacar'] = (bool)($item['destacar'] ?? false);

    return $item;
}
