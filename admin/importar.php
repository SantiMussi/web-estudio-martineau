<?php

require_once __DIR__ . '/auth.php';

$msg = $_SESSION['flash_msg'] ?? null;
unset($_SESSION['flash_msg']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar — Martineau Admin</title>

    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/apple-touch-icon.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:ital,opsz,wght@0,6..96,400..900;1,6..96,400..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css?v=7">
</head>
<body>
    <!-- Sidebar Toggle Móvil -->
    <button class="sidebar-toggle" aria-label="Menú">
        <svg viewBox="0 0 24 24"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>

    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-logo">Martineau</div>
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
                <a href="categorias.php">
                    <svg viewBox="0 0 24 24"><path d="M4 7V4h16v3M9 20h6M12 4v16"/></svg>
                    Categorías
                </a>
                <a href="importar.php" class="active">
                    <svg viewBox="0 0 24 24"><path d="M12 3v12m0-12l4 4m-4-4L8 7"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                    Importar
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
                <h1>Importar</h1>
            </div>

            <!-- Flash Messages -->
            <?php if ($msg): ?>
                <div class="alert <?= flash_alert_class($msg['type']) ?>">
                    <?= e($msg['text']) ?>
                </div>
            <?php endif; ?>

            <p style="color:var(--admin-text-muted); font-size:0.85rem; margin-bottom:1.5rem; max-width: 65ch;">
                Cargá varias categorías, proyectos o productos de una sola vez pegando datos separados por coma (o punto y coma) o subiendo un archivo <code>.csv</code> exportado de una planilla de cálculo (Excel, Google Sheets). La primera línea debe ser el encabezado con los nombres de columna. Las imágenes no se importan por este medio: se agregan después editando cada ítem desde su sección correspondiente.
            </p>

            <!-- OPTIMIZAR IMÁGENES YA SUBIDAS -->
            <div class="import-card">
                <h2>Optimizar imágenes ya subidas</h2>
                <p>
                    Redimensiona y comprime (a WebP) las fotos de productos y proyectos que ya están cargadas, para que el sitio pese menos y cargue más rápido en el celular. No borra nada, solo achica los archivos existentes. Si hay muchas imágenes puede que necesites apretar el botón más de una vez.
                </p>
                <form method="POST" action="actions/optimizar_imagenes.php">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn-admin btn-primary">Optimizar imágenes</button>
                </form>
            </div>

            <!-- CATEGORÍAS -->
            <div class="import-card">
                <h2>Categorías</h2>
                <p>Si una categoría ya existe (mismo nombre y tipo), se omite sin duplicarla.</p>

                <div class="import-columns">
                    <code>nombre</code>
                    <code>tipo <em>(producto o proyecto)</em></code>
                </div>

                <div class="import-example">nombre,tipo
Chimeneas,producto
Maceteros,producto
Jardines,proyecto</div>

                <form method="POST" action="actions/importar_categorias.php" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label for="cat-datos">Pegar datos</label>
                        <textarea id="cat-datos" name="datos" class="form-control" rows="5" placeholder="nombre,tipo&#10;Chimeneas,producto"></textarea>
                    </div>
                    <div class="import-divider">o</div>
                    <div class="form-group">
                        <label for="cat-archivo">Subir archivo .csv</label>
                        <input type="file" id="cat-archivo" name="archivo" class="form-control" accept=".csv,.txt,text/csv">
                    </div>
                    <button type="submit" class="btn-admin btn-primary">Importar categorías</button>
                </form>
            </div>

            <!-- PROYECTOS -->
            <div class="import-card">
                <h2>Proyectos</h2>
                <p>La columna <code>categoria</code> es el nombre de la categoría (tipo proyecto); si no existe, se crea sola. <code>destacar</code> acepta 1/0 o si/no.</p>

                <div class="import-columns">
                    <code>titulo</code>
                    <code>categoria</code>
                    <code>ubicacion</code>
                    <code>anio</code>
                    <code>descripcion</code>
                    <code>destacar <em>(opcional)</em></code>
                </div>

                <div class="import-example">titulo,categoria,ubicacion,anio,descripcion,destacar
Residencia Montaña,Jardines,Bariloche,2023,Jardín escultórico de piedra,si
Loft Palermo,Interiores,Buenos Aires,2022,Reforma integral de loft,</div>

                <form method="POST" action="actions/importar_proyectos.php" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label for="proy-datos">Pegar datos</label>
                        <textarea id="proy-datos" name="datos" class="form-control" rows="5" placeholder="titulo,categoria,ubicacion,anio,descripcion,destacar"></textarea>
                    </div>
                    <div class="import-divider">o</div>
                    <div class="form-group">
                        <label for="proy-archivo">Subir archivo .csv</label>
                        <input type="file" id="proy-archivo" name="archivo" class="form-control" accept=".csv,.txt,text/csv">
                    </div>
                    <button type="submit" class="btn-admin btn-primary">Importar proyectos</button>
                </form>
            </div>

            <!-- PRODUCTOS -->
            <div class="import-card">
                <h2>Productos (catálogo)</h2>
                <p>La columna <code>categoria</code> es el nombre de la categoría (tipo producto); si no existe, se crea sola. <code>destacar</code> y <code>oculto</code> aceptan 1/0 o si/no.</p>

                <div class="import-columns">
                    <code>titulo</code>
                    <code>categoria</code>
                    <code>descripcion</code>
                    <code>destacar <em>(opcional)</em></code>
                    <code>oculto <em>(opcional)</em></code>
                </div>

                <div class="import-example">titulo,categoria,descripcion,destacar,oculto
Macetero Roma,Maceteros,Macetero de piedra reconstituida,si,
Pieza Monolith,Esculturas,Pieza escultórica monolítica,,</div>

                <form method="POST" action="actions/importar_productos.php" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label for="prod-datos">Pegar datos</label>
                        <textarea id="prod-datos" name="datos" class="form-control" rows="5" placeholder="titulo,categoria,descripcion,destacar,oculto"></textarea>
                    </div>
                    <div class="import-divider">o</div>
                    <div class="form-group">
                        <label for="prod-archivo">Subir archivo .csv</label>
                        <input type="file" id="prod-archivo" name="archivo" class="form-control" accept=".csv,.txt,text/csv">
                    </div>
                    <button type="submit" class="btn-admin btn-primary">Importar productos</button>
                </form>
            </div>
        </main>
    </div>

    <script src="admin.js?v=14"></script>
</body>
</html>
