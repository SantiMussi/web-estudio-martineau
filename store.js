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
      const results = await Promise.allSettled([
        this._fetch('productos'),
        this._fetch('proyectos'),
        this._fetch('categorias')
      ]);
      
      if (results[0].status === 'fulfilled') {
        this._cache.productos = results[0].value;
      } else {
        console.error('[Store] Error productos:', results[0].reason);
        this._cache.productos = [];
      }
      
      if (results[1].status === 'fulfilled') {
        this._cache.proyectos = results[1].value;
      } else {
        console.error('[Store] Error proyectos:', results[1].reason);
        this._cache.proyectos = [];
      }
      
      if (results[2].status === 'fulfilled') {
        this._cache.categorias = results[2].value;
      } else {
        console.error('[Store] Error categorias:', results[2].reason);
        this._cache.categorias = [];
      }
    } catch (e) {
      console.warn('[Store] Error fatal:', e);
      this._cache.productos = [];
      this._cache.proyectos = [];
      this._cache.categorias = [];
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
    if (window.location.protocol === 'file:') {
      alert("Error: Estás abriendo el archivo localmente (file://). Debes usar tu servidor local (ej. http://localhost/MartinEau/)");
      throw new Error("Protocolo file:// no soportado para fetch.");
    }
    
    const res = await fetch(url.toString());
    if (!res.ok) throw new Error(`API error: ${res.status}`);
    
    const text = await res.text();
    if (text.trim().startsWith('<?php')) {
      alert("Error: El servidor no está ejecutando PHP (ej. estás usando Live Server). Debes entrar a través de tu servidor local (ej. http://localhost/MartinEau/)");
      throw new Error("El servidor devolvió código fuente PHP en lugar de JSON.");
    }
    
    return JSON.parse(text);
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
   * Devuelve las categorias (desde cache).
   * @returns {Array}
   */
  getCategorias(tipo = null) {
    let cats = this._cache.categorias || [];
    if (tipo) {
        cats = cats.filter(c => c.tipo === tipo);
    }
    return cats;
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
