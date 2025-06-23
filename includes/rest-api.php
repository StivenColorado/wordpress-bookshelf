<?php
if (!defined('ABSPATH')) {
    exit;
}

// Cargar archivos de la API REST
require_once plugin_dir_path(__FILE__) . 'rest/helpers.php';
require_once plugin_dir_path(__FILE__) . 'rest/register-book-endpoints.php';
require_once plugin_dir_path(__FILE__) . 'rest/register-genre-endpoints.php';

// Inicializar las rutas de la API
function bookshelf_register_api_routes() {
    bookshelf_register_book_endpoints();
    bookshelf_register_genre_endpoints();
}
add_action('rest_api_init', 'bookshelf_register_api_routes');