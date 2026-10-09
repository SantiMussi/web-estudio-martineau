document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initModals();
    initSpecs();
    initSpecPresets();
    initImagePreviews();
    initSlugGenerator();
    initDeleteConfirmations();
    initTableFilters();
    initSeleccion();
    initCapitalizarTitulo();
});

function capitalizarPrimeraLetra(str) {
    return str ? str.charAt(0).toUpperCase() + str.slice(1) : str;
}

function initCapitalizarTitulo() {
    document.querySelectorAll('input[name="titulo"]').forEach(input => {
        input.addEventListener('input', () => {
            const val = input.value;
            if (!val) return;
            const primera = val.charAt(0);
            const mayus = primera.toUpperCase();
            if (primera === mayus) return;
            const pos = input.selectionStart;
            input.value = mayus + val.slice(1);
            input.setSelectionRange(pos, pos);
        });
    });
}

function initTableFilters() {
    const tbodyIds = new Set();
    document.querySelectorAll('[data-filtro-categoria]').forEach(el => tbodyIds.add(el.getAttribute('data-filtro-categoria')));
    document.querySelectorAll('[data-buscar-tabla]').forEach(el => tbodyIds.add(el.getAttribute('data-buscar-tabla')));

    tbodyIds.forEach(tbodyId => {
        const tbody = document.getElementById(tbodyId);
        if (!tbody) return;

        const select = document.querySelector(`[data-filtro-categoria="${tbodyId}"]`);
        const buscador = document.querySelector(`[data-buscar-tabla="${tbodyId}"]`);
        const countEl = document.querySelector(`[data-filtro-count="${tbodyId}"]`);
        const storageKey = 'admin_filtro_categoria_' + tbodyId;

        if (select) {
            try {
                const guardado = localStorage.getItem(storageKey);
                if (guardado !== null && select.querySelector(`option[value="${guardado}"]`)) {
                    select.value = guardado;
                }
            } catch (e) {
            }
        }

        const normalizar = (str) => (str || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[̀-ͯ]/g, '');

        const aplicar = () => {
            const valorCategoria = select ? select.value : '';
            const textoBusqueda = normalizar(buscador ? buscador.value.trim() : '');
            const filas = tbody.querySelectorAll('tr[data-id]');
            let visibles = 0;

            filas.forEach(fila => {
                const coincideCategoria = !valorCategoria || fila.getAttribute('data-categoria-id') === valorCategoria;
                const tituloEl = fila.querySelector('.table-title');
                const coincideBusqueda = !textoBusqueda || normalizar(tituloEl ? tituloEl.textContent : '').includes(textoBusqueda);
                const visible = coincideCategoria && coincideBusqueda;
                fila.style.display = visible ? '' : 'none';
                if (visible) visibles++;
            });

            if (countEl) {
                countEl.textContent = (valorCategoria || textoBusqueda) ? `${visibles} de ${filas.length}` : '';
            }

            if (select) {
                try {
                    localStorage.setItem(storageKey, valorCategoria);
                } catch (e) {
                }
            }
        };

        if (select) select.addEventListener('change', aplicar);
        if (buscador) buscador.addEventListener('input', aplicar);
        aplicar();
    });
}


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

                    form.querySelectorAll('[disabled]').forEach(el => el.disabled = false);
                    form.querySelectorAll('.solo-en-edicion').forEach(el => el.hidden = true);

                    const specsContainer = form.querySelector('.specs-container');
                    if (specsContainer) specsContainer.innerHTML = '';
                    
                    const title = overlay.querySelector('.modal-header h2');
                    if (title) title.textContent = title.dataset.tituloNuevo || 'Nuevo';
                    
                    const currentMainImgDiv = form.querySelector('#current-main-image');
                    if (currentMainImgDiv) currentMainImgDiv.style.display = 'none';
                    
                    const currentGalleryDiv = form.querySelector('#current-gallery');
                    if (currentGalleryDiv) {
                        currentGalleryDiv.style.display = 'none';
                        currentGalleryDiv.innerHTML = '';
                    }

                    const imagePreviews = form.querySelectorAll('.image-preview');
                    imagePreviews.forEach(p => p.innerHTML = '');

                    const catSelect = form.querySelector('[name="categoria_id"]');
                    const filtroCat = document.querySelector('[data-filtro-categoria]');
                    if (catSelect && filtroCat && filtroCat.value) {
                        catSelect.value = filtroCat.value;
                    }
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

    form.querySelectorAll('input[type="file"]').forEach(input => { input.value = ''; });
    form.querySelectorAll('.image-preview').forEach(p => { p.innerHTML = ''; });

    const currentMainImgDiv = form.querySelector('#current-main-image');
    if (currentMainImgDiv) {
        if (data.imagen) {
            currentMainImgDiv.style.display = 'block';
            currentMainImgDiv.dataset.ruta = data.imagen;
            const previewThumb = currentMainImgDiv.querySelector('.preview-thumb');
            if (previewThumb) {
                previewThumb.style.display = 'inline-block';
                previewThumb.classList.remove('is-reemplazada');
                if (!previewThumb.querySelector('.editar-preview')) {
                    const editar = botonEditarFoto(async () => {
                        const editada = await EditorImagen.abrir('../' + currentMainImgDiv.dataset.ruta);
                        if (!editada) return;
                        const input = form.querySelector('input[name="imagen"]');
                        const dt = new DataTransfer();
                        dt.items.add(editada);
                        input.files = dt.files;
                        input.dispatchEvent(new Event('change'));
                    });
                    if (editar) previewThumb.appendChild(editar);
                }
            }
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
        currentGalleryDiv._reemplazos = new Map();
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
                const editar = botonEditarFoto(async () => {
                    const previa = currentGalleryDiv._reemplazos.get(img);
                    const editada = await EditorImagen.abrir(previa || '../' + img, { nombre: img.split('/').pop() });
                    if (!editada) return;
                    reemplazarFotoGaleria(currentGalleryDiv, img, editada);
                    const lector = new FileReader();
                    lector.onload = () => { div.querySelector('img').src = lector.result; };
                    lector.readAsDataURL(editada);
                    div.classList.add('is-editada');
                });
                if (editar) div.appendChild(editar);
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

function initSpecPresets() {
    document.querySelectorAll('.btn-preset-spec').forEach(btn => {
        btn.addEventListener('click', () => {
            const form = btn.closest('form');
            const container = btn.closest('.form-group').querySelector('.specs-container');
            if (!container) return;

            const categoriaSelect = form ? form.querySelector('select[name="categoria_id"]') : null;
            const categoriaTexto = categoriaSelect && categoriaSelect.selectedOptions.length
                ? categoriaSelect.selectedOptions[0].textContent
                : '';
            const esMaceta = /macet/i.test(categoriaTexto);

            const preset = [
                { label: 'Material', value: 'Tipo Piedra París (cemento blanco, marmolina en distintos tonos y granulado de mármol)' },
                { label: 'Refuerzo', value: 'Concreto + malla de hierro' },
                { label: 'Terminación', value: esMaceta ? 'Impermeabilizada con cerecita' : 'Mate' },
            ];

            preset.forEach(spec => addSpecRow(container, spec.label, spec.value));
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
            precargarTituloDesdeImagen(this);
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
            if (!input || !e.dataTransfer.files.length) return;

            if (input.multiple) {
                const dt = new DataTransfer();
                Array.from(input.files).forEach(f => dt.items.add(f));
                Array.from(e.dataTransfer.files).forEach(f => dt.items.add(f));
                input.files = dt.files;
            } else {
                input.files = e.dataTransfer.files;
            }

            input.dispatchEvent(new Event('change'));
        });
    });
}

function precargarTituloDesdeImagen(input) {
    const form = input.closest('form');
    if (!form) return;

    const tituloInput = form.querySelector('[name="titulo"]');
    if (!tituloInput || tituloInput.value.trim()) return;

    const file = input.files && input.files[0];
    if (!file) return;

    const titulo = file.name
        .replace(/\.[^.]+$/, '')
        .replace(/^\s*copia\s+de\s+/i, '')
        .trim();

    if (titulo) tituloInput.value = capitalizarPrimeraLetra(titulo);
}

function botonEditarFoto(alEditar) {
    if (!window.EditorImagen) return null;
    const boton = document.createElement('button');
    boton.type = 'button';
    boton.className = 'editar-preview';
    boton.title = 'Editar foto';
    boton.setAttribute('aria-label', 'Editar foto');
    boton.innerHTML = '<svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>';
    boton.addEventListener('click', alEditar);
    return boton;
}

function reemplazarFotoGaleria(contenedor, ruta, archivo) {
    contenedor._reemplazos.set(ruta, archivo);

    const anterior = contenedor.querySelector('.galeria-reemplazos');
    if (anterior) anterior.remove();

    const campos = document.createElement('div');
    campos.className = 'galeria-reemplazos';
    campos.hidden = true;
    const archivos = document.createElement('input');
    archivos.type = 'file';
    archivos.name = 'galeria_reemplazo[]';
    archivos.multiple = true;
    const dt = new DataTransfer();
    contenedor._reemplazos.forEach((foto, de) => {
        dt.items.add(foto);
        const campo = document.createElement('input');
        campo.type = 'hidden';
        campo.name = 'galeria_reemplazo_de[]';
        campo.value = de;
        campos.appendChild(campo);
    });
    archivos.files = dt.files;
    campos.appendChild(archivos);
    contenedor.appendChild(campos);
}

function previewFiles(input, previewContainer, multiple) {
    if (!previewContainer) return;
    previewContainer.innerHTML = '';

    if (!multiple) {
        const form = input.closest('form');
        const actual = form && form.querySelector('#current-main-image .preview-thumb');
        if (actual) actual.classList.toggle('is-reemplazada', input.files.length > 0);
    }

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
                previewFiles(input, previewContainer, multiple);
            });

            const editar = botonEditarFoto(async () => {
                const editada = await EditorImagen.abrir(file);
                if (!editada) return;
                const dt = new DataTransfer();
                for (let j = 0; j < input.files.length; j++) {
                    dt.items.add(j === i ? editada : input.files[j]);
                }
                input.files = dt.files;
                previewFiles(input, previewContainer, multiple);
            });
            if (editar) thumb.appendChild(editar);

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
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9\s-]/g, '')
        .trim()
        .replace(/[\s]+/g, '-')
        .replace(/-+/g, '-');
}

