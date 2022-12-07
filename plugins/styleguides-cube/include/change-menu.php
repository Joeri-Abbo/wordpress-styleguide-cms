<?php

/**
 * Remove pages inside the admin menu based on role and turning some off automatic
 *
 * @author    Joeri Abbo <joeriabbo@hotmail.com>
 * @link      https://joeriabbo.nl
 * @copyright 2022 Joeri Abbo
 */

add_action('admin_menu', 'remove_menus');

function remove_menus()
{
    remove_menu_page('index.php');                  //Dashboard
    remove_menu_page('jetpack');                    //Jetpack*
    remove_menu_page('edit.php');                   //Posts
    remove_menu_page('edit.php?post_type=page');    //Pages
    remove_menu_page('edit-comments.php');          //Comments
    remove_menu_page('themes.php');                 //Appearance
    remove_menu_page('plugins.php');                //Plugins
    remove_menu_page('tools.php');                  //Tools
    remove_menu_page('options-general.php');        //Settings
}

//only show this item if the users is an administrator.
add_action('admin_init', 'Remove_menu_items_if_role');

function Remove_menu_items_if_role()
{
    $user = wp_get_current_user();

    if (!in_array('administrator', (array)$user->roles)) {
        remove_menu_page('users.php');
        remove_menu_page('edit.php?post_type=acf-field-group');
        remove_menu_page('site-settings');
        remove_menu_page('color-settings');
    }
}

//only show this item if we are on the rood domain
add_filter('acf/settings/show_admin', 'acf_show_only_root_domain');

function acf_show_only_root_domain( $show ) {

    if (get_current_blog_id() != 1 ) {
        return false;
    }
    return true;

}
