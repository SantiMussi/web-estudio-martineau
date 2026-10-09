<?php
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