function initDeleteConfirmations() {
    document.querySelectorAll('.btn-eliminar').forEach(btn => {
        btn.addEventListener('click', (e) => {
            if (!confirm('¿Estás seguro de que querés eliminar este elemento? Esta acción no se puede deshacer.')) {
                e.preventDefault();
            }
        });
    });

    document.querySelectorAll('[data-confirmar]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            if (!confirm(btn.getAttribute('data-confirmar'))) {
                e.preventDefault();
            }
        });
    });
}

function initSeleccion() {
    document.querySelectorAll('form[data-seleccion]').forEach(barra => {
        const tbody = document.getElementById(barra.getAttribute('data-seleccion'));
        if (!tbody) return;

        const todos = document.querySelector(`[data-seleccion-todos="${tbody.id}"]`);
        const cuenta = barra.querySelector('[data-seleccion-cuenta]');
        const casillas = () => [...tbody.querySelectorAll(`input[type="checkbox"][form="${barra.id}"]`)];
        const visible = casilla => casilla.closest('tr').style.display !== 'none';
        const enPagina = casilla => visible(casilla) && !casilla.closest('tr').classList.contains('fuera-de-pagina');

        const botonTodas = document.createElement('button');
        botonTodas.type = 'button';
        botonTodas.className = 'barra-seleccion-todas';
        if (cuenta) cuenta.after(botonTodas);
        else barra.prepend(botonTodas);

        const actualizar = () => {
            const visibles = casillas().filter(visible);
            const marcadas = visibles.filter(c => c.checked).length;
            const pagina = casillas().filter(enPagina);
            const marcadasPagina = pagina.filter(c => c.checked).length;
            barra.hidden = marcadas === 0;
            if (cuenta) cuenta.textContent = marcadas === 1 ? '1 seleccionado' : `${marcadas} seleccionados`;
            if (todos) {
                todos.checked = marcadasPagina > 0 && marcadasPagina === pagina.length;
                todos.indeterminate = marcadasPagina > 0 && marcadasPagina < pagina.length;
            }
            botonTodas.hidden = visibles.length <= pagina.length;
            const filtroCat = document.querySelector(`[data-filtro-categoria="${tbody.id}"]`);
            const buscador = document.querySelector(`[data-buscar-tabla="${tbody.id}"]`);
            const deQue = filtroCat && filtroCat.value !== '' ? ' de la categoría'
                : buscador && buscador.value.trim() !== '' ? ' de la búsqueda' : '';
            botonTodas.textContent = marcadas < visibles.length
                ? `Seleccionar los ${visibles.length}${deQue}`
                : 'Deseleccionar todos';
        };

        botonTodas.addEventListener('click', () => {
            const visibles = casillas().filter(visible);
            const marcar = visibles.some(c => !c.checked);
            visibles.forEach(c => { c.checked = marcar; });
            actualizar();
        });
        tbody.addEventListener('paginacion', actualizar);

        if (todos) {
            todos.addEventListener('change', () => {
                casillas().filter(enPagina).forEach(c => { c.checked = todos.checked; });
                actualizar();
            });
        }
        const alCambiar = e => { if (e.target !== todos) actualizar(); };
        document.addEventListener('input', alCambiar);
        document.addEventListener('change', alCambiar);

        barra.addEventListener('submit', () => {
            casillas().filter(c => !visible(c)).forEach(c => { c.checked = false; });
        });

        actualizar();
    });
}

