/**
 * admin.js — Lógica del Panel de Administración MartinEau Studio
 * 
 * Maneja:
 * - Apertura/cierre de modales
 * - Cargador dinámico de specs (pares clave-valor)
 * - Auto-generación de slug para categorías
 * - Preview de imágenes antes de subir
 * - Confirmaciones de eliminación
 * - Toggle sidebar en móvil
 */

document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initModals();
    initSpecs();
    initImagePreviews();
    initSlugGenerator();
    initDeleteConfirmations();
});


// ═══════════════════════════════════════════════
// ─── SIDEBAR MOBILE TOGGLE ───
// ═══════════════════════════════════════════════

function initSidebar() {
    const toggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.admin-sidebar');

    if (toggle && sidebar) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });

        // Cerrar al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (sidebar.classList.contains('open') &&
                !sidebar.contains(e.target) &&
                !toggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }
}


// ═══════════════════════════════════════════════
// ─── MODALES ───
// ═══════════════════════════════════════════════

function initModals() {
    // Botones que abren modal
    document.querySelectorAll('[data-modal-open]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modalId = btn.getAttribute('data-modal-open');
            openModal(modalId);
        });
    });

    // Botones que cierran modal
    document.querySelectorAll('[data-modal-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            const overlay = btn.closest('.modal-overlay');
            if (overlay) closeModal(overlay);
        });
    });

    // Cerrar al clic en overlay (fuera del modal)
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeModal(overlay);
        });
    });

    // Cerrar con Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.active').forEach(closeModal);
        }
    });
}

function openModal(id) {
    const overlay = document.getElementById(id);
    if (overlay) {
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';

        // Focus en el primer input
        setTimeout(() => {
            const firstInput = overlay.querySelector('input:not([type="hidden"]), textarea, select');
            if (firstInput) firstInput.focus();
        }, 100);
    }
}

function closeModal(overlayOrId) {
    const overlay = typeof overlayOrId === 'string'
        ? document.getElementById(overlayOrId)
        : overlayOrId;

    if (overlay) {
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
}

/**
 * Abre el modal de edición y rellena los campos con datos existentes.
 * @param {Object} data — Objeto con los datos del ítem a editar
 * @param {string} modalId — ID del modal overlay
 */
function editarItem(data, modalId) {
    const overlay = document.getElementById(modalId);
    if (!overlay) return;

    const form = overlay.querySelector('form');
    if (!form) return;

    // Rellenar campos del formulario
    for (const [key, value] of Object.entries(data)) {
        const field = form.querySelector(`[name="${key}"]`);
        if (!field) continue;

        if (field.type === 'checkbox') {
            field.checked = value == 1;
        } else if (field.tagName === 'SELECT') {
            field.value = value;
        } else {
            field.value = value;
        }
    }

    // Cargar specs si existen
    if (data.specs) {
        const container = form.querySelector('.specs-container');
        if (container) {
            container.innerHTML = '';
            let specs;
            try {
                specs = typeof data.specs === 'string' ? JSON.parse(data.specs) : data.specs;
            } catch (e) {
                specs = [];
            }

            if (Array.isArray(specs)) {
                specs.forEach(spec => addSpecRow(container, spec.label, spec.value));
            }
        }
    }

    // Cambiar título del modal
    const title = overlay.querySelector('.modal-header h2');
    if (title) title.textContent = data._modal_title || 'Editar';

    openModal(modalId);
}


// ═══════════════════════════════════════════════
// ─── SPECS DINÁMICOS (Clave-Valor) ───
// ═══════════════════════════════════════════════

function initSpecs() {
    document.querySelectorAll('.btn-add-spec').forEach(btn => {
        btn.addEventListener('click', () => {
            const container = btn.closest('.form-group').querySelector('.specs-container');
            if (container) addSpecRow(container);
        });
    });
}

function addSpecRow(container, label = '', value = '') {
    const row = document.createElement('div');
    row.className = 'spec-row';
    row.innerHTML = `
        <input type="text" class="form-control spec-label-input" placeholder="Ej: Material" value="${escapeAttr(label)}">
        <input type="text" class="form-control spec-value-input" placeholder="Ej: Piedra París" value="${escapeAttr(value)}">
        <button type="button" class="btn-remove-spec" title="Quitar">&times;</button>
    `;

    // Botón quitar
    row.querySelector('.btn-remove-spec').addEventListener('click', () => {
        row.remove();
    });

    container.appendChild(row);
}

/**
 * Recolecta todos los pares clave-valor de specs y los serializa a JSON.
 * Se llama antes de enviar el formulario.
 */
function serializarSpecs(form) {
    const rows = form.querySelectorAll('.spec-row');
    const specs = [];

    rows.forEach(row => {
        const label = row.querySelector('.spec-label-input').value.trim();
        const value = row.querySelector('.spec-value-input').value.trim();
        if (label && value) {
            specs.push({ label, value });
        }
    });

    // Crear input hidden con el JSON
    let hiddenInput = form.querySelector('input[name="specs"]');
    if (!hiddenInput) {
        hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'specs';
        form.appendChild(hiddenInput);
    }
    hiddenInput.value = JSON.stringify(specs);
}


// ═══════════════════════════════════════════════
// ─── IMAGE PREVIEW ───
// ═══════════════════════════════════════════════

function initImagePreviews() {
    // Preview de imagen principal
    document.querySelectorAll('input[name="imagen"]').forEach(input => {
        input.addEventListener('change', function () {
            previewFiles(this, this.closest('.form-group').querySelector('.image-preview'), false);
        });
    });

    // Preview de galería múltiple
    document.querySelectorAll('input[name="imagenes[]"]').forEach(input => {
        input.addEventListener('change', function () {
            previewFiles(this, this.closest('.form-group').querySelector('.image-preview'), true);
        });
    });

    // Drag and drop
    document.querySelectorAll('.file-upload-area').forEach(area => {
        area.addEventListener('dragover', (e) => {
            e.preventDefault();
            area.classList.add('dragover');
        });
        area.addEventListener('dragleave', () => area.classList.remove('dragover'));
        area.addEventListener('drop', (e) => {
            e.preventDefault();
            area.classList.remove('dragover');
            const input = area.querySelector('input[type="file"]');
            if (input && e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                input.dispatchEvent(new Event('change'));
            }
        });
    });
}

function previewFiles(input, previewContainer, multiple) {
    if (!previewContainer) return;
    if (!multiple) previewContainer.innerHTML = '';

    const files = input.files;
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        if (!file.type.startsWith('image/')) continue;

        const reader = new FileReader();
        reader.onload = (e) => {
            const thumb = document.createElement('div');
            thumb.className = 'preview-thumb';
            thumb.innerHTML = `
                <img src="${e.target.result}" alt="Preview">
            `;
            previewContainer.appendChild(thumb);
        };
        reader.readAsDataURL(file);
    }
}


