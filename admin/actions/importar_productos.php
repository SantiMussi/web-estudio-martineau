<?php

require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../importar.php');
    exit();
}

verificar_csrf();

// Especificaciones que se arman a partir de las columnas de la planilla.
// La palabra clave en la columna "specs" agrega las de siempre (las mismas que el
// botón de especificaciones predeterminadas del formulario de producto).
const SPECS_PALABRAS_CLAVE = ['base', 'default', 'estandar', 'predeterminadas', 'si', '1'];
const SPECS_MATERIAL = 'Tipo Piedra París (cemento blanco, marmolina en distintos tonos y granulado de mármol)';
const SPECS_REFUERZO = 'Concreto + malla de hierro';

// Columnas de medidas → etiqueta con la que se muestran (mismas que ya usa el catálogo)
const SPECS_MEDIDAS = [
    'alto'        => 'Alto',
    'ancho'       => 'Ancho',
    'espesor'     => 'Espesor',
    'profundidad' => 'Profundidad',
    'saliente'    => 'Saliente',
    'diametro'    => 'Ancho (diámetro)',
];

/** Clave de columna sin tildes, espacios ni guiones: "Terminación" → "terminacion". */
function clave_columna(string $nombre): string
{
    $nombre = mb_strtolower(trim($nombre), 'UTF-8');
    $nombre = strtr($nombre, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
    return preg_replace('/[^a-z0-9]/', '', $nombre);
}

/** Medidas con coma decimal ("0,73") pasan a punto, como el resto del catálogo. */
function normalizar_medida(string $valor): string
{
    $valor = trim($valor);
    return preg_match('/^\d+,\d+$/', $valor) ? str_replace(',', '.', $valor) : $valor;
}

/**
 * Arma el JSON de especificaciones de una fila.
 * - specs: palabra clave (base) para las de siempre y/o "Etiqueta: valor | Etiqueta: valor".
 * - terminacion: pisa la terminación de las de siempre (o se agrega sola si no hay palabra clave).
 * - alto, ancho, espesor, profundidad, saliente, diametro: medidas.
 */
function specs_de_fila(array $fila, string $categoria_nombre): string
{
    $usar_base = false;
    $extras = [];

    $texto_specs = trim($fila['specs'] ?? '');
    if ($texto_specs !== '') {
        foreach (explode('|', $texto_specs) as $parte) {
            $parte = trim($parte);
            if ($parte === '') continue;
            if (in_array(clave_columna($parte), SPECS_PALABRAS_CLAVE, true)) {
                $usar_base = true;
            } elseif (strpos($parte, ':') !== false) {
                [$label, $value] = array_map('trim', explode(':', $parte, 2));
                if ($label !== '' && $value !== '') {
                    $extras[] = ['label' => $label, 'value' => $value];
                }
            }
        }
    }

    $terminacion = trim($fila['terminacion'] ?? '');
    if ($terminacion === '' && $usar_base) {
        $terminacion = preg_match('/macet/i', $categoria_nombre) ? 'Impermeabilizada con cerecita' : 'Mate';
    }

    $specs = [];
    if ($usar_base) {
        $specs[] = ['label' => 'Material', 'value' => SPECS_MATERIAL];
        $specs[] = ['label' => 'Refuerzo', 'value' => SPECS_REFUERZO];
    }
    if ($terminacion !== '') {
        $specs[] = ['label' => 'Terminación', 'value' => $terminacion];
    }
    foreach (SPECS_MEDIDAS as $columna => $label) {
        $valor = normalizar_medida($fila[$columna] ?? '');
        if ($valor !== '') {
            $specs[] = ['label' => $label, 'value' => $valor];
        }
    }

    return json_encode(array_merge($specs, $extras), JSON_UNESCAPED_UNICODE);
}

$creados = 0;
$errores = [];

try {
    $texto = obtener_texto_importacion('archivo', 'datos');
    $filas = parsear_csv_texto($texto);

    if (empty($filas)) {
        throw new Exception('No se encontraron filas para importar. Verificá el archivo o el texto pegado.');
    }

    foreach ($filas as $i => $fila_original) {
        $num = $i + 2;

        // Encabezados sin tildes: "Terminación" y "terminacion" son la misma columna
        $fila = [];
        foreach ($fila_original as $columna => $valor) {
            $fila[clave_columna((string)$columna)] = $valor;
        }

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
            'specs'        => specs_de_fila($fila, $categoria_nombre),
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