document.addEventListener('submit', (e) => {
    const form = e.target;

    if (requiereImagenPrincipal(form) && !tieneImagenPrincipal(form)) {
        e.preventDefault();
        alert('Tenés que subir una imagen principal antes de guardar.');
        const fileInput = form.querySelector('input[name="imagen"]');
        if (fileInput) fileInput.focus();
        return;
    }

    if (form.querySelector('.specs-container')) {
        serializarSpecs(form);
    }
});

function requiereImagenPrincipal(form) {
    const action = form.getAttribute('action') || '';
    return action.endsWith('guardar_producto.php') || action.endsWith('guardar_proyecto.php');
}

function tieneImagenPrincipal(form) {
    const fileInput = form.querySelector('input[name="imagen"]');
    const tieneArchivoNuevo = !!(fileInput && fileInput.files && fileInput.files.length > 0);

    const currentMainImgDiv = form.querySelector('#current-main-image');
    const eliminarCheckbox = form.querySelector('input[name="eliminar_imagen_principal"]');
    const tieneImagenActual = !!(
        currentMainImgDiv &&
        currentMainImgDiv.style.display !== 'none' &&
        !(eliminarCheckbox && eliminarCheckbox.checked)
    );

    return tieneArchivoNuevo || tieneImagenActual;
}

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
        if (!el || typeof Sortable === 'undefined') return;

        const conCasillas = !!el.querySelector('input[name="items[]"]');
        const multiDrag = conCasillas && Sortable.utils && typeof Sortable.utils.select === 'function';
        if (multiDrag) {
            const sincronizar = (e) => {
                if (!e.target.closest('.drag-handle')) return;
                el.querySelectorAll('tr[data-id]').forEach(tr => {
                    const casilla = tr.querySelector('input[name="items[]"]');
                    if (casilla && casilla.checked) Sortable.utils.select(tr);
                    else Sortable.utils.deselect(tr);
                });
            };
            el.addEventListener('change', (e) => {
                if (e.target.name !== 'items[]') return;
                const tr = e.target.closest('tr');
                if (tr) Sortable.utils[e.target.checked ? 'select' : 'deselect'](tr);
            });
            el.addEventListener('pointerdown', sincronizar, true);
            el.addEventListener('mousedown', sincronizar, true);
            el.addEventListener('touchstart', sincronizar, { capture: true, passive: true });
        }

        Sortable.create(el, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',
            multiDrag: multiDrag,
            multiDragKey: 'CTRL',
            selectedClass: 'fila-en-grupo',
            avoidImplicitDeselect: true,
            onEnd: function (evt) {
                const enGrupo = evt.items && evt.items.length > 1;
                if (!enGrupo && evt.oldIndex === evt.newIndex) return;
                guardarOrden(tabla, [...el.querySelectorAll('tr[data-id]')].map(tr => tr.getAttribute('data-id')));
            }
        });
    };

    initSortable('sortable-productos', 'productos');
    initSortable('sortable-proyectos', 'proyectos');
    initSortable('sortable-resenas', 'resenas');
    initSortable('sortable-portada', 'portada');

    initPaginacion('sortable-productos');
    initPaginacion('sortable-proyectos');
    initOrdenAutomatico();
    initMoverSeleccion();

    initSlugFichas();
    abrirFichaPedida();
});

