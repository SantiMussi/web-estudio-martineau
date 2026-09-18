<?php

require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../importar.php');
    exit();
}

verificar_csrf();

set_time_limit(60);

const OPT_MAX_ANCHO = 1600;
const OPT_CALIDAD = 80;
const OPT_LIMITE_POR_EJECUCION = 30;
const OPT_TIEMPO_MAXIMO_SEGUNDOS = 25;

// Redimensiona (si hace falta) y re-codifica como WebP una imagen que ya está en disco.
// Devuelve null si no se pudo procesar, o un array con la ruta final y si hubo cambios.
function optimizar_imagen_archivo(string $ruta_relativa): ?array {
    if (!function_exists('imagewebp') || !function_exists('imagecreatefromjpeg')) {
        return null; // GD no disponible en este servidor
    }

    $ruta_absoluta = dirname(__DIR__, 2) . '/' . $ruta_relativa;
    if (!file_exists($ruta_absoluta) || !is_file($ruta_absoluta)) {
        return null;
    }

    $tamano_original = filesize($ruta_absoluta);

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($ruta_absoluta);

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
    } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
        $img_res = @imagecreatefromwebp($ruta_absoluta);
    } else {
        return null;
    }

    if (!$img_res) {
        return null;
    }

    $ancho = imagesx($img_res);
    $alto = imagesy($img_res);

    // Ya es liviana y no hace falta redimensionar: no la tocamos.
    if ($mime === 'image/webp' && $ancho <= OPT_MAX_ANCHO && $tamano_original < 400 * 1024) {
        imagedestroy($img_res);
        return ['ruta' => $ruta_relativa, 'cambio' => false, 'ahorro' => 0];
    }

    if ($ancho > OPT_MAX_ANCHO) {
        $nuevo_alto = (int) round($alto * (OPT_MAX_ANCHO / $ancho));
        $redimensionada = imagecreatetruecolor(OPT_MAX_ANCHO, $nuevo_alto);
        imagealphablending($redimensionada, false);
        imagesavealpha($redimensionada, true);
        imagecopyresampled($redimensionada, $img_res, 0, 0, 0, 0, OPT_MAX_ANCHO, $nuevo_alto, $ancho, $alto);
        imagedestroy($img_res);
        $img_res = $redimensionada;
    }

    $ruta_webp_relativa = preg_replace('/\.[^.\/]+$/', '.webp', $ruta_relativa);
    $ruta_webp_absoluta = dirname(__DIR__, 2) . '/' . $ruta_webp_relativa;

    $exito = imagewebp($img_res, $ruta_webp_absoluta, OPT_CALIDAD);
    imagedestroy($img_res);

    if (!$exito) {
        return null;
    }

    if ($ruta_webp_absoluta !== $ruta_absoluta) {
        @unlink($ruta_absoluta);
    }

    $tamano_nuevo = filesize($ruta_webp_absoluta);

    return [
        'ruta'   => $ruta_webp_relativa,
        'cambio' => true,
        'ahorro' => max(0, $tamano_original - $tamano_nuevo),
    ];
}

function procesar_tabla_imagenes(
    PDO $pdo,
    string $tabla,
    float $hasta,
    int &$procesadas,
    int &$sin_cambios,
    int &$ahorro_total,
    int &$errores
): bool {
    $filas = $pdo->query("SELECT id, imagen, imagenes FROM $tabla")->fetchAll();

    foreach ($filas as $fila) {
        if ($procesadas >= OPT_LIMITE_POR_EJECUCION || microtime(true) >= $hasta) {
            return true; // queda trabajo pendiente
        }

        $huboCambios = false;
        $imagenNueva = $fila['imagen'];

        if ($fila['imagen']) {
            $resultado = optimizar_imagen_archivo($fila['imagen']);
            if ($resultado === null) {
                $errores++;
            } elseif ($resultado['cambio']) {
                $imagenNueva = $resultado['ruta'];
                $ahorro_total += $resultado['ahorro'];
                $procesadas++;
                $huboCambios = true;
            } else {
                $sin_cambios++;
            }
        }

        $galeriaActual = $fila['imagenes'] ? json_decode($fila['imagenes'], true) : [];
        $galeriaNueva = [];
        if (is_array($galeriaActual)) {
            foreach ($galeriaActual as $img) {
                if ($procesadas >= OPT_LIMITE_POR_EJECUCION || microtime(true) >= $hasta) {
                    $galeriaNueva[] = $img;
                    continue;
                }
                $resultado = optimizar_imagen_archivo($img);
                if ($resultado === null) {
                    $galeriaNueva[] = $img;
                    $errores++;
                } elseif ($resultado['cambio']) {
                    $galeriaNueva[] = $resultado['ruta'];
                    $ahorro_total += $resultado['ahorro'];
                    $procesadas++;
                    $huboCambios = true;
                } else {
                    $galeriaNueva[] = $img;
                    $sin_cambios++;
                }
            }
        }

        if ($huboCambios) {
            $stmt = $pdo->prepare("UPDATE $tabla SET imagen = :imagen, imagenes = :imagenes WHERE id = :id");
            $stmt->execute([
                'imagen'   => $imagenNueva,
                'imagenes' => !empty($galeriaNueva) ? json_encode($galeriaNueva) : null,
                'id'       => $fila['id'],
            ]);
        }
    }

    return false;
}

$procesadas = 0;
$sin_cambios = 0;
$ahorro_total = 0;
$errores = 0;
$hasta = microtime(true) + OPT_TIEMPO_MAXIMO_SEGUNDOS;

$quedaPendienteProductos = procesar_tabla_imagenes($pdo, 'productos', $hasta, $procesadas, $sin_cambios, $ahorro_total, $errores);
$quedaPendienteProyectos = false;
if (!$quedaPendienteProductos) {
    $quedaPendienteProyectos = procesar_tabla_imagenes($pdo, 'proyectos', $hasta, $procesadas, $sin_cambios, $ahorro_total, $errores);
}

$quedaPendiente = $quedaPendienteProductos || $quedaPendienteProyectos;
$ahorroMb = round($ahorro_total / (1024 * 1024), 1);

$texto = "$procesadas imagen(es) optimizada(s), $sin_cambios ya estaban livianas, ahorro de {$ahorroMb} MB.";
if ($errores > 0) {
    $texto .= " $errores no se pudieron procesar.";
}
if ($quedaPendiente) {
    $texto .= ' Quedan más por optimizar: volvé a apretar el botón para seguir.';
}

$_SESSION['flash_msg'] = [
    'type' => $quedaPendiente ? 'warning' : 'success',
    'text' => $texto,
];

header('Location: ../importar.php');
exit();
