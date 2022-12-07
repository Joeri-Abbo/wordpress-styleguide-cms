<?php
// hook into the init action and call create_page_taxonomies when it fires
add_action('init', 'create_guide_taxonomies', 0);

function create_guide_taxonomies()
{
    // Add new taxonomy, make it hierarchical (like categories)
    $labels = [
        'name' => _x('Categorieën', 'taxonomy general name', 'styleguide-cube'),
        'singular_name' => _x('taxonomie', 'taxonomy singular name', 'styleguide-cube'),
        'search_items' => __('Search taxonomie', 'styleguide-cube'),
        'all_items' => __('Alle Categorieën', 'styleguide-cube'),
        'parent_item' => __('Parent taxonomie', 'styleguide-cube'),
        'parent_item_colon' => __('Parent taxonomie:', 'styleguide-cube'),
        'edit_item' => __('Edit categorie', 'styleguide-cube'),
        'update_item' => __('Update categorie', 'styleguide-cube'),
        'add_new_item' => __('Voeg een nieuwe categorie toe', 'styleguide-cube'),
        'new_item_name' => __('Nieuwe naam voor categorie', 'styleguide-cube'),
        'menu_name' => __('Categorieën', 'styleguide-cube'),
    ];

    $args = [
        'labels' => $labels,
        'show_ui'                    => true,
        'show_in_quick_edit'         => false,
        'meta_box_cb'                => false,

    ];

    register_taxonomy('taxonomie', 'guide', $args);
}

