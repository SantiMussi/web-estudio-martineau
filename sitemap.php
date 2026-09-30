<?php
/*
 * Sitemap para buscadores, generado desde la base: se sirve como /sitemap.xml
 * (ver .htaccess). Incluye las páginas fijas y cada producto y proyecto publicado,
 * con sus imágenes (así también aparecen en Google Imágenes).
 */
require __DIR__ . '/inc/ficha.php';

$paginas = [
    ['loc' => SEO_SITIO . '/', 'prioridad' => '1.0'],
    ['loc' => SEO_SITIO . '/catalogo', 'prioridad' => '0.9'],
    ['loc' => SEO_SITIO . '/portfolio', 'prioridad' => '0.8'],
    ['loc' => SEO_SITIO . '/nosotros', 'prioridad' => '0.7'],
];

try {
    if (!defined('IS_API_ENDPOINT')) define('IS_API_ENDPOINT', true);
    require __DIR__ . '/admin/config.php';

    foreach (['producto' => 'productos', 'proyecto' => 'proyectos'] as $tipo => $tabla) {
        $filas = $pdo->query("
            SELECT id, imagen, imagenes FROM $tabla
            WHERE IFNULL(oculto, 0) = 0
            ORDER BY orden ASC, created_at DESC
        ")->fetchAll();

        foreach ($filas as $fila) {
            $galeria = json_decode((string)$fila['imagenes'], true);
            $imagenes = array_filter(array_merge([$fila['imagen']], is_array($galeria) ? $galeria : []));
            $paginas[] = [
                'loc' => SEO_SITIO . '/' . $tipo . '?id=' . (int)$fila['id'],
                'prioridad' => $tipo === 'producto' ? '0.7' : '0.6',
                'imagenes' => array_map('seo_url_absoluta', $imagenes),
            ];
        }
    }
} catch (Throwable $e) {
    // Sin base igual se publican las páginas fijas
    error_log('[sitemap.php] ' . $e->getMessage());
}

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
<?php foreach ($paginas as $p): ?>
  <url>
    <loc><?= seo_e($p['loc']) ?></loc>
    <priority><?= $p['prioridad'] ?></priority>
<?php foreach ($p['imagenes'] ?? [] as $img): ?>
    <image:image>
      <image:loc><?= seo_e($img) ?></image:loc>
    </image:image>
<?php endforeach; ?>
  </url>
<?php endforeach; ?>
</urlset>
