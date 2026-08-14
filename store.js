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
    const url = new URL('api/datos.php', window.location.origin + window.location.pathname.replace(/[^/]*$/, ''));
    url.searchParams.set('tipo', tipo);
    for (const [key, val] of Object.entries(params)) {
      url.searchParams.set(key, val);
    }

    if (window.location.protocol === 'file:') {
      throw new Error("Protocolo file:// no soportado para fetch.");
    }
    
    console.log('[Store] Fetching:', url.toString());
    
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

  getProductosDestacados() {
    return this.getProductos().filter(p => p.destacar === true);
  },

  getProyectosDestacados() {
    return this.getProyectos().filter(p => p.destacar === true);
  },

  getProductoById(id) {
    return this.getProductos().find(p => p.id === parseInt(id)) || null;
  },

  getProyectoById(id) {
    return this.getProyectos().find(p => p.id === parseInt(id)) || null;
  },

};
