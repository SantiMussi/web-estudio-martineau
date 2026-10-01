<?php

require_once __DIR__ . '/auth.php';

$purgados = 0;
try {
    $purgados = papelera_purgar_vencidos($pdo);
} catch (Exception $e) {
    error_log('[papelera.php] ' . $e->getMessage());
}

$items = [];
foreach (PAPELERA_TABLAS as $tipo => $tabla) {
    $filas = $pdo->query("
        SELECT p.id, p.titulo, p.imagen, p.eliminado_at, c.nombre AS categoria_nombre
        FROM $tabla p LEFT JOIN categorias c ON p.categoria_id = c.id
        WHERE p.eliminado_at IS NOT NULL
    ")->fetchAll();
    foreach ($filas as $fila) {
        $items[] = ['tipo' => $tipo] + $fila;
    }
}
// Lo último que se eliminó, primero
usort($items, function ($a, $b) {
    return strcmp($b['eliminado_at'], $a['eliminado_at']);
});

$msg = $_SESSION['flash_msg'] ?? null;
unset($_SESSION['flash_msg']);

$pagina_activa = 'papelera.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Papelera — Martineau Admin</title>

    <link rel="icon" type="image/x-icon" href="/favicon.ico?v=2">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon-32.png?v=2">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon-16.png?v=2">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/apple-touch-icon.png?v=2">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:ital,opsz,wght@0,6..96,400..900;1,6..96,400..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css?v=8">
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
                <h1>Papelera</h1>
                <?php if ($items): ?>
                    <div class="admin-topbar-actions">
                        <form method="POST" action="actions/papelera.php">
                            <?= csrf_field() ?>
                            <input type="hidden" name="accion" value="vaciar">
                            <button type="submit" class="btn-admin btn-danger" data-confirmar="¿Borrar para siempre los <?= count($items) ?> ítems de la papelera, con sus imágenes? No se puede deshacer.">
                                Vaciar papelera
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Flash Messages -->
            <?php if ($msg): ?>
                <div class="alert <?= flash_alert_class($msg['type']) ?>">
                    <?= e($msg['text']) ?>
                </div>
            <?php endif; ?>
            <?php if ($purgados > 0): ?>
                <div class="alert alert-warning">
                    Se borraron <?= $purgados ?> ítem(s) que llevaban más de <?= PAPELERA_DIAS ?> días en la papelera.
                </div>
            <?php endif; ?>

            <p class="ajustes-intro">
                Lo que eliminás de Productos o Proyectos queda acá <?= PAPELERA_DIAS ?> días, oculto del sitio. Si lo restaurás vuelve tal cual estaba, con sus fotos; pasado ese plazo se borra solo.
            </p>

            <?php if (!$items): ?>
                <div class="admin-table-wrapper">
                    <div class="empty-state">
                        <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        <p>La papelera está vacía.</p>
                    </div>
                </div>
            <?php else: ?>
                <!-- Acciones sobre los seleccionados (las casillas de la tabla apuntan a este form) -->
                <form method="POST" action="actions/papelera.php" id="form-seleccion" class="barra-seleccion" data-seleccion="tabla-papelera" hidden>
                    <?= csrf_field() ?>
                    <span class="barra-seleccion-cuenta" data-seleccion-cuenta></span>
                    <button type="submit" name="accion" value="restaurar" class="btn-admin btn-secondary btn-sm">Restaurar</button>
                    <button type="submit" name="accion" value="borrar" class="btn-admin btn-danger btn-sm" data-confirmar="¿Borrar para siempre los ítems seleccionados, con sus imágenes? No se puede deshacer.">Borrar para siempre</button>
                </form>

                <div class="admin-table-wrapper">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th class="col-check"><input type="checkbox" data-seleccion-todos="tabla-papelera" aria-label="Seleccionar todos"></th>
                                <th>Imagen</th>
                                <th>Título</th>
                                <th>Tipo</th>
                                <th>Eliminado</th>
                                <th>Se borra</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-papelera">
                            <?php foreach ($items as $item):
                                $clave = $item['tipo'] . ':' . (int)$item['id'];
                                $eliminado = new DateTime($item['eliminado_at']);
                                $vence = (clone $eliminado)->modify('+' . PAPELERA_DIAS . ' days');
                                $dias = max(0, (int)ceil(($vence->getTimestamp() - time()) / 86400));
                            ?>
                                <tr>
                                    <td class="col-check"><input type="checkbox" name="items[]" value="<?= e($clave) ?>" form="form-seleccion" aria-label="Seleccionar <?= e($item['titulo']) ?>"></td>
                                    <td>
                                        <?php if ($item['imagen']): ?>
                                            <img src="../<?= e($item['imagen']) ?>" alt="<?= e($item['titulo']) ?>" class="table-thumb" loading="lazy" decoding="async">
                                        <?php else: ?>
                                            <div class="table-thumb" style="background: var(--admin-bg); display:flex; align-items:center; justify-content:center; color:var(--admin-text-light); font-size:0.6rem;">Sin img</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="table-title">
                                        <?= e($item['titulo']) ?>
                                        <?php if ($item['categoria_nombre']): ?>
                                            <br><span class="badge badge-cat"><?= e($item['categoria_nombre']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $item['tipo'] === 'producto' ? 'Producto' : 'Proyecto' ?></td>
                                    <td><?= $eliminado->format('d/m/Y') ?></td>
                                    <td>
                                        <?= $dias === 1 ? 'Mañana' : ($dias === 0 ? 'Hoy' : 'En ' . $dias . ' días') ?>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <form method="POST" action="actions/papelera.php" style="display:inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="items[]" value="<?= e($clave) ?>">
                                                <button type="submit" name="accion" value="restaurar" class="btn-admin btn-secondary btn-sm">Restaurar</button>
                                                <button type="submit" name="accion" value="borrar" class="btn-admin btn-danger btn-sm" data-confirmar="¿Borrar &quot;<?= e($item['titulo']) ?>&quot; para siempre, con sus imágenes? No se puede deshacer.">Borrar para siempre</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="admin.js?v=20"></script>
</body>
</html>
