<?php

require_once __DIR__ . '/auth.php';

try {
    $disenos = $pdo->query('SELECT id, nombre, miniatura, updated_at FROM disenos ORDER BY updated_at DESC, id DESC')->fetchAll();
} catch (Exception $e) {
    $disenos = [];
}

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
    <link rel="stylesheet" href="admin.css?v=17">
</head>
<body>
    <button class="sidebar-toggle" aria-label="Menú">
        <svg viewBox="0 0 24 24"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>

    <div class="admin-layout">
        <?php require __DIR__ . '/sidebar.php'; ?>

        <main class="admin-main">
            <div class="admin-topbar">
                <h1>Editor de fotos</h1>
            </div>

            <?= csrf_field() ?>

            <p class="ajustes-intro">
                Para dejar lista una foto sin pasar por Photoshop: recortar, girar, corregir la luz y el color, y agregarle texto, formas o el logo en la etapa Capas. Al terminar se descarga a la compu o al celular. Con "Guardar diseño" queda guardado acá abajo para seguir otro día, o para usar sus capas en otras fotos. Las fotos de un producto o proyecto también se pueden editar directo desde su ficha, con el lápiz que aparece sobre cada foto.
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

            <div class="import-card">
                <h2>Diseños guardados</h2>
                <?php if (empty($disenos)): ?>
                    <p>Todavía no hay ninguno. En el editor, "Guardar diseño" guarda la foto con todos sus ajustes y capas para retomarla después.</p>
                <?php else: ?>
                    <p>Abrí uno para seguir editándolo. Desde el editor de un producto también se pueden abrir con el botón "Diseños".</p>
                    <div class="disenos-grilla">
                        <?php foreach ($disenos as $d): ?>
                            <div class="diseno-tarjeta" data-diseno="<?= (int)$d['id'] ?>">
                                <?php if ($d['miniatura']): ?>
                                    <img src="../<?= e($d['miniatura']) ?>" alt="" loading="lazy" decoding="async">
                                <?php endif; ?>
                                <div class="diseno-tarjeta-cuerpo">
                                    <strong title="<?= e($d['nombre']) ?>"><?= e($d['nombre']) ?></strong>
                                    <small>Guardado el <?= e(date('d/m/Y H:i', strtotime($d['updated_at']))) ?></small>
                                    <div class="diseno-tarjeta-botones">
                                        <button type="button" class="btn-admin btn-primary btn-sm" data-abrir-diseno>Abrir</button>
                                        <button type="button" class="btn-admin btn-danger btn-sm" data-eliminar-diseno>Eliminar</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="admin.js?v=25"></script>
    <script src="editor-imagen.js?v=4"></script>
    <script>
        (() => {
            const area = document.querySelector('[data-editor-suelto]');
            const input = area.querySelector('input[type="file"]');
            const OPCIONES = { boton: 'Descargar', siempre: true };
            let guardoAlgo = false;

            window.addEventListener('editor-imagen:diseno-guardado', () => { guardoAlgo = true; });

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

            const alTerminar = () => {
                if (guardoAlgo) setTimeout(() => window.location.reload(), 600);
            };

            const editar = async archivos => {
                for (const archivo of archivos) {
                    if (!archivo.type.startsWith('image/')) continue;
                    const editada = await EditorImagen.abrir(archivo, OPCIONES);
                    if (editada) descargar(editada);
                }
                input.value = '';
                alTerminar();
            };

            input.addEventListener('change', () => editar([...input.files]));

            document.querySelectorAll('[data-diseno]').forEach(tarjeta => {
                const id = tarjeta.dataset.diseno;
                tarjeta.querySelector('[data-abrir-diseno]').addEventListener('click', async () => {
                    try {
                        const editada = await EditorImagen.abrirDiseno(id, OPCIONES);
                        if (editada) descargar(editada);
                        alTerminar();
                    } catch (err) {
                        alert(err.message);
                    }
                });
                tarjeta.querySelector('[data-eliminar-diseno]').addEventListener('click', async () => {
                    if (!confirm('¿Eliminar este diseño? Se borra para siempre, con su foto original.')) return;
                    const datos = new FormData();
                    datos.append('accion', 'eliminar');
                    datos.append('id', id);
                    datos.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
                    const res = await fetch('actions/disenos.php', { method: 'POST', body: datos });
                    const json = await res.json().catch(() => null);
                    if (json && json.ok) tarjeta.remove();
                    else alert((json && json.error) || 'No se pudo eliminar.');
                });
            });
        })();
    </script>
</body>
</html>
