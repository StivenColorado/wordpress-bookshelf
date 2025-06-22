<?php
if (!defined('ABSPATH')) {
    exit;
}

function bookshelf_register_genre_taxonomy() {
    $labels = [
        'name' => 'Géneros',
        'singular_name' => 'Género',
        'menu_name' => 'Géneros',
        'all_items' => 'Todos los Géneros',
        'edit_item' => 'Editar Género',
        'view_item' => 'Ver Género',
        'update_item' => 'Actualizar Género',
        'add_new_item' => 'Añadir Nuevo Género',
        'new_item_name' => 'Nuevo Nombre de Género',
        'parent_item' => 'Género Padre',
        'parent_item_colon' => 'Género Padre:',
        'search_items' => 'Buscar Géneros',
        'popular_items' => 'Géneros Populares',
        'separate_items_with_commas' => 'Separar géneros con comas',
        'add_or_remove_items' => 'Añadir o eliminar géneros',
        'choose_from_most_used' => 'Elegir de los más usados',
        'not_found' => 'No se encontraron géneros',
    ];

    $args = [
        'labels' => $labels,
        'public' => true,
        'publicly_queryable' => true,
        'hierarchical' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_in_rest' => true,
        'rest_base' => 'genres',
        'show_tagcloud' => true,
        'show_in_quick_edit' => true,
        'show_admin_column' => true,
        'rewrite' => [
            'slug' => 'genero',
            'with_front' => false,
            'hierarchical' => true,
        ],
        'query_var' => true,
        'capabilities' => [
            'manage_terms' => 'manage_categories',
            'edit_terms' => 'manage_categories',
            'delete_terms' => 'manage_categories',
            'assign_terms' => 'edit_posts',
        ],
    ];

    register_taxonomy('genre', 'book', $args);
}
add_action('init', 'bookshelf_register_genre_taxonomy');

// Agregar campos personalizados a la taxonomía género
function bookshelf_add_genre_fields() {
    ?>
    <div class="form-field">
        <label for="genre_color">Color del Género</label>
        <input type="color" id="genre_color" name="genre_color" value="#000000" />
        <p class="description">Selecciona un color para representar este género.</p>
    </div>
    
    <div class="form-field">
        <label for="genre_description_extended">Descripción Extendida</label>
        <textarea id="genre_description_extended" name="genre_description_extended" rows="5" cols="50"></textarea>
        <p class="description">Una descripción más detallada del género.</p>
    </div>
    <?php
}
add_action('genre_add_form_fields', 'bookshelf_add_genre_fields');

function bookshelf_edit_genre_fields($term, $taxonomy) {
    $color = get_term_meta($term->term_id, 'genre_color', true);
    $description_extended = get_term_meta($term->term_id, 'genre_description_extended', true);
    ?>
    <tr class="form-field">
        <th scope="row">
            <label for="genre_color">Color del Género</label>
        </th>
        <td>
            <input type="color" id="genre_color" name="genre_color" 
                   value="<?php echo esc_attr($color ? $color : '#000000'); ?>" />
            <p class="description">Selecciona un color para representar este género.</p>
        </td>
    </tr>
    
    <tr class="form-field">
        <th scope="row">
            <label for="genre_description_extended">Descripción Extendida</label>
        </th>
        <td>
            <textarea id="genre_description_extended" name="genre_description_extended" 
                      rows="5" cols="50"><?php echo esc_textarea($description_extended); ?></textarea>
            <p class="description">Una descripción más detallada del género.</p>
        </td>
    </tr>
    <?php
}
add_action('genre_edit_form_fields', 'bookshelf_edit_genre_fields', 10, 2);

// Guardar campos personalizados de la taxonomía
function bookshelf_save_genre_fields($term_id, $tt_id, $taxonomy) {
    if ($taxonomy !== 'genre') {
        return;
    }

    if (isset($_POST['genre_color'])) {
        update_term_meta($term_id, 'genre_color', sanitize_hex_color($_POST['genre_color']));
    }

    if (isset($_POST['genre_description_extended'])) {
        update_term_meta($term_id, 'genre_description_extended', sanitize_textarea_field($_POST['genre_description_extended']));
    }
}
add_action('created_genre', 'bookshelf_save_genre_fields', 10, 3);
add_action('edited_genre', 'bookshelf_save_genre_fields', 10, 3);

// Agregar columna de color en el listado de géneros
function bookshelf_add_genre_columns($columns) {
    $columns['genre_color'] = 'Color';
    return $columns;
}
add_filter('manage_edit-genre_columns', 'bookshelf_add_genre_columns');

function bookshelf_genre_column_content($content, $column_name, $term_id) {
    if ($column_name === 'genre_color') {
        $color = get_term_meta($term_id, 'genre_color', true);
        if ($color) {
            $content = '<span style="display: inline-block; width: 20px; height: 20px; background-color: ' . esc_attr($color) . '; border: 1px solid #ccc; border-radius: 3px;"></span>';
        }
    }
    return $content;
}
add_filter('manage_genre_custom_column', 'bookshelf_genre_column_content', 10, 3);

// Registrar metadatos de términos en REST API
function bookshelf_register_genre_meta_rest() {
    register_term_meta('genre', 'genre_color', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'sanitize_callback' => 'sanitize_hex_color',
        'auth_callback' => function() {
            return current_user_can('manage_categories');
        }
    ]);

    register_term_meta('genre', 'genre_description_extended', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'sanitize_callback' => 'sanitize_textarea_field',
        'auth_callback' => function() {
            return current_user_can('manage_categories');
        }
    ]);
}
add_action('init', 'bookshelf_register_genre_meta_rest');