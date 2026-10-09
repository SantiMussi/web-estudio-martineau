<?php

require_once __DIR__ . '/ficha.php';

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
        }
    }
    return PORTADA_DEFECTO;
}
