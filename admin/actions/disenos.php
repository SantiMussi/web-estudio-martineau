<?php
require_once __DIR__ . '/../auth.php';

header('Content-Type: application/json; charset=utf-8');

const DISENOS_CARPETA = 'admin/uploads/disenos';
const DISENOS_TAMANO_MAX = 20 * 1024 * 1024;
const DISENOS_DATOS_MAX = 40 * 1024 * 1024;

function disenos_responder(array $respuesta, int $codigo = 200): void
{
    http_response_code($codigo);
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

function disenos_guardar_archivo(array $archivo, string $base, string $sufijo, array $tipos): string
{
    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'])) {
        throw new Exception('No llegó una de las imágenes del diseño. Probá de nuevo.');
    }
    if ($archivo['size'] > DISENOS_TAMANO_MAX) {
        throw new Exception('La foto del diseño pesa más de 20 MB.');
    }
    $extensiones = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
    if (!in_array($mime, $tipos, true) || !isset($extensiones[$mime]) || !@getimagesize($archivo['tmp_name'])) {
        throw new Exception('Una de las imágenes del diseño no es válida.');
    }

    $carpeta = dirname(__DIR__, 2) . '/' . DISENOS_CARPETA;
    if (!is_dir($carpeta)) mkdir($carpeta, 0755, true);
    $nombre = "$base-$sufijo." . $extensiones[$mime];
    if (!move_uploaded_file($archivo['tmp_name'], "$carpeta/$nombre")) {
        throw new Exception('No se pudo guardar el diseño en el servidor.');
    }
    return DISENOS_CARPETA . "/$nombre";
}

function disenos_borrar(?string $ruta): void
{
    if ($ruta && strpos($ruta, DISENOS_CARPETA . '/') === 0 && strpos($ruta, '..') === false) {
        @unlink(dirname(__DIR__, 2) . '/' . $ruta);
    }
}

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

try {
    if ($accion === 'listar') {
        $filas = $pdo->query('SELECT id, nombre, miniatura, updated_at FROM disenos ORDER BY updated_at DESC, id DESC')->fetchAll();
        disenos_responder(['ok' => true, 'disenos' => array_map(fn($f) => [
            'id'          => (int)$f['id'],
            'nombre'      => $f['nombre'],
            'miniatura'   => $f['miniatura'],
            'actualizado' => $f['updated_at'],
        ], $filas)]);
    }

    if ($accion === 'ver') {
        $stmt = $pdo->prepare('SELECT * FROM disenos WHERE id = :id');
        $stmt->execute(['id' => (int)($_GET['id'] ?? 0)]);
        $diseno = $stmt->fetch();
        if (!$diseno) {
            throw new Exception('Ese diseño ya no existe.');
        }
        disenos_responder(['ok' => true, 'diseno' => [
            'id'       => (int)$diseno['id'],
            'nombre'   => $diseno['nombre'],
            'original' => $diseno['original'],
            'datos'    => json_decode($diseno['datos'], true),
        ]]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        disenos_responder(['ok' => false, 'error' => 'Pedido no válido.'], 400);
    }
    verificar_csrf();

    if ($accion === 'guardar') {
        $id = (int)($_POST['id'] ?? 0);
        $nombre = trim(mb_substr((string)($_POST['nombre'] ?? ''), 0, 120)) ?: 'Diseño sin nombre';
        $datos = (string)($_POST['datos'] ?? '');
        if (strlen($datos) > DISENOS_DATOS_MAX) {
            throw new Exception('El diseño es demasiado grande (¿muchas imágenes agregadas en las capas?).');
        }
        if (!is_array(json_decode($datos, true))) {
            throw new Exception('Los datos del diseño llegaron incompletos. Probá de nuevo.');
        }

        $actual = null;
        if ($id) {
            $stmt = $pdo->prepare('SELECT * FROM disenos WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $actual = $stmt->fetch() ?: null;
            if (!$actual) {
                throw new Exception('Ese diseño ya no existe. Guardalo de nuevo.');
            }
        }

        $base = 'diseno-' . bin2hex(random_bytes(8));
        $nuevos = [];
        try {
            $original = $actual['original'] ?? null;
            if (!$actual) {
                $original = disenos_guardar_archivo($_FILES['original'] ?? [], $base, 'original', ['image/jpeg', 'image/png', 'image/webp']);
                $nuevos[] = $original;
            }
            $miniatura = disenos_guardar_archivo($_FILES['miniatura'] ?? [], $base, 'miniatura', ['image/jpeg']);
            $nuevos[] = $miniatura;

            if ($actual) {
                $pdo->prepare('
                    UPDATE disenos SET nombre = :nombre, miniatura = :miniatura, datos = :datos
                    WHERE id = :id
                ')->execute([
                    'nombre'    => $nombre,
                    'miniatura' => $miniatura,
                    'datos'     => $datos,
                    'id'        => $id,
                ]);
                disenos_borrar($actual['miniatura']);
            } else {
                $pdo->prepare('
                    INSERT INTO disenos (nombre, original, miniatura, datos)
                    VALUES (:nombre, :original, :miniatura, :datos)
                ')->execute([
                    'nombre'    => $nombre,
                    'original'  => $original,
                    'miniatura' => $miniatura,
                    'datos'     => $datos,
                ]);
                $id = (int)$pdo->lastInsertId();
            }
        } catch (Exception $e) {
            foreach ($nuevos as $ruta) disenos_borrar($ruta);
            throw $e;
        }

        disenos_responder(['ok' => true, 'id' => $id, 'nombre' => $nombre, 'original' => $original]);
    }

    if ($accion === 'eliminar') {
        $stmt = $pdo->prepare('SELECT * FROM disenos WHERE id = :id');
        $stmt->execute(['id' => (int)($_POST['id'] ?? 0)]);
        $diseno = $stmt->fetch();
        if ($diseno) {
            $pdo->prepare('DELETE FROM disenos WHERE id = :id')->execute(['id' => $diseno['id']]);
            foreach (['original', 'miniatura'] as $campo) disenos_borrar($diseno[$campo]);
        }
        disenos_responder(['ok' => true]);
    }

    disenos_responder(['ok' => false, 'error' => 'Acción desconocida.'], 400);

} catch (Exception $e) {
    disenos_responder(['ok' => false, 'error' => $e->getMessage()], 400);
}
