<?php

require_once __DIR__ . '/auth.php';

$resenas = $pdo->query('SELECT * FROM resenas ORDER BY orden ASC, created_at DESC')->fetchAll();

$msg = $_SESSION['flash_msg'] ?? null;
unset($_SESSION['flash_msg']);

$pagina_activa = 'resenas.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reseñas — Martineau Admin</title>

    <link rel="icon" type="image/x-icon" href="/favicon.ico?v=2">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon-32.png?v=2">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon-16.png?v=2">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/apple-touch-icon.png?v=2">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:ital,opsz,wght@0,6..96,400..900;1,6..96,400..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css?v=10">
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
                <h1>Reseñas</h1>
                <div class="admin-topbar-actions">
                    <button class="btn-admin btn-primary" data-modal-open="modal-resena">
                        <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Nueva reseña
                    </button>
                </div>
            </div>

            <!-- Flash Messages -->
            <?php if ($msg): ?>
                <div class="alert <?= flash_alert_class($msg['type']) ?>">
                    <?= e($msg['text']) ?>
                </div>
            <?php endif; ?>

            <p class="ajustes-intro">
                Se muestran en la sección "Lo que dicen nuestros clientes" de la página Nosotros, en este orden (arrastrá ☰ para cambiarlo). Las ocultas quedan guardadas pero no aparecen en el sitio; si están todas ocultas, la sección no se muestra.
            </p>

            <?php if (empty($resenas)): ?>
                <div class="admin-table-wrapper">
                    <div class="empty-state">
                        <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        <p>No hay reseñas cargadas todavía.</p>
                        <button class="btn-admin btn-primary" data-modal-open="modal-resena">Agregar la primera</button>
                    </div>
                </div>
            <?php else: ?>
                <div class="admin-table-wrapper">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th style="width: 40px;"></th>
                                <th>Reseña</th>
                                <th>Quién</th>
                                <th>Visibilidad</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="sortable-resenas">
                            <?php foreach ($resenas as $resena): ?>
                                <tr data-id="<?= (int)$resena['id'] ?>">
                                    <td class="drag-handle" title="Arrastrar para reordenar">☰</td>
                                    <td class="resena-texto">“<?= e($resena['texto']) ?>”</td>
                                    <td class="table-title">
                                        <?= e($resena['nombre']) ?>
                                        <?php if ($resena['detalle']): ?>
                                            <br><span class="badge badge-cat"><?= e($resena['detalle']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" action="actions/toggle_ocultar.php" style="display:inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="tipo" value="resena">
                                            <input type="hidden" name="id" value="<?= (int)$resena['id'] ?>">
                                            <button type="submit" class="toggle-btn" title="Cambiar visibilidad">
                                                <?php if ($resena['oculto']): ?>
                                                    <span class="badge badge-inactive">👁 Oculta</span>
                                                <?php else: ?>
                                                    <span class="badge badge-active">👁 Visible</span>
                                                <?php endif; ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <button type="button" class="btn-admin btn-secondary btn-sm" onclick='editarItem(<?= json_encode([
                                                "id" => $resena["id"],
                                                "nombre" => $resena["nombre"],
                                                "detalle" => $resena["detalle"] ?? "",
                                                "texto" => $resena["texto"],
                                                "oculto" => $resena["oculto"],
                                                "_modal_title" => "Editar reseña"
                                            ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>, "modal-resena")'>
                                                Editar
                                            </button>

                                            <form method="POST" action="actions/eliminar_resena.php" style="display:inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= (int)$resena['id'] ?>">
                                                <button type="submit" class="btn-admin btn-danger btn-sm" data-confirmar="¿Eliminar la reseña de <?= e($resena['nombre']) ?>? No se puede deshacer. Si solo querés sacarla del sitio, ocultala.">Eliminar</button>
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

    <!-- MODAL: Crear/Editar Reseña -->
    <div class="modal-overlay" id="modal-resena">
        <div class="modal" style="max-width: 560px;">
            <div class="modal-header">
                <h2 data-titulo-nuevo="Nueva reseña">Nueva reseña</h2>
                <button class="modal-close" data-modal-close>
                    <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <form method="POST" action="actions/guardar_resena.php">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="">

                <div class="modal-body">
                    <div class="form-group">
                        <label for="texto">Reseña</label>
                        <textarea id="texto" name="texto" class="form-control" rows="4" placeholder="Lo que dijo el cliente…" required></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="nombre">Nombre</label>
                            <input type="text" id="nombre" name="nombre" class="form-control" placeholder="Ej: José María Del Bonnis" maxlength="120" required>
                        </div>
                        <div class="form-group">
                            <label for="detalle">Detalle <span style="text-transform:none; letter-spacing:0; color:var(--admin-text-light)">(opcional)</span></label>
                            <input type="text" id="detalle" name="detalle" class="form-control" placeholder="Ej: Arquitecto, Particular" maxlength="120">
                        </div>
                    </div>

                    <label class="form-check">
                        <input type="checkbox" name="oculto" value="1">
                        <span>Ocultar (queda guardada pero no se muestra en el sitio)</span>
                    </label>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-admin btn-secondary" data-modal-close>Cancelar</button>
                    <button type="submit" class="btn-admin btn-primary">Guardar reseña</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script src="admin.js?v=21"></script>
</body>
</html>
