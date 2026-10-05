<?php
// La portada se arma en el servidor solo para el slideshow (fotos cargadas desde el
// panel, ver inc/portada.php); el resto de la página es el mismo HTML de siempre.
require __DIR__ . '/inc/portada.php';
$fotos_portada = portada_fotos();
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
// Si una foto no tiene versión para celular, se usa la de computadora
$movil = fn($f) => $f['imagen_movil'] ?: $f['imagen'];
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es-AR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Martineau | Chimeneas y esculturas en piedra París desde 1922</title>
  <meta name="description" content="Taller en Chacarita, Buenos Aires, que desde 1922 fabrica a mano chimeneas, esculturas, ménsulas, maceteros y piezas de piedra París y yeso a medida.">
  <link rel="canonical" href="https://armartineau.com.ar/">
  <meta name="robots" content="index, follow, max-image-preview:large">
  <meta name="author" content="Santiago Mussi">
  <meta name="theme-color" content="#2E2820">

  <!-- Vista previa al compartir (WhatsApp, Facebook, LinkedIn, X) -->
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="Martineau">
  <meta property="og:locale" content="es_AR">
  <meta property="og:url" content="https://armartineau.com.ar/">
  <meta property="og:title" content="Martineau | Chimeneas y esculturas en piedra París desde 1922">
  <meta property="og:description" content="Taller en Chacarita, Buenos Aires, que desde 1922 fabrica a mano chimeneas, esculturas, ménsulas, maceteros y piezas de piedra París y yeso a medida.">
  <meta property="og:image" content="https://armartineau.com.ar/assets/og-martineau.jpg">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:image:alt" content="Taller de Martineau con chimeneas y esculturas de piedra París">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="Martineau | Chimeneas y esculturas en piedra París desde 1922">
  <meta name="twitter:description" content="Taller en Chacarita, Buenos Aires, que desde 1922 fabrica a mano chimeneas, esculturas, ménsulas, maceteros y piezas de piedra París y yeso a medida.">
  <meta name="twitter:image" content="https://armartineau.com.ar/assets/og-martineau.jpg">

  <!-- Datos estructurados (schema.org) -->
  <script type="application/ld+json" data-contacto="ld">
  {
    "@context": "https://schema.org",
    "@graph": [
      {
        "@type": "HomeAndConstructionBusiness",
        "@id": "https://armartineau.com.ar/#empresa",
        "name": "Martineau",
        "alternateName": [
          "Martineau Studio",
          "AR Martineau"
        ],
        "description": "Taller fundado en 1922 que fabrica a mano chimeneas, esculturas, ménsulas, maceteros y piezas de piedra París y yeso para arquitectura y decoración.",
        "url": "https://armartineau.com.ar/",
        "logo": "https://armartineau.com.ar/assets/favicon-512.png",
        "image": "https://armartineau.com.ar/assets/og-martineau.jpg",
        "telephone": "+54 9 11 3191-7014",
        "email": "contacto@armartineau.com.ar",
        "foundingDate": "1922",
        "founder": {
          "@type": "Person",
          "name": "Agustín Martineau"
        },
        "address": {
          "@type": "PostalAddress",
          "streetAddress": "Santos Dumont 4457",
          "addressLocality": "Ciudad Autónoma de Buenos Aires",
          "addressRegion": "CABA",
          "addressCountry": "AR"
        },
        "areaServed": [
          "AR",
          "UY",
          "BR",
          "US"
        ],
        "sameAs": [
          "https://www.instagram.com/armartineau/"
        ]
      },
      {
        "@type": "WebSite",
        "@id": "https://armartineau.com.ar/#sitio",
        "url": "https://armartineau.com.ar/",
        "name": "Martineau",
        "inLanguage": "es-AR",
        "publisher": {
          "@id": "https://armartineau.com.ar/#empresa"
        }
      }
    ]
  }
  </script>

  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="/favicon.ico?v=2">
  <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon-32.png?v=2">
  <link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon-16.png?v=2">
  <link rel="apple-touch-icon" sizes="180x180" href="/assets/apple-touch-icon.png?v=2">

  <!-- Fuentes -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <!-- La primera foto del hero es lo más grande que se pinta (LCP): se pide de entrada -->
  <link rel="preload" as="image" href="<?= $e($movil($fotos_portada[0])) ?>"
    media="(max-width: 767px)" fetchpriority="high">
  <link rel="preload" as="image" href="<?= $e($fotos_portada[0]['imagen']) ?>"
    media="(min-width: 768px)" fetchpriority="high">

  <!-- Fuente sin bloquear el primer pintado: se pide ya y se aplica apenas llega -->
  <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" onload="this.onload=null;this.rel='stylesheet'">
  <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"></noscript>

  <!-- Estilos -->
  <link rel="stylesheet" href="styles.css?v=69">
