/**
 * store.js — Capa de datos de AR Martineau
 *
 * ┌─────────────────────────────────────────────────────────────┐
 * │  Conectado a API real: api/datos.php                        │
 * │  Todas las funciones devuelven datos desde MySQL vía fetch  │
 * │  La interfaz pública se mantiene idéntica para no romper    │
 * │  el código de rendering existente en cada HTML.             │
 * └─────────────────────────────────────────────────────────────┘
 */

const Store = {

  // Cache local para evitar requests duplicados en la misma carga de página
  _cache: {},

  /**
   * Inicializa el store. Precarga productos y proyectos.
   * Compatible con la interfaz anterior (await Store.init()).
   */
  async init() {
    try {
      const [productos, proyectos] = await Promise.all([
        this._fetch('productos'),
        this._fetch('proyectos')
      ]);
      this._cache.productos = productos;
      this._cache.proyectos = proyectos;
    } catch (e) {
      console.warn('[Store] Error al cargar datos desde API:', e);
      this._cache.productos = [];
      this._cache.proyectos = [];
    }
  },

  /**
   * Fetch genérico al endpoint de la API.
   * @param {string} tipo — Parámetro 'tipo' para api/datos.php
   * @param {Object} params — Parámetros adicionales (ej: { id: 5 })
   * @returns {Promise<Array|Object>}
   */
  async _fetch(tipo, params = {}) {
    const url = new URL('api/datos.php', window.location.origin + window.location.pathname.replace(/[^/]*$/, ''));
    url.searchParams.set('tipo', tipo);
    for (const [key, val] of Object.entries(params)) {
      url.searchParams.set(key, val);
    }
    const res = await fetch(url.toString());
    if (!res.ok) throw new Error(`API error: ${res.status}`);
    return res.json();
  },

  /**
   * Devuelve todos los productos (desde cache).
   * @returns {Array}
   */
  getProductos() {
    return this._cache.productos || [];
  },

  /**
   * Devuelve todos los proyectos (desde cache).
   * @returns {Array}
   */
  getProyectos() {
    return this._cache.proyectos || [];
  },

  /**
   * Devuelve solo los productos marcados como destacados (destacar: true).
   * @returns {Array}
   */
  getProductosDestacados() {
    return this.getProductos().filter(p => p.destacar === true);
  },

  /**
   * Devuelve solo los proyectos marcados como destacados (destacar: true).
   * @returns {Array}
   */
  getProyectosDestacados() {
    return this.getProyectos().filter(p => p.destacar === true);
  },

  /**
   * Devuelve un producto por su ID.
   * @param {number|string} id
   * @returns {Object|null}
   */
  getProductoById(id) {
    return this.getProductos().find(p => p.id === parseInt(id)) || null;
  },

  /**
   * Devuelve un proyecto por su ID.
   * @param {number|string} id
   * @returns {Object|null}
   */
  getProyectoById(id) {
    return this.getProyectos().find(p => p.id === parseInt(id)) || null;
  },

};
