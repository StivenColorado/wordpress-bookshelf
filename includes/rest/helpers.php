<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Funciones auxiliares para la API REST
 */

// Función helper para formatear un libro
function bookshelf_format_book($post) {
    $genres = get_the_terms($post->ID, 'genre');
    $genre_data = [];
    
    if ($genres && !is_wp_error($genres)) {
        foreach ($genres as $genre) {
            $genre_data[] = [
                'id' => $genre->term_id,
                'name' => $genre->name,
                'slug' => $genre->slug,
                'color' => get_term_meta($genre->term_id, 'genre_color', true),
            ];
        }
    }

    return [
        'id' => $post->ID,
        'title' => $post->post_title,
        'content' => $post->post_content,
        'excerpt' => $post->post_excerpt,
        'author' => get_post_meta($post->ID, 'author', true),
        'published_year' => get_post_meta($post->ID, 'published_year', true),
        'isbn' => get_post_meta($post->ID, 'isbn', true),
        'pages' => get_post_meta($post->ID, 'pages', true),
        'genres' => $genre_data,
        'date_created' => $post->post_date,
        'date_modified' => $post->post_modified,
        'featured_image' => get_the_post_thumbnail_url($post->ID, 'medium'),
    ];
}

// Validaciones personalizadas
function bookshelf_validate_title($param) {
    return !empty(trim($param));
}

function bookshelf_validate_author($param) {
    return !empty(trim($param));
}

function bookshelf_validate_year($param) {
    return $param >= 1000 && $param <= date('Y');
}

function bookshelf_validate_numeric($param) {
    return is_numeric($param);
}

// Sanitizaciones personalizadas
function bookshelf_sanitize_genres($genres) {
    return array_map('absint', (array) $genres);
}

// Constantes
function bookshelf_get_api_namespace() {
    return 'bookshelf/v1';
}

// Argumentos comunes para validación
function bookshelf_get_common_book_args() {
    return [
        'title' => [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'bookshelf_validate_title',
        ],
        'content' => [
            'type' => 'string',
            'sanitize_callback' => 'wp_kses_post',
        ],
        'author' => [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'bookshelf_validate_author',
        ],
        'published_year' => [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'validate_callback' => 'bookshelf_validate_year',
        ],
        'isbn' => [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ],
        'pages' => [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
        ],
        'genres' => [
            'type' => 'array',
            'items' => [
                'type' => 'integer',
            ],
            'sanitize_callback' => 'bookshelf_sanitize_genres',
        ]
    ];
}