<?php

require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../importar.php');
    exit();
}

verificar_csrf();

$creados = 0;
$errores = [];

try {
    $texto = obtener_texto_importacion('archivo', 'datos');
    $filas = parsear_csv_texto($texto);

    if (empty($filas)) {
        throw new Exception('No se encontraron filas para importar. Verificá el archivo o el texto pegado.');
    }

    foreach ($filas as $i => $fila) {
        $num = $i + 2;
        $titulo = trim($fila['titulo'] ?? '');

        if ($titulo === '') {
            $errores[] = "Fila $num: falta el título.";
            continue;
        }

        $categoria_nombre = trim($fila['categoria'] ?? '');
        $categoria_id = $categoria_nombre !== '' ? obtener_o_crear_categoria($pdo, $categoria_nombre, 'producto') : null;

        $descripcion = trim($fila['descripcion'] ?? '');
        $destacar = valor_booleano($fila['destacar'] ?? '');
        $oculto = valor_booleano($fila['oculto'] ?? '');

        $stmt = $pdo->prepare('
            INSERT INTO productos (titulo, categoria_id, descripcion, specs, destacar, oculto)
            VALUES (:titulo, :categoria_id, :descripcion, :specs, :destacar, :oculto)
        ');
        $stmt->execute([
            'titulo'       => $titulo,
            'categoria_id' => $categoria_id,
            'descripcion'  => $descripcion,
            'specs'        => '[]',
            'destacar'     => $destacar,
            'oculto'       => $oculto,
        ]);
        $creados++;
    }

    $texto_resumen = "$creados producto(s) creado(s). Las imágenes se agregan después, editando cada producto.";
    if (!empty($errores)) {
        $texto_resumen .= ' Errores: ' . implode(' ', array_slice($errores, 0, 10));
        if (count($errores) > 10) {
            $texto_resumen .= ' (y ' . (count($errores) - 10) . ' más)';
        }
    }

    $_SESSION['flash_msg'] = [
        'type' => $creados > 0 ? ($errores ? 'warning' : 'success') : 'error',
        'text' => $texto_resumen,
    ];

} catch (Exception $e) {
    $_SESSION['flash_msg'] = ['type' => 'error', 'text' => $e->getMessage()];
}

header('Location: ../index.php');
exit();
