<?php

require_once __DIR__ . '/auth.php';

/*
 * Fichas a las que les falta algo. Importa para el SEO: la descripción es lo que
 * Google y la vista previa de WhatsApp muestran de cada pieza, y las fichas con
 * varias fotos se ven mejor en Google Imágenes.
 */
const REVISION_DESC_MINIMA = 80;

$problemas = [
    'sin_descripcion' => ['Sin descripción', 'Google muestra un texto genérico en su lugar.'],
    'descripcion_corta' => ['Descripción corta', 'Menos de ' . REVISION_DESC_MINIMA . ' caracteres: conviene contar material, medidas o estilo.'],
    'una_foto' => ['Una sola foto', 'Sin galería: sumar detalles o la pieza instalada.'],
    'sin_imagen' => ['Sin foto principal', 'No se ve bien en el catálogo ni al compartir.'],
    'sin_categoria' => ['Sin categoría', 'No aparece en los filtros del catálogo.'],
];

$fichas = [];
foreach (['producto' => 'productos', 'proyecto' => 'proyectos'] as $tipo => $tabla) {
    $filas = $pdo->query("
        SELECT id, titulo, descripcion, imagen, imagenes, categoria_id, IFNULL(oculto, 0) AS oculto
        FROM $tabla
        WHERE eliminado_at IS NULL
        ORDER BY IFNULL(oculto, 0) ASC, orden ASC, id DESC
    ")->fetchAll();

    foreach ($filas as $f) {
        $desc = trim((string)$f['descripcion']);
        $galeria = json_decode((string)$f['imagenes'], true);
        $faltas = [];
        if ($desc === '') $faltas[] = 'sin_descripcion';
        elseif (mb_strlen($desc) < REVISION_DESC_MINIMA) $faltas[] = 'descripcion_corta';
        if (empty($f['imagen'])) $faltas[] = 'sin_imagen';
        elseif (!is_array($galeria) || count(array_filter($galeria)) === 0) $faltas[] = 'una_foto';
        if (empty($f['categoria_id'])) $faltas[] = 'sin_categoria';

        if ($faltas) {
            $fichas[] = $f + ['tipo' => $tipo, 'faltas' => $faltas];
        }
    }
}

$conteo = array_fill_keys(array_keys($problemas), 0);
foreach ($fichas as $f) {
    foreach ($f['faltas'] as $p) $conteo[$p]++;
}

$filtro = $_GET['problema'] ?? '';
if (!isset($problemas[$filtro])) $filtro = '';
$mostrar = $filtro ? array_filter($fichas, fn($f) => in_array($filtro, $f['faltas'], true)) : $fichas;

$pagina_activa = 'revision.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revisar fichas — Martineau Admin</title>

    <link rel="icon" type="image/x-icon" href="/favicon.ico?v=2">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon-32.png?v=2">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon-16.png?v=2">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/apple-touch-icon.png?v=2">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:ital,opsz,wght@0,6..96,400..900;1,6..96,400..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css?v=11">
</head>
<body>
    <!-- Sidebar Toggle Móvil -->
    <button class="sidebar-toggle" aria-label="Menú">
        <svg viewBox="0 0 24 24"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>

    <div class="admin-layout">
        <?php require __DIR__ . '/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="admin-main">
            <div class="admin-topbar">
                <h1>Revisar fichas</h1>
            </div>

            <p class="ajustes-intro">
                Productos y proyectos a los que les falta algo. Completarlos ayuda a que aparezcan mejor en Google: la descripción es lo que se muestra en los resultados y al compartir el link por WhatsApp. "Completar" abre la ficha para editarla. Los que están en la papelera no se cuentan.
            </p>

            <div class="revision-resumen">
                <a href="revision.php" class="revision-tarjeta<?= $filtro === '' ? ' is-activa' : '' ?>">
                    <strong><?= count($fichas) ?></strong>
                    <span>Fichas para revisar</span>
                </a>
                <?php foreach ($problemas as $clave => [$nombre, $ayuda]): ?>
                    <a href="revision.php?problema=<?= $clave ?>" class="revision-tarjeta<?= $filtro === $clave ? ' is-activa' : '' ?><?= $conteo[$clave] === 0 ? ' is-vacia' : '' ?>" title="<?= e($ayuda) ?>">
                        <strong><?= $conteo[$clave] ?></strong>
                        <span><?= e($nombre) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if (empty($mostrar)): ?>
                <div class="admin-table-wrapper">
                    <div class="empty-state">
                        <svg viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        <p><?= $filtro ? 'No hay fichas con este problema.' : 'Todas las fichas están completas.' ?></p>
                    </div>
                </div>
            <?php else: ?>
                <div class="admin-table-wrapper">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Imagen</th>
                                <th>Título</th>
                                <th>Qué falta</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mostrar as $f): ?>
                                <tr>
                                    <td>
                                        <?php if ($f['imagen']): ?>
                                            <img src="../<?= e($f['imagen']) ?>" alt="" class="table-thumb" loading="lazy" decoding="async">
                                        <?php endif; ?>
                                    </td>
                                    <td class="table-title">
                                        <?= e($f['titulo']) ?>
                                        <br><span class="badge badge-cat"><?= $f['tipo'] === 'producto' ? 'Producto' : 'Proyecto' ?></span>
                                        <?php if ($f['oculto']): ?>
                                            <span class="badge badge-inactive">Oculto</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="revision-faltas">
                                            <?php foreach ($f['faltas'] as $p): ?>
                                                <span class="badge badge-falta" title="<?= e($problemas[$p][1]) ?>"><?= e($problemas[$p][0]) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="<?= $f['tipo'] === 'producto' ? 'index.php' : 'proyectos.php' ?>?editar=<?= (int)$f['id'] ?>" class="btn-admin btn-secondary btn-sm">Completar</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="admin.js?v=21"></script>
</body>
</html>
