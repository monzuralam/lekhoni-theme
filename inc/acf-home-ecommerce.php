<?php

/**
 * ACF fields for the "Home – E-commerce" page template
 * (templates/template-home-ecommerce.php): a multi-department store home,
 * one tab per section in homepage order. Design: lekhoni/design/home-ecommerce.html.
 *
 * Exposed to GraphQL as `page { homeEcommerce { … } }`. Every section has an
 * on/off switch (never saved = on) and falls back to automatic content
 * (top-level categories, best sellers, newest, on sale) when left empty.
 *
 * Field keys and names are permanent: ACF stores values by them, so renaming
 * one loses its saved content.
 *
 * @package WordPress
 * @subpackage Lekhoni
 * @since Lekhoni 1.0
 */

/**
 * Background tints shared by the e-commerce and cosmetics homes.
 *
 * @return array<string, string>
 */
function lekhoni_tone_choices()
{
    return array(
        'accent'  => 'Accent tint',
        'sale'    => 'Pink tint',
        'success' => 'Green tint',
        'star'    => 'Yellow tint',
    );
}

/**
 * Icons for the new home templates: the shared list plus the shop icons
 * (app/components/Icons.tsx).
 *
 * @return array<string, string>
 */
function lekhoni_shop_icon_choices()
{
    return array_merge(
        lekhoni_icon_choices(),
        array(
            'smartphone'  => 'Smartphone',
            'shirt'       => 'Shirt',
            'home'        => 'Home',
            'sparkles'    => 'Sparkles',
            'droplet'     => 'Droplet',
            'leaf'        => 'Leaf',
            'activity'    => 'Activity (sports)',
            'smile'       => 'Smile (kids)',
            'basket'      => 'Basket (grocery)',
            'bag'         => 'Shopping bag',
            'credit-card' => 'Credit card',
            'percent'     => 'Percent',
            'award'       => 'Award',
            'flask'       => 'Flask',
            'package'     => 'Package',
            'sun'         => 'Sun',
            'moon'        => 'Moon',
            'instagram'   => 'Instagram',
        )
    );
}

/**
 * Product category picker (taxonomy field) returned as a GraphQL connection.
 *
 * @param string $key          Unique field key.
 * @param string $name         Field name.
 * @param string $label        Admin label.
 * @param string $instructions Admin help text.
 * @param bool   $multiple     Several categories (in order) or one.
 * @param array  $extra        Extra settings.
 * @return array
 */
function lekhoni_home_category_field($key, $name, $label, $instructions, $multiple = true, $extra = array())
{
    return lekhoni_acf_field(
        $key,
        $name,
        $label,
        'taxonomy',
        array_merge(
            array(
                'instructions'            => $instructions,
                'taxonomy'                => 'product_cat',
                'add_term'                => 0,
                'save_terms'              => 0,
                'load_terms'              => 0,
                'return_format'           => 'id',
                'field_type'              => $multiple ? 'multi_select' : 'select',
                'allow_null'              => 1,
                'multiple'                => 0,
                'graphql_connection_type' => $multiple ? 'one_to_many' : 'one_to_one',
            ),
            $extra
        )
    );
}

/**
 * "Show this section" switch (on by default).
 *
 * @param string $key Unique field key.
 * @return array
 */
function lekhoni_home_enabled($key)
{
    return lekhoni_settings_switch($key, 'enabled', 'Show This Section', array('wrapper' => array('width' => '25')));
}

/**
 * The "Home – E-commerce" field group.
 *
 * @return void
 */
