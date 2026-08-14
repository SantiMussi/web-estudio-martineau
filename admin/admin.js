document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initModals();
    initSpecs();
    initImagePreviews();
    initSlugGenerator();
    initDeleteConfirmations();
});


function initSidebar() {
    const toggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.admin-sidebar');

    if (toggle && sidebar) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });

        document.addEventListener('click', (e) => {
            if (sidebar.classList.contains('open') &&
                !sidebar.contains(e.target) &&
                !toggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }
}

function initModals() {
    document.querySelectorAll('[data-modal-open]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modalId = btn.getAttribute('data-modal-open');
            const overlay = document.getElementById(modalId);
            if (overlay && !btn.hasAttribute('onclick')) {
                const form = overlay.querySelector('form');
                if (form) {
                    form.reset();
                    const idInput = form.querySelector('input[name="id"]');
                    if (idInput) idInput.value = '';
                    
                    const specsContainer = form.querySelector('.specs-container');
                    if (specsContainer) specsContainer.innerHTML = '';
                    
                    const title = overlay.querySelector('.modal-header h2');
                    if (title) title.textContent = 'Nuevo';
                    
                    const currentMainImgDiv = form.querySelector('#current-main-image');
                    if (currentMainImgDiv) currentMainImgDiv.style.display = 'none';
                    
                    const currentGalleryDiv = form.querySelector('#current-gallery');
                    if (currentGalleryDiv) currentGalleryDiv.style.display = 'none';
                    
                    const imagePreviews = form.querySelectorAll('.image-preview');
                    imagePreviews.forEach(p => p.innerHTML = '');
                }
            }
            openModal(modalId);
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            const overlay = btn.closest('.modal-overlay');
            if (overlay) closeModal(overlay);
        });
    });

    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeModal(overlay);
        });
    });

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

function editarItem(data, modalId) {
    const overlay = document.getElementById(modalId);
    if (!overlay) return;

    const form = overlay.querySelector('form');
    if (!form) return;

    for (const [key, value] of Object.entries(data)) {
        const field = form.querySelector(`[name="${key}"]`);
        if (!field) continue;
        if (field.type === 'file') continue;

        try {
            if (field.type === 'checkbox') {
                field.checked = value == 1;
            } else if (field.tagName === 'SELECT') {
                field.value = value;
            } else {
                field.value = value;
            }
        } catch (e) {
            console.warn(`Error al asignar valor al campo ${key}:`, e);
        }
    }

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

    const title = overlay.querySelector('.modal-header h2');
    if (title) title.textContent = data._modal_title || 'Editar';

    const currentMainImgDiv = form.querySelector('#current-main-image');
    if (currentMainImgDiv) {
        if (data.imagen) {
            currentMainImgDiv.style.display = 'block';
            const previewThumb = currentMainImgDiv.querySelector('.preview-thumb');
            if (previewThumb) previewThumb.style.display = 'inline-block';
            currentMainImgDiv.querySelector('img').src = '../' + escapeAttr(data.imagen);
            const checkbox = currentMainImgDiv.querySelector('input[type="checkbox"]');
            if (checkbox) checkbox.checked = false;
        } else {
            currentMainImgDiv.style.display = 'none';
        }
    }

    const currentGalleryDiv = form.querySelector('#current-gallery');
    if (currentGalleryDiv) {
        currentGalleryDiv.innerHTML = '';
        if (data.imagenes && data.imagenes.length > 0) {
            currentGalleryDiv.style.display = 'flex';
            data.imagenes.forEach(img => {
                const div = document.createElement('div');
                div.className = 'preview-thumb';
                div.innerHTML = `
                    <img src="../${escapeAttr(img)}" loading="lazy" decoding="async" style="width: 80px; height: 80px; object-fit: cover; border-radius: 6px; border: 1px solid var(--admin-border);">
                    <button type="button" class="remove-preview" title="Eliminar imagen">&times;</button>
                `;
                div.querySelector('.remove-preview').addEventListener('click', () => {
                    div.style.display = 'none';
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'eliminar_galeria[]';
                    hidden.value = img;
                    div.appendChild(hidden);
                });
                currentGalleryDiv.appendChild(div);
            });
        } else {
            currentGalleryDiv.style.display = 'none';
        }
    }

    openModal(modalId);
}

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

    row.querySelector('.btn-remove-spec').addEventListener('click', () => {
        row.remove();
    });

    container.appendChild(row);
}

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

    let hiddenInput = form.querySelector('input[name="specs"]');
    if (!hiddenInput) {
        hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'specs';
        form.appendChild(hiddenInput);
    }
    hiddenInput.value = JSON.stringify(specs);
}

function initImagePreviews() {
    document.querySelectorAll('input[name="imagen"]').forEach(input => {
        input.addEventListener('change', function () {
            previewFiles(this, this.closest('.form-group').querySelector('.image-preview'), false);
        });
    });

    document.querySelectorAll('input[name="imagenes[]"]').forEach(input => {
        input.addEventListener('change', function () {
            previewFiles(this, this.closest('.form-group').querySelector('.image-preview'), true);
        });
    });

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
    previewContainer.innerHTML = ''; // Limpiar siempre porque el input file nativo reemplaza la selección

    const files = input.files;
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        if (!file.type.startsWith('image/')) continue;

        const reader = new FileReader();
        reader.onload = (e) => {
            const thumb = document.createElement('div');
            thumb.className = 'preview-thumb';
            thumb.innerHTML = `
                <img src="${e.target.result}" alt="Preview" style="width: 80px; height: 80px; object-fit: cover; border-radius: 6px; border: 1px solid var(--admin-border);">
                <button type="button" class="remove-preview" title="Eliminar imagen">&times;</button>
            `;
            
            thumb.querySelector('.remove-preview').addEventListener('click', () => {
                const dt = new DataTransfer();
                for (let j = 0; j < input.files.length; j++) {
                    if (j !== i) dt.items.add(input.files[j]);
                }
                input.files = dt.files;
                previewFiles(input, previewContainer, multiple); // Volver a renderizar
            });

            previewContainer.appendChild(thumb);
        };
        reader.readAsDataURL(file);
    }
}

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

function initDeleteConfirmations() {
    document.querySelectorAll('.btn-eliminar').forEach(btn => {
        btn.addEventListener('click', (e) => {
            if (!confirm('¿Estás seguro de que querés eliminar este elemento? Esta acción no se puede deshacer.')) {
                e.preventDefault();
            }
        });
    });
}

document.addEventListener('submit', (e) => {
    const form = e.target;
    if (form.querySelector('.specs-container')) {
        serializarSpecs(form);
    }
});

function escapeAttr(str) {
    if (!str) return '';
    return str
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

document.addEventListener('DOMContentLoaded', () => {
    const initSortable = (id, tabla) => {
        const el = document.getElementById(id);
        if (!el) return;

        Sortable.create(el, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',
            onEnd: function (evt) {
                if (evt.oldIndex === evt.newIndex) return;

                const orden = [];
                el.querySelectorAll('tr[data-id]').forEach(tr => {
                    orden.push(tr.getAttribute('data-id'));
                });

                fetch('actions/guardar_orden.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        tabla: tabla,
                        orden: orden
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        alert('Error al guardar el nuevo orden: ' + (data.error || 'Desconocido'));
                    }
                })
                .catch(err => {
                    console.error('Error saving order:', err);
                    alert('Error de conexión al guardar el orden.');
                });
            }
        });
    };

    initSortable('sortable-productos', 'productos');
    initSortable('sortable-proyectos', 'proyectos');
});
