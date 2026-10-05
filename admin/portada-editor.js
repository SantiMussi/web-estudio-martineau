/*
 * Editor básico de las fotos de la portada (admin/portada.php).
 *
 * - Brillo, contraste y saturación, con vista previa en vivo.
 * - Un recuadro que se arrastra para elegir qué parte de la foto se ve en celulares
 *   (la versión para celular es un recorte vertical de 750×1210).
 * - Siempre parte de la foto ORIGINAL: al guardar se aplica todo sobre ella en tamaño
 *   completo y se mandan las dos versiones (computadora y celular) a
 *   actions/editar_portada.php, que las guarda como WebP.
 */
(() => {
    const overlay = document.getElementById('modal-editor-portada');
    if (!overlay) return;

    const MOVIL_ANCHO = 750;
    const MOVIL_ALTO = 1210;
    const PROPORCION = MOVIL_ANCHO / MOVIL_ALTO;
    const VISTA_ANCHO_MAX = 900;   // la vista previa se procesa achicada, para que sea fluida

    const vista = overlay.querySelector('[data-editor-vista]');
    const celular = overlay.querySelector('[data-editor-celular]');
    const marco = overlay.querySelector('[data-editor-marco]');
    const estado = overlay.querySelector('[data-editor-estado]');
    const guardar = overlay.querySelector('[data-editor-guardar]');
    const deslizadores = [...overlay.querySelectorAll('[data-editor-ajuste]')];

    const AJUSTES_INICIALES = { brillo: 0, contraste: 0, saturacion: 0, foco_x: 0.5, foco_y: 0.5 };
    let foto = null;          // { id, original }
    let imagen = null;        // la original cargada
    let base = null;          // píxeles de la vista previa sin ajustes
    let ajustes = { ...AJUSTES_INICIALES };

    // Brillo, contraste y saturación sobre los píxeles (igual en la vista previa y al guardar)
    const aplicar = (datos, a) => {
        const b = 1 + a.brillo / 100;
        const c = 1 + a.contraste / 100;
        const s = 1 + a.saturacion / 100;
        const p = datos.data;
        for (let i = 0; i < p.length; i += 4) {
            let r = ((p[i] * b) - 128) * c + 128;
            let g = ((p[i + 1] * b) - 128) * c + 128;
            let v = ((p[i + 2] * b) - 128) * c + 128;
            const lum = 0.299 * r + 0.587 * g + 0.114 * v;
            p[i] = lum + (r - lum) * s;          // Uint8ClampedArray: recorta solo a 0–255
            p[i + 1] = lum + (g - lum) * s;
            p[i + 2] = lum + (v - lum) * s;
        }
        return datos;
    };

    // Zona de la foto que va al celular, en coordenadas de una imagen de ancho × alto
    const recorte = (ancho, alto) => {
        let w, h;
        if (ancho / alto > PROPORCION) { h = alto; w = alto * PROPORCION; }
        else { w = ancho; h = ancho / PROPORCION; }
        const x = Math.min(Math.max(ajustes.foco_x * ancho - w / 2, 0), ancho - w);
        const y = Math.min(Math.max(ajustes.foco_y * alto - h / 2, 0), alto - h);
        return { x, y, w, h };
    };

    const dibujarMarco = () => {
        const r = recorte(vista.width, vista.height);
        const escala = vista.clientWidth / vista.width;
        Object.assign(marco.style, {
            left: r.x * escala + 'px',
            top: r.y * escala + 'px',
            width: r.w * escala + 'px',
            height: r.h * escala + 'px',
        });
        const ctx = celular.getContext('2d');
        ctx.drawImage(vista, r.x, r.y, r.w, r.h, 0, 0, celular.width, celular.height);
    };

    const actualizar = () => {
        if (!base) return;
        const copia = new ImageData(new Uint8ClampedArray(base.data), base.width, base.height);
        vista.getContext('2d').putImageData(aplicar(copia, ajustes), 0, 0);
        dibujarMarco();
    };

    const sincronizarControles = () => {
        deslizadores.forEach(d => {
            d.value = ajustes[d.dataset.editorAjuste];
            overlay.querySelector(`[data-editor-valor="${d.dataset.editorAjuste}"]`).textContent = d.value;
        });
    };

    const abrir = datos => {
        foto = datos;
        ajustes = { ...AJUSTES_INICIALES, ...(datos.ajustes || {}) };
        sincronizarControles();
        base = null;
        imagen = null;
        guardar.disabled = true;
        marco.hidden = true;
        estado.hidden = false;
        estado.textContent = 'Cargando la foto…';
        openModal('modal-editor-portada');

        const img = new Image();
        img.onload = () => {
            if (foto !== datos) return;   // se abrió otra mientras cargaba
            imagen = img;
            const escala = Math.min(1, VISTA_ANCHO_MAX / img.naturalWidth);
            vista.width = Math.round(img.naturalWidth * escala);
            vista.height = Math.round(img.naturalHeight * escala);
            const ctx = vista.getContext('2d', { willReadFrequently: true });
            ctx.drawImage(img, 0, 0, vista.width, vista.height);
            base = ctx.getImageData(0, 0, vista.width, vista.height);
            estado.hidden = true;
            marco.hidden = false;
            guardar.disabled = false;
            // El tamaño en pantalla del lienzo se conoce recién después de pintarlo
            requestAnimationFrame(actualizar);
        };
        img.onerror = () => { estado.textContent = 'No se pudo cargar la foto original.'; };
        img.src = datos.original;
    };

    document.querySelectorAll('[data-editar-portada]').forEach(btn => {
        btn.addEventListener('click', () => abrir(JSON.parse(btn.dataset.editarPortada)));
    });

    deslizadores.forEach(d => d.addEventListener('input', () => {
        ajustes[d.dataset.editorAjuste] = Number(d.value);
        overlay.querySelector(`[data-editor-valor="${d.dataset.editorAjuste}"]`).textContent = d.value;
        actualizar();
    }));

    overlay.querySelector('[data-editor-restablecer]').addEventListener('click', () => {
        ajustes = { ...AJUSTES_INICIALES };
        sincronizarControles();
        actualizar();
    });

    window.addEventListener('resize', () => { if (base) dibujarMarco(); });

    // Arrastrar el recuadro del celular (mouse o dedo)
    marco.addEventListener('pointerdown', e => {
        e.preventDefault();
        marco.setPointerCapture(e.pointerId);
        const inicio = { x: e.clientX, y: e.clientY, fx: ajustes.foco_x, fy: ajustes.foco_y };
        const mover = ev => {
            const rect = vista.getBoundingClientRect();
            const r = recorte(vista.width, vista.height);
            // Límites del centro del recuadro (no se puede salir de la foto)
            const minX = (r.w / 2) / vista.width, maxX = 1 - minX;
            const minY = (r.h / 2) / vista.height, maxY = 1 - minY;
            ajustes.foco_x = Math.min(maxX, Math.max(minX, inicio.fx + (ev.clientX - inicio.x) / rect.width));
            ajustes.foco_y = Math.min(maxY, Math.max(minY, inicio.fy + (ev.clientY - inicio.y) / rect.height));
            dibujarMarco();
        };
        const soltar = () => {
            marco.removeEventListener('pointermove', mover);
            marco.removeEventListener('pointerup', soltar);
            marco.removeEventListener('pointercancel', soltar);
        };
        marco.addEventListener('pointermove', mover);
        marco.addEventListener('pointerup', soltar);
        marco.addEventListener('pointercancel', soltar);
    });

    const aBlob = (canvas, calidad) => new Promise(listo => canvas.toBlob(listo, 'image/jpeg', calidad));

    guardar.addEventListener('click', async () => {
        if (!imagen || !foto) return;
        guardar.disabled = true;
        const textoBoton = guardar.textContent;
        guardar.textContent = 'Guardando…';

        try {
            // Foto completa con los ajustes, en tamaño original
            const grande = document.createElement('canvas');
            grande.width = imagen.naturalWidth;
            grande.height = imagen.naturalHeight;
            const ctx = grande.getContext('2d', { willReadFrequently: true });
            ctx.drawImage(imagen, 0, 0);
            ctx.putImageData(aplicar(ctx.getImageData(0, 0, grande.width, grande.height), ajustes), 0, 0);

            // Recorte para celular
            const r = recorte(grande.width, grande.height);
            const movil = document.createElement('canvas');
            movil.width = MOVIL_ANCHO;
            movil.height = MOVIL_ALTO;
            movil.getContext('2d').drawImage(grande, r.x, r.y, r.w, r.h, 0, 0, MOVIL_ANCHO, MOVIL_ALTO);

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
