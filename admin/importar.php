<?php

require_once __DIR__ . '/auth.php';

$msg = $_SESSION['flash_msg'] ?? null;
unset($_SESSION['flash_msg']);

$pagina_activa = 'importar.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar y exportar — Martineau Admin</title>

    <link rel="icon" type="image/x-icon" href="/favicon.ico?v=2">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon-32.png?v=2">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon-16.png?v=2">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/apple-touch-icon.png?v=2">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:ital,opsz,wght@0,6..96,400..900;1,6..96,400..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css?v=12">
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
                <h1>Importar y exportar</h1>
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

            <!-- EXPORTAR Y BACKUP -->
            <div class="import-card">
                <h2>Exportar y backup</h2>
                <p>
                    Las planillas bajan con las mismas columnas que se usan para importar, así se pueden abrir en Excel o Google Sheets y volver a cargar. No incluyen las imágenes ni lo que está en la papelera.
                </p>
                <div class="exportar-botones">
                    <?php foreach (['productos' => 'Productos', 'proyectos' => 'Proyectos', 'categorias' => 'Categorías'] as $que => $nombre): ?>
                        <form method="POST" action="actions/exportar.php">
                            <?= csrf_field() ?>
                            <input type="hidden" name="que" value="<?= $que ?>">
                            <button type="submit" class="btn-admin btn-secondary">Planilla de <?= $nombre ?> (.csv)</button>
                        </form>
                    <?php endforeach; ?>
                </div>

                <div class="import-divider">backup</div>

                <p class="exportar-ayuda">
                    El backup es una copia completa de la base (productos, proyectos, categorías, datos de contacto y usuarios del panel) en un archivo <code>.sql</code>. Conviene bajarlo antes de un cambio grande. Para volver a ese estado se importa desde phpMyAdmin, y reemplaza todo lo que haya en ese momento. Las fotos no van en el backup: están en la carpeta <code>admin/uploads</code> del servidor.
                </p>
                <form method="POST" action="actions/exportar.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="que" value="backup">
                    <button type="submit" class="btn-admin btn-primary">Descargar backup de la base</button>
                </form>
            </div>

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

    <script src="admin.js?v=22"></script>
</body>
</html>
