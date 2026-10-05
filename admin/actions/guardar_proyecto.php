<?php


require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../galeria_reemplazos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../proyectos.php');
    exit();
}

verificar_csrf();

try {
    $id           = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $titulo       = trim($_POST['titulo'] ?? '');
    $categoria_id = !empty($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : null;
    $ubicacion    = trim($_POST['ubicacion'] ?? '');
    $anio         = trim($_POST['anio'] ?? '');
    $descripcion  = trim($_POST['descripcion'] ?? '');
    $destacar     = isset($_POST['destacar']) ? 1 : 0;
    $specs        = $_POST['specs'] ?? '[]';

    if (empty($titulo)) {
        throw new Exception('El título es obligatorio.');
    }

    $specs_decoded = json_decode($specs, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $specs = '[]';
    }

    $imagen_path = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $imagen_path = subir_imagen($_FILES['imagen']);
        if ($imagen_path === false) {
            throw new Exception('Error al subir la imagen. Verificá que sea JPG, PNG o WebP y no supere 10MB.');
        }
    }

    $imagenes_nuevas = [];
    if (isset($_FILES['imagenes'])) {
        $total = count($_FILES['imagenes']['name']);
        for ($i = 0; $i < $total; $i++) {
            if ($_FILES['imagenes']['error'][$i] === UPLOAD_ERR_OK) {
                $file = [
                    'name'     => $_FILES['imagenes']['name'][$i],
                    'type'     => $_FILES['imagenes']['type'][$i],
                    'tmp_name' => $_FILES['imagenes']['tmp_name'][$i],
                    'error'    => $_FILES['imagenes']['error'][$i],
                    'size'     => $_FILES['imagenes']['size'][$i],
                ];
                $path = subir_imagen($file);
                if ($path !== false) {
                    $imagenes_nuevas[] = $path;
                }
            }
        }
    }

    if ($id) {
        $stmt = $pdo->prepare('SELECT imagen, imagenes FROM proyectos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $actual = $stmt->fetch();

        if (!$actual) {
            throw new Exception('Proyecto no encontrado.');
        }

        if (!$imagen_path) {
            $imagen_path = $actual['imagen'];
        } else {
            if ($actual['imagen']) {
                eliminar_imagen($actual['imagen']);
            }
        }

        // Galería: igual que en productos, las fotos nuevas se suman, las marcadas con la
        // cruz se borran y las editadas en el panel reemplazan a la original en su lugar
        $galeria_actual = $actual['imagenes'] ? json_decode($actual['imagenes'], true) : [];
        if (!is_array($galeria_actual)) $galeria_actual = [];

        $imagenes_a_eliminar = $_POST['eliminar_galeria'] ?? [];
        if (!is_array($imagenes_a_eliminar)) $imagenes_a_eliminar = [];

        $reemplazos = fgal_reemplazos();

        $galeria_final = [];
        foreach ($galeria_actual as $img) {
            if (in_array($img, $imagenes_a_eliminar)) {
                eliminar_imagen($img);
            } elseif (isset($reemplazos[$img]) && ($editada = subir_imagen($reemplazos[$img])) !== false) {
                eliminar_imagen($img);
                $galeria_final[] = $editada;
            } else {
                $galeria_final[] = $img;
            }
        }

        $galeria_final = array_merge($galeria_final, $imagenes_nuevas);
        $imagenes_json = !empty($galeria_final) ? json_encode($galeria_final) : null;

        $stmt = $pdo->prepare('
            UPDATE proyectos SET 
                titulo = :titulo,
                categoria_id = :categoria_id,
                ubicacion = :ubicacion,
                anio = :anio,
                descripcion = :descripcion,
                imagen = :imagen,
                imagenes = :imagenes,
                specs = :specs,
                destacar = :destacar
            WHERE id = :id
        ');
        $stmt->execute([
            'titulo'       => $titulo,
            'categoria_id' => $categoria_id,
            'ubicacion'    => $ubicacion,
            'anio'         => $anio,
            'descripcion'  => $descripcion,
            'imagen'       => $imagen_path,
            'imagenes'     => $imagenes_json,
            'specs'        => $specs,
            'destacar'     => $destacar,
            'id'           => $id,
        ]);

        $_SESSION['flash_msg'] = ['type' => 'success', 'text' => 'Proyecto actualizado correctamente.'];

    } else {
        $imagenes_json = !empty($imagenes_nuevas) ? json_encode($imagenes_nuevas) : null;

        $stmt = $pdo->prepare('
            INSERT INTO proyectos (titulo, categoria_id, ubicacion, anio, descripcion, imagen, imagenes, specs, destacar) 
            VALUES (:titulo, :categoria_id, :ubicacion, :anio, :descripcion, :imagen, :imagenes, :specs, :destacar)
        ');
        $stmt->execute([
            'titulo'       => $titulo,
            'categoria_id' => $categoria_id,
            'ubicacion'    => $ubicacion,
            'anio'         => $anio,
            'descripcion'  => $descripcion,
            'imagen'       => $imagen_path,
            'imagenes'     => $imagenes_json,
            'specs'        => $specs,
            'destacar'     => $destacar,
        ]);

        $_SESSION['flash_msg'] = ['type' => 'success', 'text' => 'Proyecto creado correctamente.'];
    }

    // URL amigable de la ficha (/proyecto/nombre-de-la-pieza, ver inc/slugs.php)
    $id_guardado = $id ?: (int)$pdo->lastInsertId();
    if ($id_guardado > 0 && fslug_disponibles($pdo)) {
        fslug_asignar($pdo, 'proyecto', $id_guardado, trim((string)($_POST['slug'] ?? '')), $titulo);
    }

} catch (Exception $e) {
    $_SESSION['flash_msg'] = ['type' => 'error', 'text' => $e->getMessage()];
}

header('Location: ../proyectos.php');
exit();
