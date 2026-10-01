<?php
/*
 * Datos de contacto del sitio (teléfono, WhatsApp, mail, Instagram, dirección, horario).
 *
 * Se editan desde el panel (admin/ajustes.php) y se guardan en la tabla `ajustes`
 * (clave → valor). Las fichas PHP los leen acá; las páginas HTML los piden a
 * api/datos.php?tipo=contacto y los aplican con script.js. Si la tabla todavía no
 * existe o la base no responde, se usan los valores por defecto de abajo, que son
 * los mismos que tiene escritos el HTML.
 */

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
            // La tabla se crea al entrar al panel después de actualizar el código
        }
    }
    return $cache = $ajustes;
}

/** La tabla la crea admin/migraciones.php al entrar al panel */
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

/** Los datos ya listos para usar en links (los mismos que arma script.js) */
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
