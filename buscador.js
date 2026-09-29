// Buscador de productos. Se inyecta solo en el header de cada página (lupa) y,
// en el catálogo, como barra visible arriba de los filtros. Usa los productos
// que Store ya cargó, así que no hace pedidos extra al servidor.
(function () {
  'use strict';

  const MAX_RESULTADOS = 20;

  const STOP = new Set(['de', 'del', 'la', 'las', 'el', 'los', 'y', 'e', 'con', 'para', 'en', 'un', 'una',
    'n', 'nro', 'no', 'num', 'numero', 'por', 'a', 'al', 'o']);

  // Grupos de palabras que el cliente usa para lo mismo. Todo en forma "raíz" (ver raiz()).
  const SINONIMOS = [
    ['maceta', 'macetero', 'jardinera', 'matera'],
    ['lampara', 'luz', 'aplique', 'plafon', 'farol'],
    ['estatua', 'escultura', 'figura', 'busto', 'cabeza'],
    ['hogar', 'estufa', 'chimenea'],
    ['baranda', 'barandal', 'balaustr', 'balaustre', 'balaustra', 'balaustrada'],
    ['piso', 'baldosa', 'loseta', 'solado'],
    ['fuente', 'bebedero'],
    ['columna', 'capitel'],
    ['jarron', 'anfora', 'urna', 'pedestal'],
    ['relieve', 'bajorelieve', 'altorelieve', 'placa', 'mural'],
    ['angel', 'angelito', 'querubin'],
    ['bola', 'esfera', 'bocha'],
  ];

  // ---------- Normalización ----------

  function normalizar(texto) {
    return String(texto || '')
      .toLowerCase()
      .replace(/n\s*[°º]\s*/g, ' ')          // "N°106" / "Nº 106" -> " 106"
      .normalize('NFD').replace(/[̀-ͯ]/g, '')
      .replace(/[^a-z0-9\s]/g, ' ')
      .replace(/\s+/g, ' ')
      .trim();
  }

  // Singulariza de forma simple. Se aplica igual a la búsqueda y a los productos,
  // así que lo importante es que sea consistente, no que sea gramaticalmente perfecto.
  function raiz(palabra) {
    if (/^\d+$/.test(palabra) || palabra.length <= 3) return palabra;
    if (palabra.endsWith('ces')) return palabra.slice(0, -3) + 'z';
    if (/[nrldj]es$/.test(palabra)) return palabra.slice(0, -2);
    if (palabra.endsWith('s')) return palabra.slice(0, -1);
    return palabra;
  }

  function palabras(texto) {
    return normalizar(texto).split(' ').filter(Boolean).map(raiz);
  }

  function tokensDeBusqueda(texto) {
    return normalizar(texto).split(' ').filter(p => p && !STOP.has(p)).map(raiz);
  }

  function levenshtein(a, b) {
    if (a === b) return 0;
    const fila = Array.from({ length: b.length + 1 }, (_, i) => i);
    for (let i = 1; i <= a.length; i++) {
      let anterior = fila[0];
      fila[0] = i;
      for (let j = 1; j <= b.length; j++) {
        const tmp = fila[j];
        fila[j] = Math.min(fila[j] + 1, fila[j - 1] + 1, anterior + (a[i - 1] === b[j - 1] ? 0 : 1));
        anterior = tmp;
      }
    }
    return fila[b.length];
  }

  const esNumero = s => /^\d+$/.test(s);

  // Qué tan bien una palabra buscada coincide con una palabra del producto (0 = nada, 1 = exacta).
  function coincidencia(buscada, palabra) {
    if (buscada === palabra) return 1;
    if (esNumero(buscada) || esNumero(palabra)) return 0; // los números tienen que ser exactos
    if (buscada.length >= 2 && palabra.startsWith(buscada)) return 0.8; // "chim" -> "chimenea"
    if (buscada.length >= 4 && palabra.includes(buscada)) return 0.6;   // "relieve" -> "bajorelieve"
    if (palabra.length >= 5 && buscada.startsWith(palabra)) return 0.6;
    if (buscada.length >= 4 && Math.abs(buscada.length - palabra.length) <= 2) {
      const tolerancia = buscada.length >= 7 ? 2 : 1;
      if (levenshtein(buscada, palabra) <= tolerancia) return 0.5;    // errores de tipeo
    }
    return 0;
  }

  function variantes(token) {
    const lista = [{ palabra: token, peso: 1 }];
    if (token.length < 3 || esNumero(token)) return lista;
    SINONIMOS.forEach(grupo => {
      const pertenece = grupo.some(g => g === token || (token.length >= 4 && g.startsWith(token)));
      if (pertenece) grupo.forEach(g => { if (g !== token) lista.push({ palabra: g, peso: 0.85 }); });
    });
    return lista;
  }

  // ---------- Índice ----------

  let indice = null;
  let indiceTamanio = -1;

  function datosListos() {
    return typeof Store !== 'undefined' && Store._cache && Array.isArray(Store._cache.productos);
  }

  function construirIndice() {
    const productos = Store.getProductos();
    if (indice && indiceTamanio === productos.length) return indice;

    const categorias = {};
    Store.getCategorias('producto').forEach(c => { categorias[c.slug] = c.nombre; });

    indice = productos.map(p => {
      const nombreCat = categorias[p.categoria] || p.categoria || '';
      return {
        producto: p,
        categoria: nombreCat,
        tituloRaiz: ' ' + palabras(p.titulo).join(' ') + ' ',
        campos: [
          { peso: 3, palabras: palabras(p.titulo) },
          { peso: 2, palabras: palabras(nombreCat) },
          { peso: 1, palabras: palabras(p.descripcion) },
        ],
      };
    });
    indiceTamanio = productos.length;
    return indice;
  }

  // Suma lo mejor de cada campo: una pieza que coincide en título Y categoría
  // (un macetero en "Maceteros") queda arriba de una que solo lo nombra.
  // Con minimo = 0.6 se ignoran las coincidencias por error de tipeo.
  function puntuarToken(entrada, token, minimo) {
    const vars = variantes(token);
    return entrada.campos.reduce((total, campo) => {
      let mejorCampo = 0;
      vars.forEach(v => {
        campo.palabras.forEach(pal => {
          const c = coincidencia(v.palabra, pal);
          if (c >= minimo) mejorCampo = Math.max(mejorCampo, c * v.peso);
        });
      });
      return total + mejorCampo * campo.peso;
    }, 0);
  }

  function buscar(texto) {
    const tokens = tokensDeBusqueda(texto);
    if (!tokens.length) return { resultados: [], aproximado: false, tokens };

    const frase = ' ' + tokens.join(' ') + ' ';
    const entradas = construirIndice();

    // La tolerancia a errores de tipeo se usa solo para palabras que no aparecen
    // bien escritas en ningún producto ("chimnea" sí, "pina" no: existe "Piña").
    const minimos = tokens.map(t => entradas.some(e => puntuarToken(e, t, 0.6) > 0) ? 0.6 : 0.01);

    const evaluados = entradas.map(entrada => {
      const puntajes = tokens.map((t, i) => puntuarToken(entrada, t, minimos[i]));
      let total = puntajes.reduce((a, b) => a + b, 0);
      // Bonus si la frase entera aparece tal cual en el título (ej. "marmol negro")
      if (tokens.length > 1 && entrada.tituloRaiz.includes(frase)) total += 2;
      return { entrada, total, aciertos: puntajes.filter(p => p > 0).length };
    });

    let resultados = evaluados.filter(r => r.aciertos === tokens.length);
    let aproximado = false;

    // Si ningún producto cumple con todas las palabras, mostramos los que más se acercan.
    if (!resultados.length) {
      resultados = evaluados.filter(r => r.aciertos > 0);
      aproximado = resultados.length > 0;
    }

    resultados.sort((a, b) =>
      (b.aciertos - a.aciertos) || (b.total - a.total) ||
      a.entrada.producto.titulo.localeCompare(b.entrada.producto.titulo, 'es', { numeric: true }));

    return { resultados, aproximado, tokens };
  }

  function categoriasQueCoinciden(tokens) {
    const textuales = tokens.filter(t => !esNumero(t));
    if (!textuales.length) return [];
    return Store.getCategorias('producto').filter(cat => {
      const pals = palabras(cat.nombre);
      return textuales.some(t => variantes(t).some(v => pals.some(p => coincidencia(v.palabra, p) >= 0.6)));
    });
  }

  function cantidadPorCategoria() {
    const cuenta = {};
    Store.getProductos().forEach(p => { cuenta[p.categoria] = (cuenta[p.categoria] || 0) + 1; });
    return cuenta;
  }

  // ---------- UI ----------

  const ICONO_LUPA = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><line x1="20.5" y1="20.5" x2="16" y2="16"></line></svg>';

  let panel, input, lista, estado, chips, ultimoFoco = null, activo = -1, temporizador = null;

  function crearPanel() {
    panel = document.createElement('div');
    panel.className = 'bq';
    panel.hidden = true;
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-modal', 'true');
    panel.setAttribute('aria-label', 'Buscar productos');
    panel.innerHTML = `
      <div class="bq-backdrop" data-bq-cerrar></div>
      <div class="bq-panel">
        <div class="bq-barra">
          ${ICONO_LUPA}
          <input type="search" class="bq-input" id="bqInput" autocomplete="off" spellcheck="false"
            placeholder="Chimenea, maceta, 106…" aria-controls="bqResultados">
          <button type="button" class="bq-cerrar" data-bq-cerrar aria-label="Cerrar búsqueda">Esc</button>
        </div>
        <div class="bq-cuerpo">
          <div class="bq-chips"></div>
          <p class="bq-estado" aria-live="polite"></p>
          <ul class="bq-resultados" id="bqResultados" role="listbox"></ul>
        </div>
      </div>`;
    document.body.appendChild(panel);

    input = panel.querySelector('.bq-input');
    lista = panel.querySelector('.bq-resultados');
    estado = panel.querySelector('.bq-estado');
    chips = panel.querySelector('.bq-chips');

    panel.querySelectorAll('[data-bq-cerrar]').forEach(el => el.addEventListener('click', cerrar));
    // Varios eventos por robustez: algunos teclados móviles/autocompletado no
    // disparan todos. Si el texto no cambió, render() no se repite.
    let ultimoTexto = null;
    const programar = () => {
      clearTimeout(temporizador);
      temporizador = setTimeout(() => {
        if (input.value === ultimoTexto) return;
        ultimoTexto = input.value;
        render();
      }, 70);
    };
    ['input', 'keyup', 'search', 'compositionend'].forEach(ev => input.addEventListener(ev, programar));
    input.addEventListener('keydown', teclado);
  }

  function abrir(textoInicial) {
    if (!panel) crearPanel();
    ultimoFoco = document.activeElement;
    panel.hidden = false;
    document.body.classList.add('no-scroll');
    requestAnimationFrame(() => panel.classList.add('is-open'));
    if (typeof textoInicial === 'string') input.value = textoInicial;
    render();
    setTimeout(() => input.focus(), 30);
  }

  function cerrar() {
    if (!panel || panel.hidden) return;
    panel.classList.remove('is-open');
    document.body.classList.remove('no-scroll');
    setTimeout(() => { panel.hidden = true; }, 250);
    if (ultimoFoco && ultimoFoco.focus) ultimoFoco.focus();
  }

  function chip(cat, cuenta) {
    const a = document.createElement('a');
    a.className = 'bq-chip';
    a.href = 'catalogo?categoria=' + encodeURIComponent(cat.slug);
    a.textContent = cat.nombre;
    if (cuenta) {
      const n = document.createElement('span');
      n.textContent = cuenta;
      a.appendChild(n);
    }
    return a;
  }

  // Resalta en el título las palabras que coincidieron con la búsqueda.
  function tituloResaltado(titulo, tokens) {
    const frag = document.createDocumentFragment();
    titulo.split(/(\s+)/).forEach(parte => {
      if (!parte.trim()) { frag.appendChild(document.createTextNode(parte)); return; }
      const pals = palabras(parte);
      const coincide = pals.length && tokens.some(t =>
        variantes(t).some(v => pals.some(p => coincidencia(v.palabra, p) >= 0.5)));
      if (coincide) {
        const mark = document.createElement('mark');
        mark.textContent = parte;
        frag.appendChild(mark);
      } else {
        frag.appendChild(document.createTextNode(parte));
      }
    });
    return frag;
  }

  function render() {
    activo = -1;
    lista.innerHTML = '';
    chips.innerHTML = '';

    if (!datosListos()) {
      estado.textContent = 'Cargando productos…';
      setTimeout(() => { if (!panel.hidden) render(); }, 300);
      return;
    }

    const texto = input.value;
    const cuentas = cantidadPorCategoria();

    if (!tokensDeBusqueda(texto).length) {
      const titulo = document.createElement('span');
      titulo.className = 'bq-chips-titulo';
      titulo.textContent = 'Explorá por categoría';
      chips.appendChild(titulo);
      Store.getCategorias('producto')
        .filter(c => cuentas[c.slug])
        .forEach(c => chips.appendChild(chip(c, cuentas[c.slug])));
      estado.textContent = 'Podés buscar por nombre, tipo de pieza o número (por ejemplo, “chimenea 106”).';
      return;
    }

    const { resultados, aproximado, tokens } = buscar(texto);

    categoriasQueCoinciden(tokens).slice(0, 4).forEach(c => chips.appendChild(chip(c, cuentas[c.slug])));

    if (!resultados.length) {
      estado.textContent = `No encontramos piezas para “${texto.trim()}”. Probá con otra palabra o consultanos por WhatsApp.`;
      return;
    }

    const total = resultados.length;
    estado.textContent = aproximado
      ? `No hay coincidencias exactas. Estas son las más parecidas:`
      : `${total} ${total === 1 ? 'pieza encontrada' : 'piezas encontradas'}`;

    resultados.slice(0, MAX_RESULTADOS).forEach((r, i) => {
      const p = r.entrada.producto;
      const li = document.createElement('li');
      li.setAttribute('role', 'option');
      li.id = 'bq-op-' + i;

      const a = document.createElement('a');
      a.className = 'bq-item';
      a.href = 'producto?id=' + encodeURIComponent(p.id);

      const foto = document.createElement('span');
      foto.className = 'bq-item-foto';
      if (p.imagen) {
        const img = document.createElement('img');
        img.src = p.imagen;
        img.alt = '';
        img.loading = 'lazy';
        foto.appendChild(img);
      }

      const textoItem = document.createElement('span');
      textoItem.className = 'bq-item-texto';
      const t = document.createElement('span');
      t.className = 'bq-item-titulo';
      t.appendChild(tituloResaltado(p.titulo, tokens));
      const c = document.createElement('span');
      c.className = 'bq-item-cat';
      c.textContent = r.entrada.categoria;
      textoItem.append(t, c);

      const flecha = document.createElement('span');
      flecha.className = 'bq-item-flecha';
      flecha.setAttribute('aria-hidden', 'true');
      flecha.textContent = '→';

      a.append(foto, textoItem, flecha);
      li.appendChild(a);
      lista.appendChild(li);
    });

    if (total > MAX_RESULTADOS) {
      const mas = document.createElement('li');
      mas.className = 'bq-mas';
      mas.textContent = `Y ${total - MAX_RESULTADOS} más. Agregá otra palabra para afinar la búsqueda.`;
      lista.appendChild(mas);
    }
  }

  function marcarActivo(nuevo) {
    const opciones = lista.querySelectorAll('[role="option"]');
    if (!opciones.length) return;
    activo = (nuevo + opciones.length) % opciones.length;
    opciones.forEach((op, i) => op.classList.toggle('is-active', i === activo));
    opciones[activo].scrollIntoView({ block: 'nearest' });
    input.setAttribute('aria-activedescendant', opciones[activo].id);
  }

  function teclado(e) {
    if (e.key === 'ArrowDown') { e.preventDefault(); marcarActivo(activo + 1); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); marcarActivo(activo - 1); }
    else if (e.key === 'Enter') {
      const opciones = lista.querySelectorAll('[role="option"] a');
      const destino = opciones[activo >= 0 ? activo : 0];
      if (destino) { e.preventDefault(); window.location.href = destino.href; }
    }
  }

  function crearDisparadores() {
    // Desktop: último ítem del menú
    const navList = document.querySelector('.main-nav .nav-list');
    if (navList) {
      const li = document.createElement('li');
      li.innerHTML = `<button type="button" class="nav-search" aria-label="Buscar productos">${ICONO_LUPA}</button>`;
      navList.appendChild(li);
    }

    // Mobile: al lado del menú hamburguesa
    const toggle = document.querySelector('.header-inner .nav-toggle');
    if (toggle) {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'nav-search nav-search--mobile';
      btn.setAttribute('aria-label', 'Buscar productos');
      btn.innerHTML = ICONO_LUPA;
      toggle.parentNode.insertBefore(btn, toggle);
    }

    // Catálogo: barra visible arriba de los filtros
    const filtros = document.getElementById('catalog-filters');
    if (filtros) {
      const barra = document.createElement('button');
      barra.type = 'button';
      barra.className = 'bq-disparador reveal';
      barra.innerHTML = `${ICONO_LUPA}<span>Buscá una pieza o un número…</span><kbd>Ctrl K</kbd>`;
      filtros.parentNode.insertBefore(barra, filtros);
    }

    document.querySelectorAll('.nav-search, .bq-disparador').forEach(b => b.addEventListener('click', () => abrir()));
  }

  document.addEventListener('keydown', (e) => {
    const escribiendo = /^(INPUT|TEXTAREA|SELECT)$/.test(e.target.tagName) || e.target.isContentEditable;
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); abrir(); return; }
    if (e.key === '/' && !escribiendo) { e.preventDefault(); abrir(); return; }
    if (e.key === 'Escape' && panel && !panel.hidden) cerrar();
  });

  document.addEventListener('DOMContentLoaded', crearDisparadores);

  // Expuesto para probar la lógica desde la consola
  window.BuscadorMartineau = { buscar, normalizar, raiz, abrir };
})();
