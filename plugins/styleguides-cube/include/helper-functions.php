<?php
/**
 *
 * Copied from cube-theme ->actions.php
 *
 * Force a custom upload directory and URL
 * Attention: This will only work when no UPDATE constant is defined
 *
 */

/**
 * Removes the check for admin mail when logging in.
 *
 * @author    Joeri Abbo <joeriabbo@hotmail.com>
 * @link      https://joeriabbo.nl/
 * @copyright 2022 Joeri Abbo
 */

add_filter( 'admin_email_check_interval', '__return_false' );

//copy from admin core

function cube_upload_path() {

    $upload_path        = get_option('upload_path');
    $upload_url_path    = get_option('upload_url_path');
    $upload_directory   = 'assets';

    // Build new upload path and URL from
    $new_upload_dir     = trailingslashit( WP_CONTENT_DIR ) . $upload_directory;
    $new_upload_path    = trailingslashit( WP_CONTENT_URL ) . $upload_directory;

    // Check if new upload path and URL is equal to the ones in the DB
    // Else force an update with the new upload path and URL
    if( empty( $upload_url_path ) || $upload_url_path !== $new_upload_path ) {

        update_option( 'upload_path', $new_upload_dir );
        update_option( 'upload_url_path', $new_upload_path );

    }

}

add_action('init', 'cube_upload_path');

// load custom login logo
function cube_custom_login_logo() {
    echo '
    <style type="text/css">
        h1 a {
            background-image:url(' . plugin_dir_url( __FILE__ ) . '/include/assets/logo.svg) !important;
            height: 87px !important;
            width: 200px !important;
            background-size: 200px 87px !important;
        }
    </style>';
}

add_action( 'login_head', 'cube_custom_login_logo' ) ; // load custom login logo

// load custom login logo URL
function cube_custom_loginlogo_url($url) {
    return get_bloginfo('url');
}

add_filter( 'login_headerurl', 'cube_custom_loginlogo_url');

// load custom login logo TITLE
function cube_custom_login_title () {
    return get_bloginfo ('name');
}

add_filter( 'login_headertext', 'cube_custom_login_title');


if ( ! function_exists( 'blog_exists' ) ) {

    /**
     * Checks if a blog exists and is not marked as deleted.
     *
     * @link   http://wordpress.stackexchange.com/q/138300/73
     * @param  int $blog_id
     * @param  int $site_id
     * @return bool
     */
    function blog_exists( $blog_id, $site_id = 0 ) {

        global $wpdb;
        static $cache = array ();

        $site_id = (int) $site_id;

        if ( 0 === $site_id )
            $site_id = get_current_site()->id;

        if ( empty ( $cache ) or empty ( $cache[ $site_id ] ) ) {

            if ( wp_is_large_network() ) // we do not test large sites.
                return TRUE;

            $query = "SELECT `blog_id` FROM $wpdb->blogs
                    WHERE site_id = $site_id AND deleted = 0";

            $result = $wpdb->get_col( $query );

            // Make sure the array is always filled with something.
            if ( empty ( $result ) )
                $cache[ $site_id ] = array ( 'do not check again' );
            else
                $cache[ $site_id ] = $result;
        }

        return in_array( $blog_id, $cache[ $site_id ] );
    }
}

/**
 * Compare 2 strings to check which is newer
 *
 * @param $a
 * @param $b
 * @return int|lt
 * @author    Joeri Abbo <joeriabbo@hotmail.com>
 * @link      https://joeriabbo.nl/
 * @copyright 2022 Joeri Abbo
 */

function cube_cmp_term_order($a, $b)
{
    return strcmp($a->term_order, $b->term_order);
}

/**
 * Allow the upload of zip/gz/SVG inside WP media library
 *
 * @author    Joeri Abbo <joeriabbo@hotmail.com>
 * @link      https://joeriabbo.nl/
 * @copyright 2022 Joeri Abbo
 */

add_filter('upload_mimes', 'cube_upload_mimes');
function cube_upload_mimes ( $mimes ) {
    // add your extension to the mimes array as below
    $mimes['zip'] = 'application/zip';
    $mimes['gz'] = 'application/x-gzip';
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
}

/**
 * Fix SVG inside WP media library
 *
 * @author    Joeri Abbo <joeriabbo@hotmail.com>
 * @link      https://joeriabbo.nl/
 * @copyright 2022 Joeri Abbo
 */

add_action('admin_head', 'cube_fix_svg_thumb_display');
function cube_fix_svg_thumb_display() {
    echo '<style>
    td.media-icon img[src$=".svg"], img[src$=".svg"].attachment-post-thumbnail { 
      width: 100% !important; 
      height: auto !important; 
    }
  </style>';
}

/**
 * Remove the slug edit box of a post because we dont use this and only makes an distraction for the user
 *
 * @author    Joeri Abbo <joeriabbo@hotmail.com>
 * @link      https://joeriabbo.nl/
 * @copyright 2022 Joeri Abbo
 */

add_action('admin_head', 'cube_remove_edit_slug_box');
function cube_remove_edit_slug_box() {
    echo '<style>
    #edit-slug-box { 
      display: none;
    }
  </style>';
}

