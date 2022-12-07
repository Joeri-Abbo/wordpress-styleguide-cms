<?php

// Set the namespace
namespace Cube\PostTypes;

// Import the required classes
use WP_Query;
use Cube\taxonomy\TaxonomyAdminDropdown;
use Cube\taxonomy\TaxonomyAdminRadio;
use Cube\taxonomy\TaxonomyAdminCheckbox;

/**
 *
 * Cube PostTypeAdmin Class
 *
 * Class to manage post types in WordPress dashboard
 *
 * @author  Joeri Abbo
 * @since   1.0
 */

class PostTypeAdmin {

    /**
     * Default arguments for custom post types.
     *
     * @since   1.0
     * @author  Joeri Abbo
     *
     * @var array
     */

    protected $defaults = [
        'quick_edit'           => true,
        'dashboard_glance'     => true,
        'admin_cols'           => null,
        'enter_title_here'     => null,
        'select2'              => true,
        'hide_search'          => false,
        'extend_search'        => false,
        'admin_filters'        => [
            'month' => false,
            'seo'   => false,
        ],
    ];

    public      $cpt;
    public      $args;
    protected   $_cols;
    protected   $the_cols = null;
    protected   $connection_exists = array();

    /**
     * Construct this class
     *
     * @since   1.0
     * @author  Joeri Abbo
     *
     * @param   PostType    $cpt    An extended post type object.
     * @param   array       $args   Optional. The post type arguments.
     */

    public function __construct( PostType $cpt, array $args = [] ) {

        // Assign the extended post type object
        $this->cpt = $cpt;

        // Merge args with defaults
        $this->args = array_merge( $this->defaults, $args );

        // Initiate the class
        add_action( 'admin_init', [ $this, 'init' ] );

    }

    /**
     * Initiate the class
     *
     * @since   1.0
     * @author  Joeri Abbo
     */

    public function init() {

        global $type;

        // If typenow is empty
        if( empty( $type ) ) {

            // Try to pick it up from the query string
            if (!empty($_GET['post']) && !empty(get_post($_GET['post']))) {
                $post = get_post($_GET['post']);
                $type = $post->post_type;
            }

        }

        // Check if the actual screen matches the post type
        if( $type == $this->cpt->post_type ) {

            // Init filters and actions for post type actions
            if( $this->args['admin_cols'] ) {

                add_filter( 'manage_posts_columns',                                 [ $this, '_log_default_cols' ], 0 );
                add_filter( 'manage_pages_columns',                                 [ $this, '_log_default_cols' ], 0 );
                add_filter( "manage_edit-{$this->cpt->post_type}_sortable_columns", [ $this, 'sortables' ] );
                add_filter( "manage_{$this->cpt->post_type}_posts_columns",         [ $this, 'cols' ] );
                add_action( "manage_{$this->cpt->post_type}_posts_custom_column",   [ $this, 'col' ] );
                add_action( 'load-edit.php',                                        [ $this, 'default_sort' ] );
                add_filter( 'pre_get_posts',                                        [ $this, 'maybe_sort_by_fields' ] );
                add_filter( 'posts_clauses',                                        [ $this, 'maybe_sort_by_taxonomy' ], 10, 2 );

            }

            // Init filters and actions for post type filters
            if( $this->args['admin_filters'] ) {
                add_filter( 'pre_get_posts',         [ $this, 'maybe_filter' ] );
                add_filter( 'query_vars',            [ $this, 'add_query_vars' ] );
                add_action( 'restrict_manage_posts', [ $this, 'filters' ] );
            }

            if( $this->args['extend_search'] && is_array( $this->args['extend_search'] ) ) {
                add_filter( 'pre_get_posts', [ $this, 'maybe_extend_search' ] );
            }

            // Quick Edit filter for post type
            if ( ! $this->args['quick_edit'] ) {
                add_filter( 'post_row_actions',                          [ $this, 'remove_quick_edit_action' ], 10, 2 );
                add_filter( 'page_row_actions',                          [ $this, 'remove_quick_edit_action' ], 10, 2 );
                add_filter( "bulk_actions-edit-{$this->cpt->post_type}", [ $this, 'remove_quick_edit_menu' ] );
            }

            // 'At a Glance' dashboard panels for post type
            if ( $this->args['dashboard_glance'] ) {
                add_filter( 'dashboard_glance_items', [ $this, 'glance_items' ], $this->cpt->post_type );
            }

            // Updated messages for post type
            add_filter( 'post_updated_messages',      [ $this, 'post_updated_messages' ], 1 );
            add_filter( 'bulk_post_updated_messages', [ $this, 'bulk_post_updated_messages' ], 1, 2 );

            // Adjust the filtering UI
            add_action( 'admin_head-edit.php', [ $this, 'admin_head' ] );

            // Load the required select2 jQuery scripts
            if( !empty( $this->args['select2'] ) ){
                add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_select2_jquery' ] );
            }

            // 'Enter title here' filter for post type
            if( $this->args['enter_title_here'] ) {
                add_filter( 'enter_title_here', [ $this, 'enter_title_here' ], 10, 2 );
            }

        }

    }

    /**
     * Admin head WP action
     *
     * @since   1.0
     * @author  Joeri Abbo
     */

    public function admin_head() {

        // Add custom JS and CSS for the select2 selectboxes
        if( !empty( $this->args['select2'] ) ){
            $this->admin_head_select2_filters();
        }

        // Hide the searchbox for this post type
        if( !empty( $this->args['hide_search'] ) ) {
            $this->admin_head_hide_search();
        }

        // Hide the month filter selectbox for this post type
        if( isset( $this->args['admin_filters']['month'] ) && !$this->args['admin_filters']['month'] ) {
            $this->admin_head_hide_month();
        }

        // Hide the seo filter selectbox for this post type
        if( isset( $this->args['admin_filters']['seo'] ) && !$this->args['admin_filters']['seo'] ) {
            $this->admin_head_hide_seo();
        }

        // hide filter button when seo and month are false and no custom filters defined
        if( empty( $this->args['admin_filters']['month'] ) && empty( $this->args['admin_filters']['seo'] ) ) {

            if( empty( $this->args['admin_filters'] ) ) {
                return;
            }

            // Check if any custom filters are defined
            $check_filters = array_map( 'cube_pt_has_custom_filters', $this->args['admin_filters'] );

            if( !in_array( true, $check_filters ) ) {
                $this->admin_head_no_filter();
            }

        }

    }

