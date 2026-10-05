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
 * - Luz (exposición, contraste, luces, sombras), color (saturación, viveza, calidez, tinte)
 *   y efectos (nitidez, viñeta, desvanecido), con vista previa en vivo e histograma.
 * - Ajuste automático (niveles por canal), filtros listos y logo como marca de agua.
 * - Deshacer / rehacer (Ctrl+Z / Ctrl+Y) y comparar con la original manteniendo apretado.
 * - La foto final sale con el lado más largo hasta el tamaño elegido.
 * - Etapa Capas (editor-capas.js): texto, formas, imágenes, logo, al estilo Canva.
 * - Diseños guardados en el servidor (actions/disenos.php) para retomarlos después.
 *   EditorImagen.abrirDiseno(id, opciones) abre uno, igual que abrir().
 */
window.EditorImagen = (() => {
    const VISTA_MAX = 900;          // la vista previa se procesa achicada, para que sea fluida
    const LADO_RECOMENDADO = 1000;  // por debajo de esto, en la ficha se puede ver borrosa
    const RECORTE_MINIMO = 0.08;    // el recorte no puede ser menor al 8% de la foto
    const CALIDAD = 0.9;
    const MINIATURA = 84;           // lado de las vistas previas de los filtros
    const ASSETS = new URL('../assets/', document.baseURI).href;
    const FABRIC = 'https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js';
    const SCRIPT = document.currentScript && document.currentScript.src;   // para cargar editor-capas.js de la misma carpeta
    const API_DISENOS = new URL('actions/disenos.php', SCRIPT || document.baseURI).href;
    const SITIO = new URL('../', SCRIPT || document.baseURI).href;          // las rutas guardadas son relativas al sitio
    const PROPORCIONES = [
        ['0', 'Libre'],
        ['0.8', '4:5 (como en el catálogo)'],
        ['1', '1:1 (cuadrada)'],
        ['0.75', '3:4 (vertical)'],
        ['1.3333', '4:3 (horizontal)'],
        ['1.5', '3:2 (como las fotos)'],
        ['1.7778', '16:9 (pantalla ancha)'],
    ];

    const TAMANOS = [
        ['2400', 'Grande (2400 px)'],
        ['1600', 'Mediana (1600 px)'],
        ['1200', 'Liviana (1200 px)'],
    ];

    // [clave, nombre, mínimo, máximo, paso, unidad]
    const DESLIZADORES = {
        recorte: [
            ['enderezar', 'Enderezar', -15, 15, 0.5, '°'],
        ],
        luz: [
            ['brillo', 'Exposición', -50, 50, 1, ''],
            ['contraste', 'Contraste', -50, 50, 1, ''],
            ['luces', 'Luces', -100, 100, 1, ''],
            ['sombras', 'Sombras', -100, 100, 1, ''],
        ],
        color: [
            ['saturacion', 'Saturación', -100, 50, 1, ''],
            ['viveza', 'Viveza', -100, 100, 1, ''],
            ['calidez', 'Calidez', -50, 50, 1, ''],
            ['tinte', 'Tinte', -50, 50, 1, ''],
        ],
        efectos: [
            ['nitidez', 'Nitidez', 0, 100, 1, ''],
            ['vineta', 'Viñeta', -100, 100, 1, ''],
            ['desvanecer', 'Desvanecer', 0, 100, 1, ''],
        ],
    };
    const TODOS = Object.values(DESLIZADORES).flat();
    const COLOR = TODOS.map(d => d[0]).filter(c => c !== 'enderezar');

    const AYUDAS = {
        luces: 'Negativo recupera las zonas quemadas; positivo las aclara.',
        sombras: 'Positivo aclara las zonas oscuras sin tocar el resto.',
        viveza: 'Como la saturación, pero cuida los colores que ya están fuertes.',
        tinte: 'Corrige fotos verdosas (positivo) o rosadas (negativo).',
        vineta: 'Positivo oscurece los bordes; negativo los aclara hacia el blanco.',
    };

    // Filtros listos: fijan los ajustes de color (lo demás queda como está)
    const FILTROS = [
        ['', 'Original', {}],
        ['natural', 'Natural', { contraste: 8, sombras: 15, viveza: 20 }],
        ['luminosa', 'Luminosa', { brillo: 12, sombras: 30, luces: -20, viveza: 10 }],
        ['piedra', 'Piedra', { contraste: 12, saturacion: -30, calidez: 8, nitidez: 25 }],
        ['galeria', 'Galería', { brillo: 6, sombras: 20, vineta: -55 }],
        ['calida', 'Cálida', { calidez: 30, viveza: 15 }],
        ['fria', 'Fría', { calidez: -25, tinte: -5 }],
        ['vivida', 'Vívida', { contraste: 15, saturacion: 20, viveza: 25 }],
        ['mate', 'Mate', { contraste: -10, saturacion: -15, desvanecer: 45 }],
        ['byn', 'Blanco y negro', { contraste: 15, saturacion: -100 }],
        ['sepia', 'Sepia', { saturacion: -100, calidez: 45, desvanecer: 15 }],
        ['dramatica', 'Dramática', { contraste: 30, luces: -30, sombras: -15, saturacion: -20, vineta: 45 }],
    ];

    const LOGOS = {
        texto: ['logo-wordmark.webp', 'Martineau (texto)'],
        sello: ['logo-badge.webp', 'Sello'],
    };
    const POSICIONES = [
        ['abajo-der', 'Abajo a la derecha'],
        ['abajo-izq', 'Abajo a la izquierda'],
        ['arriba-der', 'Arriba a la derecha'],
        ['arriba-izq', 'Arriba a la izquierda'],
        ['centro', 'En el centro'],
    ];

    const iniciales = () => ({
        giro: 0,            // cuartos de vuelta a la derecha (0–3)
        espejo: false,
        enderezar: 0,       // grados
        ...Object.fromEntries(COLOR.map(c => [c, 0])),
        auto: null,         // niveles por canal del ajuste automático: [bajo, factor] × R, G, B
        filtro: '',
        recorte: { x: 0, y: 0, w: 1, h: 1 },   // fracciones de la foto ya girada
        proporcion: 0,
    });

    // El logo y el tamaño final se recuerdan para las próximas fotos
    const MARCA_INICIAL = { activa: false, estilo: 'texto', color: 'claro', posicion: 'abajo-der', tamano: 28, opacidad: 70 };
    const leer = (clave, defecto) => {
        try { return { ...defecto, ...JSON.parse(localStorage.getItem(clave) || '{}') }; } catch (e) { return { ...defecto }; }
    };
    const guardarPreferencia = (clave, valor) => {
        try { localStorage.setItem(clave, JSON.stringify(valor)); } catch (e) { /* sin storage: no se recuerda */ }
    };

    const icono = d => `<svg viewBox="0 0 24 24" aria-hidden="true">${d}</svg>`;

    let el = null;          // referencias a los elementos del modal (se arma al abrirlo la primera vez)
    let imagen = null;      // la foto cargada
    let base = null;        // píxeles de la vista previa ya girada, sin ajustes de color
    let ajustes = iniciales();
    let marca = leer('editor_imagen_marca', MARCA_INICIAL);
    let preferencias = leer('editor_imagen_preferencias', { lado: '2400' });
    let logos = {};         // estilo → Image cargada
    let historial = [];
    let indice = -1;
    let comparando = false;
    let pestana = 'recorte';
    let etapa = 'foto';     // 'foto' (ajustes) o 'capas' (texto, formas… encima)
    let capas = null;       // instancia de EditorCapas
    let cargandoCapas = null;
    let pendiente = 0;      // requestAnimationFrame de la vista previa
    let terminar = null;    // resuelve la promesa de abrir()
    let opciones = {};
    let fuenteActual = null;    // File o URL de la foto abierta (para guardarla como original de un diseño)
    let disenoActual = null;    // {id, nombre} si se abrió o guardó como diseño

    const limitar = (v, min, max) => Math.min(max, Math.max(min, v));

    const leerComoUrl = archivo => new Promise((listo, fallo) => {
        const lector = new FileReader();
        lector.onload = () => listo(lector.result);
        lector.onerror = fallo;
        lector.readAsDataURL(archivo);
    });
    const suave = (a, b, x) => { const t = limitar((x - a) / (b - a), 0, 1); return t * t * (3 - 2 * t); };

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

    const sinColor = a => !a.auto && COLOR.every(c => !a[c]);

    // Todos los ajustes de color sobre los píxeles (igual en la vista previa y al guardar).
    // "zona" es el recorte dentro de "datos", para centrar la viñeta; "paso" agranda el radio
    // de la nitidez en la foto final, que tiene más píxeles que la vista previa.
    const aplicar = (datos, a, zona, paso = 1) => {
        if (sinColor(a)) return datos;
        const p = datos.data, W = datos.width, H = datos.height;
        const b = 1 + a.brillo / 100;
        const c = 1 + a.contraste / 100;
        const s = 1 + a.saturacion / 100;
        const viveza = a.viveza / 100;
        const sombras = a.sombras / 100, luces = a.luces / 100;
        const rojo = (1 + a.calidez / 200) * (1 + a.tinte / 500);
        const verde = 1 - a.tinte / 250;
        const azul = (1 - a.calidez / 200) * (1 + a.tinte / 500);
        const piso = a.desvanecer * 0.45;                  // el negro sube hasta ~45
        const rango = 1 - (piso * 1.35) / 255;             // y el blanco baja un poco
        const n = a.auto;
        const vineta = a.vineta / 100;
        const z = zona || { x: 0, y: 0, w: W, h: H };
        const cx = z.x + z.w / 2, cy = z.y + z.h / 2;
        const rx = z.w / 2, ry = z.h / 2;

        for (let y = 0, i = 0; y < H; y++) {
            for (let x = 0; x < W; x++, i += 4) {
                let r = p[i], g = p[i + 1], v = p[i + 2];
                if (n) { r = (r - n[0]) * n[1]; g = (g - n[2]) * n[3]; v = (v - n[4]) * n[5]; }
                r *= b; g *= b; v *= b;

                if (sombras || luces) {
                    const L = (0.299 * r + 0.587 * g + 0.114 * v) / 255;
                    if (L > 0.002) {
                        const l = limitar(L, 0, 1);
                        let d = 0;
                        if (sombras) d += sombras * 0.5 * (1 - l) * (1 - l) * Math.min(1, l * 4);
                        if (luces) d += luces * 0.5 * l * l * Math.min(1, (1 - l) * 4);
                        const k = (L + d) / L;
                        r *= k; g *= k; v *= k;
                    }
                }

                if (c !== 1) { r = (r - 128) * c + 128; g = (g - 128) * c + 128; v = (v - 128) * c + 128; }

                if (s !== 1 || viveza) {
                    const lum = 0.299 * r + 0.587 * g + 0.114 * v;
                    let f = s;
                    if (viveza) {
                        const max = Math.max(r, g, v), min = Math.min(r, g, v);
                        const sat = max > 1 ? limitar((max - min) / max, 0, 1) : 0;
                        f *= 1 + viveza * (1 - sat);
                    }
                    r = lum + (r - lum) * f; g = lum + (g - lum) * f; v = lum + (v - lum) * f;
                }

                r *= rojo; g *= verde; v *= azul;

                if (piso) { r = piso + r * rango; g = piso + g * rango; v = piso + v * rango; }

                if (vineta) {
                    const dx = (x - cx) / rx, dy = (y - cy) / ry;
                    const t = suave(0.45, 1.45, Math.sqrt(dx * dx + dy * dy));
                    if (vineta > 0) {
                        const k = 1 - vineta * 0.8 * t;
                        r *= k; g *= k; v *= k;
                    } else {
                        const k = -vineta * 0.9 * t;
                        r += (255 - r) * k; g += (255 - g) * k; v += (255 - v) * k;
                    }
                }

                p[i] = r;          // Uint8ClampedArray: recorta solo a 0–255
                p[i + 1] = g;
                p[i + 2] = v;
            }
        }

        if (a.nitidez) enfocar(datos, a.nitidez / 100 * 0.9, paso);
        return datos;
    };

    // Nitidez: resalta la diferencia de cada píxel con sus vecinos
    const enfocar = (datos, k, paso) => {
        const W = datos.width, H = datos.height, p = datos.data;
        const o = new Uint8ClampedArray(p);
        const dx = 4 * paso, dy = W * 4 * paso;
        for (let y = paso; y < H - paso; y++) {
            for (let x = paso, i = (y * W + paso) * 4; x < W - paso; x++, i += 4) {
                for (let ch = 0; ch < 3; ch++) {
                    const j = i + ch;
                    p[j] = o[j] + k * (4 * o[j] - o[j - dx] - o[j + dx] - o[j - dy] - o[j + dy]);
                }
            }
        }
    };

    // Ajuste automático: estira cada canal entre sus extremos (sin contar el 0,4% más
    // oscuro y el más claro), lo que además corrige si la foto tiene un tinte de color
    const calcularAuto = () => {
        const W = base.width;
        const z = enPixeles(base.width, base.height);
        const hist = [new Uint32Array(256), new Uint32Array(256), new Uint32Array(256)];
        let total = 0;
        for (let y = Math.floor(z.y); y < Math.floor(z.y + z.h); y += 2) {
            for (let x = Math.floor(z.x); x < Math.floor(z.x + z.w); x += 2) {
                const i = (y * W + x) * 4;
                hist[0][base.data[i]]++; hist[1][base.data[i + 1]]++; hist[2][base.data[i + 2]]++;
                total++;
            }
        }
        const corte = total * 0.004;
        const niveles = [];
        hist.forEach(h => {
            let bajo = 0, alto = 255, suma = 0;
            while (bajo < 255 && (suma += h[bajo]) < corte) bajo++;
            suma = 0;
            while (alto > 0 && (suma += h[alto]) < corte) alto--;
            if (alto - bajo < 40) { bajo = 0; alto = 255; }   // casi plana: no se toca
            niveles.push(bajo, 255 / (alto - bajo));
        });
        return niveles;
    };

    // ─── Logo ───

    const cargarLogo = estilo => {
        if (logos[estilo]) return Promise.resolve(logos[estilo]);
        return new Promise(listo => {
            const img = new Image();
            img.onload = () => {
                // Versiones clara y oscura del logo, pintando su forma de un solo color
                const version = color => {
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
                logos[estilo] = { claro: version('#ffffff'), oscuro: version('#2a221c') };
                listo(logos[estilo]);
            };
            img.onerror = () => listo(null);
            img.src = ASSETS + LOGOS[estilo][0];
        });
    };

    // Dibuja el logo dentro de la zona (el recorte) de un lienzo
    const dibujarLogo = (ctx, zona) => {
        if (!marca.activa) return;
        const logo = logos[marca.estilo];
        if (!logo) return;
        const fuente = logo[marca.color];
        const corto = Math.min(zona.w, zona.h);
        // El sello es alto: se mide contra el lado corto para que no tape la foto
        const ancho = marca.estilo === 'sello' ? corto * marca.tamano / 100 * 0.7 : zona.w * marca.tamano / 100;
        const alto = ancho * fuente.height / fuente.width;
        const margen = corto * 0.04;
        const pos = marca.posicion;
        const x = pos === 'centro' ? zona.x + (zona.w - ancho) / 2
            : pos.endsWith('izq') ? zona.x + margen : zona.x + zona.w - ancho - margen;
        const y = pos === 'centro' ? zona.y + (zona.h - alto) / 2
            : pos.startsWith('arriba') ? zona.y + margen : zona.y + zona.h - alto - margen;
        ctx.save();
        ctx.globalAlpha = marca.opacidad / 100;
        ctx.shadowColor = marca.color === 'claro' ? 'rgba(0, 0, 0, 0.35)' : 'rgba(255, 255, 255, 0.3)';
        ctx.shadowBlur = Math.max(2, ancho * 0.01);
        ctx.drawImage(fuente, x, y, ancho, alto);
        ctx.restore();
    };

    // ─── Vista previa ───

    const ubicar = (nodo, r, escala) => Object.assign(nodo.style, {
        left: r.x * escala + 'px', top: r.y * escala + 'px',
        width: r.w * escala + 'px', height: r.h * escala + 'px',
    });

    const ladoMax = () => Number(preferencias.lado) || 2400;

    const dibujarRecorte = () => {
        const { vista } = el;
        ubicar(el.recorte, enPixeles(vista.width, vista.height), vista.clientWidth / vista.width);

        const F = marco();
        const c = enPixeles(F.w, F.h);
        const k = Math.min(1, ladoMax() / Math.max(c.w, c.h));
        const ancho = Math.round(c.w * k), alto = Math.round(c.h * k);
        el.tamano.textContent = `La foto queda de ${ancho} × ${alto} px.`;
        el.aviso.hidden = Math.max(ancho, alto) >= LADO_RECOMENDADO;
    };

    // Histograma de lo que queda dentro del recorte, ya con los ajustes
    const dibujarHistograma = datos => {
        const W = datos.width;
        const z = enPixeles(datos.width, datos.height);
        const hist = [new Uint32Array(64), new Uint32Array(64), new Uint32Array(64)];
        for (let y = Math.floor(z.y); y < Math.floor(z.y + z.h); y += 2) {
            for (let x = Math.floor(z.x); x < Math.floor(z.x + z.w); x += 2) {
                const i = (y * W + x) * 4;
                hist[0][datos.data[i] >> 2]++; hist[1][datos.data[i + 1] >> 2]++; hist[2][datos.data[i + 2] >> 2]++;
            }
        }
        // Escala sin contar los extremos, que suelen tener picos y aplastarían el resto
        let max = 1;
        hist.forEach(h => { for (let j = 1; j < 63; j++) max = Math.max(max, h[j]); });

        const c = el.histograma, ctx = c.getContext('2d');
        ctx.clearRect(0, 0, c.width, c.height);
        ctx.globalCompositeOperation = 'multiply';
        ['rgba(214, 72, 64, 0.55)', 'rgba(82, 168, 92, 0.55)', 'rgba(70, 110, 210, 0.55)'].forEach((color, k) => {
            ctx.beginPath();
            ctx.moveTo(0, c.height);
            for (let j = 0; j < 64; j++) {
                ctx.lineTo(j * c.width / 63, c.height - Math.min(1, hist[k][j] / max) * (c.height - 2));
            }
            ctx.lineTo(c.width, c.height);
            ctx.closePath();
            ctx.fillStyle = color;
            ctx.fill();
        });
        ctx.globalCompositeOperation = 'source-over';
    };

    const pintar = () => {
        pendiente = 0;
        if (!base) return;
        const ctx = el.vista.getContext('2d');
        if (comparando) {
            ctx.putImageData(base, 0, 0);
        } else {
            const zona = enPixeles(base.width, base.height);
            const copia = aplicar(new ImageData(new Uint8ClampedArray(base.data), base.width, base.height), ajustes, zona);
            ctx.putImageData(copia, 0, 0);
            dibujarHistograma(copia);
            dibujarLogo(ctx, zona);
        }
        dibujarRecorte();
    };

    // Para los deslizadores: como mucho una vista previa por cuadro
    const pintarPronto = () => {
        if (!pendiente) pendiente = requestAnimationFrame(pintar);
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
        if (pestana === 'filtros') dibujarFiltros();
    };

    // Miniaturas de cada filtro sobre lo recortado (con el ajuste automático, si está)
    const dibujarFiltros = () => {
        if (!base) return;
        const temporal = document.createElement('canvas');
        temporal.width = base.width;
        temporal.height = base.height;
        temporal.getContext('2d').putImageData(base, 0, 0);
        const z = enPixeles(base.width, base.height);
        const lado = Math.min(z.w, z.h);

        el.filtros.forEach(boton => {
            const c = boton.querySelector('canvas');
            const ctx = c.getContext('2d', { willReadFrequently: true });
            ctx.drawImage(temporal, z.x + (z.w - lado) / 2, z.y + (z.h - lado) / 2, lado, lado, 0, 0, MINIATURA, MINIATURA);
            const valores = FILTROS.find(f => f[0] === boton.dataset.filtro)[2];
            const a = { ...iniciales(), auto: ajustes.auto, ...valores };
            ctx.putImageData(aplicar(ctx.getImageData(0, 0, MINIATURA, MINIATURA), a), 0, 0);
            boton.classList.toggle('is-activo', boton.dataset.filtro === ajustes.filtro);
        });
    };

    const sincronizarControles = () => {
        TODOS.forEach(([clave, , , , , unidad]) => {
            el.deslizadores[clave].value = ajustes[clave];
            el.valores[clave].textContent = ajustes[clave] + unidad;
        });
        const opcion = [...el.proporcion.options].find(o => Math.abs(Number(o.value) - ajustes.proporcion) < 0.001);
        el.proporcion.value = opcion ? opcion.value : '0';
        el.auto.classList.toggle('is-activo', !!ajustes.auto);
        el.auto.setAttribute('aria-pressed', ajustes.auto ? 'true' : 'false');
        el.filtros.forEach(b => b.classList.toggle('is-activo', b.dataset.filtro === ajustes.filtro));
    };

    const sincronizarMarca = () => {
        el.marca.activa.checked = marca.activa;
        el.marca.estilo.value = marca.estilo;
        el.marca.color.value = marca.color;
        el.marca.posicion.value = marca.posicion;
        el.marca.tamano.value = marca.tamano;
        el.marca.opacidad.value = marca.opacidad;
        el.marca.tamanoValor.textContent = marca.tamano + '%';
        el.marca.opacidadValor.textContent = marca.opacidad + '%';
        el.marca.opciones.forEach(o => { o.disabled = !marca.activa; });
    };

    // ─── Historial (deshacer / rehacer) ───

    const actualizarHistorial = () => {
        el.deshacer.disabled = indice <= 0;
        el.rehacer.disabled = indice >= historial.length - 1;
    };

    // Guarda el estado después de cada cambio terminado (no en cada paso de un deslizador)
    const registrar = () => {
        const estado = JSON.stringify(ajustes);
        if (historial[indice] === estado) return;
        historial = historial.slice(0, indice + 1);
        historial.push(estado);
        indice = historial.length - 1;
        actualizarHistorial();
    };

    const irA = n => {
        if (!base || n < 0 || n >= historial.length) return;
        indice = n;
        ajustes = JSON.parse(historial[n]);
        sincronizarControles();
        rehacerBase();
        actualizarHistorial();
    };

    // ─── Modal ───

    const deslizador = ([clave, nombre, min, max, paso]) => `
        <label class="editor-control"${AYUDAS[clave] ? ` title="${AYUDAS[clave]}"` : ''}>
            <span>${nombre} <output data-valor="${clave}" title="Doble clic para volver a 0">0</output></span>
            <input type="range" min="${min}" max="${max}" step="${paso}" value="0" data-ajuste="${clave}">
        </label>`;

    const opcionesDe = lista => lista.map(([v, t]) => `<option value="${v}">${t}</option>`).join('');

    const armar = () => {
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.id = 'modal-editor-imagen';
        overlay.innerHTML = `
            <div class="modal editor-modal" role="dialog" aria-modal="true" aria-labelledby="editor-imagen-titulo">
                <div class="modal-header">
                    <h2 id="editor-imagen-titulo">Editar foto</h2>
                    <div class="editor-etapas" role="tablist" aria-label="Etapa">
                        <button type="button" role="tab" data-etapa="foto" class="is-activa" aria-selected="true">
                            ${icono('<path d="M3 7h4l2-3h6l2 3h4v13H3z"/><circle cx="12" cy="13" r="4"/>')}<span>Foto</span>
                        </button>
                        <button type="button" role="tab" data-etapa="capas" aria-selected="false" title="Texto, formas, logo e imágenes encima de la foto">
                            ${icono('<path d="M12 3l9 5-9 5-9-5z"/><path d="M3 13l9 5 9-5"/>')}<span>Capas</span>
                        </button>
                    </div>
                    <button type="button" class="modal-close" data-cancelar aria-label="Cerrar">
                        ${icono('<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>')}
                    </button>
                </div>
                <div class="modal-body editor-cuerpo editor-completo" data-cuerpo="foto">
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
                            <span class="editor-separador" aria-hidden="true"></span>
                            <button type="button" class="editor-herramienta solo-icono" data-deshacer title="Deshacer (Ctrl+Z)" aria-label="Deshacer">
                                ${icono('<path d="M9 14L4 9l5-5"/><path d="M4 9h11a5 5 0 0 1 0 10h-3"/>')}
                            </button>
                            <button type="button" class="editor-herramienta solo-icono" data-rehacer title="Rehacer (Ctrl+Y)" aria-label="Rehacer">
                                ${icono('<path d="M15 14l5-5-5-5"/><path d="M20 9H9a5 5 0 0 0 0 10h3"/>')}
                            </button>
                            <span class="editor-separador" aria-hidden="true"></span>
                            <button type="button" class="editor-herramienta" data-auto aria-pressed="false" title="Corrige solo la luz y el color. Otro clic lo saca.">
                                ${icono('<path d="M12 3l1.8 4.7L18.5 9.5l-4.7 1.8L12 16l-1.8-4.7L5.5 9.5l4.7-1.8z"/><path d="M19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8z"/>')}<span>Auto</span>
                            </button>
                            <button type="button" class="editor-herramienta" data-comparar title="Mantené apretado para ver la foto sin los ajustes de luz y color">
                                ${icono('<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M12 4v16"/><path d="M12 9l9-5"/>')}<span>Comparar</span>
                            </button>
                        </div>
                        <div class="editor-foto editor-foto-libre is-cargando" data-modo="recorte">
                            <canvas data-vista></canvas>
                            <div class="editor-recorte" data-recorte>
                                <i data-esquina="nw"></i><i data-esquina="ne"></i><i data-esquina="sw"></i><i data-esquina="se"></i>
                            </div>
                            <span class="editor-etiqueta" data-etiqueta-original hidden>Original</span>
                        </div>
                        <p class="form-ayuda">Mové el recuadro o achicalo desde las esquinas: lo de adentro es lo que queda. Doble clic en un número lo vuelve a 0.</p>
                        <p class="editor-cargando" data-estado>Cargando la foto…</p>
                    </div>

                    <div class="editor-controles">
                        <div class="editor-histograma-caja" title="Cuánto hay de cada tono en lo recortado: a la izquierda los oscuros, a la derecha los claros (una montaña por color: rojo, verde y azul). Si todo se amontona en una punta, la foto está muy oscura o muy clara.">
                            <span>Histograma <small>oscuros → claros</small></span>
                            <canvas class="editor-histograma" data-histograma width="256" height="64" aria-hidden="true"></canvas>
                        </div>

                        <div class="editor-pestanas" role="tablist" aria-label="Qué ajustar">
                            <button type="button" role="tab" data-pestana="recorte">Recorte</button>
                            <button type="button" role="tab" data-pestana="luz">Luz</button>
                            <button type="button" role="tab" data-pestana="color">Color</button>
                            <button type="button" role="tab" data-pestana="efectos">Efectos</button>
                            <button type="button" role="tab" data-pestana="filtros">Filtros</button>
                            <button type="button" role="tab" data-pestana="logo">Logo</button>
                        </div>

                        <div class="editor-panel" data-panel="recorte" role="tabpanel">
                            <label class="editor-control">
                                <span>Proporción del recorte</span>
                                <select class="form-control" data-proporcion>${opcionesDe(PROPORCIONES)}</select>
                            </label>
                            ${DESLIZADORES.recorte.map(deslizador).join('')}
                        </div>
                        ${['luz', 'color', 'efectos'].map(p => `
                            <div class="editor-panel" data-panel="${p}" role="tabpanel" hidden>
                                ${DESLIZADORES[p].map(deslizador).join('')}
                            </div>`).join('')}
                        <div class="editor-panel" data-panel="filtros" role="tabpanel" hidden>
                            <div class="editor-filtros">
                                ${FILTROS.map(([clave, nombre]) => `
                                    <button type="button" class="editor-filtro" data-filtro="${clave}">
                                        <canvas width="${MINIATURA}" height="${MINIATURA}"></canvas>
                                        <span>${nombre}</span>
                                    </button>`).join('')}
                            </div>
                            <p class="form-ayuda">Después se puede afinar en Luz, Color y Efectos.</p>
                        </div>
                        <div class="editor-panel" data-panel="logo" role="tabpanel" hidden>
                            <label class="form-check editor-check">
                                <input type="checkbox" data-marca="activa">
                                <span>Poner el logo en la foto</span>
                            </label>
                            <label class="editor-control">
                                <span>Logo</span>
                                <select class="form-control" data-marca="estilo">${opcionesDe(Object.entries(LOGOS).map(([k, [, t]]) => [k, t]))}</select>
                            </label>
                            <label class="editor-control">
                                <span>Color</span>
                                <select class="form-control" data-marca="color">
                                    <option value="claro">Claro (para fotos oscuras)</option>
                                    <option value="oscuro">Oscuro (para fotos claras)</option>
                                </select>
                            </label>
                            <label class="editor-control">
                                <span>Posición</span>
                                <select class="form-control" data-marca="posicion">${opcionesDe(POSICIONES)}</select>
                            </label>
                            <label class="editor-control">
                                <span>Tamaño <output data-marca-valor="tamano">0</output></span>
                                <input type="range" min="10" max="60" step="1" data-marca="tamano">
                            </label>
                            <label class="editor-control">
                                <span>Opacidad <output data-marca-valor="opacidad">0</output></span>
                                <input type="range" min="15" max="100" step="1" data-marca="opacidad">
                            </label>
                            <p class="form-ayuda">Se recuerda para las próximas fotos.</p>
                        </div>

                        <div class="editor-final">
                            <label class="editor-control">
                                <span>Tamaño final</span>
                                <select class="form-control" data-lado>${opcionesDe(TAMANOS)}</select>
                            </label>
                            <p class="form-ayuda" data-tamano></p>
                            <p class="form-ayuda editor-aviso" data-aviso hidden>Queda chica: en la ficha se puede ver un poco borrosa.</p>
                            <button type="button" class="btn-admin btn-secondary btn-sm" data-restablecer>Restablecer todo</button>
                        </div>
                    </div>
                </div>
                <div class="modal-body editor-cuerpo editor-completo" data-cuerpo="capas" hidden>
                    <div class="editor-lienzo">
                        <div class="capas-escenario" data-capas-escenario></div>
                        <p class="editor-cargando" data-capas-estado>Preparando…</p>
                    </div>
                    <div class="editor-controles capas-panel" data-capas-panel></div>
                </div>
                <div class="modal-footer editor-pie">
                    <div class="editor-pie-disenos">
                        <button type="button" class="btn-admin btn-secondary" data-guardar-diseno title="Guarda la foto con todos los ajustes y las capas, para seguir editándola otro día">
                            ${icono('<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/>')}<span>Guardar diseño</span>
                        </button>
                        <button type="button" class="btn-admin btn-secondary" data-ver-disenos aria-expanded="false">
                            ${icono('<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>')}<span>Diseños</span>
                        </button>
                        <div class="editor-disenos" data-disenos hidden>
                            <p class="capas-titulo">Diseños guardados</p>
                            <ul class="editor-disenos-lista" data-disenos-lista></ul>
                        </div>
                    </div>
                    <span class="editor-aviso-guardado" data-guardado hidden></span>
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
            deshacer: q('[data-deshacer]'),
            rehacer: q('[data-rehacer]'),
            auto: q('[data-auto]'),
            histograma: q('[data-histograma]'),
            etiquetaOriginal: q('[data-etiqueta-original]'),
            filtros: [...overlay.querySelectorAll('[data-filtro]')],
            etapas: [...overlay.querySelectorAll('[data-etapa]')],
            cuerpos: [...overlay.querySelectorAll('[data-cuerpo]')],
            capasEscenario: q('[data-capas-escenario]'),
            capasPanel: q('[data-capas-panel]'),
            capasEstado: q('[data-capas-estado]'),
            lado: q('[data-lado]'),
            guardarDiseno: q('[data-guardar-diseno]'),
            verDisenos: q('[data-ver-disenos]'),
            disenos: q('[data-disenos]'),
            disenosLista: q('[data-disenos-lista]'),
            guardado: q('[data-guardado]'),
            deslizadores: {},
            valores: {},
            marca: {
                activa: q('[data-marca="activa"]'),
                estilo: q('[data-marca="estilo"]'),
                color: q('[data-marca="color"]'),
                posicion: q('[data-marca="posicion"]'),
                tamano: q('[data-marca="tamano"]'),
                opacidad: q('[data-marca="opacidad"]'),
                tamanoValor: q('[data-marca-valor="tamano"]'),
                opacidadValor: q('[data-marca-valor="opacidad"]'),
            },
        };
        el.marca.opciones = ['estilo', 'color', 'posicion', 'tamano', 'opacidad'].map(k => el.marca[k]);

        TODOS.forEach(([clave, , , , , unidad]) => {
            const d = q(`[data-ajuste="${clave}"]`);
            el.deslizadores[clave] = d;
            el.valores[clave] = q(`[data-valor="${clave}"]`);
            d.addEventListener('input', () => {
                ajustes[clave] = Number(d.value);
                el.valores[clave].textContent = d.value + unidad;
                if (!base) return;
                if (clave === 'enderezar') rehacerBase();
                else pintarPronto();
            });
            d.addEventListener('change', registrar);
            // Doble clic en el número: vuelve a 0
            el.valores[clave].addEventListener('dblclick', e => {
                e.preventDefault();
                if (!base || !ajustes[clave]) return;
                ajustes[clave] = 0;
                sincronizarControles();
                if (clave === 'enderezar') rehacerBase();
                else pintar();
                registrar();
            });
        });

        // Pestañas
        const pestanas = [...overlay.querySelectorAll('[data-pestana]')];
        const paneles = [...overlay.querySelectorAll('[data-panel]')];
        const cambiarPestana = nombre => {
            pestana = nombre;
            pestanas.forEach(b => {
                const activa = b.dataset.pestana === nombre;
                b.classList.toggle('is-activa', activa);
                b.setAttribute('aria-selected', activa ? 'true' : 'false');
            });
            paneles.forEach(p => { p.hidden = p.dataset.panel !== nombre; });
            if (nombre === 'filtros') dibujarFiltros();
        };
        pestanas.forEach(b => b.addEventListener('click', () => cambiarPestana(b.dataset.pestana)));
        el.cambiarPestana = cambiarPestana;

        overlay.querySelectorAll('[data-girar]').forEach(b => b.addEventListener('click', () => {
            if (!base) return;
            ajustes.giro = (ajustes.giro + Number(b.dataset.girar) + 4) % 4;
            ajustes.recorte = { x: 0, y: 0, w: 1, h: 1 };   // la foto cambió de forma: se recorta de nuevo
            ajustarProporcion();
            rehacerBase();
            registrar();
        }));

        q('[data-espejar]').addEventListener('click', () => {
            if (!base) return;
            ajustes.espejo = !ajustes.espejo;
            // El recorte queda sobre la misma parte de la foto
            ajustes.recorte.x = 1 - ajustes.recorte.x - ajustes.recorte.w;
            rehacerBase();
            registrar();
        });

        el.deshacer.addEventListener('click', () => irA(indice - 1));
        el.rehacer.addEventListener('click', () => irA(indice + 1));

        el.auto.addEventListener('click', () => {
            if (!base) return;
            ajustes.auto = ajustes.auto ? null : calcularAuto();
            sincronizarControles();
            pintar();
            if (pestana === 'filtros') dibujarFiltros();
            registrar();
        });

        // Comparar: mientras se mantiene apretado se ve sin los ajustes de luz y color
        const comparar = activo => {
            if (comparando === activo || !base) return;
            comparando = activo;
            el.etiquetaOriginal.hidden = !activo;
            pintar();
        };
        const botonComparar = q('[data-comparar]');
        botonComparar.addEventListener('pointerdown', e => { e.preventDefault(); comparar(true); });
        ['pointerup', 'pointerleave', 'pointercancel'].forEach(t => botonComparar.addEventListener(t, () => comparar(false)));
        botonComparar.addEventListener('keydown', e => { if (e.key === ' ' || e.key === 'Enter') { e.preventDefault(); comparar(true); } });
        botonComparar.addEventListener('keyup', () => comparar(false));
        botonComparar.addEventListener('blur', () => comparar(false));

        el.filtros.forEach(boton => boton.addEventListener('click', () => {
            if (!base) return;
            const valores = FILTROS.find(f => f[0] === boton.dataset.filtro)[2];
            COLOR.forEach(c => { ajustes[c] = valores[c] || 0; });
            ajustes.filtro = boton.dataset.filtro;
            sincronizarControles();
            pintar();
            registrar();
        }));

        el.proporcion.addEventListener('change', () => {
            ajustes.proporcion = Number(el.proporcion.value);
            if (!base) return;
            ajustarProporcion();
            pintar();
            registrar();
        });

        // Logo
        const cambiarMarca = async () => {
            marca = {
                activa: el.marca.activa.checked,
                estilo: el.marca.estilo.value,
                color: el.marca.color.value,
                posicion: el.marca.posicion.value,
                tamano: Number(el.marca.tamano.value),
                opacidad: Number(el.marca.opacidad.value),
            };
            guardarPreferencia('editor_imagen_marca', marca);
            sincronizarMarca();
            if (marca.activa) await cargarLogo(marca.estilo);
            pintarPronto();
        };
        Object.values(el.marca).forEach(c => {
            if (c instanceof HTMLElement && c.matches('input, select')) {
                c.addEventListener(c.type === 'range' ? 'input' : 'change', cambiarMarca);
            }
        });

        const selectorLado = q('[data-lado]');
        selectorLado.value = preferencias.lado;
        selectorLado.addEventListener('change', () => {
            preferencias.lado = selectorLado.value;
            guardarPreferencia('editor_imagen_preferencias', preferencias);
            if (base) dibujarRecorte();
        });

        q('[data-restablecer]').addEventListener('click', () => {
            if (!base) return;
            ajustes = iniciales();
            sincronizarControles();
            rehacerBase();
            registrar();
        });

        overlay.querySelectorAll('[data-cancelar]').forEach(b => b.addEventListener('click', cancelar));
        el.aplicar.addEventListener('click', confirmar);
        el.etapas.forEach(b => b.addEventListener('click', () => cambiarEtapa(b.dataset.etapa)));

        // Diseños guardados
        el.guardarDiseno.addEventListener('click', guardarDiseno);
        el.verDisenos.addEventListener('click', () => mostrarDisenos(el.disenos.hidden));
        overlay.addEventListener('pointerdown', e => {
            if (!el.disenos.hidden && !e.target.closest('.editor-pie-disenos')) mostrarDisenos(false);
        });

        // Escape cierra solo el editor, no el formulario del producto que quedó abajo.
        // Ctrl+Z deshace y Ctrl+Y (o Ctrl+Shift+Z) rehace. En Capas, los atajos son los de esa etapa.
        window.addEventListener('keydown', e => {
            if (!overlay.classList.contains('active')) return;
            if (etapa === 'capas' && capas && capas.teclado(e)) {
                e.preventDefault();
                e.stopPropagation();
                return;
            }
            if (e.key === 'Escape') {
                e.stopPropagation();
                cancelar();
                return;
            }
            if (etapa !== 'foto' || !(e.ctrlKey || e.metaKey)) return;
            const tecla = e.key.toLowerCase();
            if (tecla === 'z' && !e.shiftKey) { e.preventDefault(); irA(indice - 1); }
            else if (tecla === 'y' || (tecla === 'z' && e.shiftKey)) { e.preventDefault(); irA(indice + 1); }
        }, true);

        window.addEventListener('resize', () => {
            if (!base || !overlay.classList.contains('active')) return;
            if (etapa === 'capas' && capas) capas.ajustar();
            else dibujarRecorte();
        });

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
                // El logo y la viñeta siguen al recorte
                if (marca.activa || ajustes.vineta) pintarPronto();
                else dibujarRecorte();
            };
            const soltar = () => {
                nodo.removeEventListener('pointermove', alMover);
                nodo.removeEventListener('pointerup', soltar);
                nodo.removeEventListener('pointercancel', soltar);
                pintar();
                if (pestana === 'filtros') dibujarFiltros();
                registrar();
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
        return !a.giro && !a.espejo && !a.enderezar && sinColor(a) && !marca.activa
            && !(capas && capas.hayCapas())
            && r.x === 0 && r.y === 0 && r.w === 1 && r.h === 1;
    };

    // ─── Etapa Capas (editor-capas.js + Fabric.js, se cargan la primera vez) ───

    const cargarScript = src => new Promise((listo, fallo) => {
        const s = document.createElement('script');
        s.src = src;
        s.onload = listo;
        s.onerror = () => fallo(new Error('No se pudo cargar ' + src));
        document.head.appendChild(s);
    });

    const prepararCapas = () => {
        if (!cargandoCapas) {
            cargandoCapas = (async () => {
                if (!window.fabric) await cargarScript(FABRIC);
                if (!window.EditorCapas) await cargarScript(new URL('editor-capas.js?v=3', SCRIPT || document.baseURI).href);
                capas = window.EditorCapas.crear(el.capasEscenario, el.capasPanel, { assets: ASSETS });
                return capas;
            })();
            cargandoCapas.catch(() => { cargandoCapas = null; });
        }
        return cargandoCapas;
    };

    const mostrarEtapa = nombre => {
        etapa = nombre;
        el.etapas.forEach(b => {
            const activa = b.dataset.etapa === nombre;
            b.classList.toggle('is-activa', activa);
            b.setAttribute('aria-selected', activa ? 'true' : 'false');
        });
        el.cuerpos.forEach(c => { c.hidden = c.dataset.cuerpo !== nombre; });
    };

    const cambiarEtapa = async nombre => {
        if (!base || nombre === etapa) return;
        mostrarEtapa(nombre);
        if (nombre === 'foto') {
            // El lienzo estuvo oculto: recién ahora se puede medir de nuevo
            requestAnimationFrame(dibujarRecorte);
            return;
        }
        el.capasEstado.hidden = false;
        el.capasEstado.textContent = 'Preparando…';
        try {
            await prepararCapas();
            // La foto de fondo es la editada en la etapa Foto, en tamaño final
            const lienzo = await lienzoFinal();
            if (etapa !== 'capas' || !base) return;
            capas.ponerFondo(lienzo);
            el.capasEstado.hidden = true;
        } catch (err) {
            el.capasEstado.textContent = 'No se pudo cargar el editor de capas. Revisá la conexión a internet y probá de nuevo.';
        }
    };

    // Cancelar no pide confirmación salvo que se pierda lo agregado en Capas
    const cancelar = () => {
        if (capas && capas.hayCapas() && !confirm('¿Cerrar sin aplicar? Se pierde lo que agregaste en Capas.')) return;
        cerrar(null);
    };

    // La foto final: el recorte en tamaño completo (hasta el tamaño elegido), con todos los ajustes
    const lienzoFinal = async () => {
        if (marca.activa) await cargarLogo(marca.estilo);
        const F = marco();
        const c = enPixeles(F.w, F.h);
        const k = Math.min(1, ladoMax() / Math.max(c.w, c.h));
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
            // La nitidez mira vecinos más lejanos en proporción a cuánto más grande es que la vista previa
            const paso = limitar(Math.round(lienzo.width / (el.vista.width * ajustes.recorte.w)), 1, 4);
            ctx.putImageData(aplicar(ctx.getImageData(0, 0, lienzo.width, lienzo.height), ajustes, null, paso), 0, 0);
        }
        dibujarLogo(ctx, { x: 0, y: 0, w: lienzo.width, h: lienzo.height });
        return lienzo;
    };

    // La foto final con las capas encima, si hay
    const lienzoCompleto = async () => {
        const lienzo = await lienzoFinal();
        if (!capas || !capas.hayCapas()) return lienzo;
        capas.ponerFondo(lienzo);   // por si se cambió algo en la etapa Foto después
        return capas.componer();
    };

    const aBlob = (lienzo, tipo, calidad) => new Promise((listo, fallo) => lienzo.toBlob(
        blob => blob ? listo(blob) : fallo(new Error('No se pudo armar la foto.')), tipo, calidad
    ));

    const confirmar = async () => {
        if (!imagen) return;
        if (sinCambios() && !opciones.siempre) { cerrar(null); return; }

        el.aplicar.disabled = true;
        const texto = el.aplicar.textContent;
        el.aplicar.textContent = 'Procesando…';
        try {
            const blob = await aBlob(await lienzoCompleto(), 'image/jpeg', CALIDAD);
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
        if (pendiente) { cancelAnimationFrame(pendiente); pendiente = 0; }
        imagen = null;
        base = null;
        carga++;
        comparando = false;
        el.etiquetaOriginal.hidden = true;
        mostrarDisenos(false);
        const listo = terminar;
        terminar = null;
        if (listo) listo(resultado);
    };

    // Deja el editor listo para una foto (o un diseño guardado) y la carga.
    // "carga" invalida lo que estuviera cargando antes, si se abre otra en el medio.
    let carga = 0;
    const prepararFoto = (fuente, diseno = null) => {
        const mia = ++carga;
        fuenteActual = fuente;
        disenoActual = diseno ? { id: diseno.id, nombre: diseno.nombre } : null;
        ajustes = iniciales();
        if (!diseno) marca = leer('editor_imagen_marca', MARCA_INICIAL);
        historial = [];
        indice = -1;
        imagen = null;
        base = null;
        if (capas) capas.limpiar();
        mostrarEtapa('foto');
        sincronizarControles();
        sincronizarMarca();
        actualizarHistorial();
        el.cambiarPestana('recorte');
        el.guardado.hidden = true;
        el.histograma.getContext('2d').clearRect(0, 0, el.histograma.width, el.histograma.height);
        el.aplicar.disabled = true;
        el.zona.classList.add('is-cargando');
        el.estado.hidden = false;
        el.estado.textContent = 'Cargando la foto…';
        el.tamano.textContent = '';
        el.aviso.hidden = true;
        el.vista.width = 1;
        el.vista.height = 1;

        const img = new Image();
        img.onload = async () => {
            if (mia !== carga) return;   // se cerró o se abrió otra mientras cargaba
            try {
                imagen = img;
                const datos = (diseno && diseno.datos) || {};
                if (diseno) {
                    const a = datos.ajustes || {};
                    ajustes = { ...iniciales(), ...a, recorte: { ...iniciales().recorte, ...(a.recorte || {}) } };
                    if (datos.marca) marca = { ...MARCA_INICIAL, ...datos.marca };
                    if (datos.lado) preferencias.lado = String(datos.lado);
                }
                if (marca.activa) await cargarLogo(marca.estilo);
                if (mia !== carga) return;
                el.estado.hidden = true;
                el.zona.classList.remove('is-cargando');
                el.aplicar.disabled = false;
                sincronizarControles();
                sincronizarMarca();
                el.lado.value = preferencias.lado;
                rehacerBase();
                registrar();
                // El tamaño en pantalla del lienzo se conoce recién después de pintarlo
                requestAnimationFrame(dibujarRecorte);
                // Si el diseño tenía capas, se abre directo en esa etapa
                if (datos.capas && datos.capas.objetos && datos.capas.objetos.length) {
                    await cambiarEtapa('capas');
                    if (mia === carga && capas) await capas.cargarDiseno(datos.capas);
                }
            } catch (err) {
                if (mia === carga) el.estado.textContent = err.message;
            }
        };
        img.onerror = () => {
            if (mia === carga) el.estado.textContent = 'No se pudo abrir la foto.';
        };
        if (fuente instanceof Blob) {
            // Como data: y no blob:, que la política de seguridad de algunas páginas bloquea
            leerComoUrl(fuente).then(url => { if (mia === carga) img.src = url; }, img.onerror);
        } else {
            img.src = fuente;
        }
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
        el.aplicar.textContent = opciones.boton || 'Aplicar';
        el.overlay.classList.add('active');
        document.body.style.overflow = 'hidden';

        const promesa = new Promise(listo => { terminar = listo; });
        prepararFoto(fuente, opc.diseno || null);
        return promesa;
    };

    // ─── Diseños guardados (admin/actions/disenos.php) ───

    const pedirDisenos = async (consulta, cuerpo) => {
        const res = await fetch(API_DISENOS + consulta, cuerpo ? { method: 'POST', body: cuerpo } : { cache: 'no-store' });
        const json = await res.json().catch(() => null);
        if (!json || !json.ok) throw new Error((json && json.error) || 'El servidor no respondió bien. ¿Se cerró la sesión?');
        return json;
    };

    const verDiseno = async id => (await pedirDisenos('?accion=ver&id=' + encodeURIComponent(id))).diseno;

    // Abre un diseño guardado; lo que se aplique vuelve a quien abrió el editor
    const abrirDiseno = async (id, opc = {}) => {
        const diseno = await verDiseno(id);
        return abrir(SITIO + diseno.original, { ...opc, nombre: diseno.nombre, diseno });
    };

    const blobOriginal = async () => {
        if (fuenteActual instanceof Blob) return fuenteActual;
        const res = await fetch(fuenteActual);
        if (!res.ok) throw new Error('No se pudo leer la foto original.');
        return res.blob();
    };

    const miniaturaDiseno = async () => {
        const lienzo = await lienzoCompleto();
        const k = Math.min(1, 480 / Math.max(lienzo.width, lienzo.height));
        const c = document.createElement('canvas');
        c.width = Math.max(1, Math.round(lienzo.width * k));
        c.height = Math.max(1, Math.round(lienzo.height * k));
        const ctx = c.getContext('2d');
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, c.width, c.height);
        ctx.imageSmoothingQuality = 'high';
        ctx.drawImage(lienzo, 0, 0, c.width, c.height);
        return aBlob(c, 'image/jpeg', 0.8);
    };

    let ocultarAviso = 0;
    const avisoGuardado = texto => {
        clearTimeout(ocultarAviso);
        el.guardado.textContent = texto;
        el.guardado.hidden = !texto;
        if (texto && !texto.endsWith('…')) ocultarAviso = setTimeout(() => { el.guardado.hidden = true; }, 4000);
    };

    const guardarDiseno = async () => {
        if (!imagen) return;
        const sugerido = disenoActual ? disenoActual.nombre : (opciones.nombre || 'Diseño').replace(/\.[^.]+$/, '');
        const nombre = prompt(disenoActual ? 'Nombre del diseño (se actualiza el que abriste):' : 'Nombre del diseño:', sugerido);
        if (nombre === null) return;
        const token = document.querySelector('input[name="csrf_token"]');

        el.guardarDiseno.disabled = true;
        avisoGuardado('Guardando…');
        try {
            const datos = new FormData();
            datos.append('accion', 'guardar');
            datos.append('csrf_token', token ? token.value : '');
            if (disenoActual) datos.append('id', disenoActual.id);
            datos.append('nombre', nombre.trim() || sugerido);
            datos.append('datos', JSON.stringify({
                version: 1,
                ajustes,
                marca,
                lado: preferencias.lado,
                capas: capas ? capas.exportarDiseno() : null,
            }));
            if (!disenoActual) datos.append('original', await blobOriginal(), 'original');
            datos.append('miniatura', await miniaturaDiseno(), 'miniatura.jpg');
            const json = await pedirDisenos('', datos);
            disenoActual = { id: json.id, nombre: json.nombre };
            avisoGuardado('Diseño guardado');
            window.dispatchEvent(new CustomEvent('editor-imagen:diseno-guardado', { detail: disenoActual }));
        } catch (err) {
            avisoGuardado('');
            alert('No se pudo guardar el diseño: ' + err.message);
        } finally {
            el.guardarDiseno.disabled = false;
        }
    };

    const formatoFecha = texto => {
        const f = new Date(String(texto).replace(' ', 'T'));
        return isNaN(f) ? '' : f.toLocaleDateString('es-AR', { day: 'numeric', month: 'short', year: 'numeric' });
    };

    // La lista de diseños guardados, desde el pie del editor
    const mostrarDisenos = async mostrar => {
        if (!el) return;
        el.disenos.hidden = !mostrar;
        el.verDisenos.setAttribute('aria-expanded', mostrar ? 'true' : 'false');
        if (!mostrar) return;
        const lista = el.disenosLista;
        lista.innerHTML = '<li class="editor-disenos-vacio">Cargando…</li>';
        try {
            const { disenos } = await pedirDisenos('?accion=listar');
            lista.innerHTML = '';
            if (!disenos.length) {
                lista.innerHTML = '<li class="editor-disenos-vacio">Todavía no hay diseños guardados. Usá "Guardar diseño" para guardar el que estás haciendo.</li>';
                return;
            }
            disenos.forEach(d => {
                const item = document.createElement('li');
                item.innerHTML = `
                    <img alt="" loading="lazy">
                    <span class="editor-disenos-texto"><strong></strong><small></small></span>
                    <span class="editor-disenos-botones">
                        <button type="button" class="btn-admin btn-secondary btn-sm" data-abrir title="Reemplaza la foto que estás editando por este diseño">Abrir</button>
                        <button type="button" class="btn-admin btn-secondary btn-sm" data-usar-capas title="Pone el texto, las formas y el logo de este diseño sobre la foto que estás editando">Usar sus capas</button>
                    </span>`;
                if (d.miniatura) item.querySelector('img').src = SITIO + d.miniatura;
                item.querySelector('strong').textContent = d.nombre;
                item.querySelector('small').textContent = formatoFecha(d.actualizado);
                item.querySelector('[data-abrir]').addEventListener('click', async () => {
                    if (!sinCambios() && !confirm('Se reemplaza la foto que estás editando por este diseño. ¿Seguir?')) return;
                    mostrarDisenos(false);
                    try {
                        const diseno = await verDiseno(d.id);
                        opciones.nombre = diseno.nombre;
                        prepararFoto(SITIO + diseno.original, diseno);
                    } catch (err) {
                        alert(err.message);
                    }
                });
                item.querySelector('[data-usar-capas]').addEventListener('click', async () => {
                    mostrarDisenos(false);
                    try {
                        const diseno = await verDiseno(d.id);
                        const guardadas = diseno.datos && diseno.datos.capas;
                        if (!guardadas || !guardadas.objetos || !guardadas.objetos.length) {
                            alert('Ese diseño no tiene capas (texto, formas, logo…).');
                            return;
                        }
                        if (capas && capas.hayCapas() && !confirm('Se reemplazan las capas que ya agregaste. ¿Seguir?')) return;
                        await cambiarEtapa('capas');
                        if (capas) await capas.cargarDiseno(guardadas);
                    } catch (err) {
                        alert(err.message);
                    }
                });
                lista.appendChild(item);
            });
        } catch (err) {
            lista.innerHTML = '<li class="editor-disenos-vacio"></li>';
            lista.firstChild.textContent = err.message;
        }
    };

    return { abrir, abrirDiseno };
})();
