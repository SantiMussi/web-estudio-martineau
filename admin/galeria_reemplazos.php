<?php
/*
 * Fotos de la galería de un producto o proyecto que se editaron en el panel
 * (admin/editor-imagen.js). El formulario las manda en galeria_reemplazo[] y, en el
 * mismo orden, la ruta de la foto que reemplaza cada una en galeria_reemplazo_de[].
 *
 * Devuelve [ruta anterior => archivo de $_FILES] solo con las que llegaron bien.
 */
function fgal_reemplazos(): array {
    $de = $_POST['galeria_reemplazo_de'] ?? [];
    $archivos = $_FILES['galeria_reemplazo'] ?? null;
    if (!is_array($de) || !$archivos || !is_array($archivos['name'] ?? null)) {
        return [];
    }

    $reemplazos = [];
    foreach (array_values($de) as $i => $ruta) {
        if (!is_string($ruta) || ($archivos['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            continue;
        }
        $reemplazos[$ruta] = [
            'name'     => $archivos['name'][$i],
            'type'     => $archivos['type'][$i],
            'tmp_name' => $archivos['tmp_name'][$i],
            'error'    => $archivos['error'][$i],
            'size'     => $archivos['size'][$i],
        ];
    }
    return $reemplazos;
}
