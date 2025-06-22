<?php
if (!defined('ABSPATH')) {
    exit;
}

function bookshelf_register_book_cpt() {
    $labels = [
        'name' => 'Libros',
        'singular_name' => 'Libro',
        'menu_name' => 'Libros',
        'add_new' => 'Añadir Nuevo',
        'add_new_item' => 'Añadir Nuevo Libro',
        'edit_item' => 'Editar Libro',
        'new_item' => 'Nuevo Libro',
        'view_item' => 'Ver Libro',
        'search_items' => 'Buscar Libros',
        'not_found' => 'No se encontraron libros',
        'not_found_in_trash' => 'No se encontraron libros en la papelera',
        'all_items' => 'Todos los Libros',
    ];

    $args = [
        'labels' => $labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'rest_base' => 'books',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
        'menu_icon' => 'dashicons-book',
        'has_archive' => true,
        'rewrite' => ['slug' => 'libros'],
        'capability_type' => 'post',
        'menu_position' => 25,
        'show_in_admin_bar' => true,
        'can_export' => true,
        'exclude_from_search' => false,
        'hierarchical' => false,
    ];

    register_post_type('book', $args);

    // Registrar metacampos
    register_post_meta('book', 'author', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        }
    ]);

    register_post_meta('book', 'published_year', [
        'type' => 'integer',
        'single' => true,
        'show_in_rest' => true,
        'sanitize_callback' => 'absint',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        }
    ]);

    register_post_meta('book', 'isbn', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        }
    ]);

    register_post_meta('book', 'pages', [
        'type' => 'integer',
        'single' => true,
        'show_in_rest' => true,
        'sanitize_callback' => 'absint',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        }
    ]);
}
add_action('init', 'bookshelf_register_book_cpt');

// Añadir metaboxes al editor de libros
function bookshelf_add_meta_boxes() {
    add_meta_box(
        'bookshelf_book_details',
        'Detalles del Libro',
        'bookshelf_book_details_callback',
        'book',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'bookshelf_add_meta_boxes');

function bookshelf_book_details_callback($post) {
    wp_nonce_field('bookshelf_save_book_details', 'bookshelf_book_details_nonce');
    
    $author = get_post_meta($post->ID, 'author', true);
    $published_year = get_post_meta($post->ID, 'published_year', true);
    $isbn = get_post_meta($post->ID, 'isbn', true);
    $pages = get_post_meta($post->ID, 'pages', true);
    ?>
    <table class="form-table">
        <tr>
            <th><label for="book_author">Autor</label></th>
            <td>
                <input type="text" id="book_author" name="book_author" 
                       value="<?php echo esc_attr($author); ?>" class="regular-text" />
            </td>
        </tr>
        <tr>
            <th><label for="book_published_year">Año de Publicación</label></th>
            <td>
                <input type="number" id="book_published_year" name="book_published_year" 
                       value="<?php echo esc_attr($published_year); ?>" 
                       min="1000" max="<?php echo date('Y'); ?>" class="small-text" />
            </td>
        </tr>
        <tr>
            <th><label for="book_isbn">ISBN</label></th>
            <td>
                <input type="text" id="book_isbn" name="book_isbn" 
                       value="<?php echo esc_attr($isbn); ?>" class="regular-text" />
            </td>
        </tr>
        <tr>
            <th><label for="book_pages">Número de Páginas</label></th>
            <td>
                <input type="number" id="book_pages" name="book_pages" 
                       value="<?php echo esc_attr($pages); ?>" min="1" class="small-text" />
            </td>
        </tr>
    </table>
    <?php
}

// Guardar metacampos
function bookshelf_save_book_details($post_id) {
    // Verificar nonce
    if (!isset($_POST['bookshelf_book_details_nonce']) || 
        !wp_verify_nonce($_POST['bookshelf_book_details_nonce'], 'bookshelf_save_book_details')) {
        return;
    }

    // Verificar autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Verificar permisos
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Guardar campos
    if (isset($_POST['book_author'])) {
        update_post_meta($post_id, 'author', sanitize_text_field($_POST['book_author']));
    }

    if (isset($_POST['book_published_year'])) {
        update_post_meta($post_id, 'published_year', absint($_POST['book_published_year']));
    }

    if (isset($_POST['book_isbn'])) {
        update_post_meta($post_id, 'isbn', sanitize_text_field($_POST['book_isbn']));
    }

    if (isset($_POST['book_pages'])) {
        update_post_meta($post_id, 'pages', absint($_POST['book_pages']));
    }
}
add_action('save_post', 'bookshelf_save_book_details');

// Agregar columnas personalizadas en el listado de libros
function bookshelf_book_columns($columns) {
    $new_columns = [];
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = $columns['title'];
    $new_columns['author'] = 'Autor';
    $new_columns['published_year'] = 'Año';
    $new_columns['genre'] = 'Género';
    $new_columns['date'] = $columns['date'];
    
    return $new_columns;
}
add_filter('manage_book_posts_columns', 'bookshelf_book_columns');

function bookshelf_book_custom_column($column, $post_id) {
    switch ($column) {
        case 'author':
            echo esc_html(get_post_meta($post_id, 'author', true));
            break;
        case 'published_year':
            echo esc_html(get_post_meta($post_id, 'published_year', true));
            break;
        case 'genre':
            $terms = get_the_terms($post_id, 'genre');
            if ($terms && !is_wp_error($terms)) {
                $genre_names = array_map(function($term) {
                    return $term->name;
                }, $terms);
                echo esc_html(implode(', ', $genre_names));
            }
            break;
    }
}
add_action('manage_book_posts_custom_column', 'bookshelf_book_custom_column', 10, 2);