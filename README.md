# MartinEau Studio — Web

Sitio web del estudio **AR Martineau**, empresa argentina fundada en 1922 dedicada a la fabricación artesanal de esculturas, chimeneas, ménsulas, maceteros y piezas de piedra reconstituida.

El sitio busca reflejar la identidad visual del estudio: elegante, sobrio, con esa onda de atelier que transmite oficio y tradición sin perder modernidad.

---

## Qué tiene

- **Landing** con hero parallax, scroll reveal y sección de antes/después interactiva (slider drag)
- **Catálogo de productos** con filtros por categoría, cargado dinámicamente desde la API
- **Portfolio de proyectos** en layout masonry con navegación a detalle individual
- **Página "Nosotros"** con timeline histórico del estudio (1922 → hoy)
- **Detalle de producto/proyecto** con galería de imágenes y specs
- **Panel admin** (PHP) para gestionar productos, proyectos y categorías con upload de imágenes y conversión a WebP
- **Botón flotante de WhatsApp** con animación pulse
- **Diseño full responsive** — mobile-first con menú hamburguesa animado

---

## Stack

| Capa | Tecnología |
|------|-----------|
| Front | HTML5, CSS3 (vanilla), JavaScript ES6+ |
| Back / API | PHP + MySQL (PDO) |
| Tipografía | [Cormorant Garamond](https://fonts.google.com/specimen/Cormorant+Garamond) + [Inter](https://fonts.google.com/specimen/Inter) |
| Hosting | Servidor con soporte PHP (Apache + `.htaccess` para URLs limpias) |

Sin frameworks, sin bundlers, sin dependencias de npm. Todo vanilla. La idea es que sea liviano, rápido de deployar y fácil de mantener.

## Autor

Desarrollo por **[Santiago Mussi](https://www.santimussi.com)**.

