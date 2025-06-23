<?php
if (!defined('ABSPATH')) exit;

function bookshelf_seed_books($total = 20) {
    $sample_authors = ['Gabriel García Márquez', 'Julio Verne', 'J.K. Rowling', 'Stephen King'];
    $years = range(1950, date('Y'));
    $pages_range = range(100, 800);

    $genres = get_terms(['taxonomy' => 'genre', 'hide_empty' => false]);

    if (is_wp_error($genres) || empty($genres)) {
        $default_genres = ['Fantasía', 'Terror', 'Ciencia Ficción', 'Romance'];
        foreach ($default_genres as $genre_name) {
            wp_insert_term($genre_name, 'genre');
        }
    
        // Reintenta obtenerlos después de insertarlos
        $genres = get_terms(['taxonomy' => 'genre', 'hide_empty' => false]);
    }
    

    for ($i = 1; $i <= $total; $i++) {
        $title = "Libro de prueba $i";
        $content = "Contenido de ejemplo para el libro número $i.";

        $book_id = wp_insert_post([
            'post_type' => 'book',
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => 'publish'
        ]);

        if (is_wp_error($book_id)) continue;

        update_post_meta($book_id, 'author', $sample_authors[array_rand($sample_authors)]);
        update_post_meta($book_id, 'published_year', $years[array_rand($years)]);
        update_post_meta($book_id, 'pages', rand(100, 800));
        update_post_meta($book_id, 'isbn', '978-' . rand(1000000000, 9999999999));

        // Asignar género aleatorio
        $random_genre = $genres[array_rand($genres)];
        wp_set_object_terms($book_id, [$random_genre->term_id], 'genre');
    }

    return new WP_REST_Response(['message' => "$total libros creados"], 200);
}

if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('bookshelf seed', function ($args, $assoc_args) {
        $total = isset($assoc_args['total']) ? intval($assoc_args['total']) : 20;

        $result = bookshelf_seed_books($total);

        if (is_wp_error($result)) {
            WP_CLI::error($result->get_error_message());
        } else {
            WP_CLI::success("Se crearon $total libros de prueba.");
        }
    });
}