/**
 * Wordpress: Remove titles dropdown
 *
 * @author    Joeri Abbo <joeriabbo@hotmail.com>
 * @link      https://joeriabbo.nl/
 * @copyright 2022 Joeri Abbo
 */

add_action('admin_footer', 'cube_remove_sort_by_title');
function cube_remove_sort_by_title() {
    echo "<script>
        jQuery('#title').empty().append('Titel').removeClass('sorted');
        console.log('loaded');
    </script>";
}

/**
 * Wordpress: Filter admin columns
 *
 * @author    Joeri Abbo <joeriabbo@hotmail.com>
 * @link      https://joeriabbo.nl/
 * @copyright 2022 Joeri Abbo
 */

function cube_remove_columns_date( $columns ) {
    unset( $columns['date'] );
    return $columns;
}

/**
 * Remove data column of overview page of the posts inside the cms
 *
 * @author    Joeri Abbo <joeriabbo@hotmail.com>
 * @link      https://joeriabbo.nl/
 * @copyright 2022 Joeri Abbo
 */

function cube_filter_columns() {

    $types = get_post_types(['public' => true]);

    foreach($types as $type) {
        $filter = "manage_edit-{$type}_columns";
        add_filter($filter, 'cube_remove_columns_date');
    }

}

add_action('admin_init', 'cube_filter_columns');

/**
 * Do not show parent of a taxonomy
 *
 * @author    Joeri Abbo <joeriabbo@hotmail.com>
 * @link      https://joeriabbo.nl/
 * @copyright 2022 Joeri Abbo
 */

function cube_hide_discover_type_parent() {

    global $current_screen;

    if (!empty( $current_screen->id ) ){
        $whitelist = [
            'edit-taxonomie',
        ];

        if (in_array($current_screen->id , $whitelist)) {
            echo '<style>.term-parent-wrap{display:none;}</style>';
        }
    }

}

add_action( 'admin_head-edit-tags.php', 'cube_hide_discover_type_parent' );

add_action( 'admin_head-term.php', 'cube_hide_discover_type_parent' );

/**
 * Remove the preview button of post_type guide
 *
 * @author    Joeri Abbo <joeriabbo@hotmail.com>
 * @link      https://joeriabbo.nl/
 * @copyright 2022 Joeri Abbo
 */

function cube_hide_preview_button() {
    if (!empty($_GET['post'])and isset($_GET['post'])){
        if ( get_post($_GET['post'])->post_type == 'guide'){
            echo '<style>#preview-action{display:none;}</style>';
        }
    }
}

add_action( 'admin_head', 'cube_hide_preview_button' );


/**
 * remove action sanitize user
 *
 * @author    Joeri Abbo <joeriabbo@hotmail.com>
 * @link      https://joeriabbo.nl/
 * @copyright 2022 Joeri Abbo
 */

add_action( 'init', 'cube_remove_automatic_lowercase_signup' );
function cube_remove_automatic_lowercase_signup() {
    remove_action( 'sanitize_user', 'strtolower' );
}

/**
 * Allow dots inside usernames like in the default env of wordpress
 *
 * @author    Joeri Abbo <joeriabbo@hotmail.com>
 * @link      https://joeriabbo.nl/
 * @copyright 2022 Joeri Abbo
 */

add_filter( 'wpmu_validate_user_signup', 'cube_modify_validate_user_signup' );
function cube_modify_validate_user_signup( $result ) {
    if ( ! is_wp_error( $result['errors'] ) ) {
        return $result;
    }

    $username   = $result['user_name'];
    $new_errors = new WP_Error();
    $errors     = $result['errors'];
    $codes      = $errors->get_error_codes();

    foreach ( $codes as $code ) {

        $messages = $errors->get_error_messages( $code );

        if ( 'user_name' === $code ) {

        preg_match( '/[A-Za-z0-9]+/', $username, $maybe );

        } else {
            foreach ( $messages as $message ) {
                $new_errors->add( $code, $message );
            }
        }
    }

    $result['errors'] = $new_errors;

    return $result;
}

/**
 * Remove the add taxonomy button on post.php so users cant make a taxonomy without creating it on the taxonomy page.
 *
 * @author    Joeri Abbo <joeriabbo@hotmail.com>
 * @link      https://joeriabbo.nl/
 * @copyright 2022 Joeri Abbo
 */

function cube_remove_add_new_taxonomy_on_guide_page(){
    echo '<style>';
    echo '.acf-taxonomy-field .acf-icon{ display : none } ';
    echo '</style>';
}

// add simple_role capabilities, priority must be after the initial role definition
add_action('admin_footer', 'cube_remove_add_new_taxonomy_on_guide_page'  );

/**
 * Fix scroll with css.
 *
 * @author    Joeri Abbo <joeriabbo@hotmail.com> and Tom Harms <tom.harms@cube.nl
 * @link      https://joeriabbo.nl
 * @copyright 2022 Joeri Abbo
 */

function cube_fix_scroll() {

    echo '<style>';
    echo '#wpcontent{overflow: auto;}';
    echo '#wpcontent::after{content: ""; clear: both; display: table;}';
    echo '</style>';
}

add_action( 'admin_footer', 'cube_fix_scroll' );
