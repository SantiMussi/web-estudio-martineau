<?php
/*
 * Fichas de producto / proyecto armadas en el servidor.
 *
 * Antes la página se completaba solo con JavaScript, así que Google y las vistas
 * previas al compartir (WhatsApp, Facebook…) veían "Producto — Martineau" en todas.
 * Ahora el título, la descripción, la imagen, los datos estructurados y el
 * contenido salen del servidor; el JS queda de respaldo si la base no responde.
 */

const SEO_SITIO = 'https://armartineau.com.ar';
const SEO_EMPRESA_ID = SEO_SITIO . '/#empresa';

/**
 * Trae el ítem publicado (no oculto) de la base.
 * Devuelve el array, null si no existe (→ 404) o false si la base no respondió
 * (→ la página se arma con JS como antes, sin marcarla como inexistente).
 */
function ficha_cargar(string $tipo, int $id)
{
    if ($id <= 0) return null;

    $tabla = $tipo === 'proyecto' ? 'proyectos' : 'productos';
    $extra = $tipo === 'proyecto' ? 'p.ubicacion, p.anio,' : '';

    try {
        if (!defined('IS_API_ENDPOINT')) define('IS_API_ENDPOINT', true);
        require __DIR__ . '/../admin/config.php';

        $stmt = $pdo->prepare("
            SELECT p.id, p.titulo, c.slug AS categoria, c.nombre AS categoria_nombre, $extra
                   p.descripcion, p.imagen, p.imagenes, p.specs
            FROM $tabla p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            WHERE p.id = :id AND IFNULL(p.oculto, 0) = 0
        ");
        $stmt->execute(['id' => $id]);
        $item = $stmt->fetch();
    } catch (Throwable $e) {
        error_log('[ficha.php] ' . $e->getMessage());
        return false;
    }

    if (!$item) return null;

    foreach (['imagenes', 'specs'] as $campo) {
        $decoded = is_string($item[$campo] ?? null) ? json_decode($item[$campo], true) : null;
        $item[$campo] = is_array($decoded) ? $decoded : [];
    }
    return $item;
}

function seo_e($texto): string
{
    return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
}

/** URL absoluta para una imagen guardada como ruta relativa del sitio */
function seo_url_absoluta(string $ruta): string
{
    if (preg_match('#^https?://#i', $ruta)) return $ruta;
    return SEO_SITIO . '/' . ltrim($ruta, '/');
}

/** Descripción para buscadores: una sola línea, cortada en una palabra (~155 caracteres) */
function seo_recortar(string $texto, int $max = 155): string
{
    $texto = trim(preg_replace('/\s+/u', ' ', $texto));
    if (mb_strlen($texto) <= $max) return $texto;
    $corte = mb_substr($texto, 0, $max);
    $espacio = mb_strrpos($corte, ' ');
    return rtrim(mb_substr($corte, 0, $espacio ?: $max), ' ,.;:') . '…';
}

function seo_json_ld(array $datos): string
{
    return json_encode(
        ['@context' => 'https://schema.org'] + $datos,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_PRETTY_PRINT
    );
}

/** Todas las imágenes de la ficha: la principal primero y después la galería */
function ficha_imagenes(array $item): array
{
    return array_values(array_filter(array_merge([$item['imagen']], $item['imagenes'])));
}

/**
 * Metadatos de la página según el resultado de ficha_cargar().
 * $tipo: 'producto' | 'proyecto'
 */
function ficha_seo(string $tipo, $item, int $id): array
{
    $esProyecto = $tipo === 'proyecto';
    $listado = $esProyecto
        ? ['nombre' => 'Obras realizadas', 'ruta' => '/portfolio']
        : ['nombre' => 'Catálogo', 'ruta' => '/catalogo'];
    $url = SEO_SITIO . '/' . $tipo . ($id > 0 ? '?id=' . $id : '');

    if ($item === null) {
        return [
            'titulo' => ($esProyecto ? 'Proyecto' : 'Producto') . ' no encontrado | Martineau',
            'desc' => 'La pieza que buscás ya no está publicada. Mirá el resto de ' .
                ($esProyecto ? 'las obras' : 'el catálogo') . ' de Martineau.',
            'url' => $url,
            'robots' => 'noindex, follow',
            'imagen' => SEO_SITIO . '/assets/og-martineau.jpg',
            'ld' => null,
        ];
    }

    if ($item === false) {
        return [
            'titulo' => ($esProyecto ? 'Proyecto' : 'Producto') . ' | Martineau',
            'desc' => 'Piezas de piedra París y yeso hechas a mano en Buenos Aires desde 1922.',
            'url' => $url,
            'robots' => 'index, follow, max-image-preview:large',
            'imagen' => SEO_SITIO . '/assets/og-martineau.jpg',
            'ld' => null,
        ];
    }

    $categoria = $item['categoria_nombre'] ?: ucfirst((string)$item['categoria']);
    $desc = trim((string)$item['descripcion']);
    if ($desc === '') {
        $desc = $esProyecto
            ? $item['titulo'] . ': obra realizada por Martineau' .
              (!empty($item['ubicacion']) ? ' en ' . $item['ubicacion'] : '') . '.'
            : $item['titulo'] . ' en piedra París, hecho a mano por Martineau en Buenos Aires.';
    }
    $imagenes = array_map('seo_url_absoluta', ficha_imagenes($item));

    $migas = [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => SEO_SITIO . '/'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => $listado['nombre'], 'item' => SEO_SITIO . $listado['ruta']],
    ];
    if (!empty($item['categoria'])) {
        $migas[] = ['@type' => 'ListItem', 'position' => 3, 'name' => $categoria,
            'item' => SEO_SITIO . $listado['ruta'] . '?categoria=' . rawurlencode($item['categoria'])];
    }
    $migas[] = ['@type' => 'ListItem', 'position' => count($migas) + 1, 'name' => $item['titulo']];

    // Pieza hecha a mano (no se venden con precio publicado, así que no va como Product)
    $obra = [
        '@type' => 'CreativeWork',
        'name' => $item['titulo'],
        'description' => seo_recortar($desc, 500),
        'image' => $imagenes,
        'url' => $url,
        'creator' => ['@id' => SEO_EMPRESA_ID],
    ];
    if (!empty($item['categoria_nombre'])) $obra['genre'] = $item['categoria_nombre'];
    if ($esProyecto) {
        if (!empty($item['anio'])) $obra['dateCreated'] = (string)$item['anio'];
        if (!empty($item['ubicacion'])) $obra['locationCreated'] = ['@type' => 'Place', 'name' => $item['ubicacion']];
    }

    return [
        'titulo' => $item['titulo'] . ($esProyecto ? ' — Obra' : ' — ' . $categoria) . ' | Martineau',
        'desc' => seo_recortar($desc),
        'url' => $url,
        'robots' => 'index, follow, max-image-preview:large',
        'imagen' => $imagenes[0] ?? SEO_SITIO . '/assets/og-martineau.jpg',
        'ld' => seo_json_ld(['@graph' => [
            [
                '@type' => 'ItemPage',
                '@id' => $url . '#pagina',
                'url' => $url,
                'name' => $item['titulo'],
                'inLanguage' => 'es-AR',
                'isPartOf' => ['@id' => SEO_SITIO . '/#sitio'],
                'publisher' => ['@id' => SEO_EMPRESA_ID],
                'mainEntity' => $obra,
            ] + ($imagenes ? ['primaryImageOfPage' => $imagenes[0]] : []),
            ['@type' => 'BreadcrumbList', 'itemListElement' => $migas],
        ]]),
    ];
}

