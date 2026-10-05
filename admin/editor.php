<?php

require_once __DIR__ . '/auth.php';

/*
 * Editor de fotos suelto: se elige una foto de la compu o el celular, se edita
 * (admin/editor-imagen.js) y se descarga. Todo pasa en el navegador: no se sube nada.
 * Las fotos de los productos y proyectos también se pueden editar directo desde su
 * ficha, con el lápiz de cada miniatura.
 */
$pagina_activa = 'editor.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editor de fotos — Martineau Admin</title>

    <link rel="icon" type="image/x-icon" href="/favicon.ico?v=2">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon-32.png?v=2">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon-16.png?v=2">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/apple-touch-icon.png?v=2">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:ital,opsz,wght@0,6..96,400..900;1,6..96,400..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css?v=13">
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
                <h1>Editor de fotos</h1>
            </div>

            <p class="ajustes-intro">
                Para dejar lista una foto antes de subirla: recortar, girar, enderezar y corregir brillo, contraste, color y calidez. Al terminar se descarga editada a la compu o al celular, y de ahí se sube a donde haga falta. Las fotos no se suben a ningún lado mientras las editás. Las de un producto o proyecto también se pueden editar directo desde su ficha, con el lápiz que aparece sobre cada foto.
            </p>

            <div class="import-card">
                <h2>Elegir fotos</h2>
                <p>JPG, PNG o WebP. Si elegís varias, se abren de a una. La foto editada se descarga en JPG con el mismo nombre.</p>
                <div class="file-upload-area" data-editor-suelto>
                    <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <p>Arrastrá las fotos o <strong>hacé clic para seleccionar</strong></p>
                    <input type="file" accept="image/jpeg,image/png,image/webp" multiple>
                </div>
            </div>
        </main>
    </div>

    <script src="admin.js?v=23"></script>
    <script src="editor-imagen.js?v=1"></script>
    <script>
        (() => {
            const area = document.querySelector('[data-editor-suelto]');
            const input = area.querySelector('input[type="file"]');

            const descargar = archivo => {
                const url = URL.createObjectURL(archivo);
                const enlace = document.createElement('a');
                enlace.href = url;
                enlace.download = archivo.name;
                document.body.appendChild(enlace);
                enlace.click();
                enlace.remove();
                setTimeout(() => URL.revokeObjectURL(url), 10000);
            };

            // De a una: la siguiente se abre al descargar o cancelar la anterior
            const editar = async archivos => {
                for (const archivo of archivos) {
                    if (!archivo.type.startsWith('image/')) continue;
                    const editada = await EditorImagen.abrir(archivo, { boton: 'Descargar', siempre: true });
                    if (editada) descargar(editada);
                }
                input.value = '';
            };

            // También al arrastrarlas: admin.js las pasa al input y dispara este change
            input.addEventListener('change', () => editar([...input.files]));
        })();
    </script>
</body>
</html>
