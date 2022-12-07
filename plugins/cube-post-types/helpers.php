<?php

Use Cube\PostTypes\PostType;
Use Cube\PostTypes\PostTypeAdmin;
Use Cube\Taxonomy\Taxonomy;
Use Cube\Taxonomy\TaxonomyAdmin;

/**
 * Register a custom post type.
 *
 * The `$args` parameter accepts all the standard arguments for `register_post_type()` in addition to several custom
 * arguments that provide extended functionality. Some of the default arguments differ from the defaults in
 * `register_post_type()`.
 *
 * The `$post_type` parameter is used as the post type name and to build the post type labels. This means you can create
 * a post type with just one parameter and all labels and post updated messages will be generated for you. Example:
 *
 * cube_register_posttype( 'event' );
 *
 * @see     register_post_type() for default arguments.
 * @author  Joeri Abbo
 * @since   1.0.0
 *
 * @param   string  $post_type     The post type name.
 * @param   array   $args          Default WP args + custom extended args
 * @param   array   $names
 */

if( !function_exists( 'cube_register_posttype' ) ) {

    function cube_register_posttype( $post_type, array $args = [], array $names = [] ) {

        $cpt = new PostType( $post_type, $args, $names );

        if ( is_admin() ) {
            new PostTypeAdmin( $cpt, $args );
        }

        return $cpt;

    }

}

/**
 * Register a custom taxonomy.
 *
 * The `$args` parameter accepts all the standard arguments for `register_taxonomy()` in addition to several custom
 * arguments that provide extended functionality. Some of the default arguments differ from the defaults in
 * `register_taxonomy()`.
 *
 * The `$taxonomy` parameter is used as the taxonomy name and to build the taxonomy labels. This means you can create
 * a taxonomy with just two parameters and all labels and term updated messages will be generated for you. Example:
 *
 * cube_register_taxonomy( 'location', 'post' );
 *
 * @see     register_post_type() for default arguments.
 * @author  Joeri Abbo
 * @since   1.0.0
 *
 * @param   string        $taxonomy       The taxonomy name.
 * @param   array|string  $object_type    Name(s) of the object type(s) for the taxonomy.
 * @param   array         $args           Default WP args + custom extended args
 * @param   array         $names
 */

if( !function_exists( 'cube_register_taxonomy' ) ) {

    function cube_register_taxonomy( $taxonomy, $object_type, array $args = array(), array $names = array() ) {

        $tax = new Taxonomy( $taxonomy, $object_type, $args, $names );

        if ( is_admin() ) {
            new TaxonomyAdmin( $tax, $args );
        }

        return $tax;
    }

}

/**
 * Check if custom filters are defined
 * Must be used in array_map() function
 *
 * @author  Joeri Abbo
 * @since   1.0
 *
 * @param array         $filter           Value of the filter passed
 * @param boolean
 */

if( !function_exists( 'cube_pt_has_custom_filters' ) ) {

    function cube_pt_has_custom_filters( $filter ) {
        return is_array( $filter );
    }
}

?>
