<?php

/**
 * Theme Settings: the theme's global, site-wide settings for the storefront's
 * header and footer (admin menu "Theme Settings").
 *
 * One ACF options page with one field group and a tab per area: Branding,
 * Announcement, Social Profiles, Header, Login (email link login, see
 * inc/email-login.php) and Footer. Values are stored as site options (not on
 * any page), so they apply to every page of the storefront.
 *
 * Exposed to GraphQL as `themeSettings { themeOptions { … } }`. Every field is
 * optional: the storefront falls back to the design's defaults when a field
 * is empty, and to "on" for switches that were never saved.
 *
 * Field keys and names are permanent: ACF stores values by them, so renaming
 * one loses its saved content.
 *
 * @package WordPress
 * @subpackage Lekhoni
 * @since Lekhoni 1.0
 */

const LEKHONI_SETTINGS_SLUG = 'lekhoni-theme-settings';

/**
 * Social networks the storefront has icons for (app/components/Icons.tsx).
 *
 * @return array<string, string>
 */
function lekhoni_social_choices()
{
    return array(
        'facebook'  => 'Facebook',
        'x'         => 'X (Twitter)',
        'instagram' => 'Instagram',
        'youtube'   => 'YouTube',
        'linkedin'  => 'LinkedIn',
        'rss'       => 'RSS feed',
    );
}

/**
 * Admin-only tab that groups the fields after it (not in GraphQL).
 *
 * @param string $key   Unique field key.
 * @param string $label Tab label.
 * @return array
 */
function lekhoni_settings_tab($key, $label)
{
    return array(
        'key'             => $key,
        'label'           => $label,
        'name'            => '',
        'type'            => 'tab',
        'placement'       => 'left',
        'endpoint'        => 0,
        'show_in_graphql' => 0,
    );
}

/**
 * On/off switch, on by default.
 *
 * @param string $key   Unique field key.
 * @param string $name  Field name.
 * @param string $label Admin label.
 * @param array  $extra Extra settings.
 * @return array
 */
function lekhoni_settings_switch($key, $name, $label, $extra = array())
{
    return lekhoni_acf_field(
        $key,
        $name,
        $label,
        'true_false',
        array_merge(array('ui' => 1, 'default_value' => 1), $extra)
    );
}

/**
 * Registers the "Theme Settings" options page.
 *
 * @return void
 */
function lekhoni_settings_register_page()
{
    if (! function_exists('acf_add_options_page')) {
        return;
    }

    acf_add_options_page(
        array(
            'page_title'        => 'Theme Settings',
            'menu_title'        => 'Theme Settings',
            'menu_slug'         => LEKHONI_SETTINGS_SLUG,
            'capability'        => 'edit_theme_options',
            'position'          => 61,
            'icon_url'          => 'dashicons-admin-appearance',
            'redirect'          => false,
            'autoload'          => true,
            'update_button'     => 'Save Settings',
            'updated_message'   => 'Settings saved. The storefront updates on the next visit.',
            'show_in_graphql'   => true,
            'graphql_type_name' => 'ThemeSettings',
        )
    );
}
add_action('acf/init', 'lekhoni_settings_register_page');

