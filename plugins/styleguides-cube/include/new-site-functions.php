<?php
/**
 * Automatic generated the settings for the plugin 'Intuitive Custom Post Order' when a new site is generated
 *
 * @author    Joeri Abbo <joeriabbo@hotmail.com>
 * @link      https://joeriabbo.nl
 * @copyright 2022 Joeri Abbo
 */

add_action('admin_init', function () {
    if (!get_option('hicpo_options')) {
        $options = [];
        $options['objects'] = ['guide'];
        $options['tags'] = ['taxonomie'];

        add_option('hicpo_options', $options);
    }
}, 10, 6);
