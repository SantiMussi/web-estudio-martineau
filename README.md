# 🔥 MartinEau Studio — Web

Sitio web del estudio **AR Martineau**, empresa argentina fundada en 1922 dedicada a la fabricación artesanal de esculturas, chimeneas, ménsulas, maceteros y piezas de piedra reconstituida.

El sitio busca reflejar la identidad visual del estudio: elegante, sobrio, con esa onda de atelier que transmite oficio y tradición sin perder modernidad.

---

## ✨ Qué tiene

- **Landing** con hero parallax, scroll reveal y sección de antes/después interactiva (slider drag)
- **Catálogo de productos** con filtros por categoría, cargado dinámicamente desde la API
- **Portfolio de proyectos** en layout masonry con navegación a detalle individual
- **Página "Nosotros"** con timeline histórico del estudio (1922 → hoy)
- **Detalle de producto/proyecto** con galería de imágenes y specs
- **Panel admin** (PHP) para gestionar productos, proyectos y categorías con upload de imágenes y conversión a WebP
- **Botón flotante de WhatsApp** con animación pulse
- **Diseño full responsive** — mobile-first con menú hamburguesa animado

---

## 🛠 Stack

| Capa | Tecnología |
|------|-----------|
| Front | HTML5, CSS3 (vanilla), JavaScript ES6+ |
| Back / API | PHP + MySQL (PDO) |
| Tipografía | [Cormorant Garamond](https://fonts.google.com/specimen/Cormorant+Garamond) + [Inter](https://fonts.google.com/specimen/Inter) |
| Hosting | Servidor con soporte PHP (Apache + `.htaccess` para URLs limpias) |

Sin frameworks, sin bundlers, sin dependencias de npm. Todo vanilla. La idea es que sea liviano, rápido de deployar y fácil de mantener.

---

## 📁 Estructura

```
├── index.html            # Landing principal
├── nosotros.html         # Página del estudio
├── catalogo.html         # Catálogo con filtros
├── portfolio.html        # Grilla de proyectos
├── producto.html         # Detalle de producto
├── proyecto.html         # Detalle de proyecto
├── styles.css            # Estilos globales
├── script.js             # Lógica del front (scroll, nav, lightbox, etc.)
├── store.js              # Capa de datos — fetch a la API y cache
├── .htaccess             # Rewrite rules para URLs sin extensión
│
├── api/
│   └── datos.php         # Endpoint REST (productos, proyectos, categorías)
│
├── admin/                # Panel de administración
│   ├── index.php         # Dashboard / ABM de productos
│   ├── proyectos.php     # ABM de proyectos
│   ├── categorias.php    # ABM de categorías
│   ├── config.php        # Conexión a BD (no versionado)
│   ├── login.php         # Auth del admin
│   └── ...
│
├── sim/                  # Datos de simulación para desarrollo local
│   └── data.json
│
└── assets/               # Imágenes estáticas del sitio
```

---

## 🚀 Setup local

1. Tener un servidor PHP corriendo (XAMPP, Laragon, MAMP, lo que sea)
2. Clonar el repo en la carpeta del servidor:
   ```bash
   git clone https://github.com/SantiMussi/web-estudio-martineau.git
   ```
3. Crear `admin/config.php` con los datos de tu BD local:
   ```php
   <?php
   $host = 'localhost';
   $db   = 'martineau';
   $user = 'root';
   $pass = '';
   $pdo  = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
   $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
   $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
   ```
4. Importar el schema de la base de datos
5. Entrar desde `http://localhost/web-estudio-martineau/`

> El `store.js` detecta si estás abriendo desde `file://` y te avisa que necesitás un servidor.

---

## 📝 Notas

- Las URLs limpias (`/catalogo` en vez de `/catalogo.html`) se manejan con `.htaccess`. Si usás Nginx, hay que adaptar las reglas.
- El `config.php` del admin está en `.gitignore` por seguridad — hay que crearlo a mano.
- Las imágenes subidas desde el admin se guardan en `admin/uploads/` (también ignorado del repo).

---

## 👤 Autor

Desarrollo por **[Santiago Mussi](https://www.santimussi.com)** — Drops Design.