function guardarOrden(tabla, orden) {
    const csrfInput = document.querySelector('input[name="csrf_token"]');
    return fetch('actions/guardar_orden.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            tabla: tabla,
            orden: orden,
            csrf_token: csrfInput ? csrfInput.value : ''
        })
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            alert('Error al guardar el nuevo orden: ' + (data.error || 'Desconocido'));
            return false;
        }
        return true;
    })
    .catch(err => {
        console.error('Error saving order:', err);
        alert('Error de conexión al guardar el orden.');
        return false;
    });
}

function initOrdenAutomatico() {
    document.querySelectorAll('[data-ordenar-aplicar]').forEach(boton => {
        const tbodyId = boton.getAttribute('data-ordenar-aplicar');
        const tbody = document.getElementById(tbodyId);
        const select = document.querySelector(`[data-ordenar-tabla="${tbodyId}"]`);
        const filtroCat = document.querySelector(`[data-filtro-categoria="${tbodyId}"]`);
        if (!tbody || !select) return;
        const tabla = select.getAttribute('data-ordenar-guardar');

        const colator = new Intl.Collator('es', { numeric: true, sensitivity: 'base' });
        const titulo = tr => {
            const el = tr.querySelector('.table-title');
            return el ? el.textContent.trim() : '';
        };
        const porNombre = (a, b) => colator.compare(titulo(a), titulo(b));
        const comparadores = {
            'nombre': porNombre,
            'nombre-desc': (a, b) => porNombre(b, a),
            'categoria': (a, b) => colator.compare(a.dataset.categoriaNombre || '￿', b.dataset.categoriaNombre || '￿') || porNombre(a, b),
            'nuevos': (a, b) => (b.dataset.creado || '').localeCompare(a.dataset.creado || ''),
            'viejos': (a, b) => (a.dataset.creado || '').localeCompare(b.dataset.creado || ''),
            'destacados': (a, b) => Number(b.dataset.destacado || 0) - Number(a.dataset.destacado || 0),
        };

        const categoriaActual = () => (filtroCat && filtroCat.value !== '' ? filtroCat.value : null);
        const nombreCategoria = () => (filtroCat && filtroCat.selectedOptions[0] ? filtroCat.selectedOptions[0].textContent.trim() : '');
        const textoBoton = () => (categoriaActual() !== null ? `Ordenar solo «${nombreCategoria()}»` : 'Ordenar todo');

        const actualizar = () => {
            const enCategoria = categoriaActual() !== null;
            const opcionCategoria = select.querySelector('option[value="categoria"]');
            if (opcionCategoria) {
                opcionCategoria.disabled = enCategoria;
                if (enCategoria && select.value === 'categoria') select.value = 'nombre';
            }
            boton.textContent = textoBoton();
            boton.disabled = !select.value;
        };
        select.addEventListener('change', actualizar);
        if (filtroCat) filtroCat.addEventListener('change', actualizar);
        actualizar();

        boton.addEventListener('click', () => {
            const comparar = comparadores[select.value];
            if (!comparar) return;

            const cat = categoriaActual();
            const filas = [...tbody.querySelectorAll('tr[data-id]')];
            const alcance = cat === null ? filas : filas.filter(tr => tr.getAttribute('data-categoria-id') === cat);
            if (alcance.length < 2) return;

            const criterio = select.selectedOptions[0].textContent.trim();
            const pregunta = cat === null
                ? `¿Ordenar los ${filas.length} productos por "${criterio}"?\n\nReemplaza el orden actual. Después podés seguir ajustándolo arrastrando.`
                : `¿Ordenar los ${alcance.length} productos de "${nombreCategoria()}" por "${criterio}"?\n\nLas demás categorías no se mueven. Después podés seguir ajustándolo arrastrando.`;
            if (!confirm(pregunta)) return;

            const ordenadas = [...alcance].sort(comparar);
            let k = 0;
            const nuevas = cat === null
                ? ordenadas
                : filas.map(tr => (tr.getAttribute('data-categoria-id') === cat ? ordenadas[k++] : tr));

            nuevas.forEach(tr => tbody.appendChild(tr));
            tbody.dispatchEvent(new Event('reordenado'));

            boton.disabled = true;
            boton.textContent = 'Guardando…';
            guardarOrden(tabla, nuevas.map(tr => tr.getAttribute('data-id'))).then(ok => {
                boton.textContent = ok ? '✓ Orden guardado' : textoBoton();
                setTimeout(actualizar, ok ? 1800 : 0);
            });
        });
    });
}

