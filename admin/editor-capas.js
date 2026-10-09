window.EditorCapas = (() => {
    const FUENTES = [
        ['Bodoni Moda', 'Bodoni (la del sitio)'],
        ['Playfair Display', 'Playfair Display'],
        ['Cormorant Garamond', 'Cormorant Garamond'],
        ['Montserrat', 'Montserrat'],
        ['Lato', 'Lato'],
        ['Josefin Sans', 'Josefin Sans'],
        ['Bebas Neue', 'Bebas Neue'],
        ['Great Vibes', 'Great Vibes (manuscrita)'],
    ];
    const GOOGLE_FONTS = 'https://fonts.googleapis.com/css2?family=Bodoni+Moda:ital,opsz,wght@0,6..96,400..900;1,6..96,400..700'
        + '&family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Cormorant+Garamond:ital,wght@0,400;0,700;1,400'
        + '&family=Montserrat:ital,wght@0,400;0,700;1,400&family=Lato:ital,wght@0,400;0,700;1,400'
        + '&family=Josefin+Sans:ital,wght@0,400;0,700;1,400&family=Bebas+Neue&family=Great+Vibes&display=swap';

    const PROPS = ['nombre', 'bloqueado', 'selectable', 'evented', 'esLogo', 'logoArchivo', 'colorLogo'];
    const CREMA = '#f4efea';
    const TINTA = '#2a221c';
    const MAX_HISTORIAL = 60;

    const SECCIONES = {
        textbox: ['texto', 'relleno', 'borde', 'comun'],
        rect: ['relleno', 'borde', 'esquinas', 'comun'],
        circle: ['relleno', 'borde', 'comun'],
        triangle: ['relleno', 'borde', 'comun'],
        polygon: ['relleno', 'borde', 'comun'],
        line: ['borde', 'comun'],
        path: ['borde', 'comun'],
        image: ['comun'],
        logo: ['relleno', 'comun'],
        seleccion: ['comun'],
    };

    const svg = d => `<svg viewBox="0 0 24 24" aria-hidden="true">${d}</svg>`;
    const ICONOS = {
        textbox: svg('<path d="M4 7V4h16v3"/><path d="M9 20h6"/><path d="M12 4v16"/>'),
        rect: svg('<rect x="3" y="5" width="18" height="14" rx="1"/>'),
        circle: svg('<circle cx="12" cy="12" r="8"/>'),
        triangle: svg('<path d="M12 4l9 16H3z"/>'),
        polygon: svg('<path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/>'),
        line: svg('<path d="M4 20L20 4"/>'),
        path: svg('<path d="M3 17c3-6 6-6 8-2s5 4 10-4"/>'),
        image: svg('<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/>'),
        foto: svg('<path d="M3 7h4l2-3h6l2 3h4v13H3z"/><circle cx="12" cy="13" r="4"/>'),
        ver: svg('<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/>'),
        oculto: svg('<path d="M3 3l18 18"/><path d="M10.6 5.1A10.4 10.4 0 0 1 12 5c7 0 11 7 11 7a18 18 0 0 1-3.2 3.9M6.6 6.6A18 18 0 0 0 1 12s4 7 11 7a10 10 0 0 0 5.4-1.6"/>'),
        candado: svg('<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>'),
        abierto: svg('<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 7.5-2"/>'),
        subir: svg('<path d="M12 19V5"/><path d="M5 12l7-7 7 7"/>'),
        bajar: svg('<path d="M12 5v14"/><path d="M19 12l-7 7-7-7"/>'),
    };

    let fuentesPedidas = false;
    const pedirFuentes = () => {
        if (fuentesPedidas) return;
        fuentesPedidas = true;
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = GOOGLE_FONTS;
        document.head.appendChild(link);
    };
    const cargarFuente = familia => document.fonts
        ? Promise.all([document.fonts.load(`40px "${familia}"`), document.fonts.load(`bold 40px "${familia}"`)]).catch(() => {})
        : Promise.resolve();

    const aHex = color => {
        if (!color || typeof color !== 'string') return '#000000';
        try { return '#' + new fabric.Color(color).toHex(); } catch (e) { return '#000000'; }
    };

    const leerComoUrl = archivo => new Promise((listo, fallo) => {
        const lector = new FileReader();
        lector.onload = () => listo(lector.result);
        lector.onerror = fallo;
        lector.readAsDataURL(archivo);
    });

    const crear = (escenario, panel, opciones = {}) => {
        pedirFuentes();
        fabric.Object.prototype.set({
            cornerColor: '#ffffff',
            cornerStrokeColor: '#5c4a38',
            borderColor: '#5c4a38',
            cornerStyle: 'circle',
            transparentCorners: false,
            cornerSize: 11,
            borderScaleFactor: 1.5,
            objectCaching: false,
        });

        const elemento = document.createElement('canvas');
        escenario.appendChild(elemento);
        const canvas = new fabric.Canvas(elemento, {
            preserveObjectStacking: true,
            backgroundColor: '#ffffff',
            selectionColor: 'rgba(139, 115, 85, 0.12)',
            selectionBorderColor: '#8b7355',
        });

        let fondo = null;
        let escala = 1;
        let historial = [];
        let indice = -1;
        let restaurando = false;
        let guias = { v: false, h: false };
        const logosOriginales = {};

        panel.innerHTML = `
            <div class="capas-cabecera">
                <p class="capas-titulo">Agregar</p>
                <div class="capas-historial">
                    <button type="button" class="editor-herramienta solo-icono" data-capas-deshacer title="Deshacer (Ctrl+Z)" aria-label="Deshacer">${svg('<path d="M9 14L4 9l5-5"/><path d="M4 9h11a5 5 0 0 1 0 10h-3"/>')}</button>
                    <button type="button" class="editor-herramienta solo-icono" data-capas-rehacer title="Rehacer (Ctrl+Y)" aria-label="Rehacer">${svg('<path d="M15 14l5-5-5-5"/><path d="M20 9H9a5 5 0 0 0 0 10h3"/>')}</button>
                </div>
            </div>
            <div class="capas-botones">
                <button type="button" data-agregar="texto">${ICONOS.textbox}<span>Texto</span></button>
                <button type="button" data-agregar="titulo">${svg('<path d="M5 4v16M19 4v16M5 12h14"/>')}<span>Título</span></button>
                <button type="button" data-agregar="imagen">${ICONOS.image}<span>Imagen</span></button>
                <button type="button" data-agregar="logo">${svg('<path d="M4 20V5l8 9 8-9v15"/>')}<span>Logo</span></button>
                <button type="button" data-agregar="sello">${svg('<rect x="5" y="2" width="14" height="20" rx="6"/><path d="M9 15V8l3 4 3-4v7"/>')}<span>Sello</span></button>
                <button type="button" data-agregar="franja">${svg('<rect x="2" y="14" width="20" height="6"/><path d="M7 17h10"/>')}<span>Franja</span></button>
                <button type="button" data-agregar="etiqueta">${svg('<path d="M3 12V4h8l10 10-8 8z"/><circle cx="7.5" cy="8.5" r="1.5"/>')}<span>Etiqueta</span></button>
                <button type="button" data-agregar="cita">${svg('<path d="M7 7H4v6h4l-2 4M17 7h-3v6h4l-2 4"/>')}<span>Cita</span></button>
                <button type="button" data-dibujar aria-pressed="false">${ICONOS.path}<span>Dibujar</span></button>
            </div>
            <div class="capas-formas" aria-label="Formas">
                <button type="button" data-forma="rect" title="Rectángulo">${ICONOS.rect}</button>
                <button type="button" data-forma="redondeado" title="Rectángulo redondeado">${svg('<rect x="3" y="5" width="18" height="14" rx="5"/>')}</button>
                <button type="button" data-forma="circle" title="Círculo">${ICONOS.circle}</button>
                <button type="button" data-forma="triangle" title="Triángulo">${ICONOS.triangle}</button>
                <button type="button" data-forma="estrella" title="Estrella">${ICONOS.polygon}</button>
                <button type="button" data-forma="flecha" title="Flecha">${svg('<path d="M3 12h14M13 6l6 6-6 6"/>')}</button>
                <button type="button" data-forma="line" title="Línea">${ICONOS.line}</button>
            </div>
            <div class="capas-pincel" data-pincel hidden>
                <label class="editor-control"><span>Color del pincel</span><input type="color" value="#ffffff" data-pincel-color></label>
                <label class="editor-control"><span>Grosor <output data-pincel-valor>6</output></span><input type="range" min="1" max="40" value="6" data-pincel-grosor></label>
                <p class="form-ayuda">Dibujá sobre la foto. Cada trazo queda como una capa. Esc o el mismo botón para terminar.</p>
            </div>
            <input type="file" accept="image/jpeg,image/png,image/webp" hidden data-capas-archivo>

            <div class="capas-props" data-props hidden>
                <p class="capas-titulo">Capa seleccionada</p>
                <div class="editor-panel" data-seccion="texto">
                    <label class="editor-control"><span>Fuente</span>
                        <select class="form-control" data-prop="fontFamily">${FUENTES.map(([v, t]) => `<option value="${v}">${t}</option>`).join('')}</select>
                    </label>
                    <label class="editor-control"><span>Tamaño <output data-valor-prop="fontSize"></output></span><input type="range" min="8" max="300" step="1" data-prop="fontSize"></label>
                    <div class="capas-fila">
                        <button type="button" class="capas-alternar" data-alternar="negrita" title="Negrita"><b>N</b></button>
                        <button type="button" class="capas-alternar" data-alternar="cursiva" title="Cursiva"><i>C</i></button>
                        <button type="button" class="capas-alternar" data-alternar="subrayado" title="Subrayado"><u>S</u></button>
                        <span class="editor-separador" aria-hidden="true"></span>
                        <button type="button" class="capas-alternar" data-alinear="left" title="Alinear a la izquierda">${svg('<path d="M4 6h16M4 10h10M4 14h16M4 18h10"/>')}</button>
                        <button type="button" class="capas-alternar" data-alinear="center" title="Centrar">${svg('<path d="M4 6h16M7 10h10M4 14h16M7 18h10"/>')}</button>
                        <button type="button" class="capas-alternar" data-alinear="right" title="Alinear a la derecha">${svg('<path d="M4 6h16M10 10h10M4 14h16M10 18h10"/>')}</button>
                    </div>
                    <label class="editor-control"><span>Interlineado <output data-valor-prop="lineHeight"></output></span><input type="range" min="0.7" max="2.5" step="0.05" data-prop="lineHeight"></label>
                    <label class="editor-control"><span>Espaciado <output data-valor-prop="charSpacing"></output></span><input type="range" min="-100" max="800" step="10" data-prop="charSpacing"></label>
                    <div class="capas-color">
                        <label class="form-check"><input type="checkbox" data-fondo-texto><span>Fondo detrás del texto</span></label>
                        <input type="color" data-prop="textBackgroundColor" aria-label="Color del fondo del texto">
                    </div>
                </div>
                <div class="editor-panel" data-seccion="relleno">
                    <div class="capas-color">
                        <span data-etiqueta-relleno>Color</span>
                        <input type="color" data-prop="fill" aria-label="Color">
                        <label class="form-check" data-sin-relleno-caja><input type="checkbox" data-sin-relleno><span>Sin relleno</span></label>
                    </div>
                </div>
                <div class="editor-panel" data-seccion="borde">
                    <div class="capas-color">
                        <span data-etiqueta-borde>Borde</span>
                        <input type="color" data-prop="stroke" aria-label="Color del borde">
                    </div>
                    <label class="editor-control"><span>Grosor <output data-valor-prop="strokeWidth"></output></span><input type="range" min="0" max="40" step="1" data-prop="strokeWidth"></label>
                </div>
                <div class="editor-panel" data-seccion="esquinas">
                    <label class="editor-control"><span>Esquinas redondeadas <output data-valor-prop="rx"></output></span><input type="range" min="0" max="200" step="1" data-prop="rx"></label>
                </div>
                <div class="editor-panel" data-seccion="comun">
                    <label class="editor-control"><span>Opacidad <output data-valor-opacidad></output></span><input type="range" min="5" max="100" step="1" data-opacidad></label>
                    <label class="form-check"><input type="checkbox" data-sombra><span>Sombra</span></label>
                    <div class="capas-acciones">
                        <button type="button" data-accion="centrar-h" title="Centrar de izquierda a derecha">${svg('<path d="M12 3v18M7 8h10v8H7z"/>')}<span>Centrar ↔</span></button>
                        <button type="button" data-accion="centrar-v" title="Centrar de arriba abajo">${svg('<path d="M3 12h18M8 7h8v10H8z"/>')}<span>Centrar ↕</span></button>
                        <button type="button" data-accion="duplicar" title="Duplicar (Ctrl+D)">${svg('<rect x="8" y="8" width="13" height="13" rx="2"/><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"/>')}<span>Duplicar</span></button>
                        <button type="button" data-accion="adelante" title="Traer al frente">${ICONOS.subir}<span>Al frente</span></button>
                        <button type="button" data-accion="atras" title="Mandar al fondo">${ICONOS.bajar}<span>Al fondo</span></button>
                        <button type="button" data-accion="eliminar" class="capas-eliminar" title="Eliminar (Supr)">${svg('<path d="M3 6h18M8 6V4h8v2M6 6l1 15h10l1-15"/>')}<span>Eliminar</span></button>
                    </div>
                </div>
            </div>

            <div class="capas-lista-caja">
                <p class="capas-titulo">Capas</p>
                <ol class="capas-lista" data-lista></ol>
                <p class="form-ayuda">Doble clic en un texto para escribir. Arrastrá para mover y usá las esquinas para agrandar o girar. Flechas: mover de a poco.</p>
            </div>`;

        const q = s => panel.querySelector(s);
        const props = q('[data-props]');
        const lista = q('[data-lista]');
        const secciones = [...panel.querySelectorAll('[data-seccion]')];
        const botonDibujar = q('[data-dibujar]');
        const pincel = q('[data-pincel]');
        const archivo = q('[data-capas-archivo]');
        const deshacer = q('[data-capas-deshacer]');
        const rehacer = q('[data-capas-rehacer]');

        const W = () => canvas.getWidth();
        const H = () => canvas.getHeight();
        const sombra = () => new fabric.Shadow({ color: 'rgba(0, 0, 0, 0.45)', blur: Math.max(4, W() * 0.015), offsetX: 2, offsetY: 2 });

        const instantanea = () => canvas.getObjects().map(o => o.toObject(PROPS));

        const actualizarHistorial = () => {
            deshacer.disabled = indice <= 0;
            rehacer.disabled = indice >= historial.length - 1;
        };

        const registrar = () => {
            if (restaurando) return;
            historial = historial.slice(0, indice + 1);
            historial.push(instantanea());
            if (historial.length > MAX_HISTORIAL) historial.shift();
            indice = historial.length - 1;
            actualizarHistorial();
        };

        let demora = 0;
        const registrarPronto = () => { clearTimeout(demora); demora = setTimeout(registrar, 400); };

        const irA = n => {
            if (n < 0 || n >= historial.length) return;
            indice = n;
            restaurando = true;
            canvas.discardActiveObject();
            canvas.remove(...canvas.getObjects());
            fabric.util.enlivenObjects(historial[n], objetos => {
                objetos.forEach(o => canvas.add(o));
                restaurando = false;
                canvas.requestRenderAll();
                refrescar();
                actualizarHistorial();
            });
        };

        const sumar = (objeto, nombre) => {
            objeto.set({ nombre, left: W() / 2, top: H() / 2, originX: 'center', originY: 'center' });
            if (canvas.isDrawingMode) dibujar(false);
            canvas.add(objeto);
            canvas.setActiveObject(objeto);
            canvas.requestRenderAll();
            refrescar();
        };

        const texto = async (contenido, extra = {}) => {
            const familia = extra.fontFamily || 'Bodoni Moda';
            await cargarFuente(familia);
            return new fabric.Textbox(contenido, {
                width: W() * 0.6,
                fontSize: Math.round(W() * 0.06),
                fontFamily: familia,
                fill: '#ffffff',
                textAlign: 'center',
                lineHeight: 1.15,
                shadow: sombra(),
                splitByGrapheme: false,
                ...extra,
            });
        };

        const estrella = radio => {
            const puntos = [];
            for (let i = 0; i < 10; i++) {
                const r = i % 2 ? radio * 0.45 : radio;
                const a = Math.PI / 5 * i - Math.PI / 2;
                puntos.push({ x: Math.cos(a) * r, y: Math.sin(a) * r });
            }
            return puntos;
        };

        const formas = {
            rect: () => new fabric.Rect({ width: W() * 0.3, height: W() * 0.2, fill: CREMA }),
            redondeado: () => new fabric.Rect({ width: W() * 0.3, height: W() * 0.2, rx: W() * 0.03, ry: W() * 0.03, fill: CREMA }),
            circle: () => new fabric.Circle({ radius: W() * 0.12, fill: CREMA }),
            triangle: () => new fabric.Triangle({ width: W() * 0.25, height: W() * 0.22, fill: CREMA }),
            estrella: () => new fabric.Polygon(estrella(W() * 0.13), { fill: CREMA }),
            flecha: () => {
                const l = W() * 0.3, g = l * 0.12;
                return new fabric.Polygon([
                    { x: 0, y: -g / 2 }, { x: l * 0.72, y: -g / 2 }, { x: l * 0.72, y: -g * 1.6 },
                    { x: l, y: 0 }, { x: l * 0.72, y: g * 1.6 }, { x: l * 0.72, y: g / 2 }, { x: 0, y: g / 2 },
                ], { fill: CREMA });
            },
            line: () => new fabric.Line([0, 0, W() * 0.35, 0], { stroke: CREMA, strokeWidth: Math.max(2, W() * 0.006), strokeLineCap: 'round' }),
        };
        const NOMBRES_FORMAS = {
            rect: 'Rectángulo', redondeado: 'Rectángulo', circle: 'Círculo', triangle: 'Triángulo',
            estrella: 'Estrella', flecha: 'Flecha', line: 'Línea',
        };

        const tintar = (img, color) => {
            const c = document.createElement('canvas');
            c.width = img.naturalWidth;
            c.height = img.naturalHeight;
            const ctx = c.getContext('2d');
            ctx.drawImage(img, 0, 0);
            ctx.globalCompositeOperation = 'source-in';
            ctx.fillStyle = color;
            ctx.fillRect(0, 0, c.width, c.height);
            return c;
        };

        const logoOriginal = nombreArchivo => {
            if (!logosOriginales[nombreArchivo]) {
                logosOriginales[nombreArchivo] = new Promise((listo, fallo) => {
                    const img = new Image();
                    img.onload = () => listo(img);
                    img.onerror = () => fallo(new Error('No se pudo cargar el logo.'));
                    img.src = (opciones.assets || '../assets/') + nombreArchivo;
                });
            }
            return logosOriginales[nombreArchivo];
        };

        const tintarLogo = async (objeto, color) => {
            const img = await logoOriginal(objeto.logoArchivo);
            const ancho = objeto.getScaledWidth();
            objeto.setElement(tintar(img, color));
            objeto.set({ colorLogo: color });
            objeto.scaleToWidth(ancho);
            canvas.requestRenderAll();
        };

        const agregarLogo = async (nombreArchivo, nombre, ancho) => {
            try {
                const img = await logoOriginal(nombreArchivo);
                const imagen = new fabric.Image(tintar(img, CREMA), { esLogo: true, logoArchivo: nombreArchivo, colorLogo: CREMA, shadow: sombra() });
                imagen.scaleToWidth(W() * ancho);
                sumar(imagen, nombre);
            } catch (err) {
                alert(err.message);
            }
        };

        const AGREGAR = {
            texto: async () => sumar(await texto('Escribí acá'), 'Texto'),
            titulo: async () => sumar(await texto('Título', {
                fontSize: Math.round(W() * 0.1), fontWeight: 600, charSpacing: 60, width: W() * 0.8,
            }), 'Título'),
            cita: async () => sumar(await texto('“Hecho a mano en el taller”', {
                fontFamily: 'Cormorant Garamond', fontStyle: 'italic', fontSize: Math.round(W() * 0.055), width: W() * 0.7,
            }), 'Cita'),
            imagen: () => archivo.click(),
            logo: () => agregarLogo('logo-wordmark.webp', 'Logo', 0.45),
            sello: () => agregarLogo('logo-badge.webp', 'Sello', 0.2),
            franja: async () => {
                const alto = H() * 0.16;
                const banda = new fabric.Rect({ width: W(), height: alto, fill: 'rgba(30, 24, 18, 0.72)' });
                sumar(banda, 'Franja');
                banda.set({ top: H() - alto / 2 });
                banda.setCoords();
                const leyenda = await texto('Piedra París · Hecho a mano', {
                    fontSize: Math.round(alto * 0.32), width: W() * 0.9, shadow: null, charSpacing: 80,
                });
                sumar(leyenda, 'Texto de la franja');
                leyenda.set({ top: H() - alto / 2 });
                leyenda.setCoords();
                canvas.requestRenderAll();
                registrar();
            },
            etiqueta: async () => {
                const fondoEtiqueta = new fabric.Rect({
                    width: W() * 0.26, height: W() * 0.085, rx: W() * 0.0425, ry: W() * 0.0425, fill: CREMA,
                    shadow: new fabric.Shadow({ color: 'rgba(0, 0, 0, 0.3)', blur: 8, offsetX: 0, offsetY: 2 }),
                });
                sumar(fondoEtiqueta, 'Etiqueta');
                const x = W() * 0.05 + fondoEtiqueta.width / 2, y = H() * 0.05 + fondoEtiqueta.height / 2;
                fondoEtiqueta.set({ left: x, top: y });
                fondoEtiqueta.setCoords();
                const leyenda = await texto('NUEVO', {
                    fontFamily: 'Montserrat', fontWeight: 700, fontSize: Math.round(W() * 0.035), charSpacing: 250,
                    fill: TINTA, shadow: null, width: W() * 0.24,
                });
                await cargarFuente('Montserrat');
                sumar(leyenda, 'Texto de la etiqueta');
                leyenda.set({ left: x, top: y });
                leyenda.setCoords();
                canvas.requestRenderAll();
                registrar();
            },
        };

        panel.querySelectorAll('[data-agregar]').forEach(b => b.addEventListener('click', () => AGREGAR[b.dataset.agregar]()));
        panel.querySelectorAll('[data-forma]').forEach(b => b.addEventListener('click', () => {
            sumar(formas[b.dataset.forma](), NOMBRES_FORMAS[b.dataset.forma]);
        }));

        archivo.addEventListener('change', async () => {
            const elegido = archivo.files[0];
            archivo.value = '';
            if (!elegido) return;
            const url = await leerComoUrl(elegido);
            fabric.Image.fromURL(url, imagen => {
                if (!imagen || !imagen.width) { alert('No se pudo abrir esa imagen.'); return; }
                imagen.scale(Math.min(W() * 0.45 / imagen.width, H() * 0.45 / imagen.height));
                sumar(imagen, elegido.name.replace(/\.[^.]+$/, ''));
            });
        });

        const dibujar = activo => {
            canvas.isDrawingMode = activo;
            botonDibujar.classList.toggle('is-activo', activo);
            botonDibujar.setAttribute('aria-pressed', activo ? 'true' : 'false');
            pincel.hidden = !activo;
            if (activo) {
                canvas.discardActiveObject();
                const brocha = new fabric.PencilBrush(canvas);
                brocha.color = q('[data-pincel-color]').value;
                brocha.width = Number(q('[data-pincel-grosor]').value);
                brocha.decimate = 2;
                canvas.freeDrawingBrush = brocha;
            }
            canvas.requestRenderAll();
            refrescar();
        };
        botonDibujar.addEventListener('click', () => dibujar(!canvas.isDrawingMode));
        q('[data-pincel-color]').addEventListener('input', e => { if (canvas.freeDrawingBrush) canvas.freeDrawingBrush.color = e.target.value; });
        q('[data-pincel-grosor]').addEventListener('input', e => {
            q('[data-pincel-valor]').textContent = e.target.value;
            if (canvas.freeDrawingBrush) canvas.freeDrawingBrush.width = Number(e.target.value);
        });
        canvas.on('path:created', e => {
            e.path.set({ nombre: 'Trazo' });
            historial[indice] = instantanea();
            refrescarLista();
        });

        const activos = () => canvas.getActiveObjects();

        const aplicarProp = (prop, valor) => {
            activos().forEach(o => {
                if (prop === 'rx') o.set({ rx: valor, ry: valor });
                else if (prop === 'fill' && o.esLogo) tintarLogo(o, valor);
                else if (prop === 'strokeWidth' && valor > 0 && !o.stroke) o.set({ strokeWidth: valor, stroke: TINTA });
                else o.set(prop, valor);
                o.setCoords();
            });
            canvas.requestRenderAll();
        };

        panel.querySelectorAll('[data-prop]').forEach(control => {
            const prop = control.dataset.prop;
            const salida = q(`[data-valor-prop="${prop}"]`);
            if (prop === 'fontFamily') {
                control.addEventListener('change', async () => {
                    const familia = control.value;
                    await cargarFuente(familia);
                    fabric.util.clearFabricFontCache(familia);
                    aplicarProp('fontFamily', familia);
                    activos().forEach(o => { if (o.initDimensions) o.initDimensions(); o.setCoords(); });
                    canvas.requestRenderAll();
                    registrar();
                });
                return;
            }
            control.addEventListener('input', () => {
                let valor = control.type === 'range' ? Number(control.value) : control.value;
                if (prop === 'textBackgroundColor' && !q('[data-fondo-texto]').checked) return;
                if (prop === 'fill' && q('[data-sin-relleno]').checked) q('[data-sin-relleno]').checked = false;
                if (salida) salida.textContent = control.value;
                aplicarProp(prop, valor);
            });
            control.addEventListener('change', registrar);
        });

        q('[data-fondo-texto]').addEventListener('change', e => {
            aplicarProp('textBackgroundColor', e.target.checked ? q('[data-prop="textBackgroundColor"]').value : '');
            registrar();
        });
        q('[data-sin-relleno]').addEventListener('change', e => {
            aplicarProp('fill', e.target.checked ? '' : q('[data-prop="fill"]').value);
            registrar();
        });
        q('[data-opacidad]').addEventListener('input', e => {
            q('[data-valor-opacidad]').textContent = e.target.value + '%';
            activos().forEach(o => o.set('opacity', Number(e.target.value) / 100));
            canvas.requestRenderAll();
        });
        q('[data-opacidad]').addEventListener('change', registrar);
        q('[data-sombra]').addEventListener('change', e => {
            activos().forEach(o => o.set('shadow', e.target.checked ? sombra() : null));
            canvas.requestRenderAll();
            registrar();
        });

        const ALTERNAR = {
            negrita: [o => (Number(o.fontWeight) || 400) >= 600 || o.fontWeight === 'bold', (o, si) => o.set('fontWeight', si ? 700 : 400)],
            cursiva: [o => o.fontStyle === 'italic', (o, si) => o.set('fontStyle', si ? 'italic' : 'normal')],
            subrayado: [o => !!o.underline, (o, si) => o.set('underline', si)],
        };
        panel.querySelectorAll('[data-alternar]').forEach(b => b.addEventListener('click', async () => {
            const [leer, poner] = ALTERNAR[b.dataset.alternar];
            const objetos = activos().filter(o => o.type === 'textbox');
            if (!objetos.length) return;
            const si = !leer(objetos[0]);
            objetos.forEach(o => poner(o, si));
            if (b.dataset.alternar !== 'subrayado') await cargarFuente(objetos[0].fontFamily);
            objetos.forEach(o => { o.initDimensions(); o.setCoords(); });
            canvas.requestRenderAll();
            refrescarProps();
            registrar();
        }));
        panel.querySelectorAll('[data-alinear]').forEach(b => b.addEventListener('click', () => {
            aplicarProp('textAlign', b.dataset.alinear);
            refrescarProps();
            registrar();
        }));

        const duplicar = () => {
            const objeto = canvas.getActiveObject();
            if (!objeto) return;
            objeto.clone(copia => {
                canvas.discardActiveObject();
                copia.set({ left: copia.left + 20, top: copia.top + 20, evented: true });
                if (copia.type === 'activeSelection') {
                    copia.canvas = canvas;
                    copia.forEachObject(o => canvas.add(o));
                    copia.setCoords();
                } else {
                    canvas.add(copia);
                }
                canvas.setActiveObject(copia);
                canvas.requestRenderAll();
                refrescar();
                registrar();
            }, PROPS);
        };

        const eliminar = () => {
            const objetos = activos();
            if (!objetos.length) return;
            canvas.discardActiveObject();
            restaurando = true;
            objetos.forEach(o => canvas.remove(o));
            restaurando = false;
            registrar();
            canvas.requestRenderAll();
            refrescar();
        };

        const ACCIONES = {
            'centrar-h': o => { canvas.centerObjectH(o); o.setCoords(); },
            'centrar-v': o => { canvas.centerObjectV(o); o.setCoords(); },
            adelante: o => canvas.bringToFront(o),
            atras: o => canvas.sendToBack(o),
        };
        panel.querySelectorAll('[data-accion]').forEach(b => b.addEventListener('click', () => {
            const accion = b.dataset.accion;
            if (accion === 'duplicar') return duplicar();
            if (accion === 'eliminar') return eliminar();
            const objeto = canvas.getActiveObject();
            if (!objeto) return;
            ACCIONES[accion](objeto);
            canvas.requestRenderAll();
            refrescar();
            registrar();
        }));

        const tipoDe = o => o.type === 'activeSelection' ? 'seleccion' : (o.esLogo ? 'logo' : o.type);

        const refrescarProps = () => {
            const objeto = canvas.getActiveObject();
            props.hidden = !objeto;
            if (!objeto) return;
            const tipo = tipoDe(objeto);
            const muestra = SECCIONES[tipo] || ['comun'];
            secciones.forEach(s => { s.hidden = !muestra.includes(s.dataset.seccion); });

            const ref = tipo === 'seleccion' ? activos()[0] : objeto;
            q('[data-etiqueta-relleno]').textContent = tipo === 'textbox' ? 'Color del texto' : tipo === 'logo' ? 'Color del logo' : 'Relleno';
            q('[data-etiqueta-borde]').textContent = tipo === 'textbox' ? 'Contorno de las letras' : tipo === 'line' || tipo === 'path' ? 'Color' : 'Borde';
            q('[data-sin-relleno-caja]').hidden = tipo === 'textbox' || tipo === 'logo';

            const poner = (prop, valor, texto) => {
                const control = q(`[data-prop="${prop}"]`);
                if (control) control.value = valor;
                const salida = q(`[data-valor-prop="${prop}"]`);
                if (salida) salida.textContent = texto !== undefined ? texto : valor;
            };
            if (ref.type === 'textbox') {
                poner('fontFamily', ref.fontFamily);
                poner('fontSize', Math.round(ref.fontSize));
                poner('lineHeight', ref.lineHeight);
                poner('charSpacing', ref.charSpacing);
                q('[data-fondo-texto]').checked = !!ref.textBackgroundColor;
                poner('textBackgroundColor', aHex(ref.textBackgroundColor || CREMA));
                Object.entries(ALTERNAR).forEach(([k, [leer]]) => q(`[data-alternar="${k}"]`).classList.toggle('is-activo', leer(ref)));
                panel.querySelectorAll('[data-alinear]').forEach(b => b.classList.toggle('is-activo', b.dataset.alinear === ref.textAlign));
            }
            const relleno = ref.esLogo ? ref.colorLogo : ref.fill;
            q('[data-sin-relleno]').checked = !relleno || relleno === 'transparent';
            poner('fill', aHex(relleno || CREMA));
            poner('stroke', aHex(ref.stroke || TINTA));
            poner('strokeWidth', ref.stroke ? Math.round(ref.strokeWidth) : 0);
            poner('rx', Math.round(ref.rx || 0));
            q('[data-opacidad]').value = Math.round((ref.opacity ?? 1) * 100);
            q('[data-valor-opacidad]').textContent = Math.round((ref.opacity ?? 1) * 100) + '%';
            q('[data-sombra]').checked = !!ref.shadow;
        };

        const nombreDe = o => {
            if (o.type === 'textbox') {
                const t = (o.text || '').replace(/\s+/g, ' ').trim();
                return t ? (t.length > 26 ? t.slice(0, 25) + '…' : t) : 'Texto vacío';
            }
            return o.nombre || 'Capa';
        };

        const refrescarLista = () => {
            const seleccionados = activos();
            lista.innerHTML = '';
            canvas.getObjects().slice().reverse().forEach(o => {
                const fila = document.createElement('li');
                fila.className = 'capa'
                    + (seleccionados.includes(o) ? ' is-activa' : '')
                    + (o.visible === false ? ' is-oculta' : '')
                    + (o.bloqueado ? ' is-bloqueada' : '');
                fila.innerHTML = `
                    <span class="capa-icono">${o.esLogo ? ICONOS.image : (ICONOS[o.type] || ICONOS.rect)}</span>
                    <span class="capa-nombre"></span>
                    <button type="button" data-capa="ver" title="${o.visible === false ? 'Mostrar' : 'Ocultar'}">${o.visible === false ? ICONOS.oculto : ICONOS.ver}</button>
                    <button type="button" data-capa="bloquear" title="${o.bloqueado ? 'Desbloquear' : 'Bloquear (que no se mueva)'}">${o.bloqueado ? ICONOS.candado : ICONOS.abierto}</button>
                    <button type="button" data-capa="subir" title="Subir una capa">${ICONOS.subir}</button>
                    <button type="button" data-capa="bajar" title="Bajar una capa">${ICONOS.bajar}</button>`;
                fila.querySelector('.capa-nombre').textContent = nombreDe(o);

                fila.addEventListener('click', e => {
                    const boton = e.target.closest('[data-capa]');
                    if (!boton) {
                        if (o.bloqueado || o.visible === false) return;
                        if (canvas.isDrawingMode) dibujar(false);
                        canvas.setActiveObject(o);
                        canvas.requestRenderAll();
                        refrescar();
                        return;
                    }
                    const accion = boton.dataset.capa;
                    if (accion === 'ver') {
                        o.set('visible', o.visible === false);
                        if (o.visible === false && seleccionados.includes(o)) canvas.discardActiveObject();
                    } else if (accion === 'bloquear') {
                        o.bloqueado = !o.bloqueado;
                        o.set({ selectable: !o.bloqueado, evented: !o.bloqueado });
                        if (o.bloqueado && seleccionados.includes(o)) canvas.discardActiveObject();
                    } else if (accion === 'subir') {
                        canvas.bringForward(o);
                    } else if (accion === 'bajar') {
                        canvas.sendBackwards(o);
                    }
                    canvas.requestRenderAll();
                    refrescar();
                    registrar();
                });
                lista.appendChild(fila);
            });

            const fondoFila = document.createElement('li');
            fondoFila.className = 'capa capa-fondo';
            fondoFila.innerHTML = `<span class="capa-icono">${ICONOS.foto}</span><span class="capa-nombre">Foto (fondo)</span>`;
            lista.appendChild(fondoFila);
        };

        const refrescar = () => {
            refrescarProps();
            refrescarLista();
        };

        canvas.on('object:added', () => { if (!restaurando) registrar(); refrescarLista(); });
        canvas.on('object:removed', () => { if (!restaurando) registrar(); refrescarLista(); });
        canvas.on('object:modified', e => {
            const o = e.target;
            if (o && o.type === 'textbox' && (o.scaleX !== 1 || o.scaleY !== 1)) {
                o.set({ fontSize: o.fontSize * o.scaleY, width: o.width * o.scaleX, scaleX: 1, scaleY: 1 });
                o.setCoords();
            }
            registrar();
            refrescar();
        });
        ['selection:created', 'selection:updated', 'selection:cleared'].forEach(ev => canvas.on(ev, refrescar));
        canvas.on('text:changed', refrescarLista);
        canvas.on('text:editing:exited', registrar);

        canvas.on('object:moving', e => {
            const o = e.target;
            const c = o.getCenterPoint();
            const umbral = 6;
            guias.v = Math.abs(c.x - W() / 2) < umbral;
            guias.h = Math.abs(c.y - H() / 2) < umbral;
            if (guias.v || guias.h) {
                o.setPositionByOrigin(new fabric.Point(guias.v ? W() / 2 : c.x, guias.h ? H() / 2 : c.y), 'center', 'center');
            }
        });
        canvas.on('mouse:up', () => {
            if (guias.v || guias.h) {
                guias = { v: false, h: false };
                canvas.requestRenderAll();
            }
        });
        canvas.on('after:render', () => {
            if (!guias.v && !guias.h) return;
            const ctx = canvas.getContext();
            ctx.save();
            ctx.strokeStyle = '#e0457b';
            ctx.lineWidth = 1;
            ctx.setLineDash([5, 4]);
            ctx.beginPath();
            if (guias.v) { ctx.moveTo(W() / 2, 0); ctx.lineTo(W() / 2, H()); }
            if (guias.h) { ctx.moveTo(0, H() / 2); ctx.lineTo(W(), H() / 2); }
            ctx.stroke();
            ctx.restore();
        });

        deshacer.addEventListener('click', () => irA(indice - 1));
        rehacer.addEventListener('click', () => irA(indice + 1));

        const medir = () => {
            const ancho = escenario.clientWidth || 600;
            const alto = Math.max(240, window.innerHeight * 0.62);
            escala = Math.min(ancho / fondo.width, alto / fondo.height, 1);
            canvas.setDimensions({ width: Math.round(fondo.width * escala), height: Math.round(fondo.height * escala) });
        };

        const reubicar = anterior => {
            const fx = W() / anterior.w, fy = H() / anterior.h;
            if (Math.abs(fx - 1) < 0.001 && Math.abs(fy - 1) < 0.001) return;
            const f = Math.min(fx, fy);
            canvas.getObjects().forEach(o => {
                o.set({ left: o.left * fx, top: o.top * fy, scaleX: o.scaleX * f, scaleY: o.scaleY * f });
                o.setCoords();
            });
        };

        const ponerFondo = lienzo => {
            const anterior = fondo ? { w: W(), h: H() } : null;
            fondo = lienzo;
            medir();
            if (anterior) {
                restaurando = true;
                reubicar(anterior);
                restaurando = false;
            }
            canvas.setBackgroundImage(new fabric.Image(lienzo, {
                left: 0, top: 0, originX: 'left', originY: 'top', scaleX: escala, scaleY: escala,
            }), canvas.renderAll.bind(canvas));
            if (indice < 0) registrar();
            else if (anterior) historial[indice] = instantanea();
            refrescar();
        };

        const ajustar = () => {
            if (!fondo) return;
            const anterior = { w: W(), h: H() };
            medir();
            reubicar(anterior);
            if (canvas.backgroundImage) canvas.backgroundImage.set({ scaleX: escala, scaleY: escala });
            canvas.requestRenderAll();
        };

        const componer = () => {
            canvas.discardActiveObject();
            if (canvas.isDrawingMode) dibujar(false);
            guias = { v: false, h: false };
            canvas.renderAll();
            return canvas.toCanvasElement(1 / escala);
        };

        const hayCapas = () => canvas.getObjects().some(o => o.visible !== false);

        const exportarDiseno = () => ({ objetos: instantanea(), ancho: W(), alto: H() });

        const cargarDiseno = async diseno => {
            const objetos = (diseno && diseno.objetos) || [];
            const familias = [...new Set(objetos.filter(o => o.fontFamily).map(o => o.fontFamily))];
            await Promise.all(familias.map(cargarFuente));
            await new Promise(listo => {
                restaurando = true;
                if (canvas.isDrawingMode) dibujar(false);
                canvas.discardActiveObject();
                canvas.remove(...canvas.getObjects());
                fabric.util.enlivenObjects(objetos, vivos => {
                    vivos.forEach(o => canvas.add(o));
                    if (diseno.ancho && diseno.alto) reubicar({ w: diseno.ancho, h: diseno.alto });
                    restaurando = false;
                    listo();
                });
            });
            registrar();
            canvas.requestRenderAll();
            refrescar();
        };

        const limpiar = () => {
            if (canvas.isDrawingMode) dibujar(false);
            restaurando = true;
            canvas.discardActiveObject();
            canvas.remove(...canvas.getObjects());
            restaurando = false;
            canvas.setBackgroundImage(null, () => {});
            fondo = null;
            historial = [];
            indice = -1;
            actualizarHistorial();
            refrescar();
        };

        const enCampo = e => e.target instanceof HTMLElement && e.target.matches('input, select, textarea')
            && e.target !== canvas.getActiveObject()?.hiddenTextarea;

        const teclado = e => {
            const objeto = canvas.getActiveObject();
            const escribiendo = !!(objeto && objeto.isEditing);
            if (e.key === 'Escape') {
                if (escribiendo) { objeto.exitEditing(); canvas.requestRenderAll(); return true; }
                if (canvas.isDrawingMode) { dibujar(false); return true; }
                if (objeto) { canvas.discardActiveObject(); canvas.requestRenderAll(); refrescar(); return true; }
                return false;
            }
            if (escribiendo || enCampo(e)) return false;
            const ctrl = e.ctrlKey || e.metaKey;
            const tecla = e.key.toLowerCase();
            if (ctrl && tecla === 'z') { irA(indice + (e.shiftKey ? 1 : -1)); return true; }
            if (ctrl && tecla === 'y') { irA(indice + 1); return true; }
            if (!objeto) return false;
            if (ctrl && tecla === 'd') { duplicar(); return true; }
            if (e.key === 'Delete' || e.key === 'Backspace') { eliminar(); return true; }
            const flechas = { ArrowLeft: [-1, 0], ArrowRight: [1, 0], ArrowUp: [0, -1], ArrowDown: [0, 1] };
            if (flechas[e.key]) {
                const paso = e.shiftKey ? 10 : 1;
                objeto.set({ left: objeto.left + flechas[e.key][0] * paso, top: objeto.top + flechas[e.key][1] * paso });
                objeto.setCoords();
                canvas.requestRenderAll();
                registrarPronto();
                return true;
            }
            return false;
        };

        actualizarHistorial();
        refrescar();

        return { ponerFondo, hayCapas, componer, teclado, limpiar, ajustar, exportarDiseno, cargarDiseno };
    };

    return { crear };
})();
