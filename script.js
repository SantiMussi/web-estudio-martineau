// MartinEau Studio - Script principal

document.addEventListener('DOMContentLoaded', () => {

    // Función para limitar la frecuencia de ejecución (Throttle)
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

    // Función para retrasar la ejecución (Debounce)
    const debounce = (func, delay) => {
        let timeout;
        return function () {
            const context = this;
            const args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), delay);
        };
    };

    // Comportamiento del header al hacer scroll
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

    // Menú de navegación móvil
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

    // Efecto Parallax en sección Hero — DESACTIVADO
    // Para reactivar: descomentar el cuerpo de la función
    const initParallax = () => {
        // Parallax desactivado intencionalmente
    };

    let scrollObserver = null;
    window.initScrollReveal = () => {
        // Solo buscamos los elementos que no han sido revelados ni observados
        const revealElements = document.querySelectorAll('.reveal:not(.revealed):not([data-observed])');
        if (revealElements.length === 0) return;

        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (prefersReducedMotion) {
            revealElements.forEach(el => el.classList.add('revealed'));
            return;
        }

        // Usamos un patrón Singleton: creamos el observer solo una vez
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

        // Asignamos el observer solo a los elementos nuevos
        revealElements.forEach(el => {
            el.setAttribute('data-observed', 'true');
            scrollObserver.observe(el);
        });
    };

    // Scroll suave a secciones del menú
    const initSmoothScroll = () => {
        const links = document.querySelectorAll('a[href*="#"]');
        const headerHeight = 80;

        links.forEach(link => {
            link.addEventListener('click', function (e) {
                if (this.getAttribute('href') === '#') return;

                const targetUrl = new URL(this.href, window.location.origin);
                const currentUrl = new URL(window.location.href);

                // Si apunta a la misma página
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

                        // Cerrar menú móvil si está abierto
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

    // Gestión del formulario de contacto
    const initFormHandling = () => {
        const form = document.querySelector('.contact-form');
        if (!form) return;

        const inputs = form.querySelectorAll('input, textarea');

        // Efecto visual para etiquetas flotantes
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

        // Envío y validación del formulario
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

                // Simulación de envío
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

    // Filtrado y Paginación
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

    // Antes y Después Slider
    const initBeforeAfter = () => {
        const container = document.querySelector('.ba-container');
        const slider = document.querySelector('.ba-slider');
        const afterImage = document.querySelector('.ba-after');

        if (!container || !slider || !afterImage) return;

        let isDragging = false;
        let isDemoing = false;

        const moveSlider = (e) => {
            if (!isDragging && e.type !== 'mousedown' && e.type !== 'touchstart') return;
            isDemoing = false; // Cancelar demo si el usuario interactúa activamente

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

        // Permite hacer clic en cualquier parte del contenedor para mover el slider
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

        // Animación de Demo Automática (2 segundos)
        const playDemo = () => {
            if (container.classList.contains('demo-played')) return;
            container.classList.add('demo-played');
            isDemoing = true;

            const start = performance.now();
            const duration = 2000;

            const animateDemo = (time) => {
                if (!isDemoing) return; // Se detiene si el usuario interactúa

                let elapsed = time - start;
                let progress = elapsed / duration;

                if (progress > 1) {
                    isDemoing = false;

                    // Asegurar que termine exactamente en el centro (50%)
                    slider.style.left = `50%`;
                    afterImage.style.clipPath = `polygon(0 0, 50% 0, 50% 100%, 0 100%)`;
                    return;
                }

                // Función seno: oscila desde 50% a 70%, baja a 30% y vuelve a 50%
                const percentage = 50 + Math.sin(progress * Math.PI * 2) * 20;

                slider.style.left = `${percentage}%`;
                afterImage.style.clipPath = `polygon(0 0, ${percentage}% 0, ${percentage}% 100%, 0 100%)`;

                requestAnimationFrame(animateDemo);
            };

            requestAnimationFrame(animateDemo);
        };

        // Observar cuando entra en pantalla
        const observer = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting) {
                setTimeout(playDemo, 400); // Pequeño retraso para mayor impacto
                observer.disconnect();
            }
        }, { threshold: 0.6 });

        observer.observe(container);
    };

    // Loader Premium
    const initLoader = () => {
        const loader = document.querySelector('.loader-wrapper');
        if (!loader) return;

        // Simular tiempo de carga para ver la animación de construcción (2.5s)
        window.addEventListener('load', () => {
            setTimeout(() => {
                loader.classList.add('hidden');
                setTimeout(() => loader.style.display = 'none', 1000);
            }, 2700);
        });
    };

    // Inicialización de componentes
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
