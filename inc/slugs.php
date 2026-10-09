<?php

const FSLUG_TABLAS = ['producto' => 'productos', 'proyecto' => 'proyectos'];

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

function fslug_disponibles(?PDO $pdo): bool
{
    static $hay = null;
    if ($hay !== null) return $hay;
    if (!$pdo) return $hay = false;
    try {
        return $hay = $pdo->query('SELECT slug FROM productos LIMIT 0') !== false
            && $pdo->query('SELECT slug FROM proyectos LIMIT 0') !== false;
    } catch (Throwable $e) {
        return $hay = false;
    }
}

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

function fslug_asignar(PDO $pdo, string $tipo, int $id, string $pedido, string $titulo): string
{
    $tabla = FSLUG_TABLAS[$tipo];

    $stmt = $pdo->prepare("SELECT slug FROM $tabla WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $actual = (string)$stmt->fetchColumn();

    $base = fslug_generar($pedido !== '' ? $pedido : ($actual !== '' ? $actual : $titulo));
    $nuevo = fslug_unico($pdo, $tabla, $base, $id);

    if ($actual !== '' && $actual !== $nuevo) {
        $pdo->prepare('
            INSERT INTO redirecciones_slug (tipo, slug, item_id) VALUES (:tipo, :slug, :id)
            ON DUPLICATE KEY UPDATE item_id = VALUES(item_id)
        ')->execute(['tipo' => $tipo, 'slug' => $actual, 'id' => $id]);
    }
    $pdo->prepare('DELETE FROM redirecciones_slug WHERE tipo = :tipo AND slug = :slug')
        ->execute(['tipo' => $tipo, 'slug' => $nuevo]);

    $pdo->prepare("UPDATE $tabla SET slug = :slug WHERE id = :id")->execute(['slug' => $nuevo, 'id' => $id]);
    return $nuevo;
}

function fslug_completar(PDO $pdo): void
{
    foreach (FSLUG_TABLAS as $tabla) {
        $vacios = $pdo->query("SELECT id, titulo FROM $tabla WHERE slug IS NULL OR slug = '' ORDER BY id")->fetchAll();
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

function ficha_ruta(string $tipo, array $item): string
{
    return !empty($item['slug'])
        ? $tipo . '/' . rawurlencode($item['slug'])
        : $tipo . '?id=' . (int)$item['id'];
}
