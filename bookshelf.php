<?php
/**
 * Plugin Name: BookShelf
 * Description: Gestiona libros con CPT, REST API y React.
 * Version: 1.0
 * Author: Stiven Colorado
 * Text Domain: bookshelf
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define constantes
define('BOOKSHELF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BOOKSHELF_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Carga módulos
require_once BOOKSHELF_PLUGIN_PATH . 'includes/cpt-book.php';
require_once BOOKSHELF_PLUGIN_PATH . 'includes/taxonomy-genre.php';
require_once BOOKSHELF_PLUGIN_PATH . 'includes/rest-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/cli/seed.php';

// Activación del plugin
function bookshelf_activate() {
    // Registrar CPT y taxonomía
    bookshelf_register_book_cpt();
    bookshelf_register_genre_taxonomy();
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Crear géneros por defecto
    $default_genres = ['Ficción', 'No Ficción', 'Ciencia Ficción', 'Romance', 'Misterio', 'Biografía'];
    foreach ($default_genres as $genre) {
        if (!term_exists($genre, 'genre')) {
            wp_insert_term($genre, 'genre');
        }
    }
}
register_activation_hook(__FILE__, 'bookshelf_activate');

// Desactivación del plugin
function bookshelf_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'bookshelf_deactivate');

// Enqueue React app en frontend y admin
function bookshelf_enqueue_assets() {
    $build_path = BOOKSHELF_PLUGIN_PATH . 'build/index.js';
    
    if (file_exists($build_path)) {
        wp_enqueue_script(
            'bookshelf-react-app',
            BOOKSHELF_PLUGIN_URL . 'build/index.js',
            ['wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n'],
            filemtime($build_path),
            true
        );
        
        // Localizar script con datos necesarios
        wp_localize_script('bookshelf-react-app', 'bookshelfData', [ //inyectar variables locales
            'apiUrl' => rest_url('bookshelf/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'canEdit' => current_user_can('edit_posts'),
            'canDelete' => current_user_can('delete_posts'),
        ]);
        
        wp_enqueue_style(
            'wp-components'
        );
    }
}
add_action('wp_enqueue_scripts', 'bookshelf_enqueue_assets');
add_action('admin_enqueue_scripts', 'bookshelf_enqueue_assets');

// Shortcode para render React en frontend
function bookshelf_shortcode($atts = []) {
    $atts = shortcode_atts([
        'view' => 'list', // list, form
    ], $atts);
    
    return '<div id="bookshelf-root" data-view="' . esc_attr($atts['view']) . '"></div>'; //punto de montaje
}
add_shortcode('bookshelf', 'bookshelf_shortcode');

// Página de administración
function bookshelf_admin_menu() {
    add_menu_page(
        'BookShelf',
        'BookShelf',
        'manage_options',
        'bookshelf',
        'bookshelf_admin_page',
        'dashicons-book',
        30
    );
}
add_action('admin_menu', 'bookshelf_admin_menu');

function bookshelf_admin_page() {
    ?>
    <div class="wrap">
        <h1>BookShelf - Gestión de Libros</h1>
        <div id="bookshelf-root" data-context="admin"></div>
    </div>
    <?php
}