<?php

/**
 * Add styleguides toolbars and unset default ones of ACF wysiwyg editor
 *
 * All possible settings for the toolbar
 * ['formatselect', 'bold', 'italic', 'bullist', 'numlist', 'blockquote', 'alignleft', 'aligncenter', 'aligncenter',
 * 'link', 'unlink', 'wp_more', 'spellchecker', 'fullscreen', 'wp_adv', 'strikethrough', 'hr' , 'forecolor',
 * 'pastetext','removeformat', 'charmap', 'outdent', 'indent', 'undo', 'redo', 'wp_help']
 *
 * @author    Joeri Abbo <joeriabbo@hotmail.nl>
 * @link      https://joeriabbo.nl
 * @copyright 2022 Joeri Abbo
 */


add_filter('acf/fields/wysiwyg/toolbars', 'cube_add_toolbars');
function cube_add_toolbars($toolbars){

    $toolbars['Styleguide']     = [];
    $toolbars['Styleguide'][1]  = ['bold', 'italic', 'underline', 'bullist', 'numlist'];

    // remove the 'Basic' toolbar completely
    unset($toolbars['Basic']);
    unset($toolbars['Full']);

    return $toolbars;
}

/**
 * Change tinyMCE's paste-as-text functionality
 * Force the TinyMCE editor to always paste every pasted text as plain text
 *
 * @param  array    $mceInit   The TinyMCE object
 * @param  integer  $editor_id The ID of the editor
 * @return array    $mceInit   The adjusted TinyMCE object
 * @author    Joeri Abbo <joeriabbo@hotmail.com>
 * @link      https://joeriabbo.nl
 * @copyright 2022 Joeri Abbo
 */

function cube_default_paste_as_text($mceInit, $editor_id){

    $mceInit['paste_text_use_dialog'] = false;
    $mceInit['paste_as_text'] = true;

    return $mceInit;
}

add_filter('tiny_mce_before_init', 'cube_default_paste_as_text', 1, 2);

/**
 * Add paste cube_load_paste_plugin
 * Force the TinyMCE editor to add paste plugin
 *
 * @param  array    $plugins    All plugins of TinyMCE
 * @return  array   $plugins    Return plugins of TinyMCE
 * @author    Joeri Abbo <joeriabbo@hotmail.com>
 * @link      https://joeriabbo.nl
 * @copyright 2022 Joeri Abbo
 */

function cube_load_paste_plugin($plugins) {
    $plugins[] = 'paste';
    return $plugins;
}

add_filter('teeny_mce_plugins', 'cube_load_paste_plugin', 1, 2);

