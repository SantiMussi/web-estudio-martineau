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