/** El bloque de <head> común a las dos fichas */
function ficha_head(array $seo): void
{
    ?>
  <title><?= seo_e($seo['titulo']) ?></title>
  <meta name="description" content="<?= seo_e($seo['desc']) ?>">
  <link rel="canonical" href="<?= seo_e($seo['url']) ?>">
  <meta name="robots" content="<?= seo_e($seo['robots']) ?>">
  <meta name="theme-color" content="#2E2820">

  <!-- Vista previa al compartir (WhatsApp, Facebook, LinkedIn, X) -->
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="Martineau">
  <meta property="og:locale" content="es_AR">
  <meta property="og:url" content="<?= seo_e($seo['url']) ?>">
  <meta property="og:title" content="<?= seo_e($seo['titulo']) ?>">
  <meta property="og:description" content="<?= seo_e($seo['desc']) ?>">
  <meta property="og:image" content="<?= seo_e($seo['imagen']) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= seo_e($seo['titulo']) ?>">
  <meta name="twitter:description" content="<?= seo_e($seo['desc']) ?>">
  <meta name="twitter:image" content="<?= seo_e($seo['imagen']) ?>">
<?php if ($seo['ld']): ?>

  <!-- Datos estructurados (schema.org) -->
  <script type="application/ld+json">
<?= $seo['ld'] ?>

  </script>
<?php endif;
}

