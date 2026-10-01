<?php
/*
 * Papelera de productos y proyectos.
 *
 * Eliminar desde el panel no borra: marca el ítem con eliminado_at y lo oculta (así
 * el sitio público, que ya filtra por "oculto", deja de mostrarlo sin cambiar nada).
 * Desde la papelera se puede restaurar o borrar del todo; a los PAPELERA_DIAS días
 * se borra solo, con sus imágenes.
 */

const PAPELERA_DIAS = 30;
const PAPELERA_TABLAS = ['producto' => 'productos', 'proyecto' => 'proyectos'];

function papelera_tabla(string $tipo): string
{
    if (!array_key_exists($tipo, PAPELERA_TABLAS)) {
        throw new Exception('Tipo inválido.');
    }
    return PAPELERA_TABLAS[$tipo];
}

/** "IN (:id0, :id1…)" con sus parámetros, para operar sobre varios ítems de una vez */
function papelera_in(array $ids): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), function ($id) { return $id > 0; })));
    $marcas = [];
    $params = [];
    foreach ($ids as $i => $id) {
        $marcas[] = ":id$i";
        $params["id$i"] = $id;
    }
    return [$marcas ? 'IN (' . implode(', ', $marcas) . ')' : 'IN (NULL)', $params];
}

/** Manda a la papelera. Devuelve cuántos ítems se movieron. */
function papelera_enviar(PDO $pdo, string $tipo, array $ids): int
{
    $tabla = papelera_tabla($tipo);
    [$in, $params] = papelera_in($ids);

    $stmt = $pdo->prepare("
        UPDATE $tabla
        SET oculto_antes = IFNULL(oculto, 0), oculto = 1, eliminado_at = NOW()
        WHERE id $in AND eliminado_at IS NULL
    ");
    $stmt->execute($params);
    return $stmt->rowCount();
}

/** Saca de la papelera y deja la visibilidad como estaba antes de eliminarlo */
function papelera_restaurar(PDO $pdo, string $tipo, array $ids): int
{
    $tabla = papelera_tabla($tipo);
    [$in, $params] = papelera_in($ids);

    $stmt = $pdo->prepare("
        UPDATE $tabla
        SET oculto = oculto_antes, eliminado_at = NULL
        WHERE id $in AND eliminado_at IS NOT NULL
    ");
    $stmt->execute($params);
    return $stmt->rowCount();
}

/** Borra del todo (con imágenes) ítems que ya están en la papelera */
function papelera_borrar(PDO $pdo, string $tipo, array $ids): int
{
    $tabla = papelera_tabla($tipo);
    [$in, $params] = papelera_in($ids);

    $stmt = $pdo->prepare("SELECT id, imagen, imagenes FROM $tabla WHERE id $in AND eliminado_at IS NOT NULL");
    $stmt->execute($params);
    $items = $stmt->fetchAll();
    if (!$items) return 0;

    [$in, $params] = papelera_in(array_column($items, 'id'));
    $pdo->prepare("DELETE FROM $tabla WHERE id $in")->execute($params);

    // Las imágenes se borran después de las filas: si falla el DELETE, no quedan fichas sin fotos
    foreach ($items as $item) {
        $galeria = json_decode((string)$item['imagenes'], true);
        foreach (array_filter(array_merge([$item['imagen']], is_array($galeria) ? $galeria : [])) as $img) {
            eliminar_imagen($img);
        }
    }
    return count($items);
}

/** Borra lo que lleva más de PAPELERA_DIAS días en la papelera */
function papelera_purgar_vencidos(PDO $pdo): int
{
    $borrados = 0;
    foreach (PAPELERA_TABLAS as $tipo => $tabla) {
        $ids = $pdo->query("
            SELECT id FROM $tabla
            WHERE eliminado_at IS NOT NULL AND eliminado_at < NOW() - INTERVAL " . PAPELERA_DIAS . " DAY
        ")->fetchAll(PDO::FETCH_COLUMN);
        if ($ids) {
            $borrados += papelera_borrar($pdo, $tipo, $ids);
        }
    }
    return $borrados;
}

function papelera_contar(PDO $pdo): int
{
    try {
        return (int)$pdo->query('
            SELECT (SELECT COUNT(*) FROM productos WHERE eliminado_at IS NOT NULL)
                 + (SELECT COUNT(*) FROM proyectos WHERE eliminado_at IS NOT NULL)
        ')->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}
