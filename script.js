document.addEventListener('DOMContentLoaded', async () => {

    // throttle helper
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

    // debounce helper
    const debounce = (func, delay) => {
        let timeout;
        return function () {
            const context = this;
            const args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), delay);
        };
    };

    // header sticky
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

    // menu mobile
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

    // parallax (desactivado por ahora)
    const initParallax = () => {
        // TODO: ver si lo volvemos a meter
    };

    let scrollObserver = null;
    window.initScrollReveal = () => {
        // filtramos los que ya se animaron o estan trackeados
        const revealElements = document.querySelectorAll('.reveal:not(.revealed):not([data-observed])');
        if (revealElements.length === 0) return;

        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (prefersReducedMotion) {
            revealElements.forEach(el => el.classList.add('revealed'));
            return;
        }

        // instanciamos el observer una sola vez (singleton)
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

        // trackeamos los elementos nuevos
        revealElements.forEach(el => {
            el.setAttribute('data-observed', 'true');
            scrollObserver.observe(el);
        });
    };

    // smooth scroll p/ anchors
    const initSmoothScroll = () => {
        const links = document.querySelectorAll('a[href*="#"]');
        const headerHeight = 80;

        links.forEach(link => {
            link.addEventListener('click', function (e) {
                if (this.getAttribute('href') === '#') return;

                const targetUrl = new URL(this.href, window.location.origin);
                const currentUrl = new URL(window.location.href);

                // Si apunta a la misma pagina
                const isSamePage = targetUrl.pathname === currentUrl.pathname ||
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

                        // cerramos el menu si esta abierto en mobile
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

    // forms
    const initFormHandling = () => {
        const form = document.querySelector('.contact-form');
        if (!form) return;

        const inputs = form.querySelectorAll('input, textarea');

        // floating labels
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

        // submit & checks
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

                // mock api call
                setTimeout(() => {
                    submitBtn.innerText = 'Â¡Mensaje Enviado!';
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

    // filtros / paginado
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

            let currentFilter = 'all';
            let currentPage = 1;

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

                if (paginationWrapper && paginationWrapper.classList.contains('pagination')) {
                    paginationWrapper.innerHTML = '';

                    if (totalPages > 1) {
                        for (let i = 1; i <= totalPages; i++) {
                            const btn = document.createElement('button');
                            btn.className = `page-btn ${i === currentPage ? 'active' : ''}`;
                            btn.innerText = i;
                            btn.addEventListener('click', () => {
                                currentPage = i;
                                render();
                                const headerHeight = 100;
                                const elementPosition = container.getBoundingClientRect().top;
                                window.scrollTo({
                                    top: elementPosition + window.scrollY - headerHeight - 50,
                                    behavior: 'smooth'
                                });
                            });
                            paginationWrapper.appendChild(btn);
                        }
                    }
                }
            };

            filterBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    filterBtns.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    currentFilter = btn.getAttribute('data-filter');
                    currentPage = 1;
                    render();
                });
            });

            render();
        });
    };

    // slider before/after
    const initBeforeAfter = () => {
        const container = document.querySelector('.ba-container');
        const slider = document.querySelector('.ba-slider');
        const afterImage = document.querySelector('.ba-after');

        if (!container || !slider || !afterImage) return;

        let isDragging = false;
        let isDemoing = false;

        const moveSlider = (e) => {
            if (!isDragging && e.type !== 'mousedown' && e.type !== 'touchstart') return;
            isDemoing = false; // Cancelar demo si el usuario interactua activamente

            const rect = container.getBoundingClientRect();
            let x = 0;

            if (e.type.startsWith('touch')) {
                x = e.touches[0].clientX;
            } else {
                x = e.clientX;
            }

            x = x - rect.left;

            // Limit bounds
            let percentage = (x / rect.width) * 100;
            if (percentage < 0) percentage = 0;
            if (percentage > 100) percentage = 100;

            slider.style.left = `${percentage}%`;
            afterImage.style.clipPath = `polygon(0 0, ${percentage}% 0, ${percentage}% 100%, 0 100%)`;
        };

        // click/drag en cualquier parte
        container.addEventListener('mousedown', (e) => {
            isDragging = true;
            moveSlider(e);
        });

        container.addEventListener('touchstart', (e) => {
            isDragging = true;
            moveSlider(e);
        }, { passive: true });

        window.addEventListener('mouseup', () => isDragging = false);
        window.addEventListener('touchend', () => isDragging = false);

        window.addEventListener('mousemove', moveSlider);
        window.addEventListener('touchmove', moveSlider, { passive: true });

        // autoplay demo
        const playDemo = () => {
            if (container.classList.contains('demo-played')) return;
            container.classList.add('demo-played');
            isDemoing = true;

            const start = performance.now();
            const duration = 2000;

            const animateDemo = (time) => {
                if (!isDemoing) return; // Se detiene si el usuario interactua

                let elapsed = time - start;
                let progress = elapsed / duration;

                if (progress > 1) {
                    isDemoing = false;

                    // Asegurar que termine exactamente en el centro (50%)
                    slider.style.left = `50%`;
                    afterImage.style.clipPath = `polygon(0 0, 50% 0, 50% 100%, 0 100%)`;
                    return;
                }

                // oscilacion suave p/ llamar la atencion
                const percentage = 50 + Math.sin(progress * Math.PI * 2) * 20;

                slider.style.left = `${percentage}%`;
                afterImage.style.clipPath = `polygon(0 0, ${percentage}% 0, ${percentage}% 100%, 0 100%)`;

                requestAnimationFrame(animateDemo);
            };

            requestAnimationFrame(animateDemo);
        };

        // trigger demo on scroll
        const observer = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting) {
                setTimeout(playDemo, 400);
                observer.disconnect();
            }
        }, { threshold: 0.6 });

        observer.observe(container);
    };

    // full page loader
    const initLoader = () => {
        const loader = document.querySelector('.loader-wrapper');
        if (!loader) return;

        // fake timer para ver la animacion
        window.addEventListener('load', () => {
            setTimeout(() => {
                loader.classList.add('hidden');
                setTimeout(() => loader.style.display = 'none', 1000);
            }, 3000);
        });
    };

    function capitalizar(str) {
        return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
    }

    function notFound(msg, link, linkText) {
        return '<div style="text-align:center;padding:8rem 2rem"><p style="color:var(--color-text-muted)">' + msg + '</p>' +
            '<a href="' + link + '" class="btn btn-outline-light" style="margin-top:2rem;display:inline-block">' + linkText + '</a></div>';
    }

    async function renderPages() {
        if (typeof Store === 'undefined') return;
        await Store.init();

        // index
        const gridDestacados = document.getElementById('home-catalog-grid');
        if (gridDestacados) {
            const destacados = Store.getProductosDestacados();
            if (destacados.length === 0) {
                gridDestacados.innerHTML = '<p style="color:var(--color-text-muted); text-align:center; padding: 3rem 0; grid-column: 1/-1;">No hay productos destacados.</p>';
            } else {
                gridDestacados.innerHTML = destacados.map(producto => `
                <a href="producto?id=${producto.id}" class="product-card reveal">
                    <div class="product-image-wrapper">
                    <img src="${producto.imagen}" alt="${producto.titulo}">
                    </div>
                    <div class="product-body">
                    <span class="product-category">${capitalizar(producto.categoria)}</span>
                    <h3 class="product-title">${producto.titulo}</h3>
                    <p class="product-description">${producto.descripcion}</p>
                    </div>
                </a>
                `).join('');
            }

            const gridProyectosDestacados = document.getElementById('home-portfolio-grid');
            if (gridProyectosDestacados) {
                const proyectosDestacados = Store.getProyectosDestacados();
                if (proyectosDestacados.length === 0) {
                    gridProyectosDestacados.innerHTML = '<p style="color:var(--color-text-muted); text-align:center; padding: 3rem 0; grid-column: 1/-1;">No hay proyectos destacados.</p>';
                } else {
                    gridProyectosDestacados.innerHTML = proyectosDestacados.map((proyecto, index) => {
                        const esTall = index === 0 || index === 3;
                        const tallClass = esTall ? 'portfolio-item--tall' : '';
                        return `
                        <a href="proyecto?id=${proyecto.id}" class="portfolio-item ${tallClass} reveal">
                            <img src="${proyecto.imagen}" alt="${proyecto.titulo}">
                            <figcaption class="portfolio-overlay">
                            <h3 class="portfolio-title">${proyecto.titulo}</h3>
                            <p class="portfolio-context">${capitalizar(proyecto.categoria)} — ${proyecto.anio}</p>
                            </figcaption>
                        </a>
                        `;
                    }).join('');
                }
            }
        }

        // cat
        const gridCatalogo = document.getElementById('catalog-grid');
        if (gridCatalogo) {
            const filterContainer = document.getElementById('catalog-filters');
            const categorias = Store.getCategorias('producto');

            if (filterContainer && categorias.length > 0) {
                filterContainer.innerHTML = '<button class="filter-btn active" data-filter="all">Todas</button>' +
                    categorias.map(cat => `<button class="filter-btn" data-filter="${cat.slug}">${capitalizar(cat.nombre)}</button>`).join('');
            }

            const productos = Store.getProductos();
            if (productos.length === 0) {
                gridCatalogo.innerHTML = '<p style="color:var(--color-text-muted); text-align:center; padding: 3rem 0;">No hay productos disponibles.</p>';
            } else {
                gridCatalogo.innerHTML = productos.map(producto => `
                <a href="producto?id=${producto.id}" class="product-card filter-item" data-category="${producto.categoria}">
                    <div class="product-image-wrapper">
                    <img src="${producto.imagen}" alt="${producto.titulo}">
                    </div>
                    <div class="product-body">
                    <span class="product-category">${capitalizar(producto.categoria)}</span>
                    <h3 class="product-title">${producto.titulo}</h3>
                    </div>
                </a>
                `).join('');
            }
        }

        // portfolio
        const gridPortfolio = document.getElementById('portfolio-grid');
        if (gridPortfolio) {
            const filterContainer = document.getElementById('portfolio-filters');
            const categorias = Store.getCategorias('proyecto');

            if (filterContainer && categorias.length > 0) {
                filterContainer.innerHTML = '<button class="filter-btn active" data-filter="all">Todos</button>' +
                    categorias.map(cat => `<button class="filter-btn" data-filter="${cat.slug}">${capitalizar(cat.nombre)}</button>`).join('');
            }

            const proyectos = Store.getProyectos();
            if (proyectos.length === 0) {
                gridPortfolio.innerHTML = '<p style="color:var(--color-text-muted); text-align:center; padding: 3rem 0;">No hay proyectos disponibles.</p>';
            } else {
                gridPortfolio.innerHTML = proyectos.map((proyecto, index) => {
                    const esTall = index % 3 === 0;
                    const tallClass = esTall ? 'portfolio-item--tall' : '';
                    return `
                    <a href="proyecto?id=${proyecto.id}" class="portfolio-item ${tallClass} filter-item" data-category="${proyecto.categoria}">
                        <img src="${proyecto.imagen}" alt="${proyecto.titulo}">
                        <figcaption class="portfolio-overlay">
                        <h3 class="portfolio-title">${proyecto.titulo}</h3>
                        <p class="portfolio-context">${capitalizar(proyecto.categoria)} — ${proyecto.anio}</p>
                        </figcaption>
                    </a>
                    `;
                }).join('');
            }
        }

        // detalle prod
        const rootProducto = document.getElementById('product-detail-root');
        if (rootProducto) {
            const params = new URLSearchParams(window.location.search);
            const id = params.get('id');

            if (!id) {
                rootProducto.innerHTML = notFound('No se especificó ningún producto.', 'catalogo', 'Ver catálogo');
            } else {
                const producto = Store.getProductoById(id);
                if (!producto) {
                    rootProducto.innerHTML = notFound('Producto no encontrado.', 'catalogo', 'Ver catálogo');
                } else {
                    document.title = producto.titulo + ' - AR Martineau';

                    const imagenes = [producto.imagen];
                    if (producto.imagenes && producto.imagenes.length > 0) {
                        imagenes.push(...producto.imagenes);
                    }

                    const thumbsHTML = imagenes.map((src, i) =>
                        '<div class="gallery-thumb ' + (i === 0 ? 'active' : '') + '" onclick="cambiarImagen(this,\'' + src + '\')">' +
                        '<img src="' + src + '" alt="Vista ' + (i + 1) + '"></div>'
                    ).join('');

                    const specsHTML = (producto.specs || []).map(s =>
                        '<div class="spec-item"><span class="spec-label">' + s.label + '</span><span class="spec-value">' + s.value + '</span></div>'
                    ).join('');

                    rootProducto.innerHTML =
                        '<div class="back-link-wrapper reveal">' +
                        '<a href="catalogo" class="back-link">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
                        '<line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline>' +
                        '</svg> Volver al Catálogo' +
                        '</a>' +
                        '</div>' +
                        '<div class="product-detail-grid">' +
                        '<div class="product-gallery reveal">' +
                        '<div class="gallery-main"><img src="' + imagenes[0] + '" alt="' + producto.titulo + '" id="main-product-image"></div>' +
                        '<div class="gallery-thumbnails">' + thumbsHTML + '</div>' +
                        '</div>' +
                        '<div class="product-info reveal">' +
                        '<span class="product-info-category">' + capitalizar(producto.categoria) + '</span>' +
                        '<h1 class="product-info-title">' + producto.titulo + '</h1>' +
                        '<p class="product-info-desc">' + producto.descripcion + '</p>' +
                        (specsHTML ? '<div class="product-specs">' + specsHTML + '</div>' : '') +
                        '<div class="product-actions"><a href="#contacto" class="btn">Solicitar Cotización</a></div>' +
                        '</div>' +
                        '</div>';

                    document.querySelectorAll('.gallery-thumb').forEach(thumb => {
                        thumb.addEventListener('click', function () {
                            document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
                            this.classList.add('active');
                        });
                    });
                }
            }
        }

        // detalle proj
        const rootProyecto = document.getElementById('proyecto-detail-root');
        if (rootProyecto) {
            const params = new URLSearchParams(window.location.search);
            const id = params.get('id');

            if (!id) {
                rootProyecto.innerHTML = notFound('No se especificó ningún proyecto.', 'portfolio', 'Ver portfolio');
            } else {
                const proyecto = Store.getProyectoById(id);
                if (!proyecto) {
                    rootProyecto.innerHTML = notFound('Proyecto no encontrado.', 'portfolio', 'Ver portfolio');
                } else {
                    document.title = proyecto.titulo + ' — AR Martineau';

                    const imagenes = [proyecto.imagen];
                    if (proyecto.imagenes && proyecto.imagenes.length > 0) {
                        imagenes.push(...proyecto.imagenes);
                    }

                    const thumbsHTML = imagenes.map((src, i) =>
                        '<div class="gallery-thumb ' + (i === 0 ? 'active' : '') + '" onclick="cambiarImagen(\'' + src + '\')">' +
                        '<img src="' + src + '" alt="Vista ' + (i + 1) + '"></div>'
                    ).join('');

                    const specsHTML = (proyecto.specs || []).map(s =>
                        '<div class="spec-item"><span class="spec-label">' + s.label + '</span><span class="spec-value">' + s.value + '</span></div>'
                    ).join('');

                    rootProyecto.innerHTML =
                        '<div class="back-link-wrapper reveal">' +
                        '<a href="portfolio" class="back-link">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
                        '<line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline>' +
                        '</svg> Volver al Portfolio' +
                        '</a>' +
                        '</div>' +
                        '<div class="product-detail-grid">' +
                        '<div class="product-gallery reveal">' +
                        '<div class="gallery-main"><img src="' + imagenes[0] + '" alt="' + proyecto.titulo + '" id="main-proyecto-image"></div>' +
                        '<div class="gallery-thumbnails">' + thumbsHTML + '</div>' +
                        '</div>' +
                        '<div class="product-info reveal">' +
                        '<span class="product-info-category">' + capitalizar(proyecto.categoria) + ' — ' + (proyecto.anio || '') + '</span>' +
                        '<h1 class="product-info-title">' + proyecto.titulo + '</h1>' +
                        '<p class="product-info-desc">' + (proyecto.descripcion || '') + '</p>' +
                        (specsHTML ? '<div class="product-specs">' + specsHTML + '</div>' : '') +
                        '<div class="product-actions"><a href="#contacto" class="btn">Consultar por este proyecto</a></div>' +
                        '</div>' +
                        '</div>';

                    document.querySelectorAll('.gallery-thumb').forEach(thumb => {
                        thumb.addEventListener('click', function () {
                            document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
                            this.classList.add('active');
                        });
                    });
                }
            }
        }
    }

    await renderPages();

    // init / fire it up
    const init = () => {
        initLoader();
        initHeader();
        initMobileNav();
        // initParallax(); // Parallax desactivado
        window.initScrollReveal();
        initSmoothScroll();
        initFormHandling();
        initBeforeAfter();
        window.initFiltersAndPagination();
    };

    init();
});

window.cambiarImagen = function (arg1, arg2) {
    const src = arg2 ? arg2 : arg1;
    const imgProduct = document.getElementById('main-product-image');
    const imgProyecto = document.getElementById('main-proyecto-image');
    if (imgProduct) imgProduct.src = src;
    if (imgProyecto) imgProyecto.src = src;
};