    /**
     * Hide the searchbox for this post type
     * This can be used when custom meta search fields are better
     *
     * @since   1.0
     * @author  Joeri Abbo
     *
     */

    public function admin_head_hide_search() {

        echo '<style>';
        echo ".post-type-{$this->cpt->post_type} .search-box { display: none }";
        echo '</style>';

    }

    /**
     * Add custom JS and CSS for the select2 selectboxes
     *
     * @since   1.0
     * @author  Joeri Abbo
     *
     */
    public function admin_head_select2_filters() { ?>

        <style type="text/css">
            .select2-container {
                margin: 0 2px 0 2px;
                min-width: 150px;
                max-width: 150px;
            }
            .tablenav.top #doaction, #doaction2, #post-query-submit {
                margin: 0 4px 0 4px;
            }
        </style>

        <script type='text/javascript'>
            jQuery(document).ready(function ($) {

                var $dropdowns = $(".actions").not('.bulkactions').find('select');

                $dropdowns.each( function() {

                    if( $(this).is(':visible') ) {
                        $(this).select2({ dropdownAutoWidth : true });
                    }

                });

            });
        </script>

    <?php
    }

    /**
     * Load the required select2 jQuery scripts
     *
     * @since   1.0.0
     * @author  Joeri Abbo
     *
     */
    public function enqueue_select2_jquery() {

        wp_register_style( 'select2css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/css/select2.min.css', false, '1.0', 'all' );
        wp_register_script( 'select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/js/select2.min.js', [ 'jquery' ], '1.0', true );
        wp_enqueue_style( 'select2css' );
        wp_enqueue_script( 'select2' );

    }

    /**
     * Add some CSS to hide month filter selectbox
     *
     * @since   1.0
     * @author  Joeri Abbo
     *
     */

    public function admin_head_hide_month() {

        ?>
            <style type="text/css">
                #posts-filter select[name="m"] {
                    display: none !important;
                    visibility: hidden;
                }
            </style>
        <?php

    }

    /**
     * Add some CSS to hide SEO filter selectbox
     *
     * @since   1.0
     * @author  Joeri Abbo
     *
     */
    public function admin_head_hide_seo() {

        ?>
             <style type="text/css">
                 #posts-filter select[name="seo_filter"] {
                     display: none !important;
                     visibility: hidden;
                 }
             </style>

         <?php
    }

    /**
     * Add some CSS to hide filter button
     * When all are hidden the filter button must also be hidden
     *
     * @since   1.0
     * @author  Joeri Abbo
     *
     */
    public function admin_head_no_filter() {

        ?>
             <style type="text/css">
                 #posts-filter input[name="filter_action"] {
                     display: none !important;
                     visibility: hidden;
                 }
             </style>
         <?php

    }

    /**
     * Set the default sort field and sort order on our post type admin screen.
     */
    public function default_sort() {

        if ( $this->cpt->post_type !== self::get_current_post_type() ) {
            return;
        }

        # If we've already ordered the screen, bail out:
        if ( isset( $_GET['orderby'] ) ) {
            return;
        }

        # Loop over our columns to find the default sort column (if there is one):
        foreach ( $this->args['admin_cols'] as $id => $col ) {
            if ( is_array( $col ) && isset( $col['default'] ) ) {
                $_GET['orderby'] = $id;
                $_GET['order']   = ( 'desc' === strtolower( $col['default'] ) ? 'desc' : 'asc' );
                break;
            }
        }

    }

    /**
     * Set the placeholder text for the title field for this post type.
     *
     * @param  string  $title The placeholder text.
     * @param  WP_Post $post  The current post.
     * @return string         The updated placeholder text.
     */
    public function enter_title_here( $title, WP_Post $post ) {

        if ( $this->cpt->post_type !== $post->post_type ) {
            return $title;
        }

        return $this->args['enter_title_here'];

    }

    /**
     * Returns the name of the post type for the current request.
     *
     * @return string The post type name.
     */
    protected static function get_current_post_type() {

        if ( function_exists( 'get_current_screen' ) && is_object( get_current_screen() ) && 'edit' === get_current_screen()->base ) {
            return get_current_screen()->post_type;
        } else {
            return '';
        }

    }

    /**
     * Output custom filter dropdown menus on the admin screen for this post type.
     *
     * Each item in the `admin_filters` array is an associative array of information for a filter. Defining a filter is
     * easy. Just define an array which includes the filter title and filter type. You can display filters for post meta
     * fields and taxonomy terms.
     *
     * The example below adds filters for the `event_type` meta key and the `location` taxonomy:
     *
     *     register_extended_post_type( 'event', array(
     *         'admin_filters' => array(
     *             'event_type' => array(
     *                 'title'    => 'Event Type',
     *                 'meta_key' => 'event_type'
     *             ),
     *             'event_location' => array(
     *                 'title'    => 'Location',
     *                 'taxonomy' => 'location'
     *             ),
     *             'event_is' => array(
     *                 'title'       => 'All Events',
     *                 'meta_exists' => array(
     *                     'event_featured'  => 'Featured Events',
     *                     'event_cancelled' => 'Cancelled Events'
     *                 )
     *             ),
     *         )
     *     ) );
     *
     * That's all you need to do. WordPress handles taxonomy term filtering itself, and the plugin handles the dropdown
     * menu and filtering for post meta.
     *
     * Each item in the `admin_filters` array needs either a `taxonomy`, `meta_key`, `meta_search`, or `meta_exists`
     * element containing the corresponding taxonomy name or post meta key.
     *
     * The `meta_exists` filter outputs a dropdown menu listing each of the meta_exists fields, allowing users to
     * filter the screen by posts which have the corresponding meta field.
     *
     * The `meta_search` filter outputs a search input, allowing users to filter the screen by an arbitrary search value.
     *
     * There are a few optional elements:
     *
     *  - title - The filter title. If omitted, the title will use the `all_items` taxonomy label or a formatted version
     *    of the post meta key.
     *  - cap - A capability required in order for this filter to be displayed to the current user. Defaults to null,
     *    meaning the filter is shown to all users.
     *
     * @TODO - meta_query - array
     *
     * @TODO - options - array or callable
     *
     */
    public function filters() {

        global $wpdb;

        if ( $this->cpt->post_type !== self::get_current_post_type() ) {
            return;
        }

        $pto = get_post_type_object( $this->cpt->post_type );

        foreach ( $this->args['admin_filters'] as $filter_key => $filter ) {

            if ( isset( $filter['cap'] ) && ! current_user_can( $filter['cap'] ) ) {
                continue;
            }

            if ( isset( $filter['taxonomy'] ) ) {

                $tax = get_taxonomy( $filter['taxonomy'] );

                if ( empty( $tax ) ) {
                    continue;
                }

                # For this, we need the dropdown walker from Extended Taxonomies:
                if ( ! class_exists( $class = 'Cube\Taxonomy\TaxonomyAdminDropdown' ) ) {
                    trigger_error( esc_html( sprintf(
                        __( 'The "%s" class is required in order to display taxonomy filters', 'extended-cpts' ),
                        $class
                    ) ), E_USER_WARNING );
                    continue;
                } else {
                    $walker = new TaxonomyAdminDropdown( [
                        'field' => 'slug',
                    ] );
                }

                # If we haven't specified a title, use the all_items label from the taxonomy:
                if ( ! isset( $filter['title'] ) ) {
                    $filter['title'] = $tax->labels->all_items;
                }

                # Output the dropdown:
                wp_dropdown_categories( [
                    'show_option_all' => $filter['title'],
                    'hide_empty'      => false,
                    'hide_if_empty'   => true,
                    'hierarchical'    => true,
                    'show_count'      => false,
                    'orderby'         => 'name',
                    'selected_cats'   => get_query_var( $tax->query_var ),
                    'id'              => 'filter_' . $filter_key,
                    'name'            => $tax->query_var,
                    'taxonomy'        => $filter['taxonomy'],
                    'walker'          => $walker,
                ] );

            } else if ( isset( $filter['meta_key'] ) ) {

                # If we haven't specified a title, generate one from the meta key:
                if ( ! isset( $filter['title'] ) ) {
                    $filter['title'] = str_replace( [ '-', '_' ], ' ', $filter['meta_key'] );
                    $filter['title'] = ucwords( $filter['title'] ) . 's';
                    $filter['title'] = sprintf( 'All %s', $filter['title'] );
                }

                if ( ! isset( $filter['options'] ) ) {
                    # Fetch all the values for our meta key:
                    # @TODO AND m.meta_value != null ?
                    $filter['options'] = $wpdb->get_col( $wpdb->prepare( "
                        SELECT DISTINCT meta_value
                        FROM {$wpdb->postmeta} as m
                        JOIN {$wpdb->posts} as p ON ( p.ID = m.post_id )
                        WHERE m.meta_key = %s
                        AND m.meta_value != ''
                        AND p.post_type = %s
                        ORDER BY m.meta_value ASC
                    ", $filter['meta_key'], $this->cpt->post_type ) );
                }

                if ( !empty(  $filter['options_callback'] ) && is_callable( $filter['options_callback'] ) ) {
                    $filter['options'] = call_user_func( $filter['options_callback'] ,$filter['options'], $filter['meta_key']);
                }

                if ( empty( $filter['options'] ) ) {
                    continue;
                }

                $selected = wp_unslash( get_query_var( $filter_key ) );

                $use_key = isset( $filter['value_as_key'] ) ? $filter['value_as_key'] : true;

                # Output the dropdown:
                ?>

                <select name="<?php echo esc_attr( $filter_key ); ?>" id="filter_<?php echo esc_attr( $filter_key ); ?>">
                    <option value="0"><?php echo esc_html( $filter['title'] ); ?></option>
                    <?php
                        foreach ( $filter['options'] as $k => $value ) {
                            $key = ( $use_key ? $value : $k ); ?>
                        <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $selected, $key ); ?>><?php echo esc_html( $value ); ?></option>
                    <?php } ?>
                </select>

                <?php

            } else if ( isset( $filter['meta_search_key'] ) ) {

                # If we haven't specified a title, generate one from the meta key:
                if ( ! isset( $filter['title'] ) ) {
                    $filter['title'] = str_replace( [ '-', '_' ], ' ', $filter['meta_search_key'] );
                    $filter['title'] = ucwords( $filter['title'] );
                }

                $value = wp_unslash( get_query_var( $filter_key ) );

                # Output the search box:
                ?>
                <label><input type="text" name="<?php echo esc_attr( $filter_key ); ?>" id="filter_<?php echo esc_attr( $filter_key ); ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php printf( '%s', esc_html( $filter['title'] ) ); ?>" /></label>
                <?php

            } else if ( isset( $filter['meta_exists'] ) ) {

                # If we haven't specified a title, use the all_items label from the post type:
                if ( ! isset( $filter['title'] ) ) {
                    $filter['title'] = $pto->labels->all_items;
                }

                $selected = wp_unslash( get_query_var( $filter_key ) );

                if ( 1 === count( $filter['meta_exists'] ) ) {

                    # Output a checkbox:
                    foreach ( $filter['meta_exists'] as $v => $t ) {
                        ?>
                        <label><input type="checkbox" name="<?php echo esc_attr( $filter_key ); ?>" id="filter_<?php echo esc_attr( $filter_key ); ?>" value="<?php echo esc_attr( $v ); ?>" <?php checked( $selected, $v ); ?>>&nbsp;<?php echo esc_html( $t ); ?></label>
                        <?php
                    }
                } else {

                    # Output a dropdown:
                    ?>
                    <select name="<?php echo esc_attr( $filter_key ); ?>" id="filter_<?php echo esc_attr( $filter_key ); ?>">
                        <option value=""><?php echo esc_html( $filter['title'] ); ?></option>
                        <?php foreach ( $filter['meta_exists'] as $v => $t ) { ?>
                            <option value="<?php echo esc_attr( $v ); ?>" <?php selected( $selected, $v ); ?>><?php echo esc_html( $t ); ?></option>
                        <?php } ?>
                    </select>
                    <?php

                }
            }
        }

    }

    /**
     * Add our filter names to the public query vars.
     *
     * @param  array $vars Public query variables
     * @return array       Updated public query variables
     */
    public function add_query_vars( array $vars ) {
        return array_merge( $vars, array_keys( $this->args['admin_filters'] ) );
    }

    /**
     * Search posts by custom meta fields
     *
     * @param WP_Query $wp_query Looks a bit like a `WP_Query` object
     */

    public function maybe_extend_search( WP_Query $wp_query ) {

        if ( empty( $wp_query->query['post_type'] ) || ! in_array( $this->cpt->post_type, (array) $wp_query->query['post_type'] ) ) {
            return;
        }

        // Check if search query is not empty
        if( !empty( $wp_query->get('s') ) && $wp_query->get('s') != ' ' ) {

            // Grab meta query args from cpt args
            $search_meta_fields = implode(',', $this->cpt->args['extend_search']);

            // Build meta_query args
            $meta_query_args = [
                'meta_query' => [
                    [
                        'keys'  => $search_meta_fields,
                        'value' => wp_unslash( $wp_query->get('s') ),
                        'compare' => 'LIKE'
                    ]
                ]
            ];

            // Parse meta_query args to the WP_Query object
            $this->parse_filters( $wp_query, $meta_query_args );

            // Reset the search query object to bypass original search function
            $wp_query->set('s', '');

        }

    }

    /**
     * Filter posts by our custom admin filters.
     *
     * @param WP_Query $wp_query Looks a bit like a `WP_Query` object
     */
    public function maybe_filter( WP_Query $wp_query ) {

        if ( empty( $wp_query->query['post_type'] ) || ! in_array( $this->cpt->post_type, (array) $wp_query->query['post_type'] ) ) {
            return;
        }

        // Get filter vars for this post type
        $meta_query_args = PostType::get_filter_vars( $wp_query->query, $this->cpt->args['admin_filters'] );

        $meta_query_args = apply_filters( 'cube_test_filter', $meta_query_args );

        // Parse meta_query args to the WP_Query object
        $this->parse_filters( $wp_query, $meta_query_args );

        // Reset search query param
        if( empty( $wp_query->get('s') ) || $wp_query->get('s') == ' ' ) {
            $wp_query->set('s', '');
        }

    }

    /**
     * Parse meta_query args and add them to the WP_Query object
     *
     * @param WP_Query  $wp_query           The current `WP_Query` object.
     * @param array     $meta_query_args    Array with meta query args
     */

    private function parse_filters( WP_Query $wp_query, array $meta_query_args ) {

        foreach ( $meta_query_args as $key => $value ) {

            if ( is_array( $value ) ) {
                $query = !empty($wp_query->get( $key )) ?
                    $wp_query->get( $key ) :
                    [];

                $value = array_merge( $query, $value );
            }

            $wp_query->set( $key, $value );
        }

    }

    /**
     * Set the relevant query vars for sorting posts by our admin sortables.
     *
     * @param WP_Query $wp_query The current `WP_Query` object.
     */

    public function maybe_sort_by_fields( WP_Query $wp_query ) {

        if ( empty( $wp_query->query['post_type'] ) || ! in_array( $this->cpt->post_type, (array) $wp_query->query['post_type'] ) ) {
            return;
        }

        $sort = PostType::get_sort_field_vars( $wp_query->query, $this->cpt->args['admin_cols'] );

        if ( empty( $sort ) ) {
            return;
        }

        foreach ( $sort as $key => $value ) {
            $wp_query->set( $key, $value );
        }

    }

    /**
     * Filter the query's SQL clauses so we can sort posts by taxonomy terms.
     *
     * @param  array    $clauses  The current query's SQL clauses.
     * @param  WP_Query $wp_query The current `WP_Query` object.
     * @return array              The updated SQL clauses.
     */
    public function maybe_sort_by_taxonomy( array $clauses, WP_Query $wp_query ) {

        if ( empty( $wp_query->query['post_type'] ) || ! in_array( $this->cpt->post_type, (array) $wp_query->query['post_type'] ) ) {
            return $clauses;
        }

        $sort = PostType::get_sort_taxonomy_clauses( $clauses, $wp_query->query, $this->cpt->args['admin_cols'] );

        if ( empty( $sort ) ) {
            return $clauses;
        }

        return array_merge( $clauses, $sort );

    }

    /**
     * Add our post type to the 'At a Glance' widget on the WordPress 3.8+ dashboard.
     *
     * @param  array $items Array of items to display on the widget.
     * @return array        Updated array of items.
     */
    public function glance_items( array $items ) {

        $pto = get_post_type_object( $this->cpt->post_type );

        if ( ! current_user_can( $pto->cap->edit_posts ) ) {
            return $items;
        }
        if ( $pto->_builtin ) {
            return $items;
        }

        # Get the labels and format the counts:
        $count = wp_count_posts( $this->cpt->post_type );
        $text  = self::n( $pto->labels->singular_name, $pto->labels->name, $count->publish );
        $num   = number_format_i18n( $count->publish );

        # This is absolutely not localisable. WordPress 3.8 didn't add a new post type label.
        $url = add_query_arg( [
            'post_type' => $this->cpt->post_type,
        ], admin_url( 'edit.php' ) );
        $text = '<a href="' . esc_url( $url ) . '">' . esc_html( $num . ' ' . $text ) . '</a>';

        # Go!
        $items[] = $text;

        return $items;

    }

    /**
     * Add our post type updated messages.
     *
     * The messages are as follows:
     *
     *   1 => "Post updated. {View Post}"
     *   2 => "Custom field updated."
     *   3 => "Custom field deleted."
     *   4 => "Post updated."
     *   5 => "Post restored to revision from [date]."
     *   6 => "Post published. {View post}"
     *   7 => "Post saved."
     *   8 => "Post submitted. {Preview post}"
     *   9 => "Post scheduled for: [date]. {Preview post}"
     *  10 => "Post draft updated. {Preview post}"
     *
     * @param  array $messages An associative array of post updated messages with post type as keys.
     * @return array           Updated array of post updated messages.
     */
    public function post_updated_messages( array $messages ) {

        global $post;

        $pto = get_post_type_object( $this->cpt->post_type );

        $messages[ $this->cpt->post_type ] = array(
            1 => sprintf(
                ( $pto->publicly_queryable ? '%1$s updated. <a href="%2$s">View %3$s</a>' : '%1$s updated.' ),
                esc_html( $this->cpt->post_singular ),
                esc_url( get_permalink( $post ) ),
                esc_html( $this->cpt->post_singular_low )
            ),
            2 => 'Custom field updated.',
            3 => 'Custom field deleted.',
            4 => sprintf(
                '%s updated.',
                esc_html( $this->cpt->post_singular )
            ),
            5 => isset( $_GET['revision'] ) ? sprintf(
                '%1$s restored to revision from %2$s',
                esc_html( $this->cpt->post_singular ),
                wp_post_revision_title( intval( $_GET['revision'] ), false )
            ) : false,
            6 => sprintf(
                ( $pto->publicly_queryable ? '%1$s published. <a href="%2$s">View %3$s</a>' : '%1$s published.' ),
                esc_html( $this->cpt->post_singular ),
                esc_url( get_permalink( $post ) ),
                esc_html( $this->cpt->post_singular_low )
            ),
            7 => sprintf(
                '%s saved.',
                esc_html( $this->cpt->post_singular )
            ),
            8 => sprintf(
                ( $pto->publicly_queryable ? '%1$s submitted. <a target="_blank" href="%2$s">Preview %3$s</a>' : '%1$s submitted.' ),
                esc_html( $this->cpt->post_singular ),
                esc_url( add_query_arg( 'preview', 'true', get_permalink( $post ) ) ),
                esc_html( $this->cpt->post_singular_low )
            ),
            9 => sprintf(
                ( $pto->publicly_queryable ? '%1$s scheduled for: <strong>%2$s</strong>. <a target="_blank" href="%3$s">Preview %4$s</a>' : '%1$s scheduled for: <strong>%2$s</strong>.' ),
                esc_html( $this->cpt->post_singular ),
                esc_html( date_i18n( 'M j, Y @ G:i', strtotime( $post->post_date ) ) ),
                esc_url( get_permalink( $post ) ),
                esc_html( $this->cpt->post_singular_low )
            ),
            10 => sprintf(
                ( $pto->publicly_queryable ? '%1$s draft updated. <a target="_blank" href="%2$s">Preview %3$s</a>' : '%1$s draft updated.' ),
                esc_html( $this->cpt->post_singular ),
                esc_url( add_query_arg( 'preview', 'true', get_permalink( $post ) ) ),
                esc_html( $this->cpt->post_singular_low )
            ),
        );

        return $messages;

    }

    /**
     * Add our bulk post type updated messages.
     *
     * The messages are as follows:
     *
     *  - updated   => "Post updated." | "[n] posts updated."
     *  - locked    => "Post not updated, somebody is editing it." | "[n] posts not updated, somebody is editing them."
     *  - deleted   => "Post permanently deleted." | "[n] posts permanently deleted."
     *  - trashed   => "Post moved to the trash." | "[n] posts moved to the trash."
     *  - untrashed => "Post restored from the trash." | "[n] posts restored from the trash."
     *
     * @param  array $messages An associative array of bulk post updated messages with post type as keys.
     * @param  array $counts   An array of counts for each key in `$messages`.
     * @return array           Updated array of bulk post updated messages.
     */
    public function bulk_post_updated_messages( array $messages, array $counts ) {

        $messages[ $this->cpt->post_type ] = [
            'updated' => sprintf(
                self::n( '%2$s updated.', '%1$s %3$s updated.', $counts['updated'] ),
                esc_html( number_format_i18n( $counts['updated'] ) ),
                esc_html( $this->cpt->post_singular ),
                esc_html( $this->cpt->post_plural_low )
            ),
            'locked' => sprintf(
                self::n( '%2$s not updated, somebody is editing it.', '%1$s %3$s not updated, somebody is editing them.', $counts['locked'] ),
                esc_html( number_format_i18n( $counts['locked'] ) ),
                esc_html( $this->cpt->post_singular ),
                esc_html( $this->cpt->post_plural_low )
            ),
            'deleted' => sprintf(
                self::n( '%2$s permanently deleted.', '%1$s %3$s permanently deleted.', $counts['deleted'] ),
                esc_html( number_format_i18n( $counts['deleted'] ) ),
                esc_html( $this->cpt->post_singular ),
                esc_html( $this->cpt->post_plural_low )
            ),
            'trashed' => sprintf(
                self::n( '%2$s moved to the trash.', '%1$s %3$s moved to the trash.', $counts['trashed'] ),
                esc_html( number_format_i18n( $counts['trashed'] ) ),
                esc_html( $this->cpt->post_singular ),
                esc_html( $this->cpt->post_plural_low )
            ),
            'untrashed' => sprintf(
                self::n( '%2$s restored from the trash.', '%1$s %3$s restored from the trash.', $counts['untrashed'] ),
                esc_html( number_format_i18n( $counts['untrashed'] ) ),
                esc_html( $this->cpt->post_singular ),
                esc_html( $this->cpt->post_plural_low )
            ),
        ];

        return $messages;

    }

    /**
     * Add our custom columns to the list of sortable columns.
     *
     * @param  array $cols Associative array of sortable columns
     * @return array       Updated array of sortable columns
     */
    public function sortables( array $cols ) {

        foreach ( $this->args['admin_cols'] as $id => $col ) {
            if ( ! is_array( $col ) ) {
                continue;
            }
            if ( isset( $col['sortable'] ) && ! $col['sortable'] ) {
                continue;
            }
            if ( isset( $col['meta_key'] ) || isset( $col['taxonomy'] ) || isset( $col['post_field'] ) ) {
                $cols[ $id ] = $id;
            }
        }

        return $cols;

    }

    /**
     * Add columns to the admin screen for this post type.
     *
     * Each item in the `admin_cols` array is either a string name of an existing column, or an associative
     * array of information for a custom column.
     *
     * Defining a custom column is easy. Just define an array which includes the column title, column
     * type, and optional callback function. You can display columns for post meta, taxonomy terms,
     * post fields, the featured image, and custom functions.
     *
     * The example below adds two columns; one which displays the value of the post's `event_type` meta
     * key and one which lists the post's terms from the `location` taxonomy:
     *
     *     register_extended_post_type( 'event', array(
     *         'admin_cols' => array(
     *             'event_type' => array(
     *                 'title'    => 'Event Type',
     *                 'meta_key' => 'event_type'
     *             ),
     *             'event_location' => array(
     *                 'title'    => 'Location',
     *                 'taxonomy' => 'location'
     *             )
     *         )
     *     ) );
     *
     * That's all you need to do. The columns will handle all the sorting and safely outputting the data
     * (escaping text, and comma-separating taxonomy terms). No more messing about with all of those
     * annoyingly named column filters and actions.
     *
     * Each item in the `admin_cols` array must contain one of the following elements which defines the column type:
     *
     *  - taxonomy       - The name of a taxonomy
     *  - meta_key       - A post meta key
     *  - post_field     - The name of a post field (eg. post_excerpt)
     *  - featured_image - A featured image size (eg. thumbnail)
     *  - realtion       - A meta key with a relation to any other post
     *  - function       - The name of a callback function
     *
     * The value for the corresponding taxonomy terms, post meta or post field are safely escaped and output
     * into the column, and the values are used to provide the sortable functionality for the column. For
     * featured images, the post's featured image of that size will be displayed if there is one.
     *
     * There are a few optional elements:
     *
     *  - title - Generated from the field if not specified.
     *  - function - The name of a callback function for the column (eg. `my_function`) which gets called
     *    instead of the built-in function for handling that column. Note that it's not passed any parameters,
     *    so it must use the global $post object.
     *  - default - Specifies that the admin screen should be sorted by this column by default (instead of
     *    sorting by post date). Value should be one of `asc` or `desc` to control the default order.
     *  - width & height - These are only used for the `featured_image` column type and allow you to set an
     *    explicit width and/or height on the <img> tag. Handy for downsizing the image.
     *  - field & value - These are used for the `connection` column type and allow you to specify a
     *    connection meta field and value from the fields argument of the connection type.
     *  - date_format - This is used with the `meta_key` column type. The value of the meta field will be
     *    treated as a timestamp if this is present. Unix and MySQL format timestamps are supported in the
     *    meta value. Pass in boolean true to format the date according to the 'Date Format' setting, or pass
     *    in a valid date formatting string (eg. `d/m/Y H:i:s`).
     *  - cap - A capability required in order for this column to be displayed to the current user. Defaults
     *    to null, meaning the column is shown to all users.
     *  - sortable - A boolean value which specifies whether the column should be sortable. Defaults to true.
     *
     * @TODO - post_cap
     *
     * @TODO - link
     *
     * In addition to custom columns there are also columns built in to WordPress which you can
     * use: `comments`, `date`, `title` and `author`. You can use these column names as the array value, or as the
     * array key with a string value to change the column title. You can also pass boolean false to remove
     * the `cb` or `title` columns, which are otherwise kept regardless.
     *
     * @param  array $cols Associative array of columns
     * @return array       Updated array of columns
     */
    public function cols( array $cols ) {

        // This function gets called multiple times, so let's cache it for efficiency:
        if ( isset( $this->the_cols ) ) {
            return $this->the_cols;
        }

        $new_cols = [];
        $keep_default_columns = [ 'cb', 'title' ];
        $keep = apply_filters( "cube/posttypes/{$this->cpt->post_type}/default_columns", $keep_default_columns );

        # Add existing columns we want to keep:
        foreach ( $cols as $id => $title ) {
            if ( in_array( $id, $keep ) && ! isset( $this->args['admin_cols'][ $id ] ) ) {
                $new_cols[ $id ] = $title;
            }
        }

        # Add our custom columns:
        foreach ( array_filter( $this->args['admin_cols'] ) as $id => $col ) {
            if ( is_string( $col ) && isset( $cols[ $col ] ) ) {
                # Existing (ie. built-in) column with id as the value
                $new_cols[ $col ] = $cols[ $col ];
            } else if ( is_string( $col ) && isset( $cols[ $id ] ) ) {
                # Existing (ie. built-in) column with id as the key and title as the value
                $new_cols[ $id ] = esc_html( $col );
            } else if ( 'author' === $col ) {
                # Automatic support for Co-Authors Plus plugin and special case for
                # displaying author column when the post type doesn't support 'author'
                if ( class_exists( 'coauthors_plus' ) ) {
                    $k = 'coauthors';
                } else {
                    $k = 'author';
                }
                $new_cols[ $k ] = esc_html__( 'Author' );
            } else if ( is_array( $col ) ) {
                if ( isset( $col['cap'] ) && ! current_user_can( $col['cap'] ) ) {
                    continue;
                }
                if ( isset( $col['connection'] ) && ! function_exists( 'p2p_type' ) ) {
                    continue;
                }
                if ( ! isset( $col['title'] ) ) {
                    $col['title'] = $this->get_item_title( $col );
                }
                $new_cols[ $id ] = esc_html( $col['title'] );
            }
        }

        # Re-add any custom columns:
        $custom   = array_diff_key( $cols, $this->_cols );
        $new_cols = array_merge( $new_cols, $custom );

        return $this->the_cols = $new_cols;

    }

    /**
     * Output the column data for our custom columns.
     *
     * @param string $col The column name
     */
    public function col( $col ) {

        # Shorthand:
        $c = $this->args['admin_cols'];

        # We're only interested in our custom columns:
        $custom_cols = array_filter( array_keys( $c ) );

        if ( ! in_array( $col, $custom_cols ) ) {
            return;
        }

        if ( isset( $c[ $col ]['post_cap'] ) && ! current_user_can( $c[ $col ]['post_cap'], get_the_ID() ) ) {
            return;
        }

        if ( ! isset( $c[ $col ]['link'] ) ) {
            $c[ $col ]['link'] = 'edit';
        }

        if( isset( $c[ $col ]['function'] ) ) {

            call_user_func( $c[ $col ]['function'], array(
                'meta_value' => get_post_meta( get_the_id(), $c[ $col ]['meta_key'], true ),
                'post_id' => get_the_id(),
                'settings' => $c[ $col ]
            ));

        } else if ( isset( $c[ $col ]['meta_key'] ) ) {
            $this->col_post_meta( $c[ $col ]['meta_key'], $c[ $col ] );
        } else if ( isset( $c[ $col ]['taxonomy'] ) ) {
            $this->col_taxonomy( $c[ $col ]['taxonomy'], $c[ $col ] );
        } else if ( isset( $c[ $col ]['post_field'] ) ) {
            $this->col_post_field( $c[ $col ]['post_field'], $c[ $col ] );
        } else if ( isset( $c[ $col ]['featured_image'] ) ) {
            $this->col_featured_image( $c[ $col ]['featured_image'], $c[ $col ] );
        } else if ( isset( $c[ $col ]['relation'] ) ) {
            $this->col_relation( $c[ $col ]['relation'], $c[ $col ] );
        }

    }

    /**
     * Output column data for a post meta field.
     *
     * @param string $meta_key The post meta key
     * @param array  $args     Array of arguments for this field
     */
    public function col_post_meta( $meta_key, array $args ) {

        $vals = get_post_meta( get_the_ID(), $meta_key, false );
        $output = [];
        sort( $vals );

        if ( isset( $args['date_format'] ) ) {

            foreach ( $vals as $val ) {

                // Check is passed value is a valid date
                if( $this->is_valid_date( $val ) ) {

                    // Prapre date for 'mysql2date' function
                    // Must be formatted in 'Y-m-d H:i:s' format
                    // See https://codex.wordpress.org/Function_Reference/mysql2date
                    $date = date( 'Y-m-d H:i:s', strtotime($val) );

                    // Output the translated date in the correct format
                    $output[] = mysql2date( $args['date_format'], $val );

                }

            }

        } else {

            foreach ( $vals as $val ) {

                if ( ! empty( $val ) || ( '0' === $val ) ) {
                    $output[] = $val;
                }
            }
        }

        if ( empty( $output ) ) {
            echo '&#8212;';
        } else {

            if( !empty( $args['true_false'] ) && true === $args['true_false'] ) {

                if( isset( $output[0] ) ) {

                    if( $output[0] == '1' ) {
                        echo __('Yes', 'cube');
                    } else {
                        echo __('No', 'cube');
                    }

                } else {
                    echo '&#8212;';
                }

            } else {
                echo esc_html( implode( ', ', $output ) );
            }
        }

    }

    /**
     * Output column data for a taxonomy's term names.
     *
     * @param string $taxonomy The taxonomy name
     * @param array  $args     Array of arguments for this field
     */
    public function col_taxonomy( $taxonomy, array $args ) {

        global $post;

        $terms = get_the_terms( $post, $taxonomy );
        $tax   = get_taxonomy( $taxonomy );

        if ( is_wp_error( $terms ) ) {
            echo esc_html( $terms->get_error_message() );
            return;
        }

        if ( empty( $terms ) ) {
            echo '&#8212;';
            return;
        }

        $out = [];

        foreach ( $terms as $term ) {

            if ( $args['link'] ) {

                switch ( $args['link'] ) {
                    case 'view':
                        if ( $tax->public ) {
                            $out[] = sprintf(
                                '<a href="%1$s">%2$s</a>',
                                esc_url( get_term_link( $term ) ),
                                esc_html( $term->name )
                            );
                        } else {
                            $out[] = esc_html( $term->name );
                        }
                        break;
                    case 'edit':
                        if ( current_user_can( $tax->cap->edit_terms ) ) {
                            $out[] = sprintf(
                                '<a href="%1$s">%2$s</a>',
                                esc_url( get_edit_term_link( $term, $taxonomy, $post->post_type ) ),
                                esc_html( $term->name )
                            );
                        } else {
                            $out[] = esc_html( $term->name );
                        }
                        break;
                    case 'list':
                        $link = add_query_arg( array(
                            'post_type' => $post->post_type,
                            $taxonomy   => $term->slug,
                        ), admin_url( 'edit.php' ) );
                        $out[] = sprintf(
                            '<a href="%1$s">%2$s</a>',
                            esc_url( $link ),
                            esc_html( $term->name )
                        );
                        break;
                }
            } else {

                $out[] = esc_html( $term->name );

            }
        }

        echo implode( ', ', $out ); // WPCS: XSS ok.

    }

    /**
     * Output column data for a post field.
     *
     * @param string $field The post field
     * @param array  $args  Array of arguments for this field
     */
    public function col_post_field( $field, array $args ) {

        global $post;

        switch ( $field ) {

            case 'post_date':
            case 'post_date_gmt':
            case 'post_modified':
            case 'post_modified_gmt':
                if ( '0000-00-00 00:00:00' !== get_post_field( $field, $post ) ) {
                    $date_format = !empty( $args['date_format'] ) ? $args['date_format'] : get_option( 'date_format' );
                    echo esc_html( mysql2date( $date_format, get_post_field( $field, $post ) ) );
                }
                break;

            case 'post_status':
                if ( $status = get_post_status_object( get_post_status( $post ) ) ) {
                    echo esc_html( $status->label );
                }
                break;

            case 'post_author':
                echo esc_html( get_the_author() );
                break;

            case 'post_title':
                echo esc_html( get_the_title() );
                break;

            case 'post_excerpt':
                echo esc_html( get_the_excerpt() );
                break;

            default:
                echo esc_html( get_post_field( $field, $post ) );
                break;

        }

    }

    /**
     * Output column data for a post's featured image.
     *
     * @param string $image_size The image size
     * @param array  $args       Array of `width` and `height` attributes for the image
     */
    public function col_featured_image( $image_size, array $args ) {

        if ( ! function_exists( 'has_post_thumbnail' ) ) {
            return;
        }

        if ( isset( $args['width'] ) ) {
            $width = is_numeric( $args['width'] ) ? sprintf( '%dpx', $args['width'] ) : $args['width'];
        } else {
            $width = 'auto';
        }

        if ( isset( $args['height'] ) ) {
            $height = is_numeric( $args['height'] ) ? sprintf( '%dpx', $args['height'] ) : $args['height'];
        } else {
            $height = 'auto';
        }

        $image_atts = [
            'style' => esc_attr( sprintf(
                'width:%1$s;height:%2$s',
                $width,
                $height
            ) ),
            'title' => '',
        ];

        if ( has_post_thumbnail() ) {
            the_post_thumbnail( $image_size, $image_atts );
        }

    }

    /**
     * Output column data for posts relation.
     *
     * @param string $meta_key   The metafield key to find the related posts
     * @param array  $args       Array of arguments for a given relation type
     */

    public function col_relation( $meta_key, array $args ) {

        $out            = [];
        $connected_ids  = get_post_meta( get_the_ID(), $meta_key, false );

        // If output array is multidimensional merge it back to one array
        if (count($connected_ids) != count($connected_ids, COUNT_RECURSIVE)) {
            $connected_ids = array_reduce($connected_ids, 'array_merge', []);
        }

        if( !empty( $connected_ids ) && is_array( $connected_ids ) ) {

            foreach( $connected_ids as $connected_id ) {

                if( !empty( $connected_id ) ) {

                    $connected_post = get_post( $connected_id );

                    if( !empty( $connected_post ) && is_object( $connected_post ) ) {

                        if ( $args['link'] ) {

                            switch ( $args['link'] ) {
                                case 'view':

                                    $out[] = sprintf(
                                        '<a href="%1$s">%2$s</a>',
                                        esc_url( get_permalink( $connected_post->ID ) ),
                                        esc_html( get_the_title( $connected_post->ID ) )
                                    );

                                    break;
                                case 'edit':

                                    if ( current_user_can( 'edit_post', $connected_post->ID ) ) {
                                        $out[] = sprintf(
                                            '<a href="%1$s">%2$s</a>',
                                            esc_url( get_edit_post_link( $connected_post->ID ) ),
                                            esc_html( get_the_title( $connected_post->ID ) )
                                        );
                                    } else {
                                        $out[] = esc_html( get_the_title( $connected_post->ID ) );
                                    }

                                    break;
                            }


                        } else {
                            $out[] = esc_html( get_the_title( $connected_post->ID ) );
                        }

                    }

                } else {
                    echo '&#8212;';
                }

            }

            echo implode( ', ', $out );

        } else {
            echo '&#8212;';
        }

    }

    /**
     * Removes the Quick Edit link from the post row actions.
     *
     * @param  array   $actions Array of post actions
     * @param  WP_Post $post    The current post object
     * @return array            Array of updated post actions
     */
    public function remove_quick_edit_action( array $actions, WP_Post $post ) {

        if ( $this->cpt->post_type !== $post->post_type ) {
            return $actions;
        }

        unset( $actions['inline'], $actions['inline hide-if-no-js'] );
        return $actions;

    }

    /**
     * Removes the Quick Edit link from the bulk actions menu.
     *
     * @param  array $actions Array of bulk actions
     * @return array          Array of updated bulk actions
     */
    public function remove_quick_edit_menu( array $actions ) {

        unset( $actions['edit'] );
        return $actions;

    }

    /**
     * Logs the default columns so we don't remove any custom columns added by other plugins.
     *
     * @param  array $cols The default columns for this post type screen
     * @return array       The default columns for this post type screen
     */
    public function _log_default_cols( array $cols ) {

        return $this->_cols = $cols;

    }

    /**
     * A non-localised version of _n()
     *
     * @param  string $single The text that will be used if $number is 1
     * @param  string $plural The text that will be used if $number is not 1
     * @param  int    $number The number to compare against to use either `$single` or `$plural`
     * @return string         Either `$single` or `$plural` text
     */
    protected static function n( $single, $plural, $number ) {

        return ( 1 === intval( $number ) ) ? $single : $plural;

    }

    /**
     * Get a sensible title for the current item (usually the arguments array for a column)
     *
     * @param  array  $item An array of arguments
     * @return string       The item title
     */
    protected function get_item_title( array $item ) {

        if ( isset( $item['taxonomy'] ) ) {
            if ( $tax = get_taxonomy( $item['taxonomy'] ) ) {
                if ( ! empty( $tax->exclusive ) ) {
                    return $tax->labels->singular_name;
                } else {
                    return $tax->labels->name;
                }
            } else {
                return $item['taxonomy'];
            }
        } else if ( isset( $item['post_field'] ) ) {
            return ucwords( trim( str_replace( [ 'post_', '_' ], ' ', $item['post_field'] ) ) );
        } else if ( isset( $item['meta_key'] ) ) {
            return ucwords( trim( str_replace( [ '_', '-' ], ' ', $item['meta_key'] ) ) );
        } else if ( isset( $item['connection'] ) && isset( $item['value'] ) ) {
            return ucwords( trim( str_replace( [ '_', '-' ], ' ', $item['value'] ) ) );
        }

    }

    /**
     * Check if a string date is a valid date
     *
     * @since   1.0.0
     * @author  Joeri Abbo
     *
     * @param  string  $date String with the date
     * @return bool
     */

    private function is_valid_date($date){
        return (bool)strtotime($date);
    }

}