</head>

<body>
  <!-- Loader / Splash — "relieve": la pantalla es un bloque de piedra tallado.
       Las vetas/surcos (generados abajo según el tamaño de pantalla) nacen del logo
       y se abren hacia los bordes esquivándolo; la M se talla con un barrido de
       gubia y después toma volumen con su sombra. Al salir, el molde se abre. -->
  <div class="loader-wrapper">
    <div class="loader-panel loader-panel-l" aria-hidden="true"></div>
    <div class="loader-panel loader-panel-r" aria-hidden="true"></div>

    <svg class="loader-relief" aria-hidden="true"></svg>

    <svg class="loader-frame" aria-hidden="true">
      <rect class="lg-line" x="0" y="0" width="100%" height="100%" rx="26" pathLength="1" />
    </svg>

    <div class="loader-meta" aria-hidden="true">
      <span class="lm lm-tl">Martineau Studio</span>
      <span class="lm lm-tr">Chacarita · Buenos Aires</span>
      <div class="lm-bottom">
        <span class="lm lm-bl">Esculturas &amp; Chimeneas de Autor</span>
        <span class="lm-progress"><i></i></span>
        <span class="lm lm-br">Est. 1922</span>
      </div>
    </div>

    <div class="loader-mark">
      <svg class="loader-logo" viewBox="0 0 280 375" fill="none" stroke="#F4EFEA" stroke-linecap="round"
        stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg" role="img"
        aria-label="Martineau — Piedra París 1922">
        <defs>
          <!-- Barrido de gubia: un frente diagonal de borde suave que descubre la M -->
          <linearGradient id="lgTallaGrad" gradientUnits="userSpaceOnUse" x1="-110" y1="0" x2="0" y2="0">
            <stop offset="0" stop-color="#fff" />
            <stop offset="1" stop-color="#fff" stop-opacity="0" />
          </linearGradient>
          <mask id="lgTallaMask" maskUnits="userSpaceOnUse" x="0" y="0" width="280" height="375">
            <g transform="rotate(-24 140 187)">
              <rect class="lg-talla" x="-1000" y="-200" width="1100" height="775" fill="url(#lgTallaGrad)" />
            </g>
          </mask>
          <!-- La sombra que le da volumen de relieve una vez tallada -->
          <filter id="lgSombra" x="-10%" y="-10%" width="120%" height="120%">
            <feColorMatrix type="matrix" values="0 0 0 0 0.24  0 0 0 0 0.2  0 0 0 0 0.15  0 0 0 0.6 0" />
            <feGaussianBlur stdDeviation="1.6" />
          </filter>
        </defs>
        <rect class="lg-line lg-frame-o" x="12" y="12" width="256" height="351" rx="62" pathLength="1"
          stroke-width="2.4" />
        <rect class="lg-line lg-frame-i" x="22" y="22" width="236" height="331" rx="52" pathLength="1"
          stroke-width="1.3" />
        <g mask="url(#lgTallaMask)">
          <image class="lg-m-sombra" href="assets/logo-m.webp?v=2" x="57.5" y="99.5" width="170" height="183"
            preserveAspectRatio="xMidYMid meet" filter="url(#lgSombra)" />
          <image class="lg-m-img" href="assets/logo-m.webp?v=2" x="55" y="96" width="170" height="183"
            preserveAspectRatio="xMidYMid meet" />
        </g>
        <text class="lg-cap lg-cap-name" x="141" y="303" text-anchor="middle" stroke="none" fill="#F4EFEA">PIEDRA
          PARIS</text>
        <text class="lg-cap lg-cap-year" x="142" y="326" text-anchor="middle" stroke="none" fill="#F4EFEA">1922</text>
      </svg>
    </div>
  </div>
  <script>
    /* Relieve del splash: una chimenea Luis XV de la casa dibujada en línea fina,
       en alzado, con la boiserie, el trumeau y el piso completando la pantalla.
       El logo queda dentro de la boca de la chimenea. Se genera según el tamaño
       de pantalla; las coordenadas están en unidades del logo (280 × 375),
       con el centro del logo en (0, 0). */
    (function () {
      var loader = document.querySelector('.loader-wrapper');
      var svg = loader && loader.querySelector('.loader-relief');
      if (!svg) return;
      var NS = 'http://www.w3.org/2000/svg';

      function nodo(padre, tag, attrs) {
        var el = document.createElementNS(NS, tag);
        for (var k in attrs) el.setAttribute(k, attrs[k]);
        padre.appendChild(el);
        return el;
      }

      function rr(x, y, w, h, r) {
        return 'M' + (x + r) + ' ' + y + 'H' + (x + w - r) + 'Q' + (x + w) + ' ' + y + ' ' + (x + w) + ' ' + (y + r) +
          'V' + (y + h - r) + 'Q' + (x + w) + ' ' + (y + h) + ' ' + (x + w - r) + ' ' + (y + h) +
          'H' + (x + r) + 'Q' + x + ' ' + (y + h) + ' ' + x + ' ' + (y + h - r) +
          'V' + (y + r) + 'Q' + x + ' ' + y + ' ' + (x + r) + ' ' + y + 'Z';
      }

      function build(animar) {
        var W = loader.clientWidth, H = loader.clientHeight;
        var lw = Math.min(Math.max(118, W * 0.34), 250);
        var k = lw / 280;
        var X = W / 2 / k + 20, Y = H / 2 / k + 20;

        svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H);
        svg.classList.toggle('sin-anim', !animar);
        svg.textContent = '';

        var g = nodo(svg, 'g', { transform: 'translate(' + W / 2 + ' ' + H / 2 + ') scale(' + k + ')' });
        var der = nodo(g, 'g', {});
        var izq = nodo(g, 'g', { transform: 'scale(-1 1)' });

        // Todo es simétrico: se dibuja la mitad derecha y se espeja
        function linea(d, op, delay, dur, ancho) {
          [der, izq].forEach(function (lado) {
            var p = nodo(lado, 'path', {
              d: d, pathLength: 1, 'stroke-opacity': op, 'stroke-width': ancho || 1,
              'vector-effect': 'non-scaling-stroke'
            });
            p.style.animationDelay = delay + 's';
            if (dur) p.style.animationDuration = dur + 's';
          });
        }
        // Los rellenos van enteros (no espejados) para que no quede una junta al medio
        function relleno(d, color, delay) {
          var p = nodo(der, 'path', { d: d, fill: color, 'class': 'rl-fill' });
          p.style.animationDelay = delay + 's';
        }

        // — Pared: boiserie con paneles altos y bajos, zócalo y cornisa —
        var y0 = -700, yRail = -55;
        var yTop = Math.max(y0 + 20, -Y);
        for (var x = 390, i = 0; x < X; x += 204, i++) {
          var dl = 0.1 + i * 0.12;
          linea(rr(x, yTop, 170, yRail - 20 - yTop, 4), 0.3, dl, 1.6);
          linea(rr(x + 14, yTop + 14, 142, yRail - 48 - yTop, 3), 0.2, dl + 0.1, 1.6);
          linea(rr(x, yRail + 20, 170, 150, 4), 0.3, dl + 0.05, 1.4);
          linea(rr(x + 14, yRail + 34, 142, 122, 3), 0.2, dl + 0.15, 1.4);
        }
        linea('M370 ' + yRail + 'H' + X, 0.3, 0.05, 1.8);
        linea('M370 200H' + X, 0.3, 0.1, 1.8);
        if (-Y < y0) linea('M0 ' + y0 + 'H' + X + 'M0 ' + (y0 - 12) + 'H' + X, 0.3, 0.05, 1.8);

        // — Piso: el hogar y las tablas en perspectiva —
        linea('M0 235H' + X, 0.45, 0, 1.8);
        linea('M0 258H356V235', 0.4, 0.15, 1.2);
        [300, 352, 420, 505, 610, 740, 900].forEach(function (y, n) {
          if (y < Y) linea('M0 ' + y + 'H' + X, 0.22 - n * 0.02, 0.2 + n * 0.08, 1.8);
        });

        // — Trumeau (espejo) sobre la chimenea —
        linea('M0 -372H252V-760C252 -812 150 -806 62 -836C40 -843 20 -856 0 -856', 0.38, 0.35, 1.6);
        linea('M0 -392H230V-750C230 -792 140 -786 58 -812C38 -818 18 -830 0 -830', 0.24, 0.45, 1.6);

        // — Chimenea: volumen de piedra y boca más oscura —
        var boca = 'V-150C196 -185 180 -200 160 -205C120 -212 60 -232 0 -238' +
          'C-60 -232 -120 -212 -160 -205C-180 -200 -196 -185 -196 -150';
        relleno('M-296 -320H296V-262C296 -205 268 -175 268 -115V130C268 175 292 195 300 215V235H196' + boca +
          'V235H-300V215C-292 195 -268 175 -268 130V-115C-268 -175 -296 -205 -296 -262Z',
          'rgba(244, 239, 234, 0.09)', 1.1);
        relleno('M-196 235H196' + boca + 'Z', 'rgba(46, 38, 29, 0.16)', 1.1);

        // Repisa: tabla con canto redondeado, media caña y filete
        linea('M0 -355H322Q334 -355 334 -346Q334 -338 322 -338H0', 0.85, 0.3, 1.1, 1.3);
        linea('M0 -326H300C312 -326 318 -332 318 -338', 0.6, 0.4, 1);
        linea('M0 -320H296', 0.6, 0.45, 1);
        // Pierna: contorno exterior en S con el pie abierto
        linea('M296 -320V-262C296 -205 268 -175 268 -115V130C268 175 292 195 300 215V235H196', 0.85, 0.5, 1.2, 1.3);
        // Boca y su moldura paralela
        linea('M196 235V-150C196 -185 180 -200 160 -205C120 -212 60 -232 0 -238', 0.85, 0.55, 1.2, 1.3);
        linea('M208 235V-150C208 -192 190 -212 164 -217C122 -224 60 -244 0 -250', 0.5, 0.65, 1.2);
        // Panel rehundido de la pierna y roseta del capitel
        linea('M228 -140Q237 -160 246 -140V180Q237 194 228 180Z', 0.45, 0.8, 1.1);
        linea('M261 -290a11 11 0 1 0 22 0a11 11 0 1 0 -22 0M268 -290a4 4 0 1 0 8 0a4 4 0 1 0 -8 0', 0.6, 0.9, 0.9);

        // — Talla central: la venera (concha) con sus hojas de acanto —
        linea('M0 -306C22 -306 42 -296 46 -278C48 -266 36 -258 22 -256C12 -254 6 -255 0 -254', 0.8, 1.0, 0.9, 1.2);
        linea('M0 -257L7 -304M0 -257L17 -300M0 -257L27 -293M0 -257L36 -284M0 -257L43 -272', 0.55, 1.15, 0.8);
        linea('M46 -278C72 -268 92 -298 122 -290C142 -284 140 -270 128 -270C118 -270 118 -280 126 -281', 0.7, 1.2, 1);
        linea('M42 -262C72 -250 104 -270 142 -262C172 -256 186 -268 196 -282', 0.6, 1.3, 1);
        linea('M92 -283C100 -276 108 -276 114 -280', 0.5, 1.4, 0.7);
      }

      build(true);
      var t;
      window.addEventListener('resize', function () {
        clearTimeout(t);
        t = setTimeout(function () { build(false); }, 150);
      });
    })();
  </script>

  <!-- Header y Navegación -->
  <header class="site-header" id="header">
    <div class="container header-inner">

      <a href="#" class="logo" aria-label="Martineau Studio - Ir al inicio">
        <img src="assets/logo-wordmark.webp" alt="Martineau" width="680" height="127">
      </a>

      <!-- Menú Desktop -->
      <nav class="main-nav" aria-label="Navegación principal">
        <ul class="nav-list">
          <li><a href="nosotros" class="nav-link">Nosotros</a></li>
          <li class="nav-item has-dropdown">
            <a href="catalogo" class="nav-link">Catálogo</a>
            <ul class="nav-dropdown" data-nav-dropdown="producto"></ul>
          </li>
          <li class="nav-item has-dropdown">
            <a href="portfolio" class="nav-link">Portfolio</a>
            <ul class="nav-dropdown" data-nav-dropdown="proyecto"></ul>
          </li>
          <li><a href="#contacto" class="nav-link">Contacto</a></li>
        </ul>
      </nav>

      <!-- Botón Menú Móvil -->
      <button class="nav-toggle" aria-label="Abrir menú de navegación" aria-expanded="false">
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
      </button>
    </div>

    <!-- Menú Móvil Overlay -->
    <nav class="mobile-nav" aria-label="Navegación móvil" aria-hidden="true">
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


  <!-- Sección Hero -->
  <section class="hero" id="inicio">
    <div class="hero-slides" aria-hidden="true">
      <!-- Las fotos se cargan desde el panel (Portada). En celulares va un recorte vertical
           liviano (750×1210: el mismo encuadre que se ve en pantalla). Solo la primera carga
           de entrada; el resto usa data-src y lo carga initHeroSlideshow() cuando la página
           ya terminó (salen recién a los 7 s). -->
