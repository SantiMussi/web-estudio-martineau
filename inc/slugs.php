<?php
/*
 * URLs amigables para las fichas: /producto/chimenea-luis-xv en vez de /producto?id=12.
 *
 * - Cada producto/proyecto tiene un "slug" (columna `slug`), que se genera solo desde
 *   el título y se puede editar en el panel.
 * - Si se cambia, el slug viejo queda guardado en `redirecciones_slug` y redirige (301)
 *   al nuevo, así no se rompen los links que ya se compartieron.
 * - Los links viejos con ?id= también redirigen a la URL con el slug.
 * - Hasta que se entra al panel por primera vez después de actualizar el código (ahí
 *   se crean las columnas, ver admin/migraciones.php), todo sigue andando con ?id=.
 */

const FSLUG_TABLAS = ['producto' => 'productos', 'proyecto' => 'proyectos'];

/** "Chimenea Luis XV (copia)" → "chimenea-luis-xv-copia" */
function fslug_generar(string $texto): string
{
    $t = mb_strtolower(trim($texto), 'UTF-8');
    $t = strtr($t, [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ä' => 'a', 'ã' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'ö' => 'o', 'õ' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ñ' => 'n', 'ç' => 'c',
    ]);
    $t = trim(preg_replace('/[^a-z0-9]+/', '-', $t), '-');
    if (strlen($t) > 80) $t = rtrim(substr($t, 0, 80), '-');
    return $t;
}

/** ¿Ya se corrió la migración que agrega la columna? (se consulta una vez por pedido) */
function fslug_disponibles(?PDO $pdo): bool
{
    static $hay = null;
    if ($hay !== null) return $hay;
    if (!$pdo) return $hay = false;
    try {
        // Según cómo esté configurado PDO, una consulta fallida tira excepción o devuelve false
        return $hay = $pdo->query('SELECT slug FROM productos LIMIT 0') !== false
            && $pdo->query('SELECT slug FROM proyectos LIMIT 0') !== false;
    } catch (Throwable $e) {
        return $hay = false;
    }
}

/** El slug pedido, o con -2, -3… si ya lo usa otro ítem de la misma tabla */
function fslug_unico(PDO $pdo, string $tabla, string $base, int $excluir_id = 0): string
{
    $base = $base !== '' ? $base : 'pieza';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM $tabla WHERE slug = :slug AND id <> :id");
    $slug = $base;
    for ($n = 2; ; $n++) {
        $stmt->execute(['slug' => $slug, 'id' => $excluir_id]);
        if ((int)$stmt->fetchColumn() === 0) return $slug;
        $slug = $base . '-' . $n;
    }
}

/**
 * Guarda el slug de un ítem al crearlo o editarlo desde el panel.
 * $pedido: lo que escribieron en el campo (vacío = generarlo desde el título, o
 * dejar el que ya tenía). Devuelve el slug final.
 */
function fslug_asignar(PDO $pdo, string $tipo, int $id, string $pedido, string $titulo): string
{
    $tabla = FSLUG_TABLAS[$tipo];

    $stmt = $pdo->prepare("SELECT slug FROM $tabla WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $actual = (string)$stmt->fetchColumn();

    $base = fslug_generar($pedido !== '' ? $pedido : ($actual !== '' ? $actual : $titulo));
    $nuevo = fslug_unico($pdo, $tabla, $base, $id);

    if ($actual !== '' && $actual !== $nuevo) {
        // El link viejo sigue funcionando: redirige al nuevo
        $pdo->prepare('
            INSERT INTO redirecciones_slug (tipo, slug, item_id) VALUES (:tipo, :slug, :id)
            ON DUPLICATE KEY UPDATE item_id = VALUES(item_id)
        ')->execute(['tipo' => $tipo, 'slug' => $actual, 'id' => $id]);
    }
    // Si el slug nuevo era una dirección vieja de otro ítem, ahora es de este
    $pdo->prepare('DELETE FROM redirecciones_slug WHERE tipo = :tipo AND slug = :slug')
        ->execute(['tipo' => $tipo, 'slug' => $nuevo]);

    $pdo->prepare("UPDATE $tabla SET slug = :slug WHERE id = :id")->execute(['slug' => $nuevo, 'id' => $id]);
    return $nuevo;
}

/**
 * Completa los slugs que falten o estén repetidos (ítems nuevos, duplicados o
 * importados). Lo llama auth.php en cada pedido del panel: son dos consultas livianas.
 */
function fslug_completar(PDO $pdo): void
{
    foreach (FSLUG_TABLAS as $tabla) {
        $vacios = $pdo->query("SELECT id, titulo FROM $tabla WHERE slug IS NULL OR slug = '' ORDER BY id")->fetchAll();
        // De cada slug repetido se queda con él el ítem más viejo; al resto se le arma otro
        $repetidos = $pdo->query("
            SELECT t.id, t.titulo FROM $tabla t
            JOIN (SELECT slug, MIN(id) AS primero FROM $tabla WHERE slug <> '' GROUP BY slug HAVING COUNT(*) > 1) r
              ON t.slug = r.slug AND t.id <> r.primero
            ORDER BY t.id
        ")->fetchAll();

        $upd = $pdo->prepare("UPDATE $tabla SET slug = :slug WHERE id = :id");
        foreach (array_merge($vacios, $repetidos) as $fila) {
            $upd->execute([
                'slug' => fslug_unico($pdo, $tabla, fslug_generar((string)$fila['titulo']), (int)$fila['id']),
                'id'   => (int)$fila['id'],
            ]);
        }
    }
}

/** Ruta de la ficha relativa al sitio, sin barra inicial: "producto/chimenea-luis-xv" */
function ficha_ruta(string $tipo, array $item): string
{
    return !empty($item['slug'])
        ? $tipo . '/' . rawurlencode($item['slug'])
        : $tipo . '?id=' . (int)$item['id'];
}
