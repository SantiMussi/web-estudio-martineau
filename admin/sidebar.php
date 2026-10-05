<?php
/*
 * Menú lateral del panel, común a todas las pantallas.
 * Antes de incluirlo, cada página define $pagina_activa con el nombre de su archivo.
 */
if (!isset($pagina_activa, $pdo)) {
    http_response_code(404);
    exit();
}

$en_papelera = papelera_contar($pdo);

$secciones = [
    'index.php' => ['Productos', '<path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>'],
    'proyectos.php' => ['Proyectos', '<rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>'],
    'categorias.php' => ['Categorías', '<path d="M4 7V4h16v3M9 20h6M12 4v16"/>'],
    'portada.php' => ['Portada', '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>'],
    'editor.php' => ['Editor de fotos', '<path d="M6 2v14a2 2 0 0 0 2 2h14"/><path d="M18 22V8a2 2 0 0 0-2-2H2"/>'],
    'revision.php' => ['Revisar fichas', '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>'],
    'resenas.php' => ['Reseñas', '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>'],
    'importar.php' => ['Importar y exportar', '<path d="M12 3v12m0-12l4 4m-4-4L8 7"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>'],
    'ajustes.php' => ['Contacto', '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.63 3.4 2 2 0 0 1 3.6 1.22h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.78a16 16 0 0 0 6 6l.95-.95a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/>'],
    'papelera.php' => ['Papelera', '<polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>'],
];
?>
        <aside class="admin-sidebar">
            <div class="sidebar-logo">Martineau</div>
            <div class="sidebar-label">Administración</div>

            <nav class="sidebar-nav">
<?php foreach ($secciones as $archivo => [$nombre, $icono]): ?>
                <a href="<?= $archivo ?>"<?= $archivo === $pagina_activa ? ' class="active"' : '' ?>>
                    <svg viewBox="0 0 24 24"><?= $icono ?></svg>
                    <?= e($nombre) ?>
<?php if ($archivo === 'papelera.php' && $en_papelera > 0): ?>
                    <span class="sidebar-contador"><?= $en_papelera ?></span>
<?php endif; ?>
                </a>
<?php endforeach; ?>
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
