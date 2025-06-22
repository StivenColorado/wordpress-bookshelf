# 📚 BookShelf - WordPress Plugin

Plugin de gestión de libros desarrollado con WordPress + React para demostración técnica.

## ✨ Características

- **CRUD completo** de libros con Custom Post Types
- **API REST personalizada** (`/wp-json/bookshelf/v1/`)
- **Interfaz React SPA** 
- **Taxonomías personalizadas** (géneros, autores)
- **Filtros y validaciones** en API

## 🛠️ Stack Técnico

- WordPress (CPT + Taxonomías)
- React + @wordpress/scripts
- REST API extendida

## 🚀 Instalación

1. Descarga y descomprime en `/wp-content/plugins/`
2. Activa el plugin desde el admin de WordPress
3. Accede a "BookShelf" en el menú lateral

## 📁 Estructura

```
bookshelf/
├── bookshelf.php          # Plugin principal
├── includes/              # Clases PHP (CPT, API, Admin)
├── src/                  # Código fuente React
└── build/                # Assets de producción
```

## 🔧 Desarrollo

```bash
# Instalar dependencias
npm install

# Desarrollo (watch mode)
npm start

# Build para producción
npm run build
```

## 📡 Endpoints API - se usa WP_REST_Response y no WP_SEND_JSON lo cual no es visible ante una URL

- `GET /wp-json/bookshelf/v1/books` - Lista libros
- `POST /wp-json/bookshelf/v1/books` - Crear libro
- `PUT /wp-json/bookshelf/v1/books/{id}` - Actualizar libro
- `DELETE /wp-json/bookshelf/v1/books/{id}` - Eliminar libro