function initMoverSeleccion() {
    document.querySelectorAll('[data-mover-seleccion]').forEach(boton => {
        const tbody = document.getElementById(boton.getAttribute('data-mover-tabla'));
        if (!tbody) return;
        const tabla = boton.getAttribute('data-mover-guardar');
        const destino = boton.getAttribute('data-mover-seleccion');
        const filtroCat = document.querySelector(`[data-filtro-categoria="${tbody.id}"]`);

        boton.addEventListener('click', () => {
            const cat = filtroCat && filtroCat.value !== '' ? filtroCat.value : null;
            const filas = [...tbody.querySelectorAll('tr[data-id]')];
            const enAlcance = tr => cat === null || tr.getAttribute('data-categoria-id') === cat;
            const marcada = tr => {
                const casilla = tr.querySelector('input[name="items[]"]');
                return !!(casilla && casilla.checked && tr.style.display !== 'none');
            };

            const alcance = filas.filter(enAlcance);
            const grupo = alcance.filter(marcada);
            if (!grupo.length) return;
            const resto = alcance.filter(tr => !marcada(tr));
            const ordenadas = destino === 'principio' ? [...grupo, ...resto] : [...resto, ...grupo];

            let k = 0;
            const nuevas = filas.map(tr => (enAlcance(tr) ? ordenadas[k++] : tr));
            nuevas.forEach(tr => tbody.appendChild(tr));
            tbody.dispatchEvent(new Event('reordenado'));

            const texto = boton.textContent;
            boton.disabled = true;
            guardarOrden(tabla, nuevas.map(tr => tr.getAttribute('data-id'))).then(ok => {
                boton.textContent = ok ? '✓ Movidos' : texto;
                setTimeout(() => { boton.textContent = texto; boton.disabled = false; }, ok ? 1500 : 0);
            });
        });
    });
}

