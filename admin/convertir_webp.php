<?php

require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id'])) {
    die("Acceso denegado. Iniciá sesión en el panel de admin primero.");
}

echo "<h1>Convirtiendo imágenes a WebP...</h1>";

$stmt = $pdo->query('SELECT id, imagen, imagenes FROM productos');
$productos = $stmt->fetchAll();

$convertidas = 0;
$errores = 0;

function convertir_a_webp_si_es_necesario($ruta_relativa) {
    global $convertidas, $errores;
    
    if (empty($ruta_relativa)) return null;
    
    if (pathinfo($ruta_relativa, PATHINFO_EXTENSION) === 'webp') {
        return $ruta_relativa;
    }
    
    $ruta_absoluta = dirname(__DIR__) . '/' . $ruta_relativa;
    
    if (!file_exists($ruta_absoluta)) {
        return $ruta_relativa;
    }
    
    $mime = mime_content_type($ruta_absoluta);
    
    $img_res = false;
    if ($mime === 'image/jpeg') {
        $img_res = @imagecreatefromjpeg($ruta_absoluta);
    } elseif ($mime === 'image/png') {
        $img_res = @imagecreatefrompng($ruta_absoluta);
        if ($img_res) {
            imagepalettetotruecolor($img_res);
            imagealphablending($img_res, true);
            imagesavealpha($img_res, true);
        }
    }
    
    if (!$img_res) {
        $errores++;
        return $ruta_relativa;
    }
    
    $nueva_ruta_relativa = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $ruta_relativa);
    $nueva_ruta_absoluta = dirname(__DIR__) . '/' . $nueva_ruta_relativa;
    
    if ($nueva_ruta_relativa === $ruta_relativa) {
        $nueva_ruta_relativa .= '.webp';
        $nueva_ruta_absoluta .= '.webp';
    }
    
    $exito = imagewebp($img_res, $nueva_ruta_absoluta, 80);
    imagedestroy($img_res);
    
    if ($exito) {
        unlink($ruta_absoluta);
        $convertidas++;
        return $nueva_ruta_relativa;
    } else {
        $errores++;
        return $ruta_relativa;
    }
}

foreach ($productos as $prod) {
    $actualizado = false;
    
    $nueva_imagen = convertir_a_webp_si_es_necesario($prod['imagen']);
    if ($nueva_imagen !== $prod['imagen']) {
        $actualizado = true;
    }
    
    $galeria = $prod['imagenes'] ? json_decode($prod['imagenes'], true) : [];
    $nueva_galeria = [];
    if (is_array($galeria)) {
        foreach ($galeria as $img) {
            $nueva_img = convertir_a_webp_si_es_necesario($img);
            $nueva_galeria[] = $nueva_img;
            if ($nueva_img !== $img) {
                $actualizado = true;
            }
        }
    }
    
    if ($actualizado) {
        $upd = $pdo->prepare('UPDATE productos SET imagen = :imagen, imagenes = :imagenes WHERE id = :id');
        $upd->execute([
            'imagen' => $nueva_imagen,
            'imagenes' => !empty($nueva_galeria) ? json_encode($nueva_galeria) : null,
            'id' => $prod['id']
        ]);
        echo "<p>Producto ID {$prod['id']} actualizado.</p>";
    }
}

echo "<h2>Proceso terminado.</h2>";
echo "<p>Imágenes convertidas: <strong>$convertidas</strong></p>";
if ($errores > 0) {
    echo "<p>Errores (no se pudieron convertir): <strong>$errores</strong></p>";
}
echo "<p><a href='index.php'>Volver al administrador</a></p>";
