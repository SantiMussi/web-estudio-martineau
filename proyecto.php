<?php
require __DIR__ . '/inc/ficha.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$item = ficha_cargar('proyecto', $id);   // array | null (no existe) | false (la base no respondió)
if ($item === null) http_response_code(404);
$seo = ficha_seo('proyecto', $item, $id);
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es-AR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php ficha_head($seo); ?>

  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="/favicon.ico?v=2">
  <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon-32.png?v=2">
  <link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon-16.png?v=2">
  <link rel="apple-touch-icon" sizes="180x180" href="/assets/apple-touch-icon.png?v=2">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <!-- Fuente sin bloquear el primer pintado: se pide ya y se aplica apenas llega -->
  <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" onload="this.onload=null;this.rel='stylesheet'">
  <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"></noscript>
  <link rel="stylesheet" href="styles.css?v=69">
</head>

<body class="page-detail">

  <!-- Header -->
  <header class="site-header" id="header">
    <div class="container header-inner">
      <a href="/" class="logo" aria-label="Martineau - Ir al inicio"><img src="assets/logo-wordmark.webp"
          alt="Martineau" width="680" height="127"></a>
      <nav class="main-nav" aria-label="Navegacion principal">
        <ul class="nav-list">
          <li><a href="nosotros" class="nav-link">Nosotros</a></li>
          <li class="nav-item has-dropdown">
            <a href="catalogo" class="nav-link">Catálogo</a>
            <ul class="nav-dropdown" data-nav-dropdown="producto"></ul>
          </li>
          <li class="nav-item has-dropdown">
            <a href="portfolio" class="nav-link active">Portfolio</a>
            <ul class="nav-dropdown" data-nav-dropdown="proyecto"></ul>
          </li>
          <li><a href="#contacto" class="nav-link">Contacto</a></li>
        </ul>
      </nav>
      <button class="nav-toggle" aria-label="Abrir menu de navegacion" aria-expanded="false">
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
      </button>
    </div>
    <nav class="mobile-nav" aria-label="Navegacion movil" aria-hidden="true">
      <ul class="mobile-nav-list">
        <li><a href="nosotros" class="mobile-nav-link">Nosotros</a></li>
        <li class="mobile-nav-item has-dropdown">
          <div class="mobile-nav-row">
            <a href="catalogo" class="mobile-nav-link">Catálogo</a>
            <button type="button" class="mobile-nav-chevron" aria-label="Ver categorías" aria-expanded="false">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
          </div>
          <ul class="mobile-nav-dropdown" data-nav-dropdown="producto"></ul>
        </li>
        <li class="mobile-nav-item has-dropdown">
          <div class="mobile-nav-row">
            <a href="portfolio" class="mobile-nav-link">Portfolio</a>
            <button type="button" class="mobile-nav-chevron" aria-label="Ver categorías de proyectos" aria-expanded="false">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
          </div>
          <ul class="mobile-nav-dropdown" data-nav-dropdown="proyecto"></ul>
        </li>
        <li><a href="#contacto" class="mobile-nav-link">Contacto</a></li>
      </ul>
    </nav>
  </header>

  <!-- Detalle: lo arma el servidor (inc/ficha.php); el JS de abajo es el respaldo -->
  <main class="product-detail-page container" id="proyecto-detail-root"<?= $item !== false ? ' data-ssr="1"' : '' ?>>
<?php if ($item) ficha_cuerpo('proyecto', $item); elseif ($item === null) ficha_no_encontrada('proyecto'); ?>
  </main>

  <!-- Sección Contacto -->
<?php ficha_contacto_seccion(); ?>

  <!-- Footer -->
  <footer class="site-footer">
    <div class="container footer-inner">
      <p class="footer-copy">&copy; Todos los derechos reservados, Martineau</p>
      <p class="footer-credit">Desarrollo por Santiago Mussi.</p>
    </div>
  </footer>

  <!-- WhatsApp -->
