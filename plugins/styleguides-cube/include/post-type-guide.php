<?php

$labels = [
    'name'               => _x( 'Guides', 'post type general name', 'realconomy' ),
    'singular_name'      => _x( 'Guide', 'post type singular name', 'realconomy' ),
    'menu_name'          => _x( 'Guides', 'admin menu', 'realconomy' ),
    'name_admin_bar'     => _x( 'Guides', 'add new on admin bar', 'realconomy' ),
    'add_new'            => _x( 'Nieuwe toevoegen', 'book', 'realconomy' ),
    'add_new_item'       => __( 'Nieuwe toevoegen', 'realconomy' ),
    'new_item'           => __( 'Nieuw styleguide', 'realconomy' ),
    'edit_item'          => __( 'Bewerk styleguide', 'realconomy' ),
    'view_item'          => __( 'Bekijk styleguide', 'realconomy' ),
    'all_items'          => __( 'Alle guides', 'realconomy' ),
    'search_items'       => __( 'Zoek', 'realconomy' ),
    'parent_item_colon'  => __( 'Ouders:', 'realconomy' ),
    'not_found'          => __( 'Niets gevonden.', 'realconomy' ),
    'not_found_in_trash' => __( 'Niets gevonden in de prullenbak.', 'realconomy' )
];

// Register post types
// For more info see cube_register_posttype() function
cube_register_posttype( 'guide',[

    // Add some default labels
    // When no labels are added labels will be generated in ENG
    'labels' => $labels,
    'menu_icon' => 'dashicons-admin-tools',
    'menu_position' => 8

]);
