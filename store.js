/**
 * store.js — Capa de datos de AR Martineau
 *
 * ┌─────────────────────────────────────────────────────────┐
 * │  Para conectar a un servidor real:                      │
 * │  1. Reemplazá las funciones dentro del bloque marcado   │
 * │     como  ─── [SIMULACIÓN] ───                         │
 * │  2. Hacelas async y usá fetch() a tu API               │
 * │  3. El resto de la página (forEach, render) NO cambia  │
 * └─────────────────────────────────────────────────────────┘
 */

/* ═══════════════════════════════════════════════════════════
   ─── [SIMULACIÓN] ─── Reemplazar por llamadas a API real
   ═══════════════════════════════════════════════════════════ */

const Store = {

  /**
   * Inicializa el store cargando el JSON local si localStorage está vacío.
   * → En producción: eliminar este método; el fetch irá directo a la API.
   */
  async init() {
    if (!localStorage.getItem('martineau_db')) {
      try {
        const res = await fetch('sim/data.json');
        const data = await res.json();
        localStorage.setItem('martineau_db', JSON.stringify(data));
      } catch (e) {
        console.warn('[Store] No se pudo cargar data.json. Usando datos vacíos.');
        localStorage.setItem('martineau_db', JSON.stringify({ productos: [], proyectos: [] }));
      }
    }
  },

  /**
   * Devuelve todos los productos.
   * → REEMPLAZAR por: const res = await fetch('/api/productos'); return res.json();
   * @returns {Array}
   */
  getProductos() {
    const db = JSON.parse(localStorage.getItem('martineau_db')) || { productos: [] };
    return db.productos;
  },

  /**
   * Devuelve todos los proyectos.
   * → REEMPLAZAR por: const res = await fetch('/api/proyectos'); return res.json();
   * @returns {Array}
   */
  getProyectos() {
    const db = JSON.parse(localStorage.getItem('martineau_db')) || { proyectos: [] };
    return db.proyectos;
  },

  /**
   * Devuelve solo los productos marcados como destacados (destacar: true).
   * → REEMPLAZAR por: const res = await fetch('/api/productos?destacar=true'); return res.json();
   * @returns {Array}
   */
  getProductosDestacados() {
    return this.getProductos().filter(p => p.destacar === true);
  },

  /**
   * Devuelve un producto por su ID.
   * → REEMPLAZAR por: const res = await fetch(`/api/productos/${id}`); return res.json();
   * @param {number|string} id
   * @returns {Object|null}
   */
  getProductoById(id) {
    return this.getProductos().find(p => p.id === parseInt(id)) || null;
  },

};

/* ═══════════════════════════════════════════════════════════
   ─── FIN [SIMULACIÓN] ───
   ═══════════════════════════════════════════════════════════ */

