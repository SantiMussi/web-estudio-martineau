/*
 * Editor de fotos del panel, para no tener que pasar por Photoshop antes de subir una
 * foto a un producto o proyecto (el de la portada es otro: admin/portada-editor.js).
 *
 * EditorImagen.abrir(fuente, opciones) abre el editor sobre un File o una URL y devuelve
 * una promesa con la foto editada como File JPEG, o null si se canceló o no se cambió nada.
 *   opciones.boton:   texto del botón de confirmar (por defecto "Aplicar")
 *   opciones.siempre: true para devolver la foto aunque no se haya tocado nada
 *
 * - Girar 90°, espejar y enderezar (con zoom automático para que no queden esquinas vacías).
 * - Recortar con proporción libre o fija (4:5 es la de las fotos del catálogo).
 * - Brillo, contraste, saturación y calidez, con vista previa en vivo.
 * - La foto final sale en tamaño completo, con el lado más largo hasta LADO_MAX.
 */
window.EditorImagen = (() => {
    const VISTA_MAX = 900;          // la vista previa se procesa achicada, para que sea fluida
    const LADO_MAX = 2400;          // lado más largo de la foto que se guarda
    const LADO_RECOMENDADO = 1000;  // por debajo de esto, en la ficha se puede ver borrosa
    const RECORTE_MINIMO = 0.08;    // el recorte no puede ser menor al 8% de la foto
    const CALIDAD = 0.9;

    const PROPORCIONES = [
        ['0', 'Libre'],
        ['0.8', '4:5 (como en el catálogo)'],
        ['1', '1:1 (cuadrada)'],
        ['0.75', '3:4 (vertical)'],
        ['1.3333', '4:3 (horizontal)'],
        ['1.5', '3:2 (como las fotos)'],
        ['1.7778', '16:9 (pantalla ancha)'],
    ];

    const DESLIZADORES = [
        ['enderezar', 'Enderezar', -15, 15, 0.5, '°'],
        ['brillo', 'Brillo', -50, 50, 1, ''],
        ['contraste', 'Contraste', -50, 50, 1, ''],
        ['saturacion', 'Saturación', -100, 50, 1, ''],
        ['calidez', 'Calidez', -50, 50, 1, ''],
    ];

    const iniciales = () => ({
        giro: 0,            // cuartos de vuelta a la derecha (0–3)
        espejo: false,
        enderezar: 0,       // grados
        brillo: 0, contraste: 0, saturacion: 0, calidez: 0,
        recorte: { x: 0, y: 0, w: 1, h: 1 },   // fracciones de la foto ya girada
        proporcion: 0,
    });

    const icono = d => `<svg viewBox="0 0 24 24" aria-hidden="true">${d}</svg>`;

    let el = null;          // referencias a los elementos del modal (se arma al abrirlo la primera vez)
    let imagen = null;      // la foto cargada
    let base = null;        // píxeles de la vista previa ya girada, sin ajustes de color
    let ajustes = iniciales();
    let terminar = null;    // resuelve la promesa de abrir()
    let opciones = {};
    let urlTemporal = null;

    const limitar = (v, min, max) => Math.min(max, Math.max(min, v));

    // ─── Geometría ───

    // Tamaño de la foto ya girada (en píxeles de la original)
    const marco = () => {
        const W = imagen.naturalWidth, H = imagen.naturalHeight;
        return ajustes.giro % 2 ? { w: H, h: W } : { w: W, h: H };
    };

    // Dibuja la foto girada, espejada y enderezada ocupando el marco desde (0, 0).
    // Al enderezar se agranda lo justo para que no queden esquinas vacías.
    const dibujarGirada = ctx => {
        const W = imagen.naturalWidth, H = imagen.naturalHeight;
        const F = marco();
        const t = Math.abs(ajustes.enderezar) * Math.PI / 180;
        const zoom = Math.max(
            (F.w * Math.cos(t) + F.h * Math.sin(t)) / F.w,
            (F.w * Math.sin(t) + F.h * Math.cos(t)) / F.h
        );
        const espejo = ajustes.espejo ? -1 : 1;
        ctx.translate(F.w / 2, F.h / 2);
        // Espejado primero (en pantalla) y el giro invertido: así girar y enderezar van
        // siempre para el mismo lado, esté espejada o no
        ctx.scale(espejo, 1);
        ctx.rotate(espejo * (ajustes.giro * 90 + ajustes.enderezar) * Math.PI / 180);
        ctx.scale(zoom, zoom);
        ctx.drawImage(imagen, -W / 2, -H / 2);
    };

    // El recorte en píxeles de un lienzo de ancho × alto
    const enPixeles = (ancho, alto) => {
        const r = ajustes.recorte;
        return { x: r.x * ancho, y: r.y * alto, w: r.w * ancho, h: r.h * alto };
    };

    // El recorte más grande con la proporción elegida, centrado donde estaba el actual
    const ajustarProporcion = () => {
        if (!(ajustes.proporcion > 0)) return;
        const F = marco();
        const c = enPixeles(F.w, F.h);
        let w = F.w, h = F.h;
        if (w / h > ajustes.proporcion) w = h * ajustes.proporcion;
        else h = w / ajustes.proporcion;
        const x = limitar(c.x + c.w / 2 - w / 2, 0, F.w - w);
        const y = limitar(c.y + c.h / 2 - h / 2, 0, F.h - h);
        ajustes.recorte = { x: x / F.w, y: y / F.h, w: w / F.w, h: h / F.h };
    };

    // ─── Color ───

    const sinColor = a => !a.brillo && !a.contraste && !a.saturacion && !a.calidez;

    // Brillo, contraste, saturación y calidez sobre los píxeles (igual en la vista previa y al guardar)
    const aplicar = (datos, a) => {
        if (sinColor(a)) return datos;
        const b = 1 + a.brillo / 100;
        const c = 1 + a.contraste / 100;
        const s = 1 + a.saturacion / 100;
        const rojo = b * (1 + a.calidez / 200);
        const azul = b * (1 - a.calidez / 200);
        const p = datos.data;
        for (let i = 0; i < p.length; i += 4) {
            const r = (p[i] * rojo - 128) * c + 128;
            const g = (p[i + 1] * b - 128) * c + 128;
            const v = (p[i + 2] * azul - 128) * c + 128;
            const lum = 0.299 * r + 0.587 * g + 0.114 * v;
            p[i] = lum + (r - lum) * s;          // Uint8ClampedArray: recorta solo a 0–255
            p[i + 1] = lum + (g - lum) * s;
            p[i + 2] = lum + (v - lum) * s;
        }
        return datos;
    };

    // ─── Vista previa ───

    const ubicar = (nodo, r, escala) => Object.assign(nodo.style, {
        left: r.x * escala + 'px', top: r.y * escala + 'px',
        width: r.w * escala + 'px', height: r.h * escala + 'px',
    });

    const dibujarRecorte = () => {
        const { vista } = el;
        ubicar(el.recorte, enPixeles(vista.width, vista.height), vista.clientWidth / vista.width);

        const F = marco();
        const c = enPixeles(F.w, F.h);
        const k = Math.min(1, LADO_MAX / Math.max(c.w, c.h));
        const ancho = Math.round(c.w * k), alto = Math.round(c.h * k);
        el.tamano.textContent = `La foto queda de ${ancho} × ${alto} px.`;
        el.aviso.hidden = Math.max(ancho, alto) >= LADO_RECOMENDADO;
    };

    const pintar = () => {
        if (!base) return;
        const copia = new ImageData(new Uint8ClampedArray(base.data), base.width, base.height);
        el.vista.getContext('2d').putImageData(aplicar(copia, ajustes), 0, 0);
        dibujarRecorte();
    };

    // Vuelve a armar la base cuando cambia el giro, el espejo o el enderezado
    const rehacerBase = () => {
        const F = marco();
        const e = Math.min(1, VISTA_MAX / Math.max(F.w, F.h));
        const { vista } = el;
        vista.width = Math.max(1, Math.round(F.w * e));
        vista.height = Math.max(1, Math.round(F.h * e));
        const ctx = vista.getContext('2d', { willReadFrequently: true });
        ctx.setTransform(1, 0, 0, 1, 0, 0);
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, vista.width, vista.height);
        ctx.setTransform(vista.width / F.w, 0, 0, vista.height / F.h, 0, 0);
        dibujarGirada(ctx);
        ctx.setTransform(1, 0, 0, 1, 0, 0);
        base = ctx.getImageData(0, 0, vista.width, vista.height);
        pintar();
    };

    const sincronizarControles = () => {
        DESLIZADORES.forEach(([clave, , , , , unidad]) => {
            el.deslizadores[clave].value = ajustes[clave];
            el.valores[clave].textContent = ajustes[clave] + unidad;
        });
        const opcion = [...el.proporcion.options].find(o => Math.abs(Number(o.value) - ajustes.proporcion) < 0.001);
        el.proporcion.value = opcion ? opcion.value : '0';
    };

    // ─── Modal ───

    const armar = () => {
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.id = 'modal-editor-imagen';
        overlay.innerHTML = `
            <div class="modal editor-modal" role="dialog" aria-modal="true" aria-labelledby="editor-imagen-titulo">
                <div class="modal-header">
                    <h2 id="editor-imagen-titulo">Editar foto</h2>
                    <button type="button" class="modal-close" data-cancelar aria-label="Cerrar">
                        ${icono('<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>')}
                    </button>
                </div>
                <div class="modal-body editor-cuerpo">
                    <div class="editor-lienzo">
                        <div class="editor-herramientas">
                            <button type="button" class="editor-herramienta" data-girar="-1" title="Girar a la izquierda">
                                ${icono('<path d="M3 4v6h6"/><path d="M3.5 10A9 9 0 1 1 6 17.7"/>')}<span>Girar</span>
                            </button>
                            <button type="button" class="editor-herramienta" data-girar="1" title="Girar a la derecha">
                                ${icono('<path d="M21 4v6h-6"/><path d="M20.5 10A9 9 0 1 0 18 17.7"/>')}<span>Girar</span>
                            </button>
                            <button type="button" class="editor-herramienta" data-espejar title="Espejar (dar vuelta de izquierda a derecha)">
                                ${icono('<path d="M12 3v18"/><path d="M8 7L3 17h5z"/><path d="M16 7l5 10h-5z"/>')}<span>Espejar</span>
                            </button>
                        </div>
                        <div class="editor-foto editor-foto-libre is-cargando" data-modo="recorte">
                            <canvas data-vista></canvas>
                            <div class="editor-recorte" data-recorte>
                                <i data-esquina="nw"></i><i data-esquina="ne"></i><i data-esquina="sw"></i><i data-esquina="se"></i>
                            </div>
                        </div>
                        <p class="form-ayuda">Mové el recuadro o achicalo desde las esquinas: lo de adentro es lo que queda.</p>
                        <p class="editor-cargando" data-estado>Cargando la foto…</p>
                    </div>

                    <div class="editor-controles">
                        <label class="editor-control">
                            <span>Proporción del recorte</span>
                            <select class="form-control" data-proporcion>
                                ${PROPORCIONES.map(([v, t]) => `<option value="${v}">${t}</option>`).join('')}
                            </select>
                        </label>
                        <p class="form-ayuda" data-tamano></p>
                        <p class="form-ayuda editor-aviso" data-aviso hidden>Queda chica: en la ficha se puede ver un poco borrosa.</p>

                        ${DESLIZADORES.map(([clave, nombre, min, max, paso]) => `
                            <label class="editor-control">
                                <span>${nombre} <output data-valor="${clave}">0</output></span>
                                <input type="range" min="${min}" max="${max}" step="${paso}" value="0" data-ajuste="${clave}">
                            </label>`).join('')}

                        <button type="button" class="btn-admin btn-secondary btn-sm" data-restablecer>Restablecer</button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-admin btn-secondary" data-cancelar>Cancelar</button>
                    <button type="button" class="btn-admin btn-primary" data-aplicar>Aplicar</button>
                </div>
            </div>`;
        document.body.appendChild(overlay);

        const q = s => overlay.querySelector(s);
        el = {
            overlay,
            vista: q('[data-vista]'),
            zona: q('.editor-foto'),
            recorte: q('[data-recorte]'),
            estado: q('[data-estado]'),
            tamano: q('[data-tamano]'),
            aviso: q('[data-aviso]'),
            proporcion: q('[data-proporcion]'),
            aplicar: q('[data-aplicar]'),
            deslizadores: {},
            valores: {},
        };
        DESLIZADORES.forEach(([clave, , , , , unidad]) => {
            const d = q(`[data-ajuste="${clave}"]`);
            el.deslizadores[clave] = d;
            el.valores[clave] = q(`[data-valor="${clave}"]`);
            d.addEventListener('input', () => {
                ajustes[clave] = Number(d.value);
                el.valores[clave].textContent = d.value + unidad;
                if (!base) return;
                if (clave === 'enderezar') rehacerBase();
                else pintar();
            });
        });

        overlay.querySelectorAll('[data-girar]').forEach(b => b.addEventListener('click', () => {
            if (!base) return;
            ajustes.giro = (ajustes.giro + Number(b.dataset.girar) + 4) % 4;
            ajustes.recorte = { x: 0, y: 0, w: 1, h: 1 };   // la foto cambió de forma: se recorta de nuevo
            ajustarProporcion();
            rehacerBase();
        }));

        q('[data-espejar]').addEventListener('click', () => {
            if (!base) return;
            ajustes.espejo = !ajustes.espejo;
            // El recorte queda sobre la misma parte de la foto
            ajustes.recorte.x = 1 - ajustes.recorte.x - ajustes.recorte.w;
            rehacerBase();
        });

        el.proporcion.addEventListener('change', () => {
            ajustes.proporcion = Number(el.proporcion.value);
            if (!base) return;
            ajustarProporcion();
            dibujarRecorte();
        });

        q('[data-restablecer]').addEventListener('click', () => {
            if (!base) return;
            ajustes = iniciales();
            sincronizarControles();
            rehacerBase();
        });

        overlay.querySelectorAll('[data-cancelar]').forEach(b => b.addEventListener('click', () => cerrar(null)));
        el.aplicar.addEventListener('click', confirmar);

        // Escape cierra solo el editor, no el formulario del producto que quedó abajo
        window.addEventListener('keydown', e => {
            if (e.key === 'Escape' && overlay.classList.contains('active')) {
                e.stopPropagation();
                cerrar(null);
            }
        }, true);

        window.addEventListener('resize', () => { if (base && overlay.classList.contains('active')) dibujarRecorte(); });

        initArrastre();
    };

    // Arrastre genérico: llama a mover(dx, dy) con el desplazamiento en píxeles de la vista
    const arrastrar = (nodo, alEmpezar, mover) => {
        nodo.addEventListener('pointerdown', e => {
            if (!base) return;
            e.preventDefault();
            e.stopPropagation();
            nodo.setPointerCapture(e.pointerId);
            const escala = el.vista.width / el.vista.getBoundingClientRect().width;
            const inicio = { x: e.clientX, y: e.clientY, estado: alEmpezar() };
            const alMover = ev => {
                mover((ev.clientX - inicio.x) * escala, (ev.clientY - inicio.y) * escala, inicio.estado);
                dibujarRecorte();
            };
            const soltar = () => {
                nodo.removeEventListener('pointermove', alMover);
                nodo.removeEventListener('pointerup', soltar);
                nodo.removeEventListener('pointercancel', soltar);
            };
            nodo.addEventListener('pointermove', alMover);
            nodo.addEventListener('pointerup', soltar);
            nodo.addEventListener('pointercancel', soltar);
        });
    };

    const initArrastre = () => {
        const { vista, recorte } = el;

        // Mover el recorte entero
        arrastrar(recorte, () => ({ ...ajustes.recorte }), (dx, dy, r0) => {
            ajustes.recorte.x = limitar(r0.x + dx / vista.width, 0, 1 - r0.w);
            ajustes.recorte.y = limitar(r0.y + dy / vista.height, 0, 1 - r0.h);
        });

        // Cambiar el tamaño desde una esquina (la opuesta queda fija)
        recorte.querySelectorAll('[data-esquina]').forEach(esquina => {
            const lado = esquina.dataset.esquina;   // nw, ne, sw, se
            arrastrar(esquina, () => enPixeles(vista.width, vista.height), (dx, dy, c) => {
                const W = vista.width, H = vista.height;
                const izquierda = lado.includes('w'), arriba = lado.includes('n');
                const fijoX = izquierda ? c.x + c.w : c.x;
                const fijoY = arriba ? c.y + c.h : c.y;
                const px = limitar((izquierda ? c.x : c.x + c.w) + dx, 0, W);
                const py = limitar((arriba ? c.y : c.y + c.h) + dy, 0, H);
                let w = Math.abs(px - fijoX);
                let h = Math.abs(py - fijoY);
                if (ajustes.proporcion > 0) {
                    // La proporción es de la foto en píxeles; la vista tiene la misma forma
                    if (w / h > ajustes.proporcion) w = h * ajustes.proporcion;
                    else h = w / ajustes.proporcion;
                }
                if (w < W * RECORTE_MINIMO || h < H * RECORTE_MINIMO) return;
                ajustes.recorte = {
                    x: (izquierda ? fijoX - w : fijoX) / W,
                    y: (arriba ? fijoY - h : fijoY) / H,
                    w: w / W,
                    h: h / H,
                };
            });
        });
    };

    const sinCambios = () => {
        const a = ajustes, r = a.recorte;
        return !a.giro && !a.espejo && !a.enderezar && sinColor(a)
            && r.x === 0 && r.y === 0 && r.w === 1 && r.h === 1;
    };

    // La foto final: el recorte en tamaño completo (hasta LADO_MAX), con todos los ajustes
    const exportar = () => {
        const F = marco();
        const c = enPixeles(F.w, F.h);
        const k = Math.min(1, LADO_MAX / Math.max(c.w, c.h));
        const lienzo = document.createElement('canvas');
        lienzo.width = Math.max(1, Math.round(c.w * k));
        lienzo.height = Math.max(1, Math.round(c.h * k));
        const ctx = lienzo.getContext('2d', { willReadFrequently: true });
        ctx.fillStyle = '#fff';                 // las PNG con transparencia quedan sobre blanco
        ctx.fillRect(0, 0, lienzo.width, lienzo.height);
        ctx.imageSmoothingQuality = 'high';
        ctx.setTransform(k, 0, 0, k, -c.x * k, -c.y * k);
        dibujarGirada(ctx);
        ctx.setTransform(1, 0, 0, 1, 0, 0);
        if (!sinColor(ajustes)) {
            ctx.putImageData(aplicar(ctx.getImageData(0, 0, lienzo.width, lienzo.height), ajustes), 0, 0);
        }
        return new Promise((listo, fallo) => lienzo.toBlob(
            blob => blob ? listo(blob) : fallo(new Error('No se pudo armar la foto.')),
            'image/jpeg', CALIDAD
        ));
    };

    const confirmar = async () => {
        if (!imagen) return;
        if (sinCambios() && !opciones.siempre) { cerrar(null); return; }

        el.aplicar.disabled = true;
        const texto = el.aplicar.textContent;
        el.aplicar.textContent = 'Procesando…';
        try {
            const blob = await exportar();
            const nombre = (opciones.nombre || 'foto').replace(/\.[^.]+$/, '') + '.jpg';
            cerrar(new File([blob], nombre, { type: 'image/jpeg', lastModified: Date.now() }));
        } catch (err) {
            alert(err.message);
        } finally {
            el.aplicar.disabled = false;
            el.aplicar.textContent = texto;
        }
    };

    const cerrar = resultado => {
        if (!el) return;
        el.overlay.classList.remove('active');
        // Si el editor se abrió desde el formulario de un producto, ese sigue abierto
        if (!document.querySelector('.modal-overlay.active')) document.body.style.overflow = '';
        if (urlTemporal) { URL.revokeObjectURL(urlTemporal); urlTemporal = null; }
        imagen = null;
        base = null;
        const listo = terminar;
        terminar = null;
        if (listo) listo(resultado);
    };

    const abrir = (fuente, opc = {}) => {
        if (!el) armar();
        if (terminar) cerrar(null);   // había otra edición abierta

        opciones = { ...opc };
        if (!opciones.nombre) {
            opciones.nombre = fuente instanceof Blob
                ? fuente.name
                : decodeURIComponent(String(fuente).split('?')[0].split('/').pop());
        }
        ajustes = iniciales();
        sincronizarControles();
        el.aplicar.textContent = opciones.boton || 'Aplicar';
        el.aplicar.disabled = true;
        el.zona.classList.add('is-cargando');
        el.estado.hidden = false;
        el.estado.textContent = 'Cargando la foto…';
        el.tamano.textContent = '';
        el.aviso.hidden = true;
        el.vista.width = 1;
        el.vista.height = 1;
        el.overlay.classList.add('active');
        document.body.style.overflow = 'hidden';

        const promesa = new Promise(listo => { terminar = listo; });

        const img = new Image();
        const esta = terminar;
        img.onload = () => {
            if (terminar !== esta) return;   // se cerró o se abrió otra mientras cargaba
            imagen = img;
            el.estado.hidden = true;
            el.zona.classList.remove('is-cargando');
            el.aplicar.disabled = false;
            rehacerBase();
            // El tamaño en pantalla del lienzo se conoce recién después de pintarlo
            requestAnimationFrame(dibujarRecorte);
        };
        img.onerror = () => {
            if (terminar === esta) el.estado.textContent = 'No se pudo abrir la foto.';
        };
        if (fuente instanceof Blob) {
            urlTemporal = URL.createObjectURL(fuente);
            img.src = urlTemporal;
        } else {
            img.src = fuente;
        }
        return promesa;
    };

    return { abrir };
})();
