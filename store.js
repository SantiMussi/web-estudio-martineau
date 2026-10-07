const Store = {

  _cache: {},

  async init() {
    try {
      const results = await Promise.allSettled([
        this._fetch('productos'),
        this._fetch('proyectos'),
        this._fetch('categorias')
      ]);
      
      if (results[0].status === 'fulfilled') {
        this._cache.productos = results[0].value;
      } else {
        console.error('[Store] Error cargando productos:', results[0].reason);
        this._cache.productos = [];
      }
      
      if (results[1].status === 'fulfilled') {
        this._cache.proyectos = results[1].value;
      } else {
        console.error('[Store] Error cargando proyectos:', results[1].reason);
        this._cache.proyectos = [];
      }
      
      if (results[2].status === 'fulfilled') {
        this._cache.categorias = results[2].value;
      } else {
        console.error('[Store] Error cargando categorias:', results[2].reason);
        this._cache.categorias = [];
      }
    } catch (e) {
      console.error('[Store] Error fatal en init():', e);
      this._cache.productos = [];
      this._cache.proyectos = [];
      this._cache.categorias = [];
    }
  },

  async _fetch(tipo, params = {}) {
    // document.baseURI: en las fichas (/producto/nombre) hay <base href="/">
    const url = new URL('api/datos.php', document.baseURI);
    url.searchParams.set('tipo', tipo);
    for (const [key, val] of Object.entries(params)) {
      url.searchParams.set(key, val);
    }

    if (window.location.protocol === 'file:') {
      throw new Error("Protocolo file:// no soportado para fetch.");
    }

    const res = await fetch(url.toString());

    if (!res.ok) {
      const body = await res.text();
      console.error('[Store] API respondió con error', res.status, ':', body);
      throw new Error(`API error: ${res.status} - ${body}`);
    }
    
    const text = await res.text();

    if (text.trim().startsWith('<?php')) {
      throw new Error("El servidor devolvió código fuente PHP en lugar de JSON.");
    }

    try {
      return JSON.parse(text);
    } catch (e) {
      console.error('[Store] Error parseando JSON para', tipo, '- Respuesta:', text.substring(0, 500));
      throw e;
    }
  },

  getProductos() {
    return this._cache.productos || [];
  },

  getProyectos() {
    return this._cache.proyectos || [];
  },
  
  getCategorias(tipo = null) {
    let cats = this._cache.categorias || [];
    if (tipo) {
        cats = cats.filter(c => c.tipo === tipo);
    }
    return cats;
  },

  // Si hay productos marcados como destacados manualmente, se usan esos.
  // Si no hay ninguno, se muestran unos pocos al azar (cambian en cada carga de la página).
  getProductosDestacados(cantidadAleatoria = 4) {
    const productos = this.getProductos();
    const destacados = productos.filter(p => p.destacar === true);
    return destacados.length > 0 ? destacados : this._elegirAleatorios(productos, cantidadAleatoria);
  },

  getProyectosDestacados(cantidadAleatoria = 4) {
    const proyectos = this.getProyectos();
    const destacados = proyectos.filter(p => p.destacar === true);
    return destacados.length > 0 ? destacados : this._elegirAleatorios(proyectos, cantidadAleatoria);
  },

  _elegirAleatorios(lista, cantidad) {
    const copia = [...lista];
    for (let i = copia.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [copia[i], copia[j]] = [copia[j], copia[i]];
    }
    return copia.slice(0, cantidad);
  },

  getProductoById(id) {
    return this.getProductos().find(p => p.id === parseInt(id)) || null;
  },

  getProyectoById(id) {
    return this.getProyectos().find(p => p.id === parseInt(id)) || null;
  },

};

// Link a la ficha de un producto o proyecto: /producto/chimenea-luis-xv si ya tiene
// URL amigable (ver inc/slugs.php), si no el link viejo con ?id=. Ruta relativa a la raíz.
function urlFicha(tipo, item) {
  return item.slug
    ? tipo + '/' + encodeURIComponent(item.slug)
    : tipo + '?id=' + encodeURIComponent(item.id);
}

// Escapa texto antes de insertarlo en HTML (evita XSS con datos cargados desde el panel admin).
function escapeHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

// Puebla los dropdowns de categorías del navbar (desktop) y del menú hamburguesa (mobile):
// Catálogo con las categorías de productos y Portfolio con las de proyectos.
function renderNavCategorias() {
  const destinos = { producto: 'catalogo', proyecto: 'portfolio' };

  Object.entries(destinos).forEach(([tipo, pagina]) => {
    const contenedores = document.querySelectorAll(`[data-nav-dropdown="${tipo}"]`);
    if (contenedores.length === 0) return;

    const categorias = Store.getCategorias(tipo);
    if (categorias.length === 0) {
      // Sin categorías no tiene sentido el desplegable: queda solo el link
      contenedores.forEach(el => {
        const item = el.closest('.has-dropdown');
        if (item) item.classList.remove('has-dropdown');
        const chevron = item && item.querySelector('.mobile-nav-chevron');
        if (chevron) chevron.remove();
        el.remove();
      });
      return;
    }

    const itemsHTML = categorias.map(cat =>
      `<li><a href="${pagina}?categoria=${encodeURIComponent(cat.slug)}">${escapeHtml(cat.nombre)}</a></li>`
    ).join('');

    contenedores.forEach(el => { el.innerHTML = itemsHTML; });
  });
}
