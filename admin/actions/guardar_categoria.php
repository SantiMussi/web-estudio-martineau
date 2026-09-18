<?php
require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../categorias.php');
    exit();
}

verificar_csrf();

try {
    $id     = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $nombre = trim($_POST['nombre'] ?? '');
    $tipo   = trim($_POST['tipo'] ?? '');
    $slug   = trim($_POST['slug'] ?? '');

    if (empty($nombre)) {
        throw new Exception('El nombre de la categoría es obligatorio.');
    }

    if (empty($slug)) {
        $slug = generarSlug($nombre);
    }

    if ($id) {
        // Edición: el tipo no se toca (evita dejar productos/proyectos apuntando a una categoría del tipo equivocado).
        $stmt = $pdo->prepare('SELECT id FROM categorias WHERE id = :id');
        $stmt->execute(['id' => $id]);
        if (!$stmt->fetch()) {
            throw new Exception('Categoría no encontrada.');
        }

        $stmt = $pdo->prepare('SELECT id FROM categorias WHERE slug = :slug AND id != :id');
        $stmt->execute(['slug' => $slug, 'id' => $id]);
        if ($stmt->fetch()) {
            throw new Exception('Ya existe otra categoría con ese slug.');
        }

        $stmt = $pdo->prepare('UPDATE categorias SET nombre = :nombre, slug = :slug WHERE id = :id');
        $stmt->execute([
            'nombre' => $nombre,
            'slug'   => $slug,
            'id'     => $id,
        ]);

        $_SESSION['flash_msg'] = ['type' => 'success', 'text' => 'Categoría "' . $nombre . '" actualizada correctamente.'];

    } else {
        if (!in_array($tipo, ['producto', 'proyecto'], true)) {
            throw new Exception('El tipo debe ser "producto" o "proyecto".');
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
    }

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
