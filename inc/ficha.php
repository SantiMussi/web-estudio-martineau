<?php
/*
 * Fichas de producto / proyecto armadas en el servidor.
 *
 * Antes la página se completaba solo con JavaScript, así que Google y las vistas
 * previas al compartir (WhatsApp, Facebook…) veían "Producto — Martineau" en todas.
 * Ahora el título, la descripción, la imagen, los datos estructurados y el
 * contenido salen del servidor; el JS queda de respaldo si la base no responde.
 */

require_once __DIR__ . '/ajustes.php';

const SEO_SITIO = 'https://armartineau.com.ar';
const SEO_EMPRESA_ID = SEO_SITIO . '/#empresa';

/** Conexión a la base, una sola vez por pedido (config.php no se puede incluir dos veces) */
function ficha_pdo(): ?PDO
{
    static $pdo = false;
    if ($pdo !== false) return $pdo;

    $pdo = null;
    try {
        if (!defined('IS_API_ENDPOINT')) define('IS_API_ENDPOINT', true);
        require __DIR__ . '/../admin/config.php';
    } catch (Throwable $e) {
        error_log('[ficha.php] ' . $e->getMessage());
    }
    return $pdo;
}

/** Datos de contacto cargados desde el panel, con los links ya armados */
function ficha_contacto(): array
{
    $datos = ajustes_cargar(ficha_pdo());
    return ['datos' => $datos, 'links' => contacto_links($datos)];
}

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
        $pdo = ficha_pdo();
        if (!$pdo) return false;

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
    $whatsapp = ficha_contacto()['links']['whatsapp'] . '?text=' . rawurlencode($mensaje);
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

/** Sección "Contacto" del pie, con los datos cargados desde el panel (la misma que las páginas HTML) */
function ficha_contacto_seccion(): void
{
    ['datos' => $d, 'links' => $l] = ficha_contacto();
    ?>
  <section class="contact section" id="contacto">
    <div class="container">

      <div class="contact-header reveal">
        <span class="section-label">Contacto</span>
        <h2>Estamos para escucharte</h2>
      </div>

      <div class="contact-blocks reveal">

        <div class="contact-block">
          <span class="contact-block-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
              stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z" />
              <circle cx="12" cy="10" r="3" />
            </svg>
          </span>
          <h3>Ubicación</h3>
          <p>
            <?= seo_e($d['direccion']) ?> <br>
            <?= seo_e($d['zona']) ?><br>
            Argentina
          </p>
        </div>

        <div class="contact-block-divider" aria-hidden="true"></div>

        <div class="contact-block">
          <span class="contact-block-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
              stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
              <path
                d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.63 3.4 2 2 0 0 1 3.6 1.22h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.78a16 16 0 0 0 6 6l.95-.95a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.49 16l.43.92z" />
            </svg>
          </span>
          <h3>Comunicación</h3>
          <p>
            <a href="<?= seo_e($l['tel']) ?>"><?= seo_e($d['telefono']) ?></a><br>
            <a href="<?= seo_e($l['whatsapp']) ?>" target="_blank" rel="noopener">WhatsApp</a><br>
            <a href="<?= seo_e($l['mailto']) ?>"><?= seo_e($d['email']) ?></a>
          </p>
        </div>

        <div class="contact-block-divider" aria-hidden="true"></div>

        <div class="contact-block">
          <span class="contact-block-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
              stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10" />
              <polyline points="12 6 12 12 16 14" />
            </svg>
          </span>
          <h3>Horario</h3>
          <p>
            <?= implode('<br>', array_map('seo_e', contacto_lineas($d['horario']))) ?>
          </p>
        </div>

        <div class="contact-block-divider" aria-hidden="true"></div>

        <div class="contact-block">
          <span class="contact-block-icon" aria-hidden="true"></span>
          <h3>Redes</h3>
          <div class="contact-social">
            <a href="<?= seo_e($d['instagram']) ?>" class="social-link" aria-label="Instagram" target="_blank"
              rel="noopener">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="22" height="22">
                <rect x="2" y="2" width="20" height="20" rx="5" ry="5" />
                <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" />
                <line x1="17.5" y1="6.5" x2="17.51" y2="6.5" />
              </svg>
            </a>
          </div>
        </div>

      </div>
    </div>
  </section>
<?php
}

/** Botón flotante de WhatsApp */
function ficha_whatsapp_flotante(): void
{
    $l = ficha_contacto()['links'];
    ?>
  <a href="<?= seo_e($l['whatsapp']) ?>" class="whatsapp-float" target="_blank" rel="noopener noreferrer"
    aria-label="Contactar por WhatsApp">
    <div class="whatsapp-pulse"></div>
    <div class="whatsapp-icon-wrapper">
      <svg class="whatsapp-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
        <path fill="currentColor"
          d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zM223.9 414.8c-32 0-63.1-8.4-90.6-24.3l-6.5-3.8-67.4 17.7 18-65.7-4.2-6.7c-17.5-27.9-26.7-60.2-26.7-93.1 0-103.5 84.3-187.8 187.9-187.8 50.1 0 97.2 19.5 132.6 55 35.4 35.4 55 82.5 55 132.7 0 103.5-84.3 187.8-187.9 187.8zm102.7-140.2c-5.6-2.8-33.4-16.5-38.6-18.4-5.2-1.9-9-2.8-12.8 2.8-3.8 5.6-14.7 18.4-18 22.1-3.3 3.8-6.6 4.2-12.2 1.4-5.6-2.8-23.8-8.8-45.3-27.9-16.7-14.9-28-33.3-31.3-38.9-3.3-5.6-.4-8.6 2.4-11.4 2.5-2.5 5.6-6.6 8.4-9.9 2.8-3.3 3.8-5.6 5.6-9.4 1.9-3.8.9-7.1-.4-9.9-1.4-2.8-12.8-30.9-17.5-42.3-4.6-11.1-9.3-9.6-12.8-9.8-3.3-.2-7.1-.2-10.9-.2-3.8 0-9.9 1.4-15.2 7.1-5.2 5.6-20 19.5-20 47.4 0 27.9 20.4 55 23.3 58.8 2.8 3.8 40 61.1 96.9 85.6 13.5 5.8 24.1 9.3 32.3 11.9 13.6 4.3 26 3.7 35.8 2.2 11-1.7 33.4-13.6 38.1-26.8 4.7-13.2 4.7-24.5 3.3-26.8-1.4-2.3-5.2-3.8-10.8-6.6z" />
      </svg>
    </div>
    <span class="whatsapp-text">¡Hablemos!</span>
  </a>
<?php
}
