<?php
/*
 * Fotos del slideshow de la portada (se cargan desde admin/portada.php).
 * Si la tabla todavía no existe, la base no responde o están todas ocultas, se usan
 * las 4 originales, así la portada nunca queda vacía.
 */

require_once __DIR__ . '/ficha.php';   // ficha_pdo(): la conexión compartida

const PORTADA_DEFECTO = [
    ['imagen' => 'assets/ImgsPrincipales/portada/hero-portada-1.webp', 'imagen_movil' => 'assets/ImgsPrincipales/portada/hero-portada-1-movil.webp'],
    ['imagen' => 'assets/ImgsPrincipales/portada/hero-portada-2.webp', 'imagen_movil' => 'assets/ImgsPrincipales/portada/hero-portada-2-movil.webp'],
    ['imagen' => 'assets/ImgsPrincipales/portada/hero-portada-4.webp', 'imagen_movil' => 'assets/ImgsPrincipales/portada/hero-portada-4-movil.webp'],
    ['imagen' => 'assets/ImgsPrincipales/portada/hero-portada-5.webp', 'imagen_movil' => 'assets/ImgsPrincipales/portada/hero-portada-5-movil.webp'],
];

function portada_fotos(): array
{
    $pdo = ficha_pdo();
    if ($pdo) {
        try {
            $fotos = $pdo->query('SELECT imagen, imagen_movil FROM portada WHERE oculto = 0 ORDER BY orden ASC, id ASC')->fetchAll();
            if ($fotos) return $fotos;
        } catch (Throwable $e) {
            // La tabla se crea al entrar al panel después de actualizar el código
        }
    }
    return PORTADA_DEFECTO;
}