add_action(
    'acf/include_fields',
    function () {
        if (! function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group(
            array(
                'key'        => 'group_lk_theme_settings',
                'title'      => 'Header & Footer',
                'fields'     => array(

                    // ------------------------------------------------------------ Branding
                    lekhoni_settings_tab('field_lk_ts_tab_branding', 'Branding'),
                    lekhoni_acf_field(
                        'field_lk_ts_branding',
                        'branding',
                        'Logo',
                        'group',
                        array(
                            'instructions' => 'Used in the header and footer. Without a logo image the Site Title (Settings → General) is shown as the text logo "লেখনী." The browser tab icon is the Site Icon (Settings → General).',
                            'layout'       => 'block',
                            'sub_fields'   => array(
                                lekhoni_acf_field('field_lk_ts_logo', 'logo', 'Logo', 'image', array('instructions' => 'SVG or PNG with a transparent background.', 'return_format' => 'array', 'preview_size' => 'medium', 'library' => 'all', 'wrapper' => array('width' => '40'))),
                                lekhoni_acf_field('field_lk_ts_logo_dark', 'logo_dark', 'Logo for Dark Mode', 'image', array('instructions' => 'Optional. A light version shown in dark mode.', 'return_format' => 'array', 'preview_size' => 'medium', 'library' => 'all', 'wrapper' => array('width' => '40'))),
                                lekhoni_acf_field('field_lk_ts_logo_width', 'logo_width', 'Logo Width (px)', 'number', array('instructions' => 'Header width on large screens.', 'placeholder' => '160', 'min' => 40, 'max' => 400, 'step' => 1, 'wrapper' => array('width' => '20'))),
                            ),
                        )
                    ),

                    // ------------------------------------------------------------ Announcement bar
                    lekhoni_settings_tab('field_lk_ts_tab_announcement', 'Announcement Bar'),
                    lekhoni_acf_field(
                        'field_lk_ts_announcement',
                        'announcement',
                        'Announcement Bar',
                        'group',
                        array(
                            'instructions' => 'The coloured strip above the header. When the text or link changes, it shows again for visitors who closed the old one.',
                            'layout'       => 'block',
                            'sub_fields'   => array(
                                lekhoni_settings_switch('field_lk_ts_ann_enabled', 'enabled', 'Show the Bar', array('wrapper' => array('width' => '25'))),
                                lekhoni_settings_switch('field_lk_ts_ann_dismissible', 'dismissible', 'Visitors Can Close It', array('wrapper' => array('width' => '25'))),
                                lekhoni_acf_field('field_lk_ts_ann_icon', 'icon', 'Icon', 'select', array('choices' => lekhoni_icon_choices(), 'default_value' => 'truck', 'allow_null' => 1, 'ui' => 1, 'return_format' => 'value', 'wrapper' => array('width' => '50'))),
                                lekhoni_acf_field('field_lk_ts_ann_text', 'text', 'Text', 'text', array('placeholder' => '৳১,০০০-এর বেশি অর্ডারে ফ্রি ডেলিভারি')),
                                lekhoni_acf_field('field_lk_ts_ann_link_label', 'link_label', 'Link Text', 'text', array('placeholder' => 'আজই অর্ডার করুন', 'wrapper' => array('width' => '50'))),
                                lekhoni_acf_field('field_lk_ts_ann_link_url', 'link_url', 'Link URL', 'text', array('instructions' => 'A storefront path such as /shop, or a full https:// address.', 'placeholder' => '/shop', 'wrapper' => array('width' => '50'))),
                                lekhoni_acf_field('field_lk_ts_ann_starts', 'starts_at', 'Show From', 'date_time_picker', array('instructions' => 'Optional. Site timezone.', 'display_format' => 'd/m/Y g:i a', 'return_format' => 'Y-m-d H:i:s', 'first_day' => 6, 'wrapper' => array('width' => '50'))),
                                lekhoni_acf_field('field_lk_ts_ann_ends', 'ends_at', 'Show Until', 'date_time_picker', array('instructions' => 'Optional. Site timezone.', 'display_format' => 'd/m/Y g:i a', 'return_format' => 'Y-m-d H:i:s', 'first_day' => 6, 'wrapper' => array('width' => '50'))),
                            ),
                        )
                    ),

                    // ------------------------------------------------------------ Social profiles
                    lekhoni_settings_tab('field_lk_ts_tab_social', 'Social Profiles'),
                    lekhoni_acf_field(
                        'field_lk_ts_social',
                        'social_links',
                        'Social Profiles',
                        'repeater',
                        array(
                            'instructions' => 'Shown in the header, the footer and the blog sidebar, in this order. Leave empty to hide the icons.',
                            'layout'       => 'table',
                            'button_label' => 'Add Profile',
                            'max'          => 6,
                            'sub_fields'   => array(
                                lekhoni_acf_field('field_lk_ts_social_network', 'network', 'Network', 'select', array('required' => 1, 'choices' => lekhoni_social_choices(), 'return_format' => 'value', 'wrapper' => array('width' => '30'), 'parent_repeater' => 'field_lk_ts_social')),
                                lekhoni_acf_field('field_lk_ts_social_url', 'url', 'Profile URL', 'url', array('required' => 1, 'placeholder' => 'https://facebook.com/lekhoni', 'wrapper' => array('width' => '70'), 'parent_repeater' => 'field_lk_ts_social')),
                            ),
                        )
                    ),

                    // ------------------------------------------------------------ Header
                    lekhoni_settings_tab('field_lk_ts_tab_header', 'Header'),
                    lekhoni_acf_field(
                        'field_lk_ts_header',
                        'header',
                        'Header',
                        'group',
                        array(
                            'instructions' => 'The menu is managed under Appearance → Menus (location "Primary menu"). The cart button is always shown.',
                            'layout'       => 'block',
                            'sub_fields'   => array(
                                lekhoni_settings_switch('field_lk_ts_header_social', 'show_social', 'Social Icons', array('instructions' => 'Left side, large screens.', 'wrapper' => array('width' => '20'))),
                                lekhoni_settings_switch('field_lk_ts_header_search', 'show_search', 'Search', array('wrapper' => array('width' => '20'))),
                                lekhoni_settings_switch('field_lk_ts_header_wishlist', 'show_wishlist', 'Wishlist', array('wrapper' => array('width' => '20'))),
                                lekhoni_settings_switch('field_lk_ts_header_account', 'show_account', 'My Account', array('wrapper' => array('width' => '20'))),
                                lekhoni_settings_switch('field_lk_ts_header_theme', 'show_theme_toggle', 'Dark Mode Toggle', array('wrapper' => array('width' => '20'))),
                            ),
                        )
                    ),

                    // ------------------------------------------------------------ Login
                    lekhoni_settings_tab('field_lk_ts_tab_login', 'Login'),
                    lekhoni_acf_field(
                        'field_lk_ts_login',
                        'login',
                        'Login',
                        'group',
                        array(
                            'instructions' => 'Email link login: customers enter their email on the storefront\'s login page and get a one-time link that logs them in without a password. Customer accounts only; admins and shop managers always log in with their password.',
                            'layout'       => 'block',
                            'sub_fields'   => array(
                                lekhoni_acf_field('field_lk_ts_login_email_link', 'email_link', 'Email Link Login', 'true_false', array('ui' => 1, 'default_value' => 0, 'message' => 'Allow customers to log in with a link sent by email', 'wrapper' => array('width' => '50'))),
                                lekhoni_acf_field('field_lk_ts_login_lifetime', 'link_lifetime', 'Link Valid For (minutes)', 'number', array('instructions' => 'Each link works once. Requesting a new link cancels the previous one.', 'placeholder' => '15', 'min' => 5, 'max' => 60, 'step' => 1, 'wrapper' => array('width' => '50'))),
                                lekhoni_acf_field('field_lk_ts_login_subject', 'email_subject', 'Email Subject', 'text', array('instructions' => '{site} becomes the Site Title.', 'placeholder' => '{site}-এ লগইন করার লিংক')),
                                lekhoni_acf_field('field_lk_ts_login_intro', 'email_intro', 'Email Text', 'textarea', array('instructions' => 'Shown above the login button in the email.', 'rows' => 2, 'new_lines' => '', 'placeholder' => 'আপনার অ্যাকাউন্টে লগইন করতে নিচের বোতামে চাপ দিন।')),
                            ),
                        )
                    ),

                    // ------------------------------------------------------------ Footer
                    lekhoni_settings_tab('field_lk_ts_tab_footer', 'Footer'),
                    lekhoni_acf_field(
                        'field_lk_ts_footer',
                        'footer',
                        'Footer',
                        'group',
                        array(
                            'instructions' => 'Four columns on large screens. Switch a column off to hide it.',
                            'layout'       => 'block',
                            'sub_fields'   => array(
                                lekhoni_acf_field(
                                    'field_lk_ts_footer_about',
                                    'about',
                                    'Column 1: About',
                                    'group',
                                    array(
                                        'layout'     => 'row',
                                        'sub_fields' => array(
                                            lekhoni_settings_switch('field_lk_ts_footer_about_on', 'enabled', 'Show'),
                                            lekhoni_acf_field('field_lk_ts_footer_about_title', 'title', 'Title', 'text', array('placeholder' => 'লেখনী সম্পর্কে')),
                                            lekhoni_acf_field('field_lk_ts_footer_about_text', 'text', 'Text', 'textarea', array('rows' => 3, 'new_lines' => '', 'placeholder' => 'হেডলেস ওয়ার্ডপ্রেস, GraphQL আর Next.js নিয়ে বাংলায় লেখা, সঙ্গে লেখালেখির পছন্দের জিনিসপত্রের ছোট্ট দোকান।')),
                                            lekhoni_settings_switch('field_lk_ts_footer_about_social', 'show_social', 'Social Icons', array('instructions' => 'From the Social Profiles tab.')),
                                        ),
                                    )
                                ),
                                lekhoni_acf_field(
                                    'field_lk_ts_footer_posts',
                                    'recent_posts',
                                    'Column 2: Recent Posts',
                                    'group',
                                    array(
                                        'layout'     => 'row',
                                        'sub_fields' => array(
                                            lekhoni_settings_switch('field_lk_ts_footer_posts_on', 'enabled', 'Show', array('instructions' => 'Hidden automatically when there are no posts.')),
                                            lekhoni_acf_field('field_lk_ts_footer_posts_title', 'title', 'Title', 'text', array('placeholder' => 'সাম্প্রতিক লেখা')),
                                            lekhoni_acf_field('field_lk_ts_footer_posts_count', 'count', 'Number of Posts', 'number', array('placeholder' => '4', 'min' => 1, 'max' => 8, 'step' => 1)),
                                        ),
                                    )
                                ),
                                lekhoni_acf_field(
                                    'field_lk_ts_footer_topics',
                                    'topics',
                                    'Column 3: Topics',
                                    'group',
                                    array(
                                        'layout'     => 'row',
                                        'sub_fields' => array(
                                            lekhoni_settings_switch('field_lk_ts_footer_topics_on', 'enabled', 'Show', array('instructions' => 'Hidden automatically when there is nothing to list.')),
                                            lekhoni_acf_field('field_lk_ts_footer_topics_title', 'title', 'Title', 'text', array('placeholder' => 'বিষয়')),
                                            lekhoni_acf_field('field_lk_ts_footer_topics_source', 'source', 'List', 'select', array('choices' => array('categories' => 'Post categories', 'tags' => 'Post tags', 'product_categories' => 'Product categories'), 'default_value' => 'categories', 'return_format' => 'value')),
                                            lekhoni_acf_field('field_lk_ts_footer_topics_count', 'count', 'Number of Topics', 'number', array('instructions' => 'The ones with the most posts or products.', 'placeholder' => '8', 'min' => 1, 'max' => 20, 'step' => 1)),
                                        ),
                                    )
                                ),
                                lekhoni_acf_field(
                                    'field_lk_ts_footer_links',
                                    'links',
                                    'Column 4: Links',
                                    'group',
                                    array(
                                        'layout'     => 'row',
                                        'sub_fields' => array(
                                            lekhoni_settings_switch('field_lk_ts_footer_links_on', 'enabled', 'Show', array('instructions' => 'The links come from Appearance → Menus, location "Footer menu". Until a menu is assigned there, Shop, My Account, About and Contact are shown.')),
                                            lekhoni_acf_field('field_lk_ts_footer_links_title', 'title', 'Title', 'text', array('placeholder' => 'দরকারি লিংক')),
                                        ),
                                    )
                                ),
                                lekhoni_acf_field(
                                    'field_lk_ts_footer_bottom',
                                    'bottom',
                                    'Bottom Bar',
                                    'group',
                                    array(
                                        'layout'     => 'row',
                                        'sub_fields' => array(
                                            lekhoni_acf_field(
                                                'field_lk_ts_footer_badges',
                                                'payment_badges',
                                                'Payment Badges',
                                                'repeater',
                                                array(
                                                    'instructions' => 'Labels under the logo, e.g. বিকাশ. Leave empty for the defaults (ক্যাশ অন ডেলিভারি, বিকাশ, নগদ, ভিসা/মাস্টারকার্ড).',
                                                    'layout'       => 'table',
                                                    'button_label' => 'Add Badge',
                                                    'max'          => 10,
                                                    'sub_fields'   => array(
                                                        lekhoni_acf_field('field_lk_ts_footer_badge_label', 'label', 'Label', 'text', array('required' => 1, 'parent_repeater' => 'field_lk_ts_footer_badges')),
                                                    ),
                                                )
                                            ),
                                            lekhoni_settings_switch('field_lk_ts_footer_badges_on', 'show_payment_badges', 'Show Payment Badges'),
                                            lekhoni_acf_field('field_lk_ts_footer_copyright', 'copyright', 'Copyright Text', 'text', array('instructions' => '{year} becomes the current year and {site} the Site Title.', 'placeholder' => '© {year} {site} · WordPress ও Next.js দিয়ে তৈরি')),
                                        ),
                                    )
                                ),
                            ),
                        )
                    ),
                ),
                'location'   => array(
                    array(
                        array(
                            'param'    => 'options_page',
                            'operator' => '==',
                            'value'    => LEKHONI_SETTINGS_SLUG,
                        ),
                    ),
                ),
                'menu_order'            => 0,
                'position'              => 'normal',
                'style'                 => 'seamless',
                'label_placement'       => 'top',
                'instruction_placement' => 'label',
                'active'                => true,
                'show_in_rest'          => 0,
                'show_in_graphql'       => 1,
                'graphql_field_name'    => 'themeOptions',
                'map_graphql_types_from_location_rules' => 1,
            )
        );
    }
);
