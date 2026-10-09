(() => {
    const overlay = document.getElementById('modal-editor-portada');
    if (!overlay) return;

    const MOVIL_ANCHO = 750;
    const MOVIL_ALTO = 1210;
    const PROPORCION_MOVIL = MOVIL_ANCHO / MOVIL_ALTO;
    const VISTA_ANCHO_MAX = 900;
    const RECORTE_MINIMO = 0.15;
    const ANCHO_RECOMENDADO = 1600;

    const vista = overlay.querySelector('[data-editor-vista]');
    const zona = overlay.querySelector('.editor-foto');
    const celular = overlay.querySelector('[data-editor-celular]');
    const cajaRecorte = overlay.querySelector('[data-editor-recorte]');
    const marco = overlay.querySelector('[data-editor-marco]');
    const estado = overlay.querySelector('[data-editor-estado]');
    const ayuda = overlay.querySelector('[data-editor-ayuda]');
    const aviso = overlay.querySelector('[data-editor-resolucion]');
    const selectorProporcion = overlay.querySelector('[data-editor-proporcion]');
    const guardar = overlay.querySelector('[data-editor-guardar]');
    const deslizadores = [...overlay.querySelectorAll('[data-editor-ajuste]')];
    const botonesModo = [...overlay.querySelectorAll('[data-editor-modo]')];

    const AYUDAS = {
        recorte: 'Mové el recuadro o achicalo desde las esquinas: lo de adentro es la foto que se ve en computadora.',
        celular: 'Arrastrá el recuadro para elegir qué parte del recorte se ve en celulares.',
    };

    const iniciales = () => ({
        brillo: 0, contraste: 0, saturacion: 0,
        foco_x: 0.5, foco_y: 0.5,
        recorte: { x: 0, y: 0, w: 1, h: 1 },
        proporcion: 0,
    });

    let foto = null;
    let imagen = null;
    let base = null;
    let ajustes = iniciales();

    const aplicar = (datos, a) => {
        const b = 1 + a.brillo / 100;
        const c = 1 + a.contraste / 100;
        const s = 1 + a.saturacion / 100;
        const p = datos.data;
        for (let i = 0; i < p.length; i += 4) {
            const r = ((p[i] * b) - 128) * c + 128;
            const g = ((p[i + 1] * b) - 128) * c + 128;
            const v = ((p[i + 2] * b) - 128) * c + 128;
            const lum = 0.299 * r + 0.587 * g + 0.114 * v;
            p[i] = lum + (r - lum) * s;
            p[i + 1] = lum + (g - lum) * s;
            p[i + 2] = lum + (v - lum) * s;
        }
        return datos;
    };

    const enPixeles = (ancho, alto) => {
        const r = ajustes.recorte;
        return { x: r.x * ancho, y: r.y * alto, w: r.w * ancho, h: r.h * alto };
    };

    const zonaMovil = caja => {
        let w, h;
        if (caja.w / caja.h > PROPORCION_MOVIL) { h = caja.h; w = h * PROPORCION_MOVIL; }
        else { w = caja.w; h = w / PROPORCION_MOVIL; }
        const x = caja.x + Math.min(Math.max(ajustes.foco_x * caja.w - w / 2, 0), caja.w - w);
        const y = caja.y + Math.min(Math.max(ajustes.foco_y * caja.h - h / 2, 0), caja.h - h);
        return { x, y, w, h };
    };

    const ubicar = (el, r, escala) => Object.assign(el.style, {
        left: r.x * escala + 'px', top: r.y * escala + 'px',
        width: r.w * escala + 'px', height: r.h * escala + 'px',
    });

    const dibujarMarcos = () => {
        const escala = vista.clientWidth / vista.width;
        const caja = enPixeles(vista.width, vista.height);
        const movil = zonaMovil(caja);
        ubicar(cajaRecorte, caja, escala);
        ubicar(marco, movil, escala);
        celular.getContext('2d').drawImage(vista, movil.x, movil.y, movil.w, movil.h, 0, 0, celular.width, celular.height);

        if (imagen) {
            const ancho = Math.round(ajustes.recorte.w * imagen.naturalWidth);
            aviso.hidden = ancho >= ANCHO_RECOMENDADO;
            aviso.textContent = `El recorte queda de ${ancho} px de ancho: en pantallas grandes se puede ver un poco borroso.`;
        }
    };

    const actualizar = () => {
        if (!base) return;
        const copia = new ImageData(new Uint8ClampedArray(base.data), base.width, base.height);
        vista.getContext('2d').putImageData(aplicar(copia, ajustes), 0, 0);
        dibujarMarcos();
    };

    const cambiarModo = modo => {
        zona.dataset.modo = modo;
        botonesModo.forEach(b => {
            const activo = b.dataset.editorModo === modo;
            b.classList.toggle('is-activo', activo);
            b.setAttribute('aria-selected', activo ? 'true' : 'false');
        });
        ayuda.textContent = AYUDAS[modo];
    };

    const sincronizarControles = () => {
        deslizadores.forEach(d => {
            d.value = ajustes[d.dataset.editorAjuste];
            overlay.querySelector(`[data-editor-valor="${d.dataset.editorAjuste}"]`).textContent = d.value;
        });
        const opcion = [...selectorProporcion.options].find(o => Math.abs(Number(o.value) - ajustes.proporcion) < 0.001);
        selectorProporcion.value = opcion ? opcion.value : '0';
    };

    const abrir = datos => {
        foto = datos;
        const guardados = datos.ajustes || {};
        ajustes = { ...iniciales(), ...guardados, recorte: { ...iniciales().recorte, ...(guardados.recorte || {}) } };
        sincronizarControles();
        cambiarModo('recorte');
        base = null;
        imagen = null;
        guardar.disabled = true;
        zona.classList.add('is-cargando');
        aviso.hidden = true;
        estado.hidden = false;
        estado.textContent = 'Cargando la foto…';
        openModal('modal-editor-portada');

        const img = new Image();
        img.onload = () => {
            if (foto !== datos) return;
            imagen = img;
            const escala = Math.min(1, VISTA_ANCHO_MAX / img.naturalWidth);
            vista.width = Math.round(img.naturalWidth * escala);
            vista.height = Math.round(img.naturalHeight * escala);
            const ctx = vista.getContext('2d', { willReadFrequently: true });
            ctx.drawImage(img, 0, 0, vista.width, vista.height);
            base = ctx.getImageData(0, 0, vista.width, vista.height);
            estado.hidden = true;
            zona.classList.remove('is-cargando');
            guardar.disabled = false;
            requestAnimationFrame(actualizar);
        };
        img.onerror = () => { estado.textContent = 'No se pudo cargar la foto original.'; };
        img.src = datos.original;
    };

    document.querySelectorAll('[data-editar-portada]').forEach(btn => {
        btn.addEventListener('click', () => abrir(JSON.parse(btn.dataset.editarPortada)));
    });

    botonesModo.forEach(b => b.addEventListener('click', () => cambiarModo(b.dataset.editorModo)));

    deslizadores.forEach(d => d.addEventListener('input', () => {
        ajustes[d.dataset.editorAjuste] = Number(d.value);
        overlay.querySelector(`[data-editor-valor="${d.dataset.editorAjuste}"]`).textContent = d.value;
        actualizar();
    }));

    selectorProporcion.addEventListener('change', () => {
        ajustes.proporcion = Number(selectorProporcion.value);
        if (ajustes.proporcion > 0 && base) {
            const W = vista.width, H = vista.height;
            const c = enPixeles(W, H);
            let w = c.w, h = c.h;
            if (w / h > ajustes.proporcion) w = h * ajustes.proporcion;
            else h = w / ajustes.proporcion;
            ajustes.recorte = { x: (c.x + (c.w - w) / 2) / W, y: (c.y + (c.h - h) / 2) / H, w: w / W, h: h / H };
            dibujarMarcos();
        }
    });

    overlay.querySelector('[data-editor-restablecer]').addEventListener('click', () => {
        ajustes = iniciales();
        sincronizarControles();
        actualizar();
    });

    window.addEventListener('resize', () => { if (base) dibujarMarcos(); });

    const arrastrar = (el, alEmpezar, mover) => {
        el.addEventListener('pointerdown', e => {
            if (!base) return;
            e.preventDefault();
            e.stopPropagation();
            el.setPointerCapture(e.pointerId);
            const escala = vista.width / vista.getBoundingClientRect().width;
            const inicio = { x: e.clientX, y: e.clientY, estado: alEmpezar(e) };
            const alMover = ev => {
                mover((ev.clientX - inicio.x) * escala, (ev.clientY - inicio.y) * escala, inicio.estado);
                dibujarMarcos();
            };
            const soltar = () => {
                el.removeEventListener('pointermove', alMover);
                el.removeEventListener('pointerup', soltar);
                el.removeEventListener('pointercancel', soltar);
            };
            el.addEventListener('pointermove', alMover);
            el.addEventListener('pointerup', soltar);
            el.addEventListener('pointercancel', soltar);
        });
    };

    const limitar = (v, min, max) => Math.min(max, Math.max(min, v));

    arrastrar(cajaRecorte, () => ({ ...ajustes.recorte }), (dx, dy, r0) => {
        ajustes.recorte.x = limitar(r0.x + dx / vista.width, 0, 1 - r0.w);
        ajustes.recorte.y = limitar(r0.y + dy / vista.height, 0, 1 - r0.h);
    });

    cajaRecorte.querySelectorAll('[data-esquina]').forEach(esquina => {
        const lado = esquina.dataset.esquina;
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

    arrastrar(marco, () => {
        const caja = enPixeles(vista.width, vista.height);
        return { caja, movil: zonaMovil(caja) };
    }, (dx, dy, { caja, movil }) => {
        const cx = (movil.x + movil.w / 2 + dx - caja.x) / caja.w;
        const cy = (movil.y + movil.h / 2 + dy - caja.y) / caja.h;
        const mx = (movil.w / 2) / caja.w, my = (movil.h / 2) / caja.h;
        ajustes.foco_x = limitar(cx, mx, 1 - mx);
        ajustes.foco_y = limitar(cy, my, 1 - my);
    });

    const aBlob = (canvas, calidad) => new Promise(listo => canvas.toBlob(listo, 'image/jpeg', calidad));

    guardar.addEventListener('click', async () => {
        if (!imagen || !foto) return;
        guardar.disabled = true;
        const textoBoton = guardar.textContent;
        guardar.textContent = 'Guardando…';

        try {
            const c = enPixeles(imagen.naturalWidth, imagen.naturalHeight);
            const grande = document.createElement('canvas');
            grande.width = Math.max(1, Math.round(c.w));
            grande.height = Math.max(1, Math.round(c.h));
            const ctx = grande.getContext('2d', { willReadFrequently: true });
            ctx.drawImage(imagen, c.x, c.y, c.w, c.h, 0, 0, grande.width, grande.height);
            ctx.putImageData(aplicar(ctx.getImageData(0, 0, grande.width, grande.height), ajustes), 0, 0);

            const m = zonaMovil({ x: 0, y: 0, w: grande.width, h: grande.height });
            const movil = document.createElement('canvas');
            movil.width = MOVIL_ANCHO;
            movil.height = MOVIL_ALTO;
            movil.getContext('2d').drawImage(grande, m.x, m.y, m.w, m.h, 0, 0, MOVIL_ANCHO, MOVIL_ALTO);

            const datos = new FormData();
            datos.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
            datos.append('id', foto.id);
            datos.append('ajustes', JSON.stringify(ajustes));
            datos.append('grande', await aBlob(grande, 0.92), 'grande.jpg');
            datos.append('movil', await aBlob(movil, 0.92), 'movil.jpg');

            const res = await fetch('actions/editar_portada.php', { method: 'POST', body: datos });
            const json = await res.json().catch(() => ({ ok: false, error: 'El servidor no respondió bien.' }));
            if (!json.ok) throw new Error(json.error || 'No se pudo guardar.');
            window.location.reload();
        } catch (err) {
            alert(err.message);
            guardar.disabled = false;
            guardar.textContent = textoBoton;
        }
    });
})();