<?php foreach ($fotos_portada as $i => $foto): ?>
<?php if ($i === 0): ?>
      <div class="hero-slide is-active">
        <picture>
          <source media="(max-width: 767px)" srcset="<?= $e($movil($foto)) ?>">
          <img src="<?= $e($foto['imagen']) ?>" alt="" loading="eager" fetchpriority="high">
        </picture>
      </div>
<?php else: ?>
      <div class="hero-slide">
        <picture>
          <source media="(max-width: 767px)" data-srcset="<?= $e($movil($foto)) ?>">
          <img data-src="<?= $e($foto['imagen']) ?>" alt="" decoding="async">
        </picture>
      </div>
<?php endif; ?>
<?php endforeach; ?>
    </div>
    <div class="hero-overlay" aria-hidden="true"></div>

    <div class="hero-content">
      <!-- El subtítulo va dentro del h1 para que el título principal diga qué hacen
           (esculturas y chimeneas); se ve igual que antes -->
      <h1 class="hero-heading">
        <span class="hero-subtitle reveal">Esculturas &amp; Chimeneas de Autor</span>
        <span class="hero-title reveal">
          Las piezas están hechas<br>
          para durar toda una vida
        </span>
      </h1>
      <a href="#catalogo" class="btn btn-outline-light reveal">
        Explorar Colección
      </a>
    </div>

    <!-- Indicador de Scroll -->
    <div class="scroll-indicator" aria-hidden="true">
      <span class="scroll-line"></span>
    </div>
  </section>


  <!-- Sección El Estudio -->
  <section class="about section" id="nosotros">
    <div class="container">

      <div class="about-grid">
        <!-- Columna de Imagen -->
        <div class="about-image reveal">
          <div class="about-image-wrapper">
            <img src="assets/taller-equipo2.webp" alt="Equipo de Martineau en el taller, rodeado de moldes y relieves"
              loading="lazy">
          </div>
        </div>

        <!-- Columna de Texto -->
        <div class="about-text">
          <span class="section-label reveal">Quiénes somos</span>
          <h2 class="reveal">Más de un siglo<br>haciendo historia</h2>
          <p class="reveal">
            Fundada en 1922 por Agustín Martineau, somos una empresa líder en la fabricación de productos tipo piedra
            París: frentes de chimeneas, ménsulas, maceteros, jardineras, pisos, escaleras según planos, bordes de
            piletas y piezas de yeso para interiores.
          </p>
          <p class="reveal">
            La empresa trabaja con los mejores arquitectos y decoradores de Argentina, Brasil, Uruguay y Estados Unidos.
            Todo gracias a la excelencia de nuestros productos, hechos en moldes y terminados completamente a mano.
          </p>
          <p class="reveal">
            Todas las piezas de piedra pueden usarse en exterior bajo cualquier clima. Con el tiempo, desarrollan una
            atrayente pátina natural que las hace únicas.
          </p>
          <div class="reveal" style="margin-top: 2.5rem;">
            <a href="nosotros" class="btn">Saber más</a>
          </div>
        </div>
      </div>

    </div>
  </section>


  <!-- Catálogo de Productos -->
  <section class="catalog vitrina-seccion section" id="catalogo">
    <div class="container">

      <div class="section-header reveal">
        <span class="section-label">Colección</span>
        <h2>Piezas <em>destacadas</em></h2>
        <p class="section-subtitle">Una selección del catálogo, hecha a mano en el taller.</p>
      </div>

      <!-- Vitrina: se rellena con productos destacados vía JS -->
      <div class="vitrina" id="home-catalog-grid">
        <div class="grid-loading">
          <span class="spinner"></span>
          <p>Cargando catálogo…</p>
        </div>
      </div>

      <div class="section-footer reveal" style="text-align: center; margin-top: 6rem;">
        <a href="catalogo" class="btn">Ver colección completa</a>
      </div>
    </div>
  </section>


  <!-- Sección Antes y Después -->
  <section class="before-after section" id="transformacion">
    <div class="container ba-layout">

      <div class="ba-texto">
        <span class="section-label reveal">Transformación</span>
        <h2 class="reveal">El impacto <em>del diseño</em></h2>
        <p class="ba-lead reveal">Una sola pieza cambia por completo un ambiente. Mové la línea para ver el mismo espacio antes y después de la chimenea.</p>

        <div class="ba-botones reveal" role="group" aria-label="Comparar antes y después">
          <button type="button" class="ba-boton" data-ba-ir="100">Antes</button>
          <button type="button" class="ba-boton is-activo" data-ba-ir="50">Comparar</button>
          <button type="button" class="ba-boton" data-ba-ir="0">Después</button>
        </div>

        <p class="ba-ayuda reveal">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 7l-5 5 5 5M16 7l5 5-5 5M3 12h18" /></svg>
          Arrastrá para comparar
        </p>
      </div>

      <div class="ba-marco reveal">
        <div class="ba-container" tabindex="0" role="slider" aria-label="Comparar antes y después"
          aria-valuemin="0" aria-valuemax="100" aria-valuenow="50">
          <!-- Después (fondo) -->
          <div class="ba-image ba-before">
            <img src="assets/ImgsPrincipales/despuesChimenea.webp" alt="El ambiente con la chimenea Martineau encendida" loading="lazy">
          </div>
          <!-- Antes (encima, se recorta desde la izquierda) -->
          <div class="ba-image ba-after">
            <div class="ba-after-inner">
              <img src="assets/ImgsPrincipales/antesChimenea.webp" alt="El mismo ambiente antes, con la pared vacía" loading="lazy">
            </div>
          </div>

          <span class="ba-etiqueta ba-etiqueta--antes" aria-hidden="true">Antes</span>
          <span class="ba-etiqueta ba-etiqueta--despues" aria-hidden="true">Después</span>

          <div class="ba-slider" aria-hidden="true">
            <div class="ba-slider-line"></div>
            <div class="ba-slider-button">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10 17l-5-5 5-5M14 17l5-5-5-5" />
              </svg>
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>

  <!-- Portfolio / Proyectos -->
  <section class="portfolio section" id="portfolio">
    <div class="container">

      <div class="section-header reveal">
        <span class="section-label">Proyectos</span>
        <h2>Obras <em>realizadas</em></h2>
      </div>

      <!-- Índice de obras: se rellena con JS -->
      <div class="obras" id="home-portfolio-grid">
        <div class="grid-loading">
          <span class="spinner"></span>
          <p>Cargando proyectos…</p>
        </div>
      </div>

      <div class="section-footer reveal" style="text-align: center; margin-top: 4rem;">
        <a href="portfolio" class="btn">Ver todos los proyectos</a>
      </div>
    </div>
  </section>


  <!-- Sección Contacto -->
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
          <p data-contacto="ubicacion">
            Santos Dumont 4457 <br>
            Chacarita, CABA<br>
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
            <a href="tel:+5491131917014" data-contacto="telefono">+54 9 11 3191-7014</a><br>
            <a href="https://wa.me/5491131917014" target="_blank" rel="noopener" data-contacto="whatsapp">WhatsApp</a><br>
            <a href="mailto:contacto@armartineau.com.ar" data-contacto="email">contacto@armartineau.com.ar</a>
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
          <p data-contacto="horario">
            Lunes a Viernes<br>
            Coordinar visita
          </p>
        </div>

        <div class="contact-block-divider" aria-hidden="true"></div>

        <div class="contact-block">
          <span class="contact-block-icon" aria-hidden="true"></span>
          <h3>Redes</h3>
          <div class="contact-social">
            <a href="https://www.instagram.com/armartineau/" data-contacto="instagram" class="social-link" aria-label="Instagram" target="_blank"
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


  <!-- Footer -->
  <footer class="site-footer">
    <div class="container footer-inner">
      <p class="footer-copy">
        &copy; Todos los derechos reservados, Martineau
      </p>
      <p class="footer-credit">
        Desarrollo por Santiago Mussi.
      </p>
    </div>
  </footer>

  <!-- Scripts -->
  <!-- Store compartido (capa de datos / simulación) -->
  <script src="store.js?v=9"></script>

  <script>

    document.addEventListener('DOMContentLoaded', async () => {
      await Store.init();
      renderDestacados();
      renderNavCategorias();
    });

    function renderDestacados() {
      const nombreCategoria = (slug, tipo) => {
        const cat = Store.getCategorias(tipo).find(c => c.slug === slug);
        return capitalizar(cat ? cat.nombre : (slug || '').replace(/-/g, ' '));
      };
      const dosDigitos = n => String(n).padStart(2, '0');

      // Vitrina de productos: cada pieza en su hornacina
      const grid = document.getElementById('home-catalog-grid');
      const destacados = Store.getProductosDestacados().slice(0, 8);

      if (destacados.length === 0) {
        grid.innerHTML = '<p class="vitrina-vacia">No hay productos destacados.</p>';
      } else {
        grid.innerHTML = destacados.map((producto, i) => `
          <a href="${urlFicha('producto', producto)}" class="nicho" style="--i:${i % 4}">
            <div class="nicho-arco">
              <div class="nicho-foto">
                <img src="${escapeHtml(producto.imagen)}" alt="${escapeHtml(producto.titulo)}" loading="lazy" decoding="async">
                <span class="nicho-velo" aria-hidden="true"></span>
              </div>
            </div>
            <div class="nicho-placa">
              <span class="nicho-num">Nº ${dosDigitos(i + 1)}</span>
              <h3 class="nicho-titulo">${escapeHtml(producto.titulo)}</h3>
              <span class="nicho-cat">${escapeHtml(nombreCategoria(producto.categoria, 'producto'))}</span>
              <span class="nicho-ver">Ver pieza <span aria-hidden="true">&rarr;</span></span>
            </div>
          </a>
        `).join('');
      }

      // Índice de obras: foto grande en arco + lista numerada
      const gridProyectos = document.getElementById('home-portfolio-grid');
      const proyectos = Store.getProyectosDestacados().slice(0, 6);

      if (proyectos.length === 0) {
        gridProyectos.innerHTML = '<p class="vitrina-vacia">No hay proyectos destacados.</p>';
      } else {
        const meta = p => [nombreCategoria(p.categoria, 'proyecto'), p.ubicacion, p.anio]
          .filter(Boolean).map(escapeHtml).join(' · ');

        gridProyectos.innerHTML = `
          <div class="obras-escena">
            <div class="obras-arco">
              ${proyectos.map((p, i) => `
                <a href="${urlFicha('proyecto', p)}" class="obras-foto${i === 0 ? ' is-active' : ''}" tabindex="-1" aria-hidden="${i === 0 ? 'false' : 'true'}">
                  <img src="${escapeHtml(p.imagen)}" alt="${escapeHtml(p.titulo)}" ${i === 0 ? '' : 'loading="lazy"'} decoding="async">
                </a>
              `).join('')}
            </div>
            <div class="obras-contador" aria-hidden="true">
              <span class="obras-contador-actual">01</span>
              <span class="obras-contador-total">/ ${dosDigitos(proyectos.length)}</span>
            </div>
          </div>
          <ol class="obras-lista">
            ${proyectos.map((p, i) => `
              <li>
                <a href="${urlFicha('proyecto', p)}" class="obras-item${i === 0 ? ' is-active' : ''}" data-i="${i}" style="--i:${i}">
                  <span class="obras-num">${dosDigitos(i + 1)}</span>
                  <span class="obras-texto">
                    <span class="obras-nombre">${escapeHtml(p.titulo)}</span>
                    <span class="obras-meta">${meta(p)}</span>
                  </span>
                  <span class="obras-flecha" aria-hidden="true">&rarr;</span>
                  <span class="obras-barra" aria-hidden="true"></span>
                </a>
              </li>
            `).join('')}
          </ol>
        `;
      }

      if (window.initScrollReveal) window.initScrollReveal();
      if (window.initVitrina) window.initVitrina();
      if (window.initObras) window.initObras();
    }

    function capitalizar(str) {
      return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
    }
  </script>

  <script src="buscador.js?v=3"></script>
  <script src="script.js?v=27"></script>
  <!-- WhatsApp Floating Button -->
  <a href="https://wa.me/5491131917014" data-contacto="whatsapp" class="whatsapp-float" target="_blank" rel="noopener noreferrer"
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
</body>

</html>