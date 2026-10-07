<?php

require_once __DIR__ . '/auth.php';

$stmt = $pdo->query('
    SELECT p.*, c.nombre AS categoria_nombre 
    FROM proyectos p 
    LEFT JOIN categorias c ON p.categoria_id = c.id 
    WHERE p.eliminado_at IS NULL
    ORDER BY p.orden ASC, p.created_at DESC
');
$proyectos = $stmt->fetchAll();

$stmt_cat = $pdo->prepare("SELECT id, nombre FROM categorias WHERE tipo = 'proyecto' ORDER BY nombre");
$stmt_cat->execute();
$categorias = $stmt_cat->fetchAll();

$msg = $_SESSION['flash_msg'] ?? null;
unset($_SESSION['flash_msg']);

$pagina_activa = 'proyectos.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proyectos — Martineau Admin</title>

    <link rel="icon" type="image/x-icon" href="/favicon.ico?v=2">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon-32.png?v=2">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon-16.png?v=2">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/apple-touch-icon.png?v=2">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:ital,opsz,wght@0,6..96,400..900;1,6..96,400..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css?v=16">
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
                <h1>Proyectos</h1>
                <div class="admin-topbar-actions">
                    <button class="btn-admin btn-primary" data-modal-open="modal-proyecto">
                        <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Nuevo Proyecto
                    </button>
                </div>
            </div>

            <!-- Flash Messages -->
            <?php if ($msg): ?>
                <div class="alert <?= flash_alert_class($msg['type']) ?>">
                    <?= e($msg['text']) ?>
                </div>
            <?php endif; ?>

            <!-- Tabla de Proyectos -->
            <?php if (empty($proyectos)): ?>
                <div class="admin-table-wrapper">
                    <div class="empty-state">
                        <svg viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                        <p>No hay proyectos cargados todavía.</p>
                        <button class="btn-admin btn-primary" data-modal-open="modal-proyecto">Crear primer proyecto</button>
                    </div>
                </div>
            <?php else: ?>
                <div class="admin-filter-bar">
                    <label for="buscar-proyectos">Buscar</label>
                    <input type="search" id="buscar-proyectos" class="form-control" placeholder="Por título…" data-buscar-tabla="sortable-proyectos" autocomplete="off">
                    <span class="admin-filter-count" data-filtro-count="sortable-proyectos"></span>
                </div>
                <!-- Acciones sobre los seleccionados (las casillas de la tabla apuntan a este form) -->
                <form method="POST" action="actions/acciones_masivas.php" id="form-seleccion" class="barra-seleccion" data-seleccion="sortable-proyectos" hidden>
                    <?= csrf_field() ?>
                    <input type="hidden" name="tipo" value="proyecto">
                    <span class="barra-seleccion-cuenta" data-seleccion-cuenta></span>
                    <button type="submit" name="accion" value="destacar" class="btn-admin btn-secondary btn-sm">Destacar</button>
                    <button type="submit" name="accion" value="no_destacar" class="btn-admin btn-secondary btn-sm">Quitar destacado</button>
                    <select name="categoria_id" class="form-control" aria-label="Categoría a la que moverlos">
                        <option value="">Mover a categoría…</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= (int)$cat['id'] ?>"><?= e($cat['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="accion" value="categoria" class="btn-admin btn-secondary btn-sm">Mover</button>
                    <button type="submit" name="accion" value="eliminar" class="btn-admin btn-danger btn-sm" title="Se pueden restaurar desde la Papelera">Eliminar</button>
                </form>

                <div class="admin-table-wrapper">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th class="col-check"><input type="checkbox" data-seleccion-todos="sortable-proyectos" aria-label="Seleccionar todos"></th>
                                <th style="width: 40px;"></th>
                                <th>Imagen</th>
                                <th>Título</th>
                                <th>Categoría</th>
                                <th>Ubicación</th>
                                <th>Año</th>
                                <th>Destacar</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="sortable-proyectos">
                            <?php foreach ($proyectos as $proy): ?>
                                <tr data-id="<?= $proy['id'] ?>">
                                    <td class="col-check"><input type="checkbox" name="items[]" value="<?= (int)$proy['id'] ?>" form="form-seleccion" aria-label="Seleccionar <?= e($proy['titulo']) ?>"></td>
                                    <td class="drag-handle" title="Arrastrar para reordenar">☰</td>
                                    <td>
                                        <?php if ($proy['imagen']): ?>
                                            <img src="../<?= e($proy['imagen']) ?>" alt="<?= e($proy['titulo']) ?>" class="table-thumb" loading="lazy" decoding="async">
                                        <?php else: ?>
                                            <div class="table-thumb" style="background: var(--admin-bg); display:flex; align-items:center; justify-content:center; color:var(--admin-text-light); font-size:0.6rem;">Sin img</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="table-title"><?= e($proy['titulo']) ?></td>
                                    <td>
                                        <?php if ($proy['categoria_nombre']): ?>
                                            <span class="badge badge-cat"><?= e($proy['categoria_nombre']) ?></span>
                                        <?php else: ?>
                                            <span style="color:var(--admin-text-light)">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e($proy['ubicacion'] ?? '') ?></td>
                                    <td><?= e($proy['anio'] ?? '') ?></td>
                                    <td>
                                        <form method="POST" action="actions/toggle_destacar.php" style="display:inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="tipo" value="proyecto">
                                            <input type="hidden" name="id" value="<?= (int)$proy['id'] ?>">
                                            <button type="submit" class="toggle-btn" title="Cambiar estado destacar">
                                                <?php if ($proy['destacar']): ?>
                                                    <span class="badge badge-active">★ Destacado</span>
                                                <?php else: ?>
                                                    <span class="badge badge-inactive">☆ Normal</span>
                                                <?php endif; ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <button type="button" class="btn-admin btn-secondary btn-sm" onclick='editarItem(<?= json_encode([
                                                "id" => $proy["id"],
                                                "titulo" => $proy["titulo"],
                                                "slug" => $proy["slug"] ?? "",
                                                "categoria_id" => $proy["categoria_id"],
                                                "ubicacion" => $proy["ubicacion"],
                                                "anio" => $proy["anio"],
                                                "descripcion" => $proy["descripcion"],
                                                "destacar" => $proy["destacar"],
                                                "specs" => $proy["specs"],
                                                "imagen" => $proy["imagen"],
                                                "imagenes" => $proy["imagenes"] ? json_decode($proy["imagenes"], true) : [],
                                                "_modal_title" => "Editar Proyecto"
                                            ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>, "modal-proyecto")'>
                                                Editar
                                            </button>

                                            <form method="POST" action="actions/duplicar_proyecto.php" style="display:inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= (int)$proy['id'] ?>">
                                                <button type="submit" class="btn-admin btn-secondary btn-sm">Duplicar</button>
                                            </form>

                                            <form method="POST" action="actions/eliminar_proyecto.php" style="display:inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= (int)$proy['id'] ?>">
                                                <button type="submit" class="btn-admin btn-danger btn-sm" title="Se puede restaurar desde la Papelera">Eliminar</button>
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

    <!-- MODAL: Crear/Editar Proyecto -->
    <div class="modal-overlay" id="modal-proyecto">
        <div class="modal">
            <div class="modal-header">
                <h2>Nuevo Proyecto</h2>
                <button class="modal-close" data-modal-close>
                    <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <form method="POST" action="actions/guardar_proyecto.php" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="">

                <div class="modal-body">
                    <!-- Título -->
                    <div class="form-group">
                        <label for="titulo">Título</label>
                        <input type="text" id="titulo" name="titulo" class="form-control" placeholder="Ej: Residencia Montaña" required>
                    </div>

                    <!-- Dirección web (URL amigable, ver inc/slugs.php) -->
                    <div class="form-group">
                        <label for="slug">Dirección web</label>
                        <div class="slug-campo">
                            <span class="slug-prefijo">armartineau.com.ar/proyecto/</span>
                            <input type="text" id="slug" name="slug" class="form-control" data-slug-desde="titulo" placeholder="se arma sola con el título" maxlength="80" pattern="[a-z0-9-]*" title="Solo minúsculas, números y guiones">
                        </div>
                        <span class="form-ayuda">Se completa sola con el título. Si la cambiás, la dirección anterior sigue funcionando y lleva a la nueva.</span>
                    </div>

                    <div class="form-row">
                        <!-- Categoría -->
                        <div class="form-group">
                            <label for="categoria_id">Categoría</label>
                            <select id="categoria_id" name="categoria_id" class="form-control" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= (int)$cat['id'] ?>"><?= e($cat['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Año -->
                        <div class="form-group">
                            <label for="anio">Año</label>
                            <input type="text" id="anio" name="anio" class="form-control" placeholder="Ej: 2024" maxlength="4">
                        </div>
                    </div>

                    <!-- Ubicación -->
                    <div class="form-group">
                        <label for="ubicacion">Ubicación</label>
                        <input type="text" id="ubicacion" name="ubicacion" class="form-control" placeholder="Ej: Buenos Aires, Argentina">
                    </div>

                    <!-- Destacar -->
                    <div class="form-group">
                        <label class="form-check">
                            <input type="checkbox" name="destacar" value="1">
                            <span>Destacar en la Home</span>
                        </label>
                    </div>

                    <!-- Descripción -->
                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion" class="form-control" rows="4" placeholder="Descripción del proyecto..."></textarea>
                    </div>

                    <!-- Imagen Principal -->
                    <div class="form-group">
                        <label>Imagen Principal <span style="color:var(--admin-danger)">*</span></label>
                        <div id="current-main-image" style="display:none; margin-bottom: 15px;">
                            <div class="preview-thumb">
                                <img src="" style="width: 100px; height: 100px; object-fit: cover; border-radius: 6px; border: 1px solid var(--admin-border);">
                                <button type="button" class="remove-preview" title="Eliminar imagen" onclick="this.closest('#current-main-image').querySelector('input[type=checkbox]').checked = true; this.parentElement.style.display = 'none';">&times;</button>
                            </div>
                            <input type="checkbox" name="eliminar_imagen_principal" value="1" style="display:none;">
                        </div>
                        <div class="file-upload-area">
                            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            <p>Arrastrá una imagen o <strong>hacé clic para seleccionar</strong></p>
                            <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp">
                        </div>
                        <div class="image-preview"></div>
                    </div>

                    <!-- Galería -->
                    <div class="form-group">
                        <label>Galería de Imágenes actuales</label>
                        <div id="current-gallery" style="display:none; margin-bottom: 10px; gap: 10px; flex-wrap: wrap; background: var(--admin-bg); padding: 10px; border-radius: 4px;">
                        </div>
                        <label>Subir nuevas imágenes a la galería</label>
                        <div class="file-upload-area">
                            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            <p>Seleccioná múltiples imágenes para la galería</p>
                            <input type="file" name="imagenes[]" accept="image/jpeg,image/png,image/webp" multiple>
                        </div>
                        <div class="image-preview"></div>
                    </div>

                    <!-- Specs Dinámicos -->
                    <div class="form-group">
                        <label>Especificaciones Técnicas</label>
                        <div class="specs-container">
                        </div>
                        <button type="button" class="btn-add-spec" style="margin-top: 0.75rem;">
                            + Agregar especificación
                        </button>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-admin btn-secondary" data-modal-close>Cancelar</button>
                    <button type="submit" class="btn-admin btn-primary">Guardar Proyecto</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Copia local: la política de seguridad del panel solo deja cargar scripts del propio sitio -->
    <script src="vendor/Sortable.min.js?v=1.15.7"></script>
    <script src="admin.js?v=24"></script>
    <script src="editor-imagen.js?v=4"></script>
</body>
</html>
