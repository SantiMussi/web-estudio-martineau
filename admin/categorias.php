<?php
/**
 * admin/categorias.php — Gestor de Categorías
 * 
 * Permite crear nuevas categorías (producto o proyecto),
 * genera su slug automáticamente, y permite eliminarlas
 * si no tienen ítems asociados.
 * 
 * @security Requiere autenticación. CSRF en todos los forms.
 */

require_once __DIR__ . '/auth.php';

// ─── Obtener todas las categorías con conteo de ítems ───
$categorias = $pdo->query('
    SELECT c.*, 
        (SELECT COUNT(*) FROM productos WHERE categoria_id = c.id) AS total_productos,
        (SELECT COUNT(*) FROM proyectos WHERE categoria_id = c.id) AS total_proyectos
    FROM categorias c 
    ORDER BY c.tipo, c.nombre
')->fetchAll();

// ─── Mensajes flash ───
$msg = $_SESSION['flash_msg'] ?? null;
unset($_SESSION['flash_msg']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorías — MartinEau Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <!-- Sidebar Toggle Móvil -->
    <button class="sidebar-toggle" aria-label="Menú">
        <svg viewBox="0 0 24 24"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>

    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-logo">MartinEau</div>
            <div class="sidebar-label">Administración</div>

            <nav class="sidebar-nav">
                <a href="index.php">
                    <svg viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    Productos
                </a>
                <a href="proyectos.php">
                    <svg viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    Proyectos
                </a>
                <a href="categorias.php" class="active">
                    <svg viewBox="0 0 24 24"><path d="M4 7V4h16v3M9 20h6M12 4v16"/></svg>
                    Categorías
                </a>
            </nav>

            <div class="sidebar-footer">
                <a href="../" target="_blank">
                    <svg viewBox="0 0 24 24" style="width:14px;height:14px"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    Ver sitio
                </a>
                <br>
                <a href="logout.php">
                    <svg viewBox="0 0 24 24" style="width:14px;height:14px"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Cerrar sesión
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <div class="admin-topbar">
                <h1>Categorías</h1>
                <div class="admin-topbar-actions">
                    <button class="btn-admin btn-primary" data-modal-open="modal-categoria">
                        <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Nueva Categoría
                    </button>
                </div>
            </div>

            <!-- Flash Messages -->
            <?php if ($msg): ?>
                <div class="alert <?= $msg['type'] === 'success' ? 'alert-success' : 'alert-danger' ?>">
                    <?= e($msg['text']) ?>
                </div>
            <?php endif; ?>

            <!-- Tabla de Categorías -->
            <?php if (empty($categorias)): ?>
                <div class="admin-table-wrapper">
                    <div class="empty-state">
                        <svg viewBox="0 0 24 24"><path d="M4 7V4h16v3M9 20h6M12 4v16"/></svg>
                        <p>No hay categorías creadas todavía.</p>
                        <button class="btn-admin btn-primary" data-modal-open="modal-categoria">Crear primera categoría</button>
                    </div>
                </div>
            <?php else: ?>
                <div class="admin-table-wrapper">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Slug</th>
                                <th>Tipo</th>
                                <th>Ítems Asociados</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categorias as $cat): ?>
                                <?php
                                    $total_items = $cat['tipo'] === 'producto' 
                                        ? $cat['total_productos'] 
                                        : $cat['total_proyectos'];
                                ?>
                                <tr>
                                    <td class="table-title"><?= e($cat['nombre']) ?></td>
                                    <td><code style="color:var(--admin-text-light); font-size:0.8rem;"><?= e($cat['slug']) ?></code></td>
                                    <td>
                                        <span class="badge <?= $cat['tipo'] === 'producto' ? 'badge-active' : 'badge-cat' ?>">
                                            <?= e(ucfirst($cat['tipo'])) ?>
                                        </span>
                                    </td>
                                    <td><?= (int)$total_items ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <?php if ($total_items == 0): ?>
                                                <form method="POST" action="actions/eliminar_categoria.php" style="display:inline">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
                                                    <button type="submit" class="btn-admin btn-danger btn-sm btn-eliminar">Eliminar</button>
                                                </form>
                                            <?php else: ?>
                                                <span style="font-size: 0.75rem; color: var(--admin-text-light);" title="No se puede eliminar porque tiene ítems asociados">
                                                    En uso
                                                </span>
                                            <?php endif; ?>
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

    <!-- ═══════════════════════════════════════════ -->
    <!-- MODAL: Crear Categoría -->
    <!-- ═══════════════════════════════════════════ -->
    <div class="modal-overlay" id="modal-categoria">
        <div class="modal" style="max-width: 500px;">
            <div class="modal-header">
                <h2>Nueva Categoría</h2>
                <button class="modal-close" data-modal-close>
                    <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <form method="POST" action="actions/guardar_categoria.php">
                <?= csrf_field() ?>

                <div class="modal-body">
                    <!-- Nombre -->
                    <div class="form-group">
                        <label for="nombre">Nombre</label>
                        <input type="text" id="nombre" name="nombre" class="form-control" placeholder="Ej: Chimeneas" required>
                    </div>

                    <!-- Slug (auto-generado) -->
                    <div class="form-group">
                        <label for="slug">Slug (URL)</label>
                        <input type="text" id="slug" name="slug" class="form-control" placeholder="Se genera automáticamente" readonly>
                    </div>

                    <!-- Tipo -->
                    <div class="form-group">
                        <label for="tipo">Tipo</label>
                        <select id="tipo" name="tipo" class="form-control" required>
                            <option value="">Seleccionar tipo...</option>
                            <option value="producto">Producto</option>
                            <option value="proyecto">Proyecto</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-admin btn-secondary" data-modal-close>Cancelar</button>
                    <button type="submit" class="btn-admin btn-primary">Crear Categoría</button>
                </div>
            </form>
        </div>
    </div>

    <script src="admin.js"></script>
</body>
</html>
