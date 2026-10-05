<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../inc/ajustes.php';

$ajustes = ajustes_cargar($pdo);

// Si el guardado falló por un dato inválido, se muestra lo que se había escrito
if (isset($_SESSION['ajustes_borrador'])) {
    $ajustes = array_merge($ajustes, $_SESSION['ajustes_borrador']);
    unset($_SESSION['ajustes_borrador']);
}

$msg = $_SESSION['flash_msg'] ?? null;
unset($_SESSION['flash_msg']);

$pagina_activa = 'ajustes.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacto — Martineau Admin</title>

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
                <h1>Datos de contacto</h1>
            </div>

            <!-- Flash Messages -->
            <?php if ($msg): ?>
                <div class="alert <?= flash_alert_class($msg['type']) ?>">
                    <?= e($msg['text']) ?>
                </div>
            <?php endif; ?>

            <p class="ajustes-intro">
                Lo que cargues acá aparece en todo el sitio: la sección de contacto al pie de cada página, el botón flotante de WhatsApp, los botones de cotización de cada producto y proyecto, y el mapa de la página Nosotros.
            </p>

            <form method="POST" action="actions/guardar_ajustes.php" class="ajustes-form">
                <?= csrf_field() ?>

                <div class="import-card">
                    <h2>Comunicación</h2>
                    <p>Cómo te contactan los clientes.</p>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="telefono">Teléfono</label>
                            <input type="text" id="telefono" name="telefono" class="form-control" value="<?= e($ajustes['telefono']) ?>" placeholder="+54 9 11 3191-7014" required>
                            <span class="form-ayuda">Se muestra tal cual lo escribas.</span>
                        </div>
                        <div class="form-group">
                            <label for="whatsapp">WhatsApp</label>
                            <input type="text" id="whatsapp" name="whatsapp" class="form-control" value="<?= e($ajustes['whatsapp']) ?>" placeholder="5491131917014" inputmode="numeric" required>
                            <span class="form-ayuda">Con código de país y sin el 0 ni el 15: 549 + característica + número.</span>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Mail</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?= e($ajustes['email']) ?>" placeholder="contacto@armartineau.com.ar" required>
                        </div>
                        <div class="form-group">
                            <label for="instagram">Instagram</label>
                            <input type="url" id="instagram" name="instagram" class="form-control" value="<?= e($ajustes['instagram']) ?>" placeholder="https://www.instagram.com/armartineau/" required>
                            <span class="form-ayuda">El link completo al perfil.</span>
                        </div>
                    </div>
                </div>

                <div class="import-card">
                    <h2>Ubicación y horario</h2>
                    <p>La dirección también se usa para el mapa y el botón "Abrir en Google Maps".</p>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="direccion">Dirección</label>
                            <input type="text" id="direccion" name="direccion" class="form-control" value="<?= e($ajustes['direccion']) ?>" placeholder="Santos Dumont 4457" required>
                        </div>
                        <div class="form-group">
                            <label for="zona">Barrio y ciudad</label>
                            <input type="text" id="zona" name="zona" class="form-control" value="<?= e($ajustes['zona']) ?>" placeholder="Chacarita, CABA" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="horario">Horario</label>
                        <textarea id="horario" name="horario" class="form-control" rows="3" placeholder="Lunes a Viernes&#10;Coordinar visita" required><?= e($ajustes['horario']) ?></textarea>
                        <span class="form-ayuda">Cada renglón se muestra en una línea aparte.</span>
                    </div>
                </div>

                <button type="submit" class="btn-admin btn-primary">Guardar cambios</button>
            </form>
        </main>
    </div>

    <script src="admin.js?v=21"></script>
</body>
</html>
