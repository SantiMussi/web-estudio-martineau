# Martineau Studio — Web

Sitio web del estudio **Martineau**, empresa argentina fundada en 1922 dedicada a la fabricación artesanal de esculturas, chimeneas, ménsulas, maceteros y piezas de piedra reconstituida.

El sitio busca reflejar la identidad visual del estudio: elegante, sobrio, con esa onda de atelier que transmite oficio y tradición sin perder modernidad.

---

## Qué tiene

- **Landing** con hero parallax, scroll reveal y sección de antes/después interactiva (slider drag)
- **Catálogo de productos** con filtros por categoría, cargado dinámicamente desde la API
- **Portfolio de proyectos** en layout masonry con navegación a detalle individual
- **Página "Nosotros"** con timeline histórico del estudio (1922 → hoy)
- **Detalle de producto/proyecto** con galería de imágenes y specs. Se arman en el servidor (`producto.php`, `proyecto.php` + `inc/ficha.php`) para que cada ficha tenga su propio título, descripción, imagen al compartir y datos estructurados
- **SEO**: meta tags y Open Graph en todas las páginas, datos estructurados (schema.org), `robots.txt`, `sitemap.xml` generado desde la base (`sitemap.php`) y URLs amigables para las fichas (`/producto/chimenea-luis-xv`, ver `inc/slugs.php`; los links viejos con `?id=` y los slugs cambiados redirigen solos)
- **Panel admin** (PHP) para gestionar productos, proyectos y categorías con upload de imágenes y conversión a WebP, acciones sobre varios ítems a la vez, reseñas de clientes (sección Testimonios de Nosotros), papelera (30 días para restaurar), datos de contacto editables, fotos de la portada (con versión liviana para celular generada al subirlas y un editor básico: recorte con proporción libre o fija, brillo, contraste, saturación y qué parte se ve en celulares), revisión de fichas incompletas, listas con páginas, exportación a CSV y backup de la base. Los cambios de estructura de la base se aplican solos al entrar al panel (`admin/migraciones.php`)
- **Botón flotante de WhatsApp** con animación pulse
- **Diseño full responsive** — mobile-first con menú hamburguesa animado

---

## Stack

| Capa | Tecnología |
|------|-----------|
| Front | HTML5, CSS3 (vanilla), JavaScript ES6+ |
| Back / API | PHP + MySQL (PDO) |
| Tipografía | [Bodoni Moda](https://fonts.google.com/specimen/Bodoni+Moda) (estilo Didot, en todo el sitio) |
| Hosting | Servidor con soporte PHP (Apache + `.htaccess` para URLs limpias) |

Sin frameworks, sin bundlers, sin dependencias de npm. Todo vanilla. La idea es que sea liviano, rápido de deployar y fácil de mantener.

## Autor

Desarrollo por **[Santiago Mussi](https://www.santimussi.com)**.

