<?php
require_once __DIR__ . '/config.php';

// Ruta absoluta: este archivo se incluye tanto desde admin/*.php como desde admin/actions/*.php,
// y una ruta relativa resolvería mal en ese segundo caso.
if (!isset($_SESSION['user_id'])) {
    header('Location: /admin/login.php');
    exit();
}

const SESION_INACTIVIDAD_MAX_SEGUNDOS = 1800; // 30 minutos sin actividad

if (isset($_SESSION['ultima_actividad']) && (time() - $_SESSION['ultima_actividad']) > SESION_INACTIVIDAD_MAX_SEGUNDOS) {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
    header('Location: /admin/login.php?timeout=1');
    exit();
}

$_SESSION['ultima_actividad'] = time();

require_once __DIR__ . '/migraciones.php';
require_once __DIR__ . '/papelera_funciones.php';
require_once __DIR__ . '/../inc/slugs.php';

try {
    migrar_base($pdo);
} catch (Exception $e) {
    // Se vuelve a intentar en el próximo pedido; mientras, lo nuevo del panel puede fallar
    error_log('[migraciones.php] ' . $e->getMessage());
}

// Slugs de las fichas nuevas, duplicadas o importadas (ver inc/slugs.php)
try {
    if (slugs_disponibles($pdo)) slugs_completar($pdo);
} catch (Exception $e) {
    error_log('[slugs.php] ' . $e->getMessage());
}
