<?php
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

    if (empty($slug)) {
        $slug = generarSlug($nombre);
    }

    $stmt = $pdo->prepare('SELECT id FROM categorias WHERE slug = :slug');
    $stmt->execute(['slug' => $slug]);
    if ($stmt->fetch()) {
        throw new Exception('Ya existe una categoría con ese slug.');
    }

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


function generarSlug(string $texto): string {
    $slug = mb_strtolower($texto, 'UTF-8');
    $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s]+/', '-', trim($slug));
    $slug = preg_replace('/-+/', '-', $slug);
    return $slug;
}
