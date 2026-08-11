<?php
/**
 * auth.php — Middleware de Autenticación
 * 
 * Incluir este archivo al inicio de cada página del panel admin
 * que requiera autenticación. Redirige a login.php si no hay sesión activa.
 * 
 * @security Verificación de sesión con redirección inmediata
 */

require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
