document.addEventListener('DOMContentLoaded', () => {

    const throttle = (func, limit) => {
        let inThrottle;
        return function () {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        }
    };

    const debounce = (func, delay) => {
        let timeout;
        return function () {
            const context = this;
            const args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), delay);
        };
    };

    const initHeader = () => {
        const header = document.querySelector('.site-header');
        if (!header) return;

        let ticking = false;

        const handleScroll = () => {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
            ticking = false;
        };

        window.addEventListener('scroll', () => {
            if (!ticking) {
                window.requestAnimationFrame(handleScroll);
                ticking = true;
            }
        }, { passive: true });

        handleScroll();
    };

    const initMobileNav = () => {
        const navToggle = document.querySelector('.nav-toggle');
        const mobileNav = document.querySelector('.mobile-nav');
        const navLinks = document.querySelectorAll('.mobile-nav a');

        if (!navToggle || !mobileNav) return;

        const toggleNav = () => {
            const isActive = navToggle.classList.contains('active');
            if (isActive) {
                navToggle.classList.remove('active');
                mobileNav.classList.remove('active');
                document.body.classList.remove('no-scroll');
            } else {
                navToggle.classList.add('active');
                mobileNav.classList.add('active');
                document.body.classList.add('no-scroll');
            }
        };

        navToggle.addEventListener('click', toggleNav);

        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (navToggle.classList.contains('active')) {
                    toggleNav();
                }
            });
        });
    };

    const initNavDropdowns = () => {
        document.querySelectorAll('.mobile-nav-chevron').forEach(btn => {
            btn.addEventListener('click', () => {
                const item = btn.closest('.has-dropdown');
                const dropdown = item && item.querySelector('.mobile-nav-dropdown');
                if (!dropdown) return;

                const isExpanded = btn.getAttribute('aria-expanded') === 'true';
                btn.setAttribute('aria-expanded', String(!isExpanded));
                btn.classList.toggle('expanded', !isExpanded);
                dropdown.classList.toggle('expanded', !isExpanded);
            });
        });
    };

    const initParallax = () => {
    };

    const initProceso = () => {
        const steps = document.querySelectorAll('.pp-step');
        if (!steps.length) return;

        const fotosDe = (el) => [...el.querySelectorAll('img')];
        const pedirFotos = (el) => fotosDe(el).forEach(img => { img.loading = 'eager'; });
        const fotosListas = (el) => Promise.all(fotosDe(el).map(img =>
            (img.decode ? img.decode() : Promise.resolve()).catch(() => {})
        ));

        const precarga = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    pedirFotos(entry.target);
                    precarga.unobserve(entry.target);
                }
            });
        }, { rootMargin: '150% 0px 150% 0px' });
        steps.forEach(step => precarga.observe(step));
        const cierre = document.querySelector('.pp-cierre');
        if (cierre) precarga.observe(cierre);

        const io = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const step = entry.target;
                    io.unobserve(step);
                    pedirFotos(step);
                    const tope = new Promise(r => setTimeout(r, 2500));
                    Promise.race([fotosListas(step), tope]).then(() => step.classList.add('is-in'));
                }
            });
        }, { rootMargin: '0px 0px -25% 0px', threshold: 0.15 });
        steps.forEach(step => io.observe(step));

        const list = document.getElementById('ppSteps');
        if (!list) return;
        let pendiente = false;
        const actualizarLinea = () => {
            pendiente = false;
            const r = list.getBoundingClientRect();
            const avance = (window.innerHeight * 0.55 - r.top) / r.height;
            list.style.setProperty('--pp-progress', Math.min(1, Math.max(0, avance)).toFixed(3));
        };
        window.addEventListener('scroll', () => {
            if (!pendiente) { pendiente = true; requestAnimationFrame(actualizarLinea); }
        }, { passive: true });
        actualizarLinea();
    };

    const imagenLista = (img, tope = 2500) => {
        if (!img) return Promise.resolve();
        img.loading = 'eager';
        const decodificada = img.decode ? img.decode().catch(() => {}) : Promise.resolve();
        return Promise.race([decodificada, new Promise(r => setTimeout(r, tope))]);
    };

    window.initVitrina = () => {
        const nichos = document.querySelectorAll('.nicho:not([data-vitrina])');
        if (!nichos.length) return;
        document.documentElement.classList.add('vitrina-js');

        const io = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;
                io.unobserve(entry.target);
                imagenLista(entry.target.querySelector('img'))
                    .then(() => entry.target.classList.add('is-in'));
            });
        }, { rootMargin: '0px 0px -10% 0px', threshold: 0.1 });

        nichos.forEach(n => { n.dataset.vitrina = '1'; io.observe(n); });
    };

    window.initObras = () => {
        const root = document.querySelector('.obras');
        if (!root || root.dataset.obras) return;
        const fotos = [...root.querySelectorAll('.obras-foto')];
        const items = [...root.querySelectorAll('.obras-item')];
        if (!fotos.length) return;
        root.dataset.obras = '1';
        document.documentElement.classList.add('obras-js');
        if (fotos.length === 1) root.classList.add('is-unica');

        const contador = root.querySelector('.obras-contador-actual');
        let actual = 0;

        const mostrar = (n) => {
            if (n === actual || !fotos[n]) return;
            fotos.forEach(f => f.classList.remove('is-prev'));
            fotos[actual].classList.replace('is-active', 'is-prev');
            fotos[actual].setAttribute('aria-hidden', 'true');
            items[actual].classList.remove('is-active');
            actual = n;
            fotos[actual].classList.add('is-active');
            fotos[actual].setAttribute('aria-hidden', 'false');
            items[actual].classList.add('is-active');
            if (contador) contador.textContent = String(actual + 1).padStart(2, '0');
        };

        items.forEach((item, i) => {
            const barra = item.querySelector('.obras-barra');
            if (barra) barra.addEventListener('animationend', () => {
                if (i === actual) mostrar((actual + 1) % fotos.length);
            });
            item.addEventListener('mouseenter', () => mostrar(i));
            item.addEventListener('focus', () => mostrar(i));
        });

        root.addEventListener('mouseenter', () => root.classList.add('is-hover'));
        root.addEventListener('mouseleave', () => root.classList.remove('is-hover'));

        const io = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !root.classList.contains('is-in')) {
                    fotos.forEach(f => { const img = f.querySelector('img'); if (img) img.loading = 'eager'; });
                    imagenLista(fotos[0].querySelector('img')).then(() => root.classList.add('is-in'));
                }
                root.classList.toggle('is-pausa', !entry.isIntersecting);
            });
        }, { threshold: 0.25 });
        io.observe(root);
    };

    const initHeroSlideshow = () => {
        const container = document.querySelector('.hero-slides');
        if (!container) return;

        const DURACION = 7000;
        const FUNDIDO = 2000;

        const cargo = (slide) => {
            const img = slide.querySelector('img');
            return img && img.complete && img.naturalWidth > 0;
        };

        const quitar = (slide) => {
            const eraLaActiva = slide.classList.contains('is-active');
            slide.remove();
            const primera = container.querySelector('.hero-slide');
            if (eraLaActiva && primera) primera.classList.add('is-active');
        };

        container.querySelectorAll('.hero-slide img').forEach(img => {
            const slide = img.closest('.hero-slide');
            if (!img.dataset.src && img.complete && img.naturalWidth === 0) quitar(slide);
            else img.addEventListener('error', () => quitar(slide));
        });

        const cargarDiferidas = () => {
            container.querySelectorAll('source[data-srcset]').forEach(s => {
                s.srcset = s.dataset.srcset;
                s.removeAttribute('data-srcset');
            });
            container.querySelectorAll('img[data-src]').forEach(img => {
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
            });
        };
        if (document.readyState === 'complete') cargarDiferidas();
        else window.addEventListener('load', cargarDiferidas, { once: true });

        const avanzar = () => {
            const slides = Array.from(container.querySelectorAll('.hero-slide'));
            if (slides.length < 2) return;

            const actual = container.querySelector('.hero-slide.is-active') || slides[0];
            const desde = slides.indexOf(actual);
            let siguiente = null;
            for (let i = 1; i < slides.length; i++) {
                const candidata = slides[(desde + i) % slides.length];
                if (cargo(candidata)) { siguiente = candidata; break; }
            }
            if (!siguiente) return;

            slides.forEach(s => s.classList.remove('is-prev'));
            actual.classList.remove('is-active');
            actual.classList.add('is-prev');
            siguiente.classList.add('is-active');
            setTimeout(() => actual.classList.remove('is-prev'), FUNDIDO + 100);
        };

        setInterval(avanzar, DURACION);
    };

    window.initSmartImageFit = () => {
        document.querySelectorAll('.product-image-wrapper img').forEach(img => {
            const aplicar = () => {
                if (!img.naturalWidth || !img.naturalHeight) return;
                const esMuyAncha = img.naturalWidth / img.naturalHeight > 1;
                img.style.objectFit = esMuyAncha ? 'contain' : 'cover';
            };
            if (img.complete) aplicar();
            else img.addEventListener('load', aplicar, { once: true });
        });
    };

    let scrollObserver = null;
    window.initScrollReveal = () => {
        const revealElements = document.querySelectorAll('.reveal:not(.revealed):not([data-observed])');
        if (revealElements.length === 0) return;

        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (prefersReducedMotion) {
            revealElements.forEach(el => el.classList.add('revealed'));
            return;
        }

        if (!scrollObserver) {
            const observerOptions = {
                root: null,
                rootMargin: '0px',
                threshold: 0.15
            };

            scrollObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const delay = entry.target.getAttribute('data-delay');
                        if (delay) {
                            entry.target.style.transitionDelay = `${delay}ms`;
                        }
                        entry.target.classList.add('revealed');
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);
        }

        revealElements.forEach(el => {
            el.setAttribute('data-observed', 'true');
            scrollObserver.observe(el);
        });
    };

    const initSmoothScroll = () => {
        const links = document.querySelectorAll('a[href*="#"]');
        const headerHeight = 80;

        links.forEach(link => {
            link.addEventListener('click', function (e) {
                if (this.getAttribute('href') === '#') return;

                const targetUrl = new URL(this.href, window.location.origin);
                const currentUrl = new URL(window.location.href);

                const isSamePage = this.getAttribute('href').startsWith('#') ||
                    targetUrl.pathname === currentUrl.pathname ||
                    (targetUrl.pathname === '/' && currentUrl.pathname === '/index.html') ||
                    (targetUrl.pathname === '/index.html' && currentUrl.pathname === '/');

                if (isSamePage && targetUrl.hash) {
                    const targetElement = document.querySelector(targetUrl.hash);
                    if (targetElement) {
                        e.preventDefault();
                        const elementPosition = targetElement.getBoundingClientRect().top;
                        const offsetPosition = elementPosition + window.scrollY - headerHeight;

                        window.scrollTo({
                            top: offsetPosition,
                            behavior: 'smooth'
                        });

                        const navToggle = document.querySelector('.nav-toggle');
                        const mobileNav = document.querySelector('.mobile-nav');
                        if (navToggle && navToggle.classList.contains('active')) {
                            navToggle.classList.remove('active');
                            mobileNav.classList.remove('active');
                            document.body.classList.remove('no-scroll');
                        }
                    }
                }
            });
        });
    };

    const initFormHandling = () => {
        const form = document.querySelector('.contact-form');
        if (!form) return;

        const inputs = form.querySelectorAll('input, textarea');

        inputs.forEach(input => {
            const checkValue = () => {
                if (input.value.trim() !== '') {
                    input.classList.add('has-value');
                } else {
                    input.classList.remove('has-value');
                }
            };

            checkValue();

            input.addEventListener('input', checkValue);

            input.addEventListener('focus', () => {
                if (input.parentElement) {
                    input.parentElement.classList.add('is-focused');
                }
            });

            input.addEventListener('blur', () => {
                if (input.parentElement) {
                    input.parentElement.classList.remove('is-focused');
                }
            });
        });

        form.addEventListener('submit', (e) => {
            e.preventDefault();

            let isValid = true;
            const requiredFields = form.querySelectorAll('[required]');

            requiredFields.forEach(field => {
                if (field.value.trim() === '') {
                    isValid = false;
                    field.classList.add('error');
                } else {
                    field.classList.remove('error');
                }
            });

            if (isValid) {
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerText;

                submitBtn.innerText = 'Enviando...';
                submitBtn.disabled = true;

                setTimeout(() => {
                    submitBtn.innerText = '¡Mensaje Enviado!';
                    form.reset();
                    inputs.forEach(input => input.classList.remove('has-value', 'error'));

                    setTimeout(() => {
                        submitBtn.innerText = originalText;
                        submitBtn.disabled = false;
                    }, 3000);
                }, 1500);
            }
        });
    }

    window.initFiltersAndPagination = () => {
        const containers = document.querySelectorAll('.filter-container');
        if (containers.length === 0) return;

        containers.forEach(container => {
            const items = Array.from(container.querySelectorAll('.filter-item'));
            const perPage = parseInt(container.getAttribute('data-per-page')) || 6;

            const filterWrapper = container.previousElementSibling;
            let filterBtns = [];
            if (filterWrapper && filterWrapper.classList.contains('filters')) {
                filterBtns = filterWrapper.querySelectorAll('.filter-btn');
            }

            const paginationWrapper = container.nextElementSibling;

            const botonActivo = Array.from(filterBtns).find(b => b.classList.contains('active'));
            let currentFilter = botonActivo ? botonActivo.getAttribute('data-filter') : 'all';
            let currentPage = 1;

            const syncFilterState = () => {
                if (items.length === 0) return;
                const url = new URL(window.location.href);
                if (currentFilter === 'all') url.searchParams.delete('categoria');
                else url.searchParams.set('categoria', currentFilter);
                history.replaceState(null, '', url.pathname + url.search);

                items.forEach(item => {
                    if (item.tagName !== 'A') return;
                    const itemUrl = new URL(item.getAttribute('href'), window.location.href);
                    if (currentFilter === 'all') itemUrl.searchParams.delete('categoria');
                    else itemUrl.searchParams.set('categoria', currentFilter);
                    item.setAttribute('href', itemUrl.pathname + itemUrl.search);
                });
            };

            const render = () => {
                let filteredItems = items.filter(item => {
                    return currentFilter === 'all' || item.getAttribute('data-category') === currentFilter;
                });

                const totalPages = Math.ceil(filteredItems.length / perPage);
                if (currentPage > totalPages) currentPage = totalPages || 1;

                const startIndex = (currentPage - 1) * perPage;
                const endIndex = startIndex + perPage;

                items.forEach(item => {
                    item.classList.add('hidden');
                });

                filteredItems.slice(startIndex, endIndex).forEach((item, idx) => {
                    item.classList.remove('hidden');
                    item.classList.remove('revealed');
                    setTimeout(() => item.classList.add('revealed'), idx * 50);
                });

                renderPagination(totalPages);
            };

            const mqCompacto = window.matchMedia('(max-width: 640px)');
            let ultimoTotal = 0;

            const irAPagina = (pagina) => {
                currentPage = pagina;
                render();
                const headerHeight = 100;
                const elementPosition = container.getBoundingClientRect().top;
                window.scrollTo({
                    top: elementPosition + window.scrollY - headerHeight - 50,
                    behavior: 'smooth'
                });
            };

            const crearBoton = (texto, pagina, extra = '') => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = `page-btn ${extra}`.trim();
                btn.innerHTML = texto;
                if (pagina === currentPage && !extra) {
                    btn.classList.add('active');
                    btn.setAttribute('aria-current', 'page');
                }
                if (pagina < 1 || pagina > ultimoTotal) {
                    btn.disabled = true;
                } else {
                    btn.addEventListener('click', () => irAPagina(pagina));
                }
                return btn;
            };

            const renderPagination = (totalPages) => {
                ultimoTotal = totalPages;
                if (!paginationWrapper || !paginationWrapper.classList.contains('pagination')) return;
                paginationWrapper.innerHTML = '';
                if (totalPages <= 1) return;

                const vecinos = mqCompacto.matches ? 1 : 2;
                let desde = Math.max(2, currentPage - vecinos);
                let hasta = Math.min(totalPages - 1, currentPage + vecinos);
                const ancho = vecinos * 2;
                if (currentPage - vecinos <= 2) hasta = Math.min(totalPages - 1, 1 + ancho + 1);
                if (currentPage + vecinos >= totalPages - 1) desde = Math.max(2, totalPages - ancho - 1);
                if (desde === 3) desde = 2;
                if (hasta === totalPages - 2) hasta = totalPages - 1;

                const paginas = [1];
                if (desde > 2) paginas.push('…');
                for (let i = desde; i <= hasta; i++) paginas.push(i);
                if (hasta < totalPages - 1) paginas.push('…');
                paginas.push(totalPages);

                paginationWrapper.appendChild(crearBoton('&lsaquo;', currentPage - 1, 'page-btn--flecha'));
                paginas.forEach(p => {
                    if (p === '…') {
                        const puntos = document.createElement('span');
                        puntos.className = 'page-dots';
                        puntos.textContent = '…';
                        paginationWrapper.appendChild(puntos);
                    } else {
                        paginationWrapper.appendChild(crearBoton(String(p), p));
                    }
                });
                paginationWrapper.appendChild(crearBoton('&rsaquo;', currentPage + 1, 'page-btn--flecha'));
            };

            mqCompacto.addEventListener('change', () => renderPagination(ultimoTotal));

            filterBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    filterBtns.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    currentFilter = btn.getAttribute('data-filter');
                    currentPage = 1;
                    syncFilterState();
                    render();
                });
            });

            syncFilterState();
            render();
        });
    };

    const initBeforeAfter = () => {
        const seccion = document.querySelector('.before-after');
        const container = document.querySelector('.ba-container');
        if (!seccion || !container) return;

        const botones = [...seccion.querySelectorAll('[data-ba-ir]')];
        let pos = 50;
        let arrastrando = false;
        let finAnimacion = null;

        const aplicar = (valor) => {
            pos = Math.min(100, Math.max(0, valor));
            seccion.style.setProperty('--ba-pos', pos.toFixed(2));
            container.setAttribute('aria-valuenow', Math.round(pos));
            container.classList.toggle('sin-antes', pos < 12);
            container.classList.toggle('sin-despues', pos > 88);
            botones.forEach(b => b.classList.toggle('is-activo', Number(b.dataset.baIr) === Math.round(pos)));
        };

        const animarA = (valor) => {
            seccion.classList.add('is-animando');
            aplicar(valor);
            clearTimeout(finAnimacion);
            finAnimacion = setTimeout(() => seccion.classList.remove('is-animando'), 1150);
        };

        const posDesdeEvento = (e) => {
            const rect = container.getBoundingClientRect();
            return ((e.clientX - rect.left) / rect.width) * 100;
        };

        container.addEventListener('pointerdown', (e) => {
            arrastrando = true;
            clearTimeout(finAnimacion);
            seccion.classList.remove('is-animando');
            container.classList.add('is-arrastrando');
            aplicar(posDesdeEvento(e));
        });

        window.addEventListener('pointermove', (e) => {
            if (arrastrando) aplicar(posDesdeEvento(e));
        }, { passive: true });

        const soltar = () => {
            arrastrando = false;
            container.classList.remove('is-arrastrando');
        };
        window.addEventListener('pointerup', soltar);
        window.addEventListener('pointercancel', soltar);

        container.addEventListener('keydown', (e) => {
            const pasos = { ArrowLeft: -5, ArrowRight: 5, Home: -100, End: 100 };
            if (!(e.key in pasos)) return;
            e.preventDefault();
            animarA(pos + pasos[e.key]);
        });

        botones.forEach(b => b.addEventListener('click', () => animarA(Number(b.dataset.baIr))));

        aplicar(100);
        const observer = new IntersectionObserver((entries) => {
            if (!entries[0].isIntersecting) return;
            observer.disconnect();
            setTimeout(() => { if (!arrastrando) animarA(50); }, 500);
        }, { threshold: 0.5 });
        observer.observe(container);
    };

    const initLoader = () => {
        const loader = document.querySelector('.loader-wrapper');
        if (!loader) return;

        const esperar = ms => new Promise(r => setTimeout(r, ms));

        const animacionLista = esperar(2900);

        const hero = document.querySelector('.hero-slide.is-active img');
        const heroLista = new Promise(listo => {
            if (!hero || hero.complete) return listo();
            hero.addEventListener('load', listo, { once: true });
            hero.addEventListener('error', listo, { once: true });
        });

        animacionLista
            .then(() => Promise.race([heroLista, esperar(1500)]))
            .then(() => {
                loader.classList.add('hidden');
                setTimeout(() => loader.style.display = 'none', 1400);
            });
    };

    const initContacto = () => {
        const elementos = document.querySelectorAll('[data-contacto]');
        if (!elementos.length || window.location.protocol === 'file:') return;

        const lineas = texto => String(texto || '').split('\n').map(l => l.trim()).filter(Boolean);
        const conSaltos = (el, partes) => el.replaceChildren(
            ...partes.flatMap((parte, i) => i ? [document.createElement('br'), parte] : [parte])
        );
        const fijar = (el, atributo, valor) => {
            if (valor && el.getAttribute(atributo) !== valor) el.setAttribute(atributo, valor);
        };
        const actualizarDatosEstructurados = (el, c) => {
            try {
                const ld = JSON.parse(el.textContent);
                const empresa = (ld['@graph'] || [ld]).find(n => String(n['@id'] || '').endsWith('#empresa'));
                if (!empresa) return;
                empresa.telephone = c.telefono;
                empresa.email = c.email;
                if (empresa.address) empresa.address.streetAddress = c.direccion;
                empresa.sameAs = [c.instagram];
                el.textContent = JSON.stringify(ld);
            } catch (e) {  }
        };

        fetch(new URL('api/datos.php?tipo=contacto', document.baseURI))
            .then(res => res.ok ? res.json() : Promise.reject(res.status))
            .then(c => {
                if (!c || !c.links) return;
                elementos.forEach(el => {
                    switch (el.dataset.contacto) {
                        case 'telefono': fijar(el, 'href', c.links.tel); el.textContent = c.telefono; break;
                        case 'whatsapp': fijar(el, 'href', c.links.whatsapp); break;
                        case 'email': fijar(el, 'href', c.links.mailto); el.textContent = c.email; break;
                        case 'instagram': fijar(el, 'href', c.instagram); break;
                        case 'ubicacion': conSaltos(el, [c.direccion, c.zona, 'Argentina']); break;
                        case 'ubicacion-taller': conSaltos(el, [c.direccion, c.zona + ' — Argentina']); break;
                        case 'horario': conSaltos(el, lineas(c.horario)); break;
                        case 'horario-linea': el.textContent = lineas(c.horario).join(', '); break;
                        case 'mapa-ir': fijar(el, 'href', c.links.mapa_ir); break;
                        case 'mapa-embed': fijar(el, 'src', c.links.mapa_embed); break;
                        case 'ld': actualizarDatosEstructurados(el, c); break;
                    }
                });
            })
            .catch(() => {});
    };

    const initResenas = () => {
        const grilla = document.querySelector('[data-resenas]');
        if (!grilla || window.location.protocol === 'file:') return;

        const firma = lista => lista.map(r => [r.texto, r.nombre, r.detalle || ''].join('|')).join('||');
        const actuales = [...grilla.querySelectorAll('.testimonio-card')].map(card => ({
            texto: card.querySelector('.testimonio-text').textContent.replace(/\s+/g, ' ').trim(),
            nombre: card.querySelector('.testimonio-autor strong').textContent.trim(),
            detalle: (card.querySelector('.testimonio-autor span') || {}).textContent || ''
        }));

        const publicarDatosEstructurados = lista => {
            let ld = document.getElementById('ld-resenas');
            if (!lista.length) { if (ld) ld.remove(); return; }
            if (!ld) {
                ld = document.createElement('script');
                ld.type = 'application/ld+json';
                ld.id = 'ld-resenas';
                document.head.append(ld);
            }
            ld.textContent = JSON.stringify({
                '@context': 'https://schema.org',
                '@type': 'HomeAndConstructionBusiness',
                '@id': 'https://armartineau.com.ar/#empresa',
                name: 'Martineau',
                review: lista.map(r => ({
                    '@type': 'Review',
                    author: { '@type': 'Person', name: r.nombre },
                    reviewBody: r.texto
                }))
            });
        };
        publicarDatosEstructurados(actuales);

        fetch(new URL('api/datos.php?tipo=resenas', document.baseURI))
            .then(res => res.ok ? res.json() : Promise.reject(res.status))
            .then(resenas => {
                if (!Array.isArray(resenas)) return;
                publicarDatosEstructurados(resenas);

                const seccion = grilla.closest('section');
                if (resenas.length === 0) {
                    if (seccion) seccion.hidden = true;
                    return;
                }
                if (firma(resenas) === firma(actuales)) return;

                grilla.replaceChildren(...resenas.map(r => {
                    const card = document.createElement('div');
                    card.className = 'testimonio-card reveal';

                    const texto = document.createElement('p');
                    texto.className = 'testimonio-text';
                    texto.textContent = r.texto;

                    const autor = document.createElement('div');
                    autor.className = 'testimonio-autor';
                    const nombre = document.createElement('strong');
                    nombre.textContent = r.nombre;
                    autor.append(nombre);
                    if (r.detalle) {
                        const detalle = document.createElement('span');
                        detalle.textContent = r.detalle;
                        autor.append(detalle);
                    }

                    card.append(texto, autor);
                    return card;
                }));
                window.initScrollReveal();
            })
            .catch(() => {});
    };

    const init = () => {
        initContacto();
        initResenas();
        initLoader();
        initHeader();
        initMobileNav();
        initNavDropdowns();
        window.initScrollReveal();
        initSmoothScroll();
        initFormHandling();
        initBeforeAfter();
        initHeroSlideshow();
        initProceso();
        window.initFiltersAndPagination();
    };

    init();
});
