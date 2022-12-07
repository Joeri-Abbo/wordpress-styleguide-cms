<?php
/**
 * This function changes the default wp-json to api
 *
 * @return string
 * @author    Joeri Abbo <joeriabbo@hotmail.com>
 * @link      https://joeriabbo.nl
 * @copyright 2022 Joeri Abbo
 */

function changeRestPrefix()
{
    return "api";
}

add_filter('rest_url_prefix', 'changeRestPrefix');

/**
 * Add new rest route for an output of the data of a site in the network
 *
 * @author    Joeri Abbo <joeriabbo@hotmail.com>
 * @link      https://joeriabbo.nl
 * @copyright 2022 Joeri Abbo
 */

add_action('rest_api_init', function () {
    register_rest_route('styleguide/v1', 'site', [
        'methods' => 'GET',
        'callback' => 'get_site_data'
    ]);
});


/**
 * First it checks if $_GET['id'] returns a valid site id. If not it returns a WP_Error that the site does not exist
 * then we switch to the site with id given inside the get parameter. And we return the data of the site given by $id
 *
 * @return array|string|WP_Error
 * @author    Joeri Abbo <joeriabbo@hotmail.com>
 * @link      https://joeriabbo.nl
 * @copyright 2022 Joeri Abbo
 */

function get_site_data()
{
    $site = [
        'homepage' => [
            'intro' => [
                'title' => html_entity_decode(get_field('homepage_title', 'option')),
                'subtitle' => html_entity_decode(get_field('homepage_subtitle', 'option')),
                'logo' => get_field('logo', 'option'),
            ],
        ],
        'general' => [
            'domain' => html_entity_decode(get_field('domain', 'option')),
            'client_name' => html_entity_decode(get_field('client_name', 'option')),
            'logo' => get_field('logo', 'option'),
            'favicons' => get_favicon(),
            'colors' => get_colors(),
            'fonts' => get_fonts(),
        ],
        'guides' => get_all_guides(),
        'seo' => get_seo(),
    ];

    return $site;
}

/**
 * This function returns a array with SEO settings
 *
 * @return array with SEO data
 * @author    Joeri Abbo <joeriabbo@hotmail.com>
 * @link      https://joeriabbo.nl
 * @copyright 2022 Joeri Abbo
 */

function get_seo()
{
    $seo = [
        'title' => html_entity_decode(get_field('seo_title', 'option')),
        'description' => html_entity_decode(get_field('seo_description', 'option')),
        'image' => wp_get_attachment_url(get_field('seo_image', 'option'))
    ];

    return $seo;
}

/**
 * This function returns a array with the fonts we are going to use in the frontend.
 * Based on what information is given in the cms
 * We generate the array with the font families.
 *
 * @return array
 * @author    Joeri Abbo <joeriabbo@hotmail.com>
 * @link      https://joeriabbo.nl
 * @copyright 2022 Joeri Abbo
 */

function get_fonts()
{
    $fonts = [];

    $font_type = get_field('font_type', 'option');
    if ($font_type == 'google') {
        $fonts['families']['google']['families'] = ['Lato:300,400,700', get_field('font_google_familie', 'option')];
        $fonts['names']['customFont'] = ['name' => get_field('font_name', 'option')];
    } else {
        $fonts['families']['google']['families'] = ['Lato:300,400,700'];
    }
    $fonts['names']['defaultFont'] = [
        'name' => 'Lato'
    ];
    if ($font_type == 'typekit') {
        $fonts['families']['typekit'] = [
            'id' => get_field('font_typekit_id', 'option')
        ];
        $fonts['names']['customFont'] = ['name' => get_field('font_name', 'option')];

    }

    return $fonts;
}

/**
 * This function returns an array with url's that can be used for the favicons inside the site.
 *
 * @return array
 * @author    Joeri Abbo <joeriabbo@hotmail.com>
 * @link      https://joeriabbo.nl
 * @copyright 2022 Joeri Abbo
 */

function get_favicon()
{
    $favicon = [
        'apple' => get_field('favicon_apple', 'option'),
        'icon' => get_field('favicon_icon', 'option'),
        'pinned' => get_field('favicon_pinned_icon', 'option')
    ];

    return $favicon;
}

/**
 * This function returns a array with colors that were given inside the CMS.
 *
 * @return array
 * @author    Joeri Abbo <joeriabbo@hotmail.com>
 * @link      https://joeriabbo.nl
 * @copyright 2022 Joeri Abbo
 */

function get_colors()
{

    $colors = [
        'general' => [
            'title' => get_field('general_color_title', 'option'),
            'subtitle' => get_field('general_color_subtitle', 'option'),
            'text' => get_field('general_color_text', 'option')],

        'button' => [
            'background' => get_field('button_background', 'option'),
            'text' => get_field('button_text', 'option')],

        'menu' => [
            'background' => get_field('menu_background', 'option'),
            'secondary_background' => get_field('menu_color_secondary_background', 'option'),
            'title' => get_field('menu_title', 'option'),
            'primary_item' => get_field('menu_color_primary_item', 'option'),
            'secondary_item' => get_field('menu_color_secondary_item', 'option'),
            'item_active' => get_field('menu_color_item_active', 'option'),
            'dark' => get_field('menu_dark_mode', 'option'),
        ],
        'homepage' => [
            'background' => get_field('homepage_color_background', 'option'),
            'title' => get_field('homepage_color_title', 'option'),
            'subtitle' => get_field('homepage_color_subtitle', 'option')
        ],

    ];

    return $colors;
}

