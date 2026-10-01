<?php
require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../papelera.php');
    exit();
}

verificar_csrf();

// Los ítems llegan como "producto:12" / "proyecto:7" (la papelera mezcla los dos tipos)
function papelera_items_por_tipo(array $valores): array
{
    $por_tipo = [];
    foreach ($valores as $valor) {
        [$tipo, $id] = array_pad(explode(':', (string)$valor, 2), 2, '');
        if (array_key_exists($tipo, PAPELERA_TABLAS) && (int)$id > 0) {
            $por_tipo[$tipo][] = (int)$id;
        }
    }
    return $por_tipo;
}

try {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'vaciar') {
        $por_tipo = [];
        foreach (PAPELERA_TABLAS as $tipo => $tabla) {
            $por_tipo[$tipo] = $pdo->query("SELECT id FROM $tabla WHERE eliminado_at IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
        }
    } else {
        $por_tipo = papelera_items_por_tipo((array)($_POST['items'] ?? []));
    }

    if (!array_filter($por_tipo)) {
        throw new Exception($accion === 'vaciar' ? 'La papelera ya está vacía.' : 'No seleccionaste nada.');
    }

    $total = 0;
    foreach ($por_tipo as $tipo => $ids) {
        if (!$ids) continue;
        if ($accion === 'restaurar') {
            $total += papelera_restaurar($pdo, $tipo, $ids);
        } elseif ($accion === 'borrar' || $accion === 'vaciar') {
            $total += papelera_borrar($pdo, $tipo, $ids);
        } else {
            throw new Exception('Acción inválida.');
        }
    }

    $texto = $accion === 'restaurar'
        ? ($total === 1
            ? 'Se restauró 1 ítem, con la visibilidad que tenía antes de eliminarlo.'
            : "Se restauraron $total ítems, con la visibilidad que tenían antes de eliminarlos.")
        : ($total === 1 ? 'Se borró 1 ítem para siempre, junto con sus imágenes.' : "Se borraron $total ítems para siempre, junto con sus imágenes.");

    $_SESSION['flash_msg'] = ['type' => 'success', 'text' => $texto];

} catch (Exception $e) {
    $_SESSION['flash_msg'] = ['type' => 'error', 'text' => $e->getMessage()];
}

header('Location: ../papelera.php');
exit();
