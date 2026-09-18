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

// Quita tildes y pasa a minúsculas, para comparar labels sin importar cómo estén tipeados.
function normalizar_texto(string $texto): string {
    $normalizado = mb_strtolower(trim($texto), 'UTF-8');
    return str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $normalizado);
}

// Detecta si una categoría corresponde a maceteros/macetas.
function es_categoria_macetas(?string $categoriaNombre): bool {
    if (!$categoriaNombre) {
        return false;
    }
    return mb_stripos($categoriaNombre, 'macet', 0, 'UTF-8') !== false;
}

$reglas = [
    [
        'label_normalizado' => 'material',
        'label_default'     => 'Material',
        'resolver'          => function (?string $categoriaNombre): string {
            return 'Tipo Piedra París (cemento blanco, marmolina en distintos tonos y granulado de mármol)';
        },
    ],
    [
        'label_normalizado' => 'terminacion',
        'label_default'     => 'Terminación',
        'resolver'          => function (?string $categoriaNombre): string {
            return es_categoria_macetas($categoriaNombre) ? 'Impermeabilizada con cerecita' : 'Mate';
        },
    ],
];

$stmt = $pdo->query('
    SELECT p.id, p.specs, c.nombre AS categoria_nombre
    FROM productos p
    LEFT JOIN categorias c ON p.categoria_id = c.id
');
$productos = $stmt->fetchAll();

$stats = [];
foreach ($reglas as $regla) {
    $stats[$regla['label_normalizado']] = ['corregidos' => 0, 'agregados' => 0, 'ya_bien' => 0];
}
$sin_specs = 0;

foreach ($productos as $prod) {
    $specs = $prod['specs'] ? json_decode($prod['specs'], true) : [];
    if (!is_array($specs)) {
        $specs = [];
    }

    if (empty($specs)) {
        // Producto sin ninguna especificación técnica todavía: no le agregamos filas sueltas,
        // que use el botón "Specs estándar" para cargar el set completo.
        $sin_specs++;
        continue;
    }

    $huboCambios = false;

    foreach ($reglas as $regla) {
        $valorCorrecto = $regla['resolver']($prod['categoria_nombre']);
        $encontrada = false;

        foreach ($specs as &$spec) {
            if (isset($spec['label']) && normalizar_texto((string)$spec['label']) === $regla['label_normalizado']) {
                $encontrada = true;
                if (($spec['value'] ?? '') !== $valorCorrecto) {
                    $spec['value'] = $valorCorrecto;
                    $stats[$regla['label_normalizado']]['corregidos']++;
                    $huboCambios = true;
                } else {
                    $stats[$regla['label_normalizado']]['ya_bien']++;
                }
                break;
            }
        }
        unset($spec);

        if (!$encontrada) {
            $specs[] = ['label' => $regla['label_default'], 'value' => $valorCorrecto];
            $stats[$regla['label_normalizado']]['agregados']++;
            $huboCambios = true;
        }
    }

    if ($huboCambios) {
        $stmt2 = $pdo->prepare('UPDATE productos SET specs = :specs WHERE id = :id');
        $stmt2->execute([
            'specs' => json_encode($specs, JSON_UNESCAPED_UNICODE),
            'id'    => $prod['id'],
        ]);
    }
}

$partes = [];
foreach ($reglas as $regla) {
    $s = $stats[$regla['label_normalizado']];
    $partes[] = "{$regla['label_default']}: {$s['corregidos']} corregido(s), {$s['agregados']} agregado(s), {$s['ya_bien']} ya estaban bien.";
}
$texto = implode(' ', $partes);
if ($sin_specs > 0) {
    $texto .= " $sin_specs producto(s) sin especificaciones técnicas cargadas (se dejaron como están).";
}

$_SESSION['flash_msg'] = ['type' => 'success', 'text' => $texto];

header('Location: ../importar.php');
exit();