/**
 * This function gets all taxonomies and the post that were connected to this taxonomies and returns this inside an array.
 *
 * @return array
 * @author    Joeri Abbo <joeriabbo@hotmail.com>
 * @link      https://joeriabbo.nl
 * @copyright 2022 Joeri Abbo
 */

function get_all_guides()
{
    $taxonomies = [];
    $terms = get_terms();

    usort($terms, "cube_cmp_term_order");

    foreach ($terms as $tax) {
        $guides = get_all_pages($tax);
        if (isset($guides)) {
            $taxonomie['title'] = html_entity_decode($tax->name);
            $taxonomie['description'] = html_entity_decode($tax->description);
            $taxonomie['image'] = wp_get_attachment_url(get_field('image', $tax->taxonomy . '_' . $tax->term_id));
            $taxonomie['name'] = $tax->slug;
            $taxonomie['pages'] = $guides;
            $taxonomies[$tax->slug] = $taxonomie;
            unset($taxonomie);
            unset($guides);
        }
    }

    return $taxonomies;
}

/**
 *
 * This function get's all pages from $tax and retuns the pages with the formatted array.
 *
 * @param $tax
 * @return mixed
 * @author    Joeri Abbo <joeriabbo@hotmail.com>
 * @link      https://joeriabbo.nl
 * @copyright 2022 Joeri Abbo
 */
function get_all_pages($tax)
{

    $args = [
        'post_type' => 'guide',
        'posts_per_page' => -1,
        'order_by' => 'menu_order',
        'order' => 'asc',
        'tax_query' => [[
            'taxonomy' => 'taxonomie',
            'field' => 'term_id',
            'terms' => $tax->term_id
        ]
        ]
    ];

    if (!empty($posts = new WP_Query($args))) {
        while ($posts->have_posts()) {
            global $post;
            $posts->the_post();
            $id = get_the_ID();
            $guide['id'] = $id;
            $guide['title'] = html_entity_decode(get_the_title());
            $guide['name'] = $post->post_name;
            $guide['flex'] = get_flex_modules($id);
            $guides[$post->post_name] = $guide;
        }
    }

    if (isset($guides)) {
        return $guides;
    }
}

/**
 * Based on $id it gets the Flex modules on this pages and generated's a array form this post's modules.
 *
 * @param $id
 * @return array
 * @author    Joeri Abbo <joeriabbo@hotmail.com>
 * @link      https://joeriabbo.nl
 * @copyright 2022 Joeri Abbo
 */

function get_flex_modules($id)
{
    $flexible_modules = get_field('page_modules', $id);
    if (is_array($flexible_modules) && !empty($flexible_modules)) {
        foreach ($flexible_modules as $flexible_module):

            $flex['layout'] = $flexible_module['acf_fc_layout'];

            if ($flexible_module['acf_fc_layout'] == 'title_text') {

                $flex['title'] = html_entity_decode($flexible_module['title']);

                if (isset($flexible_module['text'])) {

                    $flex['text'] = html_entity_decode($flexible_module['text']);
                }

            } elseif ($flexible_module['acf_fc_layout'] == 'image') {

                $flex['image'] = wp_get_attachment_url($flexible_module['image']);
                $flex['alt'] = html_entity_decode(get_post_meta($flexible_module['image'], '_wp_attachment_image_alt', true));
                $flex['padding'] = $flexible_module['padding'];
                $flex['border'] = $flexible_module['border'];

            } elseif ($flexible_module['acf_fc_layout'] == 'images') {

                if (!empty($flexible_module['images'])) {
                    foreach ($flexible_module['images'] as $image) {


                        $data['image'] = wp_get_attachment_url($image['image']);
                        $data['alt'] = html_entity_decode(get_post_meta($image['image'], '_wp_attachment_image_alt', true));
                        $data['padding'] = $image['padding'];
                        $data['border'] = $image['border'];

                        $storage[] = $data;
                        unset($data);
                        unset($image);
                    }
                }

                if (!empty($storage)) {
                    $flex['images'] = $storage;
                    unset($storage);
                }

            } elseif ($flexible_module['acf_fc_layout'] == 'button') {

                $flex['type'] = $flexible_module['type'];
                $flex['label'] = html_entity_decode($flexible_module['label']);

                if ($flexible_module['type'] == 'download') {
                    $url = $flexible_module['file'];
                    $external = true;
                } else {
                    $url = $flexible_module['url'];
                    $external = $flexible_module['external'];
                }

                $flex['url'] = $url;
                $flex['external'] = $external;

            } elseif ($flexible_module['acf_fc_layout'] == 'colors') {
                $flex['colors'] = $flexible_module['colors'];

            } elseif ($flexible_module['acf_fc_layout'] == 'intro') {
                $flex['title'] = html_entity_decode($flexible_module['title']);
                $flex['intro'] = html_entity_decode($flexible_module['intro']);
            }


            $modules[] = $flex;
            unset($flex);
        endforeach;
        return $modules;
    }
}
