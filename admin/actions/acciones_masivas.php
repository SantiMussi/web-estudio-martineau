<?php
require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit();
}

verificar_csrf();

// Lo mismo que los botones de cada fila, pero para varios productos o proyectos a la vez
$tipo = ($_POST['tipo'] ?? '') === 'proyecto' ? 'proyecto' : 'producto';
$volver = $tipo === 'proyecto' ? '../proyectos.php' : '../index.php';

try {
    $tabla = papelera_tabla($tipo);
    $accion = $_POST['accion'] ?? '';
    [$in, $params] = papelera_in((array)($_POST['items'] ?? []));

    if (!$params) {
        throw new Exception('No seleccionaste nada.');
    }

    $campos = [
        'destacar'    => 'destacar = 1',
        'no_destacar' => 'destacar = 0',
        'ocultar'     => 'oculto = 1',
        'mostrar'     => 'oculto = 0',
    ];

    if ($accion === 'eliminar') {
        $total = papelera_enviar($pdo, $tipo, array_values($params));
        $texto = "$total ítem(s) enviado(s) a la papelera.";

    } elseif ($accion === 'categoria') {
        $categoria_id = (int)($_POST['categoria_id'] ?? 0);
        $stmt = $pdo->prepare('SELECT nombre FROM categorias WHERE id = :id AND tipo = :tipo');
        $stmt->execute(['id' => $categoria_id, 'tipo' => $tipo]);
        $categoria = $stmt->fetchColumn();
        if ($categoria === false) {
            throw new Exception('Elegí la categoría a la que querés moverlos.');
        }

        $stmt = $pdo->prepare("UPDATE $tabla SET categoria_id = :categoria WHERE id $in AND eliminado_at IS NULL");
        $stmt->execute($params + ['categoria' => $categoria_id]);
        $texto = $stmt->rowCount() . " ítem(s) movido(s) a \"$categoria\".";

    } elseif (isset($campos[$accion])) {
        $stmt = $pdo->prepare("UPDATE $tabla SET {$campos[$accion]} WHERE id $in AND eliminado_at IS NULL");
        $stmt->execute($params);
        $texto = count($params) . ' ítem(s) actualizado(s).';

    } else {
        throw new Exception('Acción inválida.');
    }

    $_SESSION['flash_msg'] = ['type' => 'success', 'text' => $texto];

} catch (Exception $e) {
    $_SESSION['flash_msg'] = ['type' => 'error', 'text' => $e->getMessage()];
}

header('Location: ' . $volver);
exit();
