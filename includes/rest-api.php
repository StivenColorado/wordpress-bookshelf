<?php
if (!defined('ABSPATH')) {
    exit;
}

function bookshelf_register_api_routes() {
    $namespace = 'bookshelf/v1';
    
    // Endpoint para ejecutar las semillas
    register_rest_route($namespace, '/seed', [
        'methods' => 'GET',
        'callback' => 'bookshelf_execute_seed',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
        'args' => [
            'total' => [
                'description' => 'Número de libros a generar',
                'type' => 'integer',
                'default' => 20,
                'minimum' => 1,
                'maximum' => 100,
                'sanitize_callback' => 'absint',
            ],
        ],
    ]);

    // Ruta para listar libros con filtros y paginación
    register_rest_route($namespace, '/books', [
        'methods' => 'GET',
        'callback' => 'bookshelf_get_books',
        'permission_callback' => '__return_true',
        'args' => [
            'genre' => [
                'description' => 'Filtrar por género',
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'author' => [
                'description' => 'Filtrar por autor',
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'year' => [
                'description' => 'Filtrar por año de publicación',
                'type' => 'integer',
                'sanitize_callback' => 'absint',
            ],
            'per_page' => [
                'description' => 'Libros por página',
                'type' => 'integer',
                'default' => 10,
                'minimum' => 1,
                'maximum' => 100,
                'sanitize_callback' => 'absint',
            ],
            'page' => [
                'description' => 'Página actual',
                'type' => 'integer',
                'default' => 1,
                'minimum' => 1,
                'sanitize_callback' => 'absint',
            ],
            'search' => [
                'description' => 'Buscar en título y contenido',
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'orderby' => [
                'description' => 'Ordenar por',
                'type' => 'string',
                'default' => 'date',
                'enum' => ['date', 'title', 'author', 'year'],
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'order' => [
                'description' => 'Orden',
                'type' => 'string',
                'default' => 'DESC',
                'enum' => ['ASC', 'DESC'],
                'sanitize_callback' => 'sanitize_text_field',
            ]
        ]
    ]);

    // Ruta para crear un libro
    register_rest_route($namespace, '/books', [
        'methods' => 'POST',
        'callback' => 'bookshelf_create_book',
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        },
        'args' => [
            'title' => [
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param) {
                    return !empty(trim($param));
                }
            ],
            'content' => [
                'type' => 'string',
                'sanitize_callback' => 'wp_kses_post',
            ],
            'author' => [
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param) {
                    return !empty(trim($param));
                }
            ],
            'published_year' => [
                'type' => 'integer',
                'sanitize_callback' => 'absint',
                'validate_callback' => function($param) {
                    return $param >= 1000 && $param <= date('Y');
                }
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
                'sanitize_callback' => function($genres) {
                    return array_map('absint', (array) $genres);
                }
            ]
        ]
    ]);

    // Ruta para obtener un libro específico
    register_rest_route($namespace, '/books/(?P<id>\d+)', [
        'methods' => 'GET',
        'callback' => 'bookshelf_get_book',
        'permission_callback' => '__return_true',
        'args' => [
            'id' => [
                'validate_callback' => function($param) {
                    return is_numeric($param);
                }
            ]
        ]
    ]);

    // Ruta para actualizar un libro
    register_rest_route($namespace, '/books/(?P<id>\d+)', [
        'methods' => 'PUT',
        'callback' => 'bookshelf_update_book',
        'permission_callback' => function($request) {
            return current_user_can('edit_post', $request['id']);
        },
        'args' => [
            'id' => [
                'validate_callback' => function($param) {
                    return is_numeric($param);
                }
            ],
            'title' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'content' => [
                'type' => 'string',
                'sanitize_callback' => 'wp_kses_post',
            ],
            'author' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'published_year' => [
                'type' => 'integer',
                'sanitize_callback' => 'absint',
                'validate_callback' => function($param) {
                    return $param >= 1000 && $param <= date('Y');
                }
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
                'sanitize_callback' => function($genres) {
                    return array_map('absint', (array) $genres);
                }
            ]
        ]
    ]);

    // Ruta para eliminar un libro
    register_rest_route($namespace, '/books/(?P<id>\d+)', [
        'methods' => 'DELETE',
        'callback' => 'bookshelf_delete_book',
        'permission_callback' => function($request) {
            return current_user_can('delete_post', $request['id']);
        },
        'args' => [
            'id' => [
                'validate_callback' => function($param) {
                    return is_numeric($param);
                }
            ]
        ]
    ]);

    // Ruta para obtener géneros
    register_rest_route($namespace, '/genres', [
        'methods' => 'GET',
        'callback' => 'bookshelf_get_genres',
        'permission_callback' => '__return_true',
    ]);

    // Ruta para estadísticas
    register_rest_route($namespace, '/stats', [
        'methods' => 'GET',
        'callback' => 'bookshelf_get_stats',
        'permission_callback' => '__return_true',
    ]);
}
add_action('rest_api_init', 'bookshelf_register_api_routes');

// Función para obtener libros
function bookshelf_get_books($request) {
    $per_page = $request->get_param('per_page') ?: 10;
    $page = $request->get_param('page') ?: 1;
    $genre = $request->get_param('genre');
    $author = $request->get_param('author');
    $year = $request->get_param('year');
    $search = $request->get_param('search');
    $orderby = $request->get_param('orderby') ?: 'date';
    $order = $request->get_param('order') ?: 'DESC';

    $args = [
        'post_type' => 'book',
        'post_status' => 'publish',
        'posts_per_page' => $per_page,
        'paged' => $page,
        'order' => $order,
    ];

    // Ordenamiento
    switch ($orderby) {
        case 'title':
            $args['orderby'] = 'title';
            break;
        case 'author':
            $args['meta_key'] = 'author';
            $args['orderby'] = 'meta_value';
            break;
        case 'year':
            $args['meta_key'] = 'published_year';
            $args['orderby'] = 'meta_value_num';
            break;
        default:
            $args['orderby'] = 'date';
    }

    // Filtro por género
    if ($genre) {
        $args['tax_query'] = [
            [
                'taxonomy' => 'genre',
                'field' => 'slug',
                'terms' => $genre,
            ]
        ];
    }

    // Filtros por metacampos
    $meta_query = [];
    if ($author) {
        $meta_query[] = [
            'key' => 'author',
            'value' => $author,
            'compare' => 'LIKE'
        ];
    }
    if ($year) {
        $meta_query[] = [
            'key' => 'published_year',
            'value' => $year,
            'compare' => '='
        ];
    }
    if (!empty($meta_query)) {
        $args['meta_query'] = $meta_query;
    }

    // Búsqueda
    if ($search) {
        $args['s'] = $search;
    }

    $query = new WP_Query($args);
    $books = [];

    foreach ($query->posts as $post) {
        $books[] = bookshelf_format_book($post);
    }

    $response = new WP_REST_Response($books);
    $response->header('X-WP-Total', $query->found_posts);
    $response->header('X-WP-TotalPages', $query->max_num_pages);

    return $response;
}

// Función para obtener un libro específico
function bookshelf_get_book($request) {
    $id = $request['id'];
    $post = get_post($id);

    if (!$post || $post->post_type !== 'book') {
        return new WP_Error('book_not_found', 'Libro no encontrado', ['status' => 404]);
    }

    return bookshelf_format_book($post);
}

// Función para crear un libro
function bookshelf_create_book($request) {
    $title = $request->get_param('title');
    $content = $request->get_param('content');
    $author = $request->get_param('author');
    $published_year = $request->get_param('published_year');
    $isbn = $request->get_param('isbn');
    $pages = $request->get_param('pages');
    $genres = $request->get_param('genres');

    $post_data = [
        'post_title' => $title,
        'post_content' => $content ?: '',
        'post_type' => 'book',
        'post_status' => 'publish',
        'post_author' => get_current_user_id(),
    ];

    $post_id = wp_insert_post($post_data);

    if (is_wp_error($post_id)) {
        return new WP_Error('book_creation_failed', 'Error al crear el libro', ['status' => 500]);
    }

    // Guardar metacampos
    update_post_meta($post_id, 'author', $author);
    if ($published_year) {
        update_post_meta($post_id, 'published_year', $published_year);
    }
    if ($isbn) {
        update_post_meta($post_id, 'isbn', $isbn);
    }
    if ($pages) {
        update_post_meta($post_id, 'pages', $pages);
    }

    // Asignar géneros
    if ($genres && is_array($genres)) {
        wp_set_post_terms($post_id, $genres, 'genre');
    }

    $post = get_post($post_id);
    return bookshelf_format_book($post);
}

// Función para actualizar un libro
function bookshelf_update_book($request) {
    $id = $request['id'];
    $post = get_post($id);

    if (!$post || $post->post_type !== 'book') {
        return new WP_Error('book_not_found', 'Libro no encontrado', ['status' => 404]);
    }

    $post_data = ['ID' => $id];

    // Actualizar campos si se proporcionan
    if ($request->has_param('title')) {
        $post_data['post_title'] = $request->get_param('title');
    }
    if ($request->has_param('content')) {
        $post_data['post_content'] = $request->get_param('content');
    }

    $result = wp_update_post($post_data);

    if (is_wp_error($result)) {
        return new WP_Error('book_update_failed', 'Error al actualizar el libro', ['status' => 500]);
    }

    // Actualizar metacampos
    if ($request->has_param('author')) {
        update_post_meta($id, 'author', $request->get_param('author'));
    }
    if ($request->has_param('published_year')) {
        update_post_meta($id, 'published_year', $request->get_param('published_year'));
    }
    if ($request->has_param('isbn')) {
        update_post_meta($id, 'isbn', $request->get_param('isbn'));
    }
    if ($request->has_param('pages')) {
        update_post_meta($id, 'pages', $request->get_param('pages'));
    }

    // Actualizar géneros
    if ($request->has_param('genres')) {
        $genres = $request->get_param('genres');
        wp_set_post_terms($id, $genres, 'genre');
    }

    $updated_post = get_post($id);
    return bookshelf_format_book($updated_post);
}

// Función para eliminar un libro
function bookshelf_delete_book($request) {
    $id = $request['id'];
    $post = get_post($id);

    if (!$post || $post->post_type !== 'book') {
        return new WP_Error('book_not_found', 'Libro no encontrado', ['status' => 404]);
    }

    $result = wp_delete_post($id, true);

    if (!$result) {
        return new WP_Error('book_delete_failed', 'Error al eliminar el libro', ['status' => 500]);
    }

    return ['deleted' => true, 'id' => $id];
}

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

// Función para ejecutar las semillas de libros
function bookshelf_execute_seed($request) {
    // Incluir el archivo de semillas
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/seed.php';
    
    // Obtener el número de libros a generar (por defecto: 20)
    $total = $request->get_param('total') ? absint($request->get_param('total')) : 20;
    
    // Ejecutar la función de semillas
    $result = bookshelf_seed_books($total);
    
    // Retornar el resultado
    if (is_wp_error($result)) {
        return $result;
    }
    
    return $result;
}

// Función para obtener estadísticas
function bookshelf_get_stats($request) {
    $total_books = wp_count_posts('book');
    $total_genres = wp_count_terms('genre');

    // Libros por año
    $books_by_year = [];
    $years_query = new WP_Query([
        'post_type' => 'book',
        'posts_per_page' => -1,
        'meta_key' => 'published_year',
        'meta_query' => [
            [
                'key' => 'published_year',
                'compare' => 'EXISTS'
            ]
        ]
    ]);

    foreach ($years_query->posts as $post) {
        $year = get_post_meta($post->ID, 'published_year', true);
        if ($year) {
            $books_by_year[$year] = isset($books_by_year[$year]) ? $books_by_year[$year] + 1 : 1;
        }
    }

    // Autores más prolíficos
    $authors_query = new WP_Query([
        'post_type' => 'book',
        'posts_per_page' => -1,
        'meta_key' => 'author',
        'meta_query' => [
            [
                'key' => 'author',
                'compare' => 'EXISTS'
            ]
        ]
    ]);

    $authors_count = [];
    foreach ($authors_query->posts as $post) {
        $author = get_post_meta($post->ID, 'author', true);
        if ($author) {
            $authors_count[$author] = isset($authors_count[$author]) ? $authors_count[$author] + 1 : 1;
        }
    }

    arsort($authors_count);
    $top_authors = array_slice($authors_count, 0, 10, true);

    return [
        'total_books' => $total_books->publish,
        'total_genres' => $total_genres,
        'books_by_year' => $books_by_year,
        'top_authors' => $top_authors,
    ];
}

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

