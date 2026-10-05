<?php
/*
 * Fotos del slideshow de la portada: al subir una se generan dos versiones WebP.
 * - Computadora: hasta 2105 px de ancho (el tamaño de las originales).
 * - Celular: recorte vertical centrado de 750×1210, que es lo que se ve en un
 *   teléfono parado (object-position: center). Pesa ~3 veces menos y es lo que
 *   hace que la portada cargue rápido en celulares.
 */

const PORTADA_ANCHO_MAX = 2105;
const PORTADA_MOVIL_ANCHO = 750;
const PORTADA_MOVIL_ALTO = 1210;
const PORTADA_TAMANO_MAX = 15 * 1024 * 1024;

/**
 * Procesa una foto subida. Devuelve ['imagen' => ruta, 'imagen_movil' => ruta]
 * (rutas relativas al sitio) o lanza una excepción con un mensaje para el usuario.
 */
function portada_procesar(array $archivo): array
{
    if (!function_exists('imagewebp') || !function_exists('imagecreatefromjpeg')) {
        throw new Exception('El servidor no tiene habilitado el procesamiento de imágenes (GD con WebP).');
    }
    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'])) {
        throw new Exception('No se pudo subir "' . ($archivo['name'] ?? 'la foto') . '".');
    }
    if ($archivo['size'] > PORTADA_TAMANO_MAX) {
        throw new Exception('"' . $archivo['name'] . '" pesa más de 15 MB.');
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
    @ini_set('memory_limit', '256M');   // fotos de cámara grandes

    if ($mime === 'image/jpeg') {
        $origen = @imagecreatefromjpeg($archivo['tmp_name']);
    } elseif ($mime === 'image/png') {
        $origen = @imagecreatefrompng($archivo['tmp_name']);
    } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
        $origen = @imagecreatefromwebp($archivo['tmp_name']);
    } else {
        throw new Exception('"' . $archivo['name'] . '" no es JPG, PNG ni WebP.');
    }
    if (!$origen) {
        throw new Exception('No se pudo leer "' . $archivo['name'] . '".');
    }

    $ancho = imagesx($origen);
    $alto = imagesy($origen);
    $nombre = 'portada-' . bin2hex(random_bytes(8));
    $carpeta = dirname(__DIR__) . '/admin/uploads';
    if (!is_dir($carpeta)) mkdir($carpeta, 0755, true);

    // Computadora: misma proporción, como mucho PORTADA_ANCHO_MAX de ancho
    $escala = min(1, PORTADA_ANCHO_MAX / $ancho);
    $grande = portada_recortar($origen, 0, 0, $ancho, $alto, (int)round($ancho * $escala), (int)round($alto * $escala));

    // Celular: recorte centrado con la proporción de un teléfono parado
    $proporcion = PORTADA_MOVIL_ANCHO / PORTADA_MOVIL_ALTO;
    if ($ancho / $alto > $proporcion) {
        $rw = (int)round($alto * $proporcion);
        $movil = portada_recortar($origen, (int)(($ancho - $rw) / 2), 0, $rw, $alto, PORTADA_MOVIL_ANCHO, PORTADA_MOVIL_ALTO);
    } else {
        $rh = (int)round($ancho / $proporcion);
        $movil = portada_recortar($origen, 0, (int)(($alto - $rh) / 2), $ancho, $rh, PORTADA_MOVIL_ANCHO, PORTADA_MOVIL_ALTO);
    }
    imagedestroy($origen);

    $ok = imagewebp($grande, "$carpeta/$nombre.webp", 80) && imagewebp($movil, "$carpeta/$nombre-movil.webp", 74);
    imagedestroy($grande);
    imagedestroy($movil);
    if (!$ok) {
        @unlink("$carpeta/$nombre.webp");
        @unlink("$carpeta/$nombre-movil.webp");
        throw new Exception('No se pudo guardar "' . $archivo['name'] . '".');
    }

    return [
        'imagen'       => "admin/uploads/$nombre.webp",
        'imagen_movil' => "admin/uploads/$nombre-movil.webp",
    ];
}

/** Copia la zona (x, y, w, h) del origen a una imagen nueva de destW × destH */
function portada_recortar($origen, int $x, int $y, int $w, int $h, int $destW, int $destH)
{
    $destino = imagecreatetruecolor($destW, $destH);
    imagecopyresampled($destino, $origen, 0, 0, $x, $y, $destW, $destH, $w, $h);
    return $destino;
}

/** Borra los archivos de una foto, solo si los subieron desde el panel (las originales de assets/ quedan) */
function portada_borrar_archivos(array $foto): void
{
    foreach (['imagen', 'imagen_movil'] as $campo) {
        $ruta = (string)($foto[$campo] ?? '');
        if (strpos($ruta, 'admin/uploads/portada-') === 0) {
            @unlink(dirname(__DIR__) . '/' . $ruta);
        }
    }
}
