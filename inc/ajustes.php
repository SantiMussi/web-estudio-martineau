<?php

const AJUSTES_DEFECTO = [
    'telefono'  => '+54 9 11 3191-7014',
    'whatsapp'  => '5491131917014',
    'email'     => 'contacto@armartineau.com.ar',
    'instagram' => 'https://www.instagram.com/armartineau/',
    'direccion' => 'Santos Dumont 4457',
    'zona'      => 'Chacarita, CABA',
    'horario'   => "Lunes a Viernes\nCoordinar visita",
];

function ajustes_cargar(?PDO $pdo): array
{
    static $cache = null;
    if ($cache !== null) return $cache;

    $ajustes = AJUSTES_DEFECTO;
    if ($pdo) {
        try {
            foreach ($pdo->query('SELECT clave, valor FROM ajustes') as $fila) {
                if (array_key_exists($fila['clave'], $ajustes) && trim((string)$fila['valor']) !== '') {
                    $ajustes[$fila['clave']] = (string)$fila['valor'];
                }
            }
        } catch (Throwable $e) {
        }
    }
    return $cache = $ajustes;
}

function ajustes_guardar(PDO $pdo, array $valores): void
{
    $stmt = $pdo->prepare('
        INSERT INTO ajustes (clave, valor) VALUES (:clave, :valor)
        ON DUPLICATE KEY UPDATE valor = VALUES(valor)
    ');
    foreach ($valores as $clave => $valor) {
        if (array_key_exists($clave, AJUSTES_DEFECTO)) {
            $stmt->execute(['clave' => $clave, 'valor' => $valor]);
        }
    }
}

function contacto_links(array $a): array
{
    $tel = preg_replace('/[^\d+]/', '', $a['telefono']);
    $whatsapp = preg_replace('/\D/', '', $a['whatsapp']) ?: ltrim($tel, '+');
    $destino = rawurlencode($a['direccion'] . ', ' . $a['zona'] . ', Argentina');

    return [
        'tel'          => 'tel:' . $tel,
        'whatsapp'     => 'https://wa.me/' . $whatsapp,
        'mailto'       => 'mailto:' . $a['email'],
        'mapa_ir'      => 'https://www.google.com/maps/dir/?api=1&destination=' . $destino,
        'mapa_embed'   => 'https://www.google.com/maps?q=' . $destino . '&output=embed',
    ];
}

function contacto_lineas(string $texto): array
{
    return array_values(array_filter(array_map('trim', explode("\n", str_replace("\r", '', $texto))), 'strlen'));
}