<?php ficha_whatsapp_flotante(); ?>

  <!-- Store compartido (capa de datos / simulacion) -->
  <script src="store.js?v=8"></script>

  <script>

    document.addEventListener('DOMContentLoaded', async () => {
      // La ficha llega armada desde el servidor (SEO); si la base no respondió, se arma acá
      const ssr = document.getElementById('proyecto-detail-root').dataset.ssr;
      if (ssr) activarGaleria();
      await Store.init();
      if (!ssr) renderProyecto();
      renderNavCategorias();
    });

    function activarGaleria() {
      document.querySelectorAll('.gallery-thumb').forEach(thumb => {
        thumb.addEventListener('click', function () {
          document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
          this.classList.add('active');
          document.getElementById('main-proyecto-image').src = this.dataset.src;
        });
      });
    }

    function renderProyecto() {
      const root = document.getElementById('proyecto-detail-root');
      const params = new URLSearchParams(window.location.search);
      const id = params.get('id');

      if (!id) { root.innerHTML = notFound('No se especifico ningun proyecto.'); return; }

      const proyecto = Store.getProyectoById(id);
      if (!proyecto) { root.innerHTML = notFound('Proyecto no encontrado.'); return; }

      document.title = proyecto.titulo + ' — Martineau';

      const imagenes = [proyecto.imagen];
      if (proyecto.imagenes && proyecto.imagenes.length > 0) {
        imagenes.push(...proyecto.imagenes);
      }

      const thumbsHTML = imagenes.map((src, i) =>
        '<div class="gallery-thumb ' + (i === 0 ? 'active' : '') + '" data-src="' + escapeHtml(src) + '">' +
        '<img src="' + escapeHtml(src) + '" alt="' + escapeHtml(proyecto.titulo + ' — vista ' + (i + 1)) + '" loading="lazy" decoding="async"></div>'
      ).join('');

      const specsHTML = (proyecto.specs || []).map(s =>
        '<div class="spec-item"><span class="spec-label">' + escapeHtml(s.label) + '</span><span class="spec-value">' + escapeHtml(s.value) + '</span></div>'
      ).join('');

      const categoriaUrl = new URLSearchParams(window.location.search).get('categoria');
      const linkPortfolio = categoriaUrl ? 'portfolio?categoria=' + encodeURIComponent(categoriaUrl) : 'portfolio';

      const mensajeWhatsapp = encodeURIComponent('Hola! Quería consultar por el proyecto "' + proyecto.titulo + '".');
      const linkWhatsapp = <?= json_encode(ficha_contacto()['links']['whatsapp'], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?> + '?text=' + mensajeWhatsapp;

      root.innerHTML =
        '<div class="back-link-wrapper reveal">' +
        '<a href="' + linkPortfolio + '" class="back-link">' +
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
        '<line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline>' +
        '</svg> Volver al Portfolio' +
        '</a>' +
        '</div>' +
        '<div class="product-detail-grid">' +
        '<div class="product-gallery reveal">' +
        '<div class="gallery-main"><img src="' + escapeHtml(imagenes[0]) + '" alt="' + escapeHtml(proyecto.titulo) + '" id="main-proyecto-image"></div>' +
        '<div class="gallery-thumbnails">' + thumbsHTML + '</div>' +
        '</div>' +
        '<div class="product-info reveal">' +
        '<span class="product-info-category">' + capitalizar(escapeHtml(proyecto.categoria)) + ' — ' + escapeHtml(proyecto.anio || '') + '</span>' +
        '<h1 class="product-info-title">' + escapeHtml(proyecto.titulo) + '</h1>' +
        '<p class="product-info-desc">' + escapeHtml(proyecto.descripcion || '') + '</p>' +
        (specsHTML ? '<div class="product-specs">' + specsHTML + '</div>' : '') +
        '<div class="product-actions"><a href="' + linkWhatsapp + '" class="btn" target="_blank" rel="noopener">Consultar por este proyecto</a></div>' +
        '</div>' +
        '</div>';

      activarGaleria();

      // Reinicializar animaciones
      if (window.initScrollReveal) window.initScrollReveal();
    }

    function capitalizar(str) {
      return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
    }

    function notFound(msg) {
      return '<div style="text-align:center;padding:8rem 2rem"><p style="color:var(--color-text-muted)">' + msg + '</p>' +
        '<a href="portfolio" class="btn" style="margin-top:2rem;display:inline-block">Ver portfolio</a></div>';
    }
  </script>

  <script src="buscador.js?v=2"></script>
  <script src="script.js?v=25"></script>
</body>

</html>