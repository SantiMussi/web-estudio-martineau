<?php

require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

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
        $categoria_id = $categoria_nombre !== '' ? obtener_o_crear_categoria($pdo, $categoria_nombre, 'proyecto') : null;

        $ubicacion = trim($fila['ubicacion'] ?? '');
        $anio = trim($fila['anio'] ?? '');
        $descripcion = trim($fila['descripcion'] ?? '');
        $destacar = valor_booleano($fila['destacar'] ?? '');

        $stmt = $pdo->prepare('
            INSERT INTO proyectos (titulo, categoria_id, ubicacion, anio, descripcion, specs, destacar)
            VALUES (:titulo, :categoria_id, :ubicacion, :anio, :descripcion, :specs, :destacar)
        ');
        $stmt->execute([
            'titulo'       => $titulo,
            'categoria_id' => $categoria_id,
            'ubicacion'    => $ubicacion,
            'anio'         => $anio,
            'descripcion'  => $descripcion,
            'specs'        => '[]',
            'destacar'     => $destacar,
        ]);
        $creados++;
    }

    $texto_resumen = "$creados proyecto(s) creado(s). Las imágenes se agregan después, editando cada proyecto.";
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

header('Location: ../proyectos.php');
exit();