function lekhoni_register_home_ecommerce_fields()
{
    if (! function_exists('acf_add_local_field_group')) {
        return;
    }

    $tone = function ($key, $parent, $width = '20') {
        return lekhoni_acf_field($key, 'tone', 'Background', 'select', array('choices' => lekhoni_tone_choices(), 'default_value' => 'accent', 'return_format' => 'value', 'wrapper' => array('width' => $width), 'parent_repeater' => $parent));
    };

    $fields = array(

        // ------------------------------------------------------------ Hero
        lekhoni_home_tab('field_lk_he_tab_hero', 'Hero'),
        lekhoni_acf_field(
            'field_lk_he_hero',
            'hero_section',
            'Hero',
            'group',
            array(
                'layout'     => 'block',
                'sub_fields' => array(
                    lekhoni_settings_switch('field_lk_he_hero_menu', 'show_departments', 'Show Department Menu', array('instructions' => 'The list on the left (large screens). Uses the Departments tab\'s categories.', 'wrapper' => array('width' => '30'))),
                    lekhoni_acf_field('field_lk_he_hero_menu_title', 'menu_title', 'Menu Title', 'text', array('placeholder' => 'সব ডিপার্টমেন্ট', 'wrapper' => array('width' => '70'))),
                    lekhoni_acf_field(
                        'field_lk_he_hero_slides',
                        'slides',
                        'Slides',
                        'repeater',
                        array(
                            'instructions' => 'At least one slide shows the hero.',
                            'layout'       => 'block',
                            'button_label' => 'Add Slide',
                            'sub_fields'   => array(
                                lekhoni_acf_field('field_lk_he_slide_eyebrow', 'eyebrow', 'Badge', 'text', array('placeholder' => 'মেগা ইলেকট্রনিক্স সেল', 'wrapper' => array('width' => '30'), 'parent_repeater' => 'field_lk_he_hero_slides')),
                                lekhoni_acf_field('field_lk_he_slide_title', 'title', 'Title', 'text', array('required' => 1, 'wrapper' => array('width' => '50'), 'parent_repeater' => 'field_lk_he_hero_slides')),
                                $tone('field_lk_he_slide_tone', 'field_lk_he_hero_slides'),
                                lekhoni_acf_field('field_lk_he_slide_text', 'text', 'Text', 'textarea', array('rows' => 2, 'new_lines' => '', 'parent_repeater' => 'field_lk_he_hero_slides')),
                                lekhoni_acf_field('field_lk_he_slide_link', 'link', 'Button', 'link', array('instructions' => 'Empty text shows "এখনই কিনুন".', 'return_format' => 'array', 'wrapper' => array('width' => '50'), 'parent_repeater' => 'field_lk_he_hero_slides')),
                                lekhoni_acf_field('field_lk_he_slide_image', 'image', 'Image', 'image', array('required' => 1, 'instructions' => 'About 900 x 700.', 'return_format' => 'array', 'preview_size' => 'thumbnail', 'wrapper' => array('width' => '50'), 'parent_repeater' => 'field_lk_he_hero_slides')),
                            ),
                        )
                    ),
                    lekhoni_acf_field(
                        'field_lk_he_hero_banners',
                        'side_banners',
                        'Side Banners',
                        'repeater',
                        array(
                            'instructions' => 'Up to two small banners on the right (extra-large screens).',
                            'layout'       => 'table',
                            'max'          => 2,
                            'button_label' => 'Add Banner',
                            'sub_fields'   => array(
                                lekhoni_acf_field('field_lk_he_side_eyebrow', 'eyebrow', 'Badge', 'text', array('placeholder' => '−২৫% ছাড়', 'parent_repeater' => 'field_lk_he_hero_banners')),
                                lekhoni_acf_field('field_lk_he_side_title', 'title', 'Title', 'text', array('required' => 1, 'parent_repeater' => 'field_lk_he_hero_banners')),
                                lekhoni_acf_field('field_lk_he_side_image', 'image', 'Image', 'image', array('return_format' => 'array', 'preview_size' => 'thumbnail', 'parent_repeater' => 'field_lk_he_hero_banners')),
                                lekhoni_acf_field('field_lk_he_side_link', 'link', 'Link', 'url', array('parent_repeater' => 'field_lk_he_hero_banners')),
                                $tone('field_lk_he_side_tone', 'field_lk_he_hero_banners', ''),
                            ),
                        )
                    ),
                ),
            )
        ),

        // ------------------------------------------------------------ Services
        lekhoni_home_tab('field_lk_he_tab_services', 'Services'),
        lekhoni_acf_field(
            'field_lk_he_services',
            'services_section',
            'Service Strip',
            'group',
            array(
                'layout'     => 'block',
                'sub_fields' => array(
                    lekhoni_home_enabled('field_lk_he_services_on'),
                    lekhoni_acf_field(
                        'field_lk_he_services_items',
                        'items',
                        'Items',
                        'repeater',
                        array(
                            'instructions' => 'Up to four. Empty shows delivery, returns, genuine products and payment.',
                            'layout'       => 'table',
                            'max'          => 4,
                            'button_label' => 'Add Item',
                            'sub_fields'   => array(
                                lekhoni_acf_field('field_lk_he_service_icon', 'icon', 'Icon', 'select', array('choices' => lekhoni_shop_icon_choices(), 'default_value' => 'truck', 'return_format' => 'value', 'parent_repeater' => 'field_lk_he_services_items')),
                                lekhoni_acf_field('field_lk_he_service_title', 'title', 'Title', 'text', array('required' => 1, 'parent_repeater' => 'field_lk_he_services_items')),
                                lekhoni_acf_field('field_lk_he_service_text', 'text', 'Text', 'text', array('parent_repeater' => 'field_lk_he_services_items')),
                            ),
                        )
                    ),
                ),
            )
        ),

        // ------------------------------------------------------------ Departments
        lekhoni_home_tab('field_lk_he_tab_departments', 'Departments'),
        lekhoni_acf_field(
            'field_lk_he_departments',
            'departments_section',
            'Departments',
            'group',
            array(
                'instructions' => 'Each category\'s icon is set on the category itself (Products → Categories → Icon).',
                'layout'       => 'block',
                'sub_fields'   => array(
                    lekhoni_home_enabled('field_lk_he_departments_on'),
                    lekhoni_acf_field('field_lk_he_departments_title', 'title', 'Section Title', 'text', array('placeholder' => 'ডিপার্টমেন্ট', 'wrapper' => array('width' => '75'))),
                    lekhoni_home_category_field('field_lk_he_departments_cats', 'categories', 'Categories', 'In display order (8 fit one row). Leave empty to show the top-level categories. Also used by the hero\'s department menu.'),
                ),
            )
        ),

        // ------------------------------------------------------------ Deals
        lekhoni_home_tab('field_lk_he_tab_deals', 'Deals'),
        lekhoni_acf_field(
            'field_lk_he_deals',
            'deals_section',
            'Deals of the Day',
            'group',
            array(
                'layout'     => 'block',
                'sub_fields' => array(
                    lekhoni_home_enabled('field_lk_he_deals_on'),
                    lekhoni_acf_field('field_lk_he_deals_title', 'title', 'Section Title', 'text', array('placeholder' => 'আজকের ডিল', 'wrapper' => array('width' => '35'))),
                    lekhoni_acf_field('field_lk_he_deals_ends', 'ends_at', 'Ends At', 'date_time_picker', array('instructions' => 'Countdown end (site timezone). Empty uses the earliest sale end date of the products.', 'display_format' => 'd/m/Y g:i a', 'return_format' => 'Y-m-d H:i:s', 'first_day' => 6, 'wrapper' => array('width' => '40'))),
                    lekhoni_home_products_field('field_lk_he_deals_products', 'products', 'Products', 12, 'In display order. Leave empty to show the best-selling products on sale. "Sold" bars show for products with stock management.'),
                ),
            )
        ),

        // ------------------------------------------------------------ Trending tabs
        lekhoni_home_tab('field_lk_he_tab_tabs', 'Popular Products'),
        lekhoni_acf_field(
            'field_lk_he_tabs',
            'tabs_section',
            'Popular Products Tabs',
            'group',
            array(
                'layout'     => 'block',
                'sub_fields' => array(
                    lekhoni_home_enabled('field_lk_he_tabs_on'),
                    lekhoni_acf_field('field_lk_he_tabs_title', 'title', 'Section Title', 'text', array('placeholder' => 'জনপ্রিয় পণ্য', 'wrapper' => array('width' => '75'))),
                    lekhoni_acf_field('field_lk_he_tabs_new_label', 'new_label', 'New Tab', 'text', array('placeholder' => 'নতুন এসেছে', 'wrapper' => array('width' => '33'))),
                    lekhoni_acf_field('field_lk_he_tabs_best_label', 'best_label', 'Best Sellers Tab', 'text', array('placeholder' => 'বেস্টসেলার', 'wrapper' => array('width' => '33'))),
                    lekhoni_acf_field('field_lk_he_tabs_top_label', 'top_label', 'Top Rated Tab', 'text', array('placeholder' => 'সেরা রেটিং', 'wrapper' => array('width' => '34'))),
                    lekhoni_home_products_field('field_lk_he_tabs_new', 'new_products', 'New Tab Products', 8, 'Leave empty for the newest products.'),
                    lekhoni_home_products_field('field_lk_he_tabs_best', 'best_products', 'Best Sellers Tab Products', 8, 'Leave empty for the best-selling products.'),
                    lekhoni_home_products_field('field_lk_he_tabs_top', 'top_products', 'Top Rated Tab Products', 8, 'Leave empty for the highest-rated products.'),
                ),
            )
        ),

        // ------------------------------------------------------------ Wide coupon banner
        lekhoni_home_tab('field_lk_he_tab_wide', 'Coupon Banner'),
        lekhoni_acf_field(
            'field_lk_he_wide',
            'wide_banner',
            'Wide Coupon Banner',
            'group',
            array(
                'instructions' => 'Hidden while the title is empty. Create the coupon itself under Marketing → Coupons.',
                'layout'       => 'block',
                'sub_fields'   => array(
                    lekhoni_home_enabled('field_lk_he_wide_on'),
                    lekhoni_acf_field('field_lk_he_wide_eyebrow', 'eyebrow', 'Badge', 'text', array('placeholder' => '১১.১১ মেগা সেল', 'wrapper' => array('width' => '35'))),
                    lekhoni_acf_field('field_lk_he_wide_code', 'coupon_code', 'Coupon Code', 'text', array('placeholder' => 'MEGA15', 'wrapper' => array('width' => '40'))),
                    lekhoni_acf_field('field_lk_he_wide_title', 'title', 'Title', 'text', array('placeholder' => 'সব অর্ডারে অতিরিক্ত ১৫% ছাড়')),
                    lekhoni_acf_field('field_lk_he_wide_text', 'text', 'Text', 'text'),
                    lekhoni_acf_field('field_lk_he_wide_link', 'link', 'Button', 'link', array('instructions' => 'Empty text shows "কেনাকাটা শুরু করুন".', 'return_format' => 'array')),
                ),
            )
        ),

        // ------------------------------------------------------------ Department rows
        lekhoni_home_tab('field_lk_he_tab_rows', 'Category Rows'),
        lekhoni_acf_field(
            'field_lk_he_rows',
            'category_rows',
            'Category Rows',
            'repeater',
            array(
                'instructions' => 'One row per category: a banner beside a product carousel. Leave empty to hide.',
                'layout'       => 'block',
                'button_label' => 'Add Row',
                'sub_fields'   => array(
                    lekhoni_home_category_field('field_lk_he_row_cat', 'category', 'Category', 'The "see all" link and, when no products are picked, the products (best sellers first).', false, array('required' => 1, 'wrapper' => array('width' => '40'), 'parent_repeater' => 'field_lk_he_rows')),
                    lekhoni_acf_field('field_lk_he_row_title', 'title', 'Row Title', 'text', array('instructions' => 'Empty uses the category name.', 'wrapper' => array('width' => '30'), 'parent_repeater' => 'field_lk_he_rows')),
                    lekhoni_acf_field('field_lk_he_row_icon', 'icon', 'Icon', 'select', array('choices' => lekhoni_shop_icon_choices(), 'allow_null' => 1, 'instructions' => 'Empty uses the category icon.', 'return_format' => 'value', 'wrapper' => array('width' => '30'), 'parent_repeater' => 'field_lk_he_rows')),
                    lekhoni_acf_field('field_lk_he_row_eyebrow', 'banner_eyebrow', 'Banner Badge', 'text', array('placeholder' => 'গ্যাজেট উইক', 'wrapper' => array('width' => '30'), 'parent_repeater' => 'field_lk_he_rows')),
                    lekhoni_acf_field('field_lk_he_row_btitle', 'banner_title', 'Banner Title', 'text', array('instructions' => 'Empty hides the banner.', 'wrapper' => array('width' => '50'), 'parent_repeater' => 'field_lk_he_rows')),
                    $tone('field_lk_he_row_tone', 'field_lk_he_rows'),
                    lekhoni_acf_field('field_lk_he_row_image', 'banner_image', 'Banner Image', 'image', array('instructions' => 'Portrait, about 600 x 800.', 'return_format' => 'array', 'preview_size' => 'thumbnail', 'wrapper' => array('width' => '30'), 'parent_repeater' => 'field_lk_he_rows')),
                    lekhoni_home_products_field('field_lk_he_row_products', 'products', 'Products', 10, 'Optional, in display order.', array('wrapper' => array('width' => '70'), 'parent_repeater' => 'field_lk_he_rows')),
                ),
            )
        ),

        // ------------------------------------------------------------ Promo trio
        lekhoni_home_tab('field_lk_he_tab_promo', 'Promo Banners'),
        lekhoni_acf_field(
            'field_lk_he_promo',
            'promo_banners',
            'Promo Banners',
            'repeater',
            array(
                'instructions' => 'Three per row on large screens. Leave empty to hide.',
                'layout'       => 'table',
                'max'          => 6,
                'button_label' => 'Add Banner',
                'sub_fields'   => array(
                    lekhoni_acf_field('field_lk_he_promo_eyebrow', 'eyebrow', 'Badge', 'text', array('placeholder' => 'কম্বো অফার', 'parent_repeater' => 'field_lk_he_promo')),
                    lekhoni_acf_field('field_lk_he_promo_title', 'title', 'Title', 'text', array('required' => 1, 'parent_repeater' => 'field_lk_he_promo')),
                    lekhoni_acf_field('field_lk_he_promo_image', 'image', 'Image', 'image', array('return_format' => 'array', 'preview_size' => 'thumbnail', 'parent_repeater' => 'field_lk_he_promo')),
                    lekhoni_acf_field('field_lk_he_promo_link', 'link', 'Link', 'url', array('parent_repeater' => 'field_lk_he_promo')),
                    $tone('field_lk_he_promo_tone', 'field_lk_he_promo', ''),
                ),
            )
        ),

        // ------------------------------------------------------------ Product lists
        lekhoni_home_tab('field_lk_he_tab_lists', 'Product Lists'),
        lekhoni_acf_field(
            'field_lk_he_lists',
            'lists_section',
            'Product Lists',
            'group',
            array(
                'instructions' => 'Three short automatic lists: highest rated, newest and on sale.',
                'layout'       => 'block',
                'sub_fields'   => array(
                    lekhoni_home_enabled('field_lk_he_lists_on'),
                    lekhoni_acf_field('field_lk_he_lists_top', 'top_title', 'Top Rated Title', 'text', array('placeholder' => 'সেরা রেটিং', 'wrapper' => array('width' => '25'))),
                    lekhoni_acf_field('field_lk_he_lists_new', 'new_title', 'Newest Title', 'text', array('placeholder' => 'নতুন এসেছে', 'wrapper' => array('width' => '25'))),
                    lekhoni_acf_field('field_lk_he_lists_sale', 'sale_title', 'On Sale Title', 'text', array('placeholder' => 'অফারে আছে', 'wrapper' => array('width' => '25'))),
                ),
            )
        ),

        // ------------------------------------------------------------ Brands
        lekhoni_home_tab('field_lk_he_tab_brands', 'Brands'),
        lekhoni_acf_field(
            'field_lk_he_brands',
            'brands_section',
            'Brands',
            'group',
            array(
                'layout'     => 'block',
                'sub_fields' => array(
                    lekhoni_home_enabled('field_lk_he_brands_on'),
                    lekhoni_acf_field('field_lk_he_brands_title', 'title', 'Section Title', 'text', array('placeholder' => 'জনপ্রিয় ব্র্যান্ড', 'wrapper' => array('width' => '75'))),
                    lekhoni_acf_field(
                        'field_lk_he_brands_items',
                        'brands',
                        'Brands',
                        'repeater',
                        array(
                            'instructions' => 'Leave empty to show the WooCommerce product brands (Products → Brands) that have products.',
                            'layout'       => 'table',
                            'button_label' => 'Add Brand',
                            'sub_fields'   => array(
                                lekhoni_acf_field('field_lk_he_brand_name', 'name', 'Name', 'text', array('required' => 1, 'parent_repeater' => 'field_lk_he_brands_items')),
                                lekhoni_acf_field('field_lk_he_brand_logo', 'logo', 'Logo', 'image', array('return_format' => 'array', 'preview_size' => 'thumbnail', 'parent_repeater' => 'field_lk_he_brands_items')),
                                lekhoni_acf_field('field_lk_he_brand_link', 'link', 'Link', 'url', array('parent_repeater' => 'field_lk_he_brands_items')),
                            ),
                        )
                    ),
                ),
            )
        ),

        // ------------------------------------------------------------ Newsletter
        lekhoni_home_tab('field_lk_he_tab_newsletter', 'Newsletter'),
        lekhoni_settings_switch('field_lk_he_recent_on', 'show_recently_viewed', 'Show "Recently Viewed"', array('instructions' => 'The visitor\'s recently viewed products, above the newsletter.')),
        lekhoni_acf_field(
            'field_lk_he_subscribe',
            'subscribe_section',
            'Newsletter Band',
            'group',
            array(
                'instructions' => 'Sign-ups are stored under Subscribers in the admin menu.',
                'layout'       => 'block',
                'sub_fields'   => array(
                    lekhoni_home_enabled('field_lk_he_subscribe_on'),
                    lekhoni_acf_field('field_lk_he_subscribe_title', 'title', 'Title', 'text', array('placeholder' => 'অফারের খবর সবার আগে', 'wrapper' => array('width' => '75'))),
                    lekhoni_acf_field('field_lk_he_subscribe_text', 'text', 'Text', 'textarea', array('rows' => 2, 'new_lines' => '')),
                    lekhoni_acf_field('field_lk_he_subscribe_success', 'success_message', 'Success Message', 'text', array('placeholder' => 'সাবস্ক্রাইব করার জন্য ধন্যবাদ!', 'wrapper' => array('width' => '50'))),
                ),
            )
        ),
    );

    // Stored names get a "he_" prefix (sub fields are saved under their
    // parent's name), so this group never reads another home template's
    // values on the same page. GraphQL names keep the unprefixed form.
    $fields = array_map(
        function ($field) {
            if ('tab' !== $field['type']) {
                $field['name'] = 'he_' . $field['name'];
            }
            return $field;
        },
        $fields
    );

    acf_add_local_field_group(
        array(
            'key'                   => 'group_lk_home_ecommerce',
            'title'                 => 'Home – E-commerce',
            'fields'                => $fields,
            'location'              => array(
                array(
                    array(
                        'param'    => 'page_template',
                        'operator' => '==',
                        'value'    => 'templates/template-home-ecommerce.php',
                    ),
                ),
            ),
            'menu_order'            => 0,
            'position'              => 'normal',
            'style'                 => 'default',
            'label_placement'       => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen'        => array('the_content'),
            'active'                => true,
            'show_in_rest'          => 0,
            'show_in_graphql'       => 1,
            'graphql_field_name'    => 'homeEcommerce',
            'map_graphql_types_from_location_rules' => 0,
            'graphql_types'         => array('Page'),
        )
    );
}
add_action('acf/include_fields', 'lekhoni_register_home_ecommerce_fields');