/** Galería + info: el mismo HTML que arma el JS de respaldo */
function ficha_cuerpo(string $tipo, array $item): void
{
    $esProyecto = $tipo === 'proyecto';
    $imagenes = ficha_imagenes($item);
    $categoria = $item['categoria_nombre'] ?: ucfirst((string)$item['categoria']);
    $idImagen = $esProyecto ? 'main-proyecto-image' : 'main-product-image';

    $filtro = isset($_GET['categoria']) ? (string)$_GET['categoria'] : '';
    $volver = ($esProyecto ? 'portfolio' : 'catalogo') . ($filtro !== '' ? '?categoria=' . rawurlencode($filtro) : '');

    $mensaje = $esProyecto
        ? 'Hola! Quería consultar por el proyecto "' . $item['titulo'] . '".'
        : 'Hola! Quería consultar por "' . $item['titulo'] . '".';
    $whatsapp = 'https://wa.me/5491131917014?text=' . rawurlencode($mensaje);
    ?>
    <div class="back-link-wrapper reveal">
      <a href="<?= seo_e($volver) ?>" class="back-link">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
        <?= $esProyecto ? 'Volver al Portfolio' : 'Volver al Catalogo' ?>
      </a>
    </div>
    <div class="product-detail-grid">
      <div class="product-gallery reveal">
        <div class="gallery-main">
          <img src="<?= seo_e($imagenes[0] ?? '') ?>" alt="<?= seo_e($item['titulo']) ?>" id="<?= $idImagen ?>" fetchpriority="high">
        </div>
        <div class="gallery-thumbnails">
<?php foreach ($imagenes as $i => $src): ?>
          <div class="gallery-thumb<?= $i === 0 ? ' active' : '' ?>" data-src="<?= seo_e($src) ?>">
            <img src="<?= seo_e($src) ?>" alt="<?= seo_e($item['titulo'] . ' — vista ' . ($i + 1)) ?>" loading="lazy" decoding="async">
          </div>
<?php endforeach; ?>
        </div>
      </div>
      <div class="product-info reveal">
        <span class="product-info-category"><?= seo_e($categoria) ?><?= $esProyecto && !empty($item['anio']) ? ' — ' . seo_e($item['anio']) : '' ?></span>
        <h1 class="product-info-title"><?= seo_e($item['titulo']) ?></h1>
        <p class="product-info-desc"><?= seo_e($item['descripcion']) ?></p>
<?php if ($item['specs']): ?>
        <div class="product-specs">
<?php foreach ($item['specs'] as $spec): ?>
          <div class="spec-item"><span class="spec-label"><?= seo_e($spec['label'] ?? '') ?></span><span class="spec-value"><?= seo_e($spec['value'] ?? '') ?></span></div>
<?php endforeach; ?>
        </div>
<?php endif; ?>
        <div class="product-actions"><a href="<?= seo_e($whatsapp) ?>" class="btn" target="_blank" rel="noopener"><?= $esProyecto ? 'Consultar por este proyecto' : 'Solicitar Cotización' ?></a></div>
      </div>
    </div>
<?php
}

/** Mensaje cuando el id no existe (la respuesta ya salió con 404) */
function ficha_no_encontrada(string $tipo): void
{
    $esProyecto = $tipo === 'proyecto';
    ?>
    <div style="text-align:center;padding:8rem 2rem">
      <p style="color:var(--color-text-muted)"><?= $esProyecto ? 'Proyecto no encontrado.' : 'Producto no encontrado.' ?></p>
      <a href="<?= $esProyecto ? 'portfolio' : 'catalogo' ?>" class="btn" style="margin-top:2rem;display:inline-block"><?= $esProyecto ? 'Ver portfolio' : 'Ver catalogo' ?></a>
    </div>
<?php
}
