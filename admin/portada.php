<?php

require_once __DIR__ . '/auth.php';

try {
    $fotos = $pdo->query('SELECT * FROM portada ORDER BY orden ASC, id ASC')->fetchAll();
} catch (Exception $e) {
    // La tabla la crea migraciones.php; si falló, se reintenta en el próximo pedido
    $fotos = [];
}
$visibles = count(array_filter($fotos, fn($f) => !$f['oculto']));

$msg = $_SESSION['flash_msg'] ?? null;
unset($_SESSION['flash_msg']);

$pagina_activa = 'portada.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portada — Martineau Admin</title>

    <link rel="icon" type="image/x-icon" href="/favicon.ico?v=2">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon-32.png?v=2">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon-16.png?v=2">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/apple-touch-icon.png?v=2">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:ital,opsz,wght@0,6..96,400..900;1,6..96,400..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css?v=9">
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
                <h1>Fotos de la portada</h1>
            </div>

            <!-- Flash Messages -->
            <?php if ($msg): ?>
                <div class="alert <?= flash_alert_class($msg['type']) ?>">
                    <?= e($msg['text']) ?>
                </div>
            <?php endif; ?>

            <p class="ajustes-intro">
                Son las fotos que van pasando de fondo al entrar al sitio, en este orden (arrastrá ☰ para cambiarlo). La primera es la que se ve apenas carga la página, así que conviene que sea la mejor. Al subir una se arma sola la versión para celular: un recorte vertical del centro de la foto, más liviano.
            </p>

            <?php if ($fotos && $visibles === 0): ?>
                <div class="alert alert-warning">Todas las fotos están ocultas: mientras tanto el sitio muestra las 4 fotos originales.</div>
            <?php endif; ?>

            <form method="POST" action="actions/guardar_portada.php" enctype="multipart/form-data" class="import-card portada-subir">
                <?= csrf_field() ?>
                <h2>Agregar fotos</h2>
                <p>Horizontales, de al menos 1920 px de ancho, y con lo importante en el centro (en celulares se ve solo la parte del medio). JPG, PNG o WebP de hasta 15 MB.</p>
                <div class="file-upload-area">
                    <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <p>Arrastrá las fotos o <strong>hacé clic para seleccionar</strong></p>
                    <input type="file" name="fotos[]" accept="image/jpeg,image/png,image/webp" multiple required>
                </div>
                <div class="image-preview"></div>
                <button type="submit" class="btn-admin btn-primary">Subir fotos</button>
            </form>

            <?php if (empty($fotos)): ?>
                <div class="admin-table-wrapper">
                    <div class="empty-state">
                        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        <p>No hay fotos cargadas: el sitio muestra las 4 fotos originales.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="admin-table-wrapper">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th style="width: 40px;"></th>
                                <th>Computadora</th>
                                <th>Celular</th>
                                <th>Visibilidad</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="sortable-portada">
                            <?php foreach ($fotos as $i => $foto): ?>
                                <tr data-id="<?= (int)$foto['id'] ?>">
                                    <td class="drag-handle" title="Arrastrar para reordenar">☰</td>
                                    <td>
                                        <img src="../<?= e($foto['imagen']) ?>" alt="" class="portada-thumb" loading="lazy" decoding="async">
                                    </td>
                                    <td>
                                        <?php if ($foto['imagen_movil']): ?>
                                            <img src="../<?= e($foto['imagen_movil']) ?>" alt="" class="portada-thumb portada-thumb-movil" loading="lazy" decoding="async">
                                        <?php else: ?>
                                            <span class="form-ayuda">Usa la de computadora</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" action="actions/toggle_ocultar.php" style="display:inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="tipo" value="portada">
                                            <input type="hidden" name="id" value="<?= (int)$foto['id'] ?>">
                                            <button type="submit" class="toggle-btn" title="Cambiar visibilidad">
                                                <?php if ($foto['oculto']): ?>
                                                    <span class="badge badge-inactive">👁 Oculta</span>
                                                <?php else: ?>
                                                    <span class="badge badge-active">👁 Visible</span>
                                                <?php endif; ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <form method="POST" action="actions/eliminar_portada.php" style="display:inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int)$foto['id'] ?>">
                                            <button type="submit" class="btn-admin btn-danger btn-sm" data-confirmar="¿Eliminar esta foto de la portada? No se puede deshacer. Si solo querés sacarla un tiempo, ocultala.">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script src="admin.js?v=21"></script>
</body>
</html>
