<?php


require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit();
}

verificar_csrf();

try {
    $id           = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $titulo       = trim($_POST['titulo'] ?? '');
    $categoria_id = !empty($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : null;
    $descripcion  = trim($_POST['descripcion'] ?? '');
    $destacar     = isset($_POST['destacar']) ? 1 : 0;
    $oculto       = isset($_POST['oculto']) ? 1 : 0;
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
        $stmt = $pdo->prepare('SELECT imagen, imagenes FROM productos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $actual = $stmt->fetch();

        if (!$actual) {
            throw new Exception('Producto no encontrado.');
        }

        if (!$imagen_path) {
            if (isset($_POST['eliminar_imagen_principal']) && $_POST['eliminar_imagen_principal'] == '1') {
                if ($actual['imagen']) eliminar_imagen($actual['imagen']);
                $imagen_path = null;
            } else {
                $imagen_path = $actual['imagen'];
            }
        } else {
            if ($actual['imagen']) {
                eliminar_imagen($actual['imagen']);
            }
        }

        $galeria_actual = $actual['imagenes'] ? json_decode($actual['imagenes'], true) : [];
        if (!is_array($galeria_actual)) $galeria_actual = [];
        
        $imagenes_a_eliminar = $_POST['eliminar_galeria'] ?? [];
        if (!is_array($imagenes_a_eliminar)) $imagenes_a_eliminar = [];
        
        $galeria_final = [];
        foreach ($galeria_actual as $img) {
            if (in_array($img, $imagenes_a_eliminar)) {
                eliminar_imagen($img);
            } else {
                $galeria_final[] = $img;
            }
        }
        
        if (!empty($imagenes_nuevas)) {
            $galeria_final = array_merge($galeria_final, $imagenes_nuevas);
        }
        
        $imagenes_json = !empty($galeria_final) ? json_encode($galeria_final) : null;

        $stmt = $pdo->prepare('
            UPDATE productos SET 
                titulo = :titulo,
                categoria_id = :categoria_id,
                descripcion = :descripcion,
                imagen = :imagen,
                imagenes = :imagenes,
                specs = :specs,
                destacar = :destacar,
                oculto = :oculto
            WHERE id = :id
        ');
        $stmt->execute([
            'titulo'       => $titulo,
            'categoria_id' => $categoria_id,
            'descripcion'  => $descripcion,
            'imagen'       => $imagen_path,
            'imagenes'     => $imagenes_json,
            'specs'        => $specs,
            'destacar'     => $destacar,
            'oculto'       => $oculto,
            'id'           => $id,
        ]);

        $_SESSION['flash_msg'] = ['type' => 'success', 'text' => 'Producto actualizado correctamente.'];

    } else {
        $imagenes_json = !empty($imagenes_nuevas) ? json_encode($imagenes_nuevas) : null;

        $stmt = $pdo->prepare('
            INSERT INTO productos (titulo, categoria_id, descripcion, imagen, imagenes, specs, destacar, oculto) 
            VALUES (:titulo, :categoria_id, :descripcion, :imagen, :imagenes, :specs, :destacar, :oculto)
        ');
        $stmt->execute([
            'titulo'       => $titulo,
            'categoria_id' => $categoria_id,
            'descripcion'  => $descripcion,
            'imagen'       => $imagen_path,
            'imagenes'     => $imagenes_json,
            'specs'        => $specs,
            'destacar'     => $destacar,
            'oculto'       => $oculto,
        ]);

        $_SESSION['flash_msg'] = ['type' => 'success', 'text' => 'Producto creado correctamente.'];
    }

} catch (Exception $e) {
    $_SESSION['flash_msg'] = ['type' => 'error', 'text' => $e->getMessage()];
}

header('Location: ../index.php');
exit();
