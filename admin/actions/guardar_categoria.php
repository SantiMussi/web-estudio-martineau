<?php
/**
 * actions/guardar_categoria.php — Crear Categoría
 * 
 * Genera slug automáticamente desde el nombre.
 * 
 * @security CSRF + PDO Prepared Statements
 */

require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../categorias.php');
    exit();
}

verificar_csrf();

try {
    $nombre = trim($_POST['nombre'] ?? '');
    $tipo   = trim($_POST['tipo'] ?? '');
    $slug   = trim($_POST['slug'] ?? '');

    if (empty($nombre)) {
        throw new Exception('El nombre de la categoría es obligatorio.');
    }

    if (!in_array($tipo, ['producto', 'proyecto'], true)) {
        throw new Exception('El tipo debe ser "producto" o "proyecto".');
    }

    // Generar slug si no se envió desde JS
    if (empty($slug)) {
        $slug = generarSlug($nombre);
    }

    // Verificar que el slug no exista ya
    $stmt = $pdo->prepare('SELECT id FROM categorias WHERE slug = :slug');
    $stmt->execute(['slug' => $slug]);
    if ($stmt->fetch()) {
        throw new Exception('Ya existe una categoría con ese slug.');
    }

    // INSERT
    $stmt = $pdo->prepare('INSERT INTO categorias (nombre, slug, tipo) VALUES (:nombre, :slug, :tipo)');
    $stmt->execute([
        'nombre' => $nombre,
        'slug'   => $slug,
        'tipo'   => $tipo,
    ]);

    $_SESSION['flash_msg'] = ['type' => 'success', 'text' => 'Categoría "' . $nombre . '" creada correctamente.'];

} catch (Exception $e) {
    $_SESSION['flash_msg'] = ['type' => 'error', 'text' => $e->getMessage()];
}

header('Location: ../categorias.php');
exit();

/**
 * Genera un slug URL-friendly desde un texto.
 * Ej: "Chimeneas de Lujo" → "chimeneas-de-lujo"
 */
function generarSlug(string $texto): string {
    $slug = mb_strtolower($texto, 'UTF-8');
    // Transliterar caracteres especiales
    $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug);
    // Solo alfanuméricos y guiones
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s]+/', '-', trim($slug));
    $slug = preg_replace('/-+/', '-', $slug);
    return $slug;
}