// ═══════════════════════════════════════════════
// ─── SLUG GENERATOR ───
// ═══════════════════════════════════════════════

function initSlugGenerator() {
    const nombreInput = document.querySelector('input[name="nombre"]');
    const slugInput = document.querySelector('input[name="slug"]');

    if (nombreInput && slugInput) {
        nombreInput.addEventListener('input', () => {
            slugInput.value = generarSlug(nombreInput.value);
        });
    }
}

function generarSlug(texto) {
    return texto
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '') // Quitar acentos
        .replace(/[^a-z0-9\s-]/g, '')    // Solo alfanuméricos
        .trim()
        .replace(/[\s]+/g, '-')          // Espacios a guiones
        .replace(/-+/g, '-');            // Múltiples guiones a uno
}


// ═══════════════════════════════════════════════
// ─── DELETE CONFIRMATIONS ───
// ═══════════════════════════════════════════════

function initDeleteConfirmations() {
    document.querySelectorAll('.btn-eliminar').forEach(btn => {
        btn.addEventListener('click', (e) => {
            if (!confirm('¿Estás seguro de que querés eliminar este elemento? Esta acción no se puede deshacer.')) {
                e.preventDefault();
            }
        });
    });
}


// ═══════════════════════════════════════════════
// ─── FORM SUBMIT CON SPECS ───
// ═══════════════════════════════════════════════

/**
 * Intercepta el submit de formularios que tienen specs dinámicos
 * para serializar los pares clave-valor a JSON antes del envío.
 */
document.addEventListener('submit', (e) => {
    const form = e.target;
    if (form.querySelector('.specs-container')) {
        serializarSpecs(form);
    }
});


// ═══════════════════════════════════════════════
// ─── UTILITY ───
// ═══════════════════════════════════════════════

/**
 * Escapa un string para atributos HTML.
 */
function escapeAttr(str) {
    if (!str) return '';
    return str
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}