function initPaginacion(tbodyId, porPagina = 30) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;
    const wrapper = tbody.closest('.admin-table-wrapper');
    if (!wrapper) return;

    const nav = document.createElement('nav');
    nav.className = 'admin-paginacion';
    nav.setAttribute('aria-label', 'Páginas');
    wrapper.after(nav);

    const clave = 'admin_pagina_' + tbodyId;
    let pagina = 1;
    try { pagina = parseInt(sessionStorage.getItem(clave), 10) || 1; } catch (e) {  }

    const filtradas = () => [...tbody.querySelectorAll('tr[data-id]')].filter(tr => tr.style.display !== 'none');

    const pedida = new URLSearchParams(window.location.search).get('editar');
    if (pedida) {
        const i = filtradas().findIndex(tr => tr.getAttribute('data-id') === pedida);
        if (i >= 0) pagina = Math.floor(i / porPagina) + 1;
    }

    const boton = (texto, destino, opciones = {}) => {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'admin-pagina' + (opciones.actual ? ' is-actual' : '');
        b.textContent = texto;
        b.disabled = !!opciones.desactivado;
        if (opciones.actual) b.setAttribute('aria-current', 'page');
        if (opciones.etiqueta) b.setAttribute('aria-label', opciones.etiqueta);
        b.addEventListener('click', () => {
            pagina = destino;
            render();
            wrapper.scrollIntoView({ block: 'start' });
        });
        return b;
    };

    const render = () => {
        const filas = filtradas();
        const total = Math.max(1, Math.ceil(filas.length / porPagina));
        pagina = Math.min(Math.max(1, pagina), total);
        try { sessionStorage.setItem(clave, pagina); } catch (e) {  }

        tbody.querySelectorAll('tr[data-id]').forEach(tr => tr.classList.remove('fuera-de-pagina'));
        filas.forEach((tr, i) => {
            if (Math.floor(i / porPagina) + 1 !== pagina) tr.classList.add('fuera-de-pagina');
        });

        nav.replaceChildren();
        nav.hidden = total === 1;
        if (total > 1) {
            const desde = (pagina - 1) * porPagina + 1;
            const hasta = Math.min(pagina * porPagina, filas.length);
            const info = document.createElement('span');
            info.className = 'admin-paginacion-info';
            info.textContent = `${desde}–${hasta} de ${filas.length}`;

            const numeros = document.createElement('div');
            numeros.className = 'admin-paginacion-numeros';
            numeros.append(boton('‹', pagina - 1, { desactivado: pagina === 1, etiqueta: 'Página anterior' }));
            for (let n = 1; n <= total; n++) {
                if (n === 1 || n === total || Math.abs(n - pagina) <= 1) {
                    numeros.append(boton(String(n), n, { actual: n === pagina }));
                } else if (Math.abs(n - pagina) === 2) {
                    const puntos = document.createElement('span');
                    puntos.className = 'admin-paginacion-puntos';
                    puntos.textContent = '…';
                    numeros.append(puntos);
                }
            }
            numeros.append(boton('›', pagina + 1, { desactivado: pagina === total, etiqueta: 'Página siguiente' }));
            nav.append(info, numeros);
        }
        tbody.dispatchEvent(new Event('paginacion'));
    };

    document.querySelectorAll(`[data-buscar-tabla="${tbodyId}"], [data-filtro-categoria="${tbodyId}"]`).forEach(el => {
        el.addEventListener(el.tagName === 'SELECT' ? 'change' : 'input', () => {
            pagina = 1;
            render();
        });
    });
    tbody.addEventListener('reordenado', () => {
        pagina = 1;
        render();
    });

    render();
}

function initSlugFichas() {
    document.querySelectorAll('input[data-slug-desde]').forEach(slug => {
        const form = slug.form;
        const titulo = form && form.querySelector(`[name="${slug.dataset.slugDesde}"]`);
        const id = form && form.querySelector('input[name="id"]');
        if (!titulo) return;

        titulo.addEventListener('input', () => {
            const esNueva = !id || !id.value;
            if (esNueva && (slug.value === '' || slug.value === slug.dataset.auto)) {
                slug.value = slug.dataset.auto = generarSlug(titulo.value);
            }
        });
        slug.addEventListener('blur', () => { slug.value = generarSlug(slug.value); });
    });
}

function abrirFichaPedida() {
    const id = new URLSearchParams(window.location.search).get('editar');
    if (!id || !/^\d+$/.test(id)) return;
    const fila = document.querySelector(`tr[data-id="${id}"]`);
    const editar = fila && fila.querySelector('[onclick^="editarItem"]');
    if (!editar) return;
    fila.scrollIntoView({ block: 'center' });
    editar.click();
}
