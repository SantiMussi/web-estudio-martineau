<?php
require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../importar.php');
    exit();
}

verificar_csrf();

$que = $_POST['que'] ?? '';
$fecha = date('Y-m-d');

try {
    if ($que === 'backup') {
        $sql = exportar_backup_sql($pdo);
        exportar_descarga("martineau-backup-$fecha.sql", 'application/sql', $sql);
    }

    // Planillas con las mismas columnas que Importar, separadas por ";" para que
    // Excel en español las abra en columnas (Importar detecta el separador solo).
    // No incluyen lo que está en la papelera.
    $planillas = [
        'productos' => [
            'columnas' => ['titulo', 'categoria', 'descripcion', 'destacar', 'oculto', 'specs'],
            'sql' => "
                SELECT p.titulo, c.nombre AS categoria, p.descripcion, p.destacar, IFNULL(p.oculto, 0) AS oculto, p.specs
                FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id
                WHERE p.eliminado_at IS NULL
                ORDER BY p.orden ASC, p.created_at DESC
            ",
        ],
        'proyectos' => [
            'columnas' => ['titulo', 'categoria', 'ubicacion', 'anio', 'descripcion', 'destacar'],
            'sql' => "
                SELECT p.titulo, c.nombre AS categoria, p.ubicacion, p.anio, p.descripcion, p.destacar
                FROM proyectos p LEFT JOIN categorias c ON p.categoria_id = c.id
                WHERE p.eliminado_at IS NULL
                ORDER BY p.orden ASC, p.created_at DESC
            ",
        ],
        'categorias' => [
            'columnas' => ['nombre', 'tipo'],
            'sql' => 'SELECT nombre, tipo FROM categorias ORDER BY tipo, nombre',
        ],
    ];

    if (!isset($planillas[$que])) {
        throw new Exception('Exportación inválida.');
    }

    $planilla = $planillas[$que];
    $csv = fopen('php://temp', 'r+');
    fputcsv($csv, $planilla['columnas'], ';');
    foreach ($pdo->query($planilla['sql']) as $fila) {
        $valores = [];
        foreach ($planilla['columnas'] as $columna) {
            $valor = (string)($fila[$columna] ?? '');
            if ($columna === 'destacar' || $columna === 'oculto') {
                $valor = $valor ? 'si' : 'no';
            }
            // Especificaciones en el mismo formato que lee Importar: "Etiqueta: valor | Etiqueta: valor"
            if ($columna === 'specs') {
                $lista = json_decode($valor, true);
                $valor = is_array($lista) ? implode(' | ', array_map(function ($s) {
                    return trim($s['label'] ?? '') . ': ' . trim($s['value'] ?? '');
                }, array_filter($lista, function ($s) {
                    return is_array($s) && trim($s['label'] ?? '') !== '' && trim($s['value'] ?? '') !== '';
                }))) : '';
            }
            // Importar lee una fila por renglón: los saltos de línea de la descripción se pasan a espacios
            $valores[] = trim(preg_replace('/\s*[\r\n]+\s*/', ' ', $valor));
        }
        fputcsv($csv, $valores, ';');
    }
    rewind($csv);
    // BOM: sin él, Excel muestra mal los acentos
    exportar_descarga("martineau-$que-$fecha.csv", 'text/csv; charset=utf-8', "\xEF\xBB\xBF" . stream_get_contents($csv));

} catch (Exception $e) {
    error_log('[exportar.php] ' . $e->getMessage());
    $_SESSION['flash_msg'] = ['type' => 'error', 'text' => 'No se pudo exportar: ' . $e->getMessage()];
    header('Location: ../importar.php');
    exit();
}

function exportar_descarga(string $nombre, string $tipo, string $contenido): void
{
    header('Content-Type: ' . $tipo);
    header('Content-Disposition: attachment; filename="' . $nombre . '"');
    header('Content-Length: ' . strlen($contenido));
    header('Cache-Control: no-store');
    echo $contenido;
    exit();
}

/**
 * Copia completa de la base en SQL (estructura + datos de todas las tablas).
 * Se restaura desde phpMyAdmin → Importar: borra y vuelve a crear cada tabla.
 */
function exportar_backup_sql(PDO $pdo): string
{
    // Intentos de login: no vale la pena guardarlos
    $sin_datos = ['intentos_login'];

    $salida = "-- Backup de la base de Martineau\n"
        . '-- Generado el ' . date('d/m/Y H:i') . " desde el panel\n"
        . "-- Para restaurarlo: phpMyAdmin → elegir la base → Importar → este archivo.\n"
        . "-- Reemplaza las tablas actuales por las de este backup. Las fotos no van acá: están en admin/uploads.\n\n"
        . "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n";

    $tablas = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tablas as $tabla) {
        $nombre = '`' . str_replace('`', '``', $tabla) . '`';
        $crear = $pdo->query("SHOW CREATE TABLE $nombre")->fetch(PDO::FETCH_NUM)[1];

        $salida .= "-- Tabla $tabla\nDROP TABLE IF EXISTS $nombre;\n$crear;\n\n";

        if (in_array($tabla, $sin_datos, true)) continue;

        $filas = $pdo->query("SELECT * FROM $nombre")->fetchAll(PDO::FETCH_ASSOC);
        foreach (array_chunk($filas, 100) as $tanda) {
            $columnas = implode(', ', array_map(function ($c) {
                return '`' . str_replace('`', '``', $c) . '`';
            }, array_keys($tanda[0])));

            $valores = array_map(function ($fila) use ($pdo) {
                return '(' . implode(', ', array_map(function ($v) use ($pdo) {
                    return $v === null ? 'NULL' : $pdo->quote((string)$v);
                }, $fila)) . ')';
            }, $tanda);

            $salida .= "INSERT INTO $nombre ($columnas) VALUES\n" . implode(",\n", $valores) . ";\n";
        }
        $salida .= "\n";
    }

    return $salida . "SET FOREIGN_KEY_CHECKS = 1;\n";
}
