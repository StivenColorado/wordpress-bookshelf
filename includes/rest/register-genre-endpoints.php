<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registrar endpoints relacionados con géneros
 */
function bookshelf_register_genre_endpoints() {
    $namespace = bookshelf_get_api_namespace();

    // Ruta para obtener géneros
    register_rest_route($namespace, '/genres', [
        'methods' => 'GET',
        'callback' => 'bookshelf_get_genres',
        'permission_callback' => '__return_true',
    ]);
}

/**
 * Callbacks para los endpoints de géneros
 */

// Función para obtener géneros
function bookshelf_get_genres($request) {
    $terms = get_terms([
        'taxonomy' => 'genre',
        'hide_empty' => false,
    ]);

    if (is_wp_error($terms)) {
        return [];
    }

    $genres = [];
    foreach ($terms as $term) {
        $genres[] = [
            'id' => $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'description' => $term->description,
            'count' => $term->count,
            'color' => get_term_meta($term->term_id, 'genre_color', true),
            'description_extended' => get_term_meta($term->term_id, 'genre_description_extended', true),
        ];
    }

    return $genres;
}