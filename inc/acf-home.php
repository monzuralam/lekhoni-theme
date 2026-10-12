<?php

/**
 * ACF fields for the home page: one "Home Shop" field group, shown on pages
 * using the "Home Shop" template (templates/template-home.php), with one tab
 * per section in homepage order.
 *
 * Exposed to GraphQL as `page { homeShop { … } }`. Every section has
 * fallbacks on the storefront and is hidden when it has nothing to show.
 *
 * Field keys and names are permanent: ACF stores values by them, so renaming
 * one loses its saved content.
 *
 * @package WordPress
 * @subpackage Lekhoni
 * @since Lekhoni 1.0
 */

/**
 * Admin-only tab that groups the fields after it (not in GraphQL).
 *
 * @param string $key   Unique field key.
 * @param string $label Tab label.
 * @return array
 */
function lekhoni_home_tab($key, $label)
{
    return array(
        'key'             => $key,
        'label'           => $label,
        'name'            => '',
        'type'            => 'tab',
        'placement'       => 'top',
        'endpoint'        => 0,
        'show_in_graphql' => 0,
    );
}

/**
 * Relationship field for hand-picked products, returned as a GraphQL connection.
 *
 * @param string $key          Unique field key.
 * @param string $name         Field name.
 * @param string $label        Admin label.
 * @param int    $max          Maximum number of products.
 * @param string $instructions Admin help text.
 * @param array  $extra        Extra settings.
 * @return array
 */
function lekhoni_home_products_field($key, $name, $label, $max, $instructions, $extra = array())
{
    return lekhoni_acf_field(
        $key,
        $name,
        $label,
        'relationship',
        array_merge(
            array(
                'instructions'            => $instructions,
                'post_type'               => array('product'),
                'post_status'             => array('publish'),
                'filters'                 => array('search', 'taxonomy'),
                'elements'                => array('featured_image'),
                'return_format'           => 'id',
                'min'                     => '',
                'max'                     => $max,
                'graphql_connection_type' => 1 === $max ? 'one_to_one' : 'one_to_many',
            ),
            $extra
        )
    );
}

/**
 * Icons for the trust strip. Kept as its own list (a superset of
 * lekhoni_icon_choices()) so saved choices stay valid.
 *
 * @return array<string, string>
 */
function lekhoni_trust_icon_choices()
{
    return array(
        'search'       => 'Search',
        'sun'          => 'Sun',
        'moon'         => 'Moon',
        'menu'         => 'Menu',
        'close'        => 'Close',
        'chevron-down' => 'Chevron Down',
        'arrow-up'     => 'Arrow Up',
        'arrow-left'   => 'Arrow Left',
        'arrow-right'  => 'Arrow Right',
        'facebook'     => 'Facebook',
        'x'            => 'X',
        'instagram'    => 'Instagram',
        'youtube'      => 'YouTube',
        'rss'          => 'RSS',
        'copy'         => 'Copy',
        'link'         => 'Link',
        'share'        => 'Share',
        'chat'         => 'Chat',
        'linkedin'     => 'LinkedIn',
        'minus'        => 'Minus',
        'plus'         => 'Plus',
        'book'         => 'Book',
        'cart'         => 'Cart',
        'user'         => 'User',
        'heart'        => 'Heart',
        'trash'        => 'Trash',
        'check'        => 'Check',
        'eye'          => 'Eye',
        'eye-off'      => 'Eye Off',
        'lock'         => 'Lock',
        'mail'         => 'Mail',
        'truck'        => 'Truck',
        'star'         => 'Star',
        'shield-check' => 'Shield Check',
        'rotate-ccw'   => 'Rotate CCW',
        'clock'        => 'Clock',
        'tag'          => 'Tag',
        'zap'          => 'Zap',
        'gift'         => 'Gift',
        'headset'      => 'Headset',
        'quote'        => 'Quote',
        'pause'        => 'Pause',
        'play'         => 'Play',
        'phone'        => 'Phone',
        'map-pin'      => 'Map Pin',
        'grid'         => 'Grid',
        'list'         => 'List',
        'filter'       => 'Filter',
        'calendar'     => 'Calendar',
    );
}

add_action(
    'acf/include_fields',
    function () {
        if (! function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group(
            array(
                'key'        => 'group_6aca0d1273713',
                'title'      => 'Home Shop',
                'fields'     => array(

                    // ------------------------------------------------------------ Hero slider
                    lekhoni_home_tab('field_lk_home_tab_slider', 'Slider'),
                    lekhoni_acf_field(
                        'field_6aca0d27b4f62',
                        'slider_section',
                        'Slider Section',
                        'group',
                        array(
                            'layout'     => 'block',
                            'sub_fields' => array(
                                lekhoni_acf_field(
                                    'field_6aca15d473452',
                                    'slider_items',
                                    'Slider Items',
                                    'repeater',
                                    array(
                                        'graphql_field_name' => 'sliderItem',
                                        'layout'             => 'block',
                                        'button_label'       => 'Add Slide',
                                        'sub_fields'         => array(
                                            lekhoni_acf_field('field_6aca1720c739c', 'eyebrow', 'Eyebrow', 'text', array('required' => 1, 'parent_repeater' => 'field_6aca15d473452')),
                                            lekhoni_acf_field('field_6aca162b73453', 'title', 'Title', 'text', array('required' => 1, 'parent_repeater' => 'field_6aca15d473452')),
                                            lekhoni_acf_field('field_6aca164073454', 'description', 'Description', 'text', array('required' => 1, 'parent_repeater' => 'field_6aca15d473452')),
                                            lekhoni_acf_field('field_6aca164d73455', 'image', 'Image', 'image', array('required' => 1, 'return_format' => 'array', 'library' => 'all', 'preview_size' => 'thumbnail', 'parent_repeater' => 'field_6aca15d473452')),
                                            lekhoni_acf_field('field_6aca168073456', 'buy_now_url', 'Buy Now URL', 'url', array('wrapper' => array('width' => '50'), 'parent_repeater' => 'field_6aca15d473452')),
                                            lekhoni_acf_field('field_6aca16a373457', 'collection_url', 'Collection URL', 'url', array('wrapper' => array('width' => '50'), 'parent_repeater' => 'field_6aca15d473452')),
                                        ),
                                    )
                                ),
                            ),
                        )
                    ),

                    // ------------------------------------------------------------ Trust strip
                    lekhoni_home_tab('field_lk_home_tab_trust', 'Trust'),
                    lekhoni_acf_field(
                        'field_6aca33806dd57',
                        'trust_section',
                        'Trust Section',
                        'group',
                        array(
                            'layout'     => 'block',
                            'sub_fields' => array(
                                lekhoni_acf_field(
                                    'field_6aca338c6dd58',
                                    'items',
                                    'Items',
                                    'repeater',
                                    array(
                                        'layout'       => 'block',
                                        'button_label' => 'Add Item',
                                        'sub_fields'   => array(
                                            lekhoni_acf_field('field_6aca33f36dd5b', 'icon', 'Icon', 'select', array('required' => 1, 'choices' => lekhoni_trust_icon_choices(), 'return_format' => 'value', 'wrapper' => array('width' => '25'), 'parent_repeater' => 'field_6aca338c6dd58')),
                                            lekhoni_acf_field('field_6aca33d96dd59', 'title', 'Title', 'text', array('required' => 1, 'wrapper' => array('width' => '35'), 'parent_repeater' => 'field_6aca338c6dd58')),
                                            lekhoni_acf_field('field_6aca33e96dd5a', 'description', 'Description', 'text', array('required' => 1, 'wrapper' => array('width' => '40'), 'parent_repeater' => 'field_6aca338c6dd58')),
                                        ),
                                    )
                                ),
                            ),
                        )
                    ),

                    // ------------------------------------------------------------ Shop by category
                    lekhoni_home_tab('field_lk_home_tab_categories', 'Categories'),
                    lekhoni_acf_field(
                        'field_6acb10a1c5e01',
                        'category_section',
                        'Category Section',
                        'group',
                        array(
                            'layout'     => 'block',
                            'sub_fields' => array(
                                lekhoni_acf_field(
                                    'field_6acb10b4c5e02',
                                    'categories',
                                    'Categories',
                                    'taxonomy',
                                    array(
                                        'instructions'            => 'Pick the categories to show, in display order. Leave empty to show the top-level categories automatically.',
                                        'taxonomy'                => 'product_cat',
                                        'add_term'                => 0,
                                        'save_terms'              => 0,
                                        'load_terms'              => 0,
                                        'return_format'           => 'id',
                                        'field_type'              => 'multi_select',
                                        'allow_null'              => 1,
                                        'multiple'                => 0,
                                        'graphql_connection_type' => 'one_to_many',
                                    )
                                ),
                            ),
                        )
                    ),

                    // ------------------------------------------------------------ Flash sale
                    lekhoni_home_tab('field_lk_home_tab_flash', 'Flash Sale'),
                    lekhoni_acf_field(
                        'field_6acb2f10d7a01',
                        'flash_sale_section',
                        'Flash Sale Section',
                        'group',
                        array(
                            'layout'     => 'block',
                            'sub_fields' => array(
                                lekhoni_acf_field('field_6acb2f1ed7a02', 'title', 'Title', 'text', array('instructions' => 'Leave empty to use "ডিল অফ দ্য ডে".', 'placeholder' => 'ডিল অফ দ্য ডে', 'wrapper' => array('width' => '50'))),
                                lekhoni_acf_field('field_6acb2f2bd7a03', 'ends_at', 'Ends At', 'date_time_picker', array('instructions' => 'Countdown end time (site timezone). Leave empty to use the earliest product sale end date; no countdown if neither is set.', 'display_format' => 'd/m/Y g:i a', 'return_format' => 'Y-m-d H:i:s', 'first_day' => 6, 'wrapper' => array('width' => '50'))),
                                lekhoni_home_products_field('field_6acb2f39d7a04', 'products', 'Products', 12, 'Pick the products to show, in display order. Leave empty to show the best-selling products that are on sale.'),
                            ),
                        )
                    ),

                    // ------------------------------------------------------------ Featured products tabs
                    lekhoni_home_tab('field_lk_home_tab_featured', 'Featured Products'),
                    lekhoni_acf_field(
                        'field_lk_hp_featured',
                        'featured_section',
                        'Featured Products Tabs',
                        'group',
                        array(
                            'layout'     => 'block',
                            'sub_fields' => array(
                                lekhoni_acf_field('field_lk_hp_featured_title', 'title', 'Section Title', 'text', array('instructions' => 'Leave empty to use "নির্বাচিত পণ্য".', 'placeholder' => 'নির্বাচিত পণ্য', 'wrapper' => array('width' => '25'))),
                                lekhoni_acf_field('field_lk_hp_featured_new_label', 'new_label', 'New Tab Label', 'text', array('placeholder' => 'নতুন এসেছে', 'wrapper' => array('width' => '25'))),
                                lekhoni_acf_field('field_lk_hp_featured_best_label', 'best_label', 'Best Sellers Tab Label', 'text', array('placeholder' => 'বেস্টসেলার', 'wrapper' => array('width' => '25'))),
                                lekhoni_acf_field('field_lk_hp_featured_sale_label', 'sale_label', 'On Sale Tab Label', 'text', array('placeholder' => 'অফারে', 'wrapper' => array('width' => '25'))),
                                lekhoni_home_products_field('field_lk_hp_featured_new', 'new_products', 'New Tab Products', 8, 'In display order. Leave empty to show the newest products.'),
                                lekhoni_home_products_field('field_lk_hp_featured_best', 'best_products', 'Best Sellers Tab Products', 8, 'In display order. Leave empty to show the best-selling products.'),
                                lekhoni_home_products_field('field_lk_hp_featured_sale', 'sale_products', 'On Sale Tab Products', 8, 'In display order. Leave empty to show products that are on sale. A tab with no products is hidden.'),
                            ),
                        )
                    ),

                    // ------------------------------------------------------------ Promo banners
                    lekhoni_home_tab('field_lk_home_tab_promo', 'Promo Banners'),
                    lekhoni_acf_field(
                        'field_lk_hc_promo',
                        'promo_section',
                        'Promo Banners',
                        'group',
                        array(
                            'layout'     => 'block',
                            'sub_fields' => array(
                                lekhoni_acf_field('field_lk_hc_promo_title', 'title', 'Section Title', 'text', array('instructions' => 'For screen readers only (the banners carry their own titles).', 'placeholder' => 'চলতি অফার')),
                                lekhoni_acf_field(
                                    'field_lk_hc_promo_banners',
                                    'banners',
                                    'Banners',
                                    'repeater',
                                    array(
                                        'instructions' => 'Shown two per row on large screens. Leave empty to hide the section.',
                                        'layout'       => 'block',
                                        'button_label' => 'Add Banner',
                                        'sub_fields'   => array(
                                            lekhoni_acf_field('field_lk_hc_promo_eyebrow', 'eyebrow', 'Eyebrow', 'text', array('placeholder' => 'সীমিত সময়ের অফার', 'wrapper' => array('width' => '35'), 'parent_repeater' => 'field_lk_hc_promo_banners')),
                                            lekhoni_acf_field('field_lk_hc_promo_banner_title', 'title', 'Title', 'text', array('required' => 1, 'wrapper' => array('width' => '45'), 'parent_repeater' => 'field_lk_hc_promo_banners')),
                                            lekhoni_acf_field('field_lk_hc_promo_style', 'style', 'Background', 'select', array('choices' => array('accent' => 'Accent tint', 'sale' => 'Sale tint'), 'default_value' => 'accent', 'return_format' => 'value', 'wrapper' => array('width' => '20'), 'parent_repeater' => 'field_lk_hc_promo_banners')),
                                            lekhoni_acf_field('field_lk_hc_promo_text', 'text', 'Text', 'textarea', array('rows' => 2, 'new_lines' => '', 'parent_repeater' => 'field_lk_hc_promo_banners')),
                                            lekhoni_acf_field('field_lk_hc_promo_link', 'link', 'Button', 'link', array('instructions' => 'Empty button text shows "অফার দেখুন". No link hides the button.', 'return_format' => 'array', 'wrapper' => array('width' => '50'), 'parent_repeater' => 'field_lk_hc_promo_banners')),
                                            lekhoni_acf_field('field_lk_hc_promo_image', 'image', 'Image', 'image', array('instructions' => 'Square, about 800 x 800.', 'return_format' => 'array', 'preview_size' => 'thumbnail', 'wrapper' => array('width' => '50'), 'parent_repeater' => 'field_lk_hc_promo_banners')),
                                        ),
                                    )
                                ),
                            ),
                        )
                    ),

                    // ------------------------------------------------------------ Product spotlight
                    lekhoni_home_tab('field_lk_home_tab_spotlight', 'Spotlight'),
                    lekhoni_acf_field(
                        'field_lk_hp_spotlight',
                        'spotlight_section',
                        'Product Spotlight',
                        'group',
                        array(
                            'layout'     => 'block',
                            'sub_fields' => array(
                                lekhoni_acf_field('field_lk_hp_spotlight_eyebrow', 'eyebrow', 'Badge', 'text', array('instructions' => 'Leave empty to use "এই সপ্তাহের সেরা".', 'placeholder' => 'এই সপ্তাহের সেরা', 'wrapper' => array('width' => '40'))),
                                lekhoni_home_products_field('field_lk_hp_spotlight_product', 'product', 'Product', 1, 'Leave empty to feature the best-selling in-stock variable product (or, failing that, the best-selling in-stock product).'),
                                lekhoni_acf_field(
                                    'field_lk_hp_spotlight_highlights',
                                    'highlights',
                                    'Highlights',
                                    'repeater',
                                    array(
                                        'instructions' => 'Optional check-mark list shown under the price. The short description is shown when this is empty.',
                                        'layout'       => 'table',
                                        'button_label' => 'Add Highlight',
                                        'max'          => 6,
                                        'sub_fields'   => array(
                                            lekhoni_acf_field('field_lk_hp_spotlight_highlight_text', 'text', 'Text', 'text', array('required' => 1, 'parent_repeater' => 'field_lk_hp_spotlight_highlights')),
                                        ),
                                    )
                                ),
                            ),
                        )
                    ),

                    // ------------------------------------------------------------ Best sellers carousel
                    lekhoni_home_tab('field_lk_home_tab_best', 'Best Sellers'),
                    lekhoni_acf_field(
                        'field_lk_hp_best',
                        'best_sellers_section',
                        'Best Sellers Carousel',
                        'group',
                        array(
                            'layout'     => 'block',
                            'sub_fields' => array(
                                lekhoni_acf_field('field_lk_hp_best_title', 'title', 'Section Title', 'text', array('instructions' => 'Leave empty to use "সবচেয়ে বেশি বিক্রীত".', 'placeholder' => 'সবচেয়ে বেশি বিক্রীত')),
                                lekhoni_home_products_field('field_lk_hp_best_products', 'products', 'Products', 8, 'In display order. Leave empty to show the 8 best-selling products.'),
                            ),
                        )
                    ),

                    // ------------------------------------------------------------ Customer reviews
                    lekhoni_home_tab('field_lk_home_tab_reviews', 'Reviews'),
                    lekhoni_acf_field(
                        'field_lk_hp_reviews',
                        'reviews_section',
                        'Customer Reviews',
                        'group',
                        array(
                            'layout'     => 'block',
                            'sub_fields' => array(
                                lekhoni_acf_field('field_lk_hp_reviews_title', 'title', 'Section Title', 'text', array('instructions' => 'Leave empty to use "ক্রেতারা যা বলছেন".', 'placeholder' => 'ক্রেতারা যা বলছেন', 'wrapper' => array('width' => '50'))),
                                lekhoni_acf_field(
                                    'field_lk_hp_reviews_source',
                                    'source',
                                    'Show',
                                    'select',
                                    array(
                                        'instructions'  => 'Product reviews are approved WooCommerce reviews rated 4 or 5 stars.',
                                        'choices'       => array(
                                            'auto'         => 'Product reviews, or the testimonials below when there are none',
                                            'reviews'      => 'Product reviews only',
                                            'testimonials' => 'Testimonials only',
                                            'both'         => 'Testimonials, then product reviews',
                                        ),
                                        'default_value' => 'auto',
                                        'return_format' => 'value',
                                        'wrapper'       => array('width' => '50'),
                                    )
                                ),
                                lekhoni_acf_field(
                                    'field_lk_hp_reviews_items',
                                    'testimonials',
                                    'Testimonials',
                                    'repeater',
                                    array(
                                        'layout'       => 'block',
                                        'button_label' => 'Add Testimonial',
                                        'sub_fields'   => array(
                                            lekhoni_acf_field('field_lk_hp_review_name', 'name', 'Name', 'text', array('required' => 1, 'wrapper' => array('width' => '30'), 'parent_repeater' => 'field_lk_hp_reviews_items')),
                                            lekhoni_acf_field('field_lk_hp_review_location', 'location', 'Location or Role', 'text', array('wrapper' => array('width' => '30'), 'parent_repeater' => 'field_lk_hp_reviews_items')),
                                            lekhoni_acf_field('field_lk_hp_review_rating', 'rating', 'Rating', 'number', array('min' => 1, 'max' => 5, 'step' => 1, 'default_value' => 5, 'wrapper' => array('width' => '20'), 'parent_repeater' => 'field_lk_hp_reviews_items')),
                                            lekhoni_acf_field('field_lk_hp_review_verified', 'verified', 'Verified Buyer', 'true_false', array('ui' => 1, 'default_value' => 1, 'wrapper' => array('width' => '20'), 'parent_repeater' => 'field_lk_hp_reviews_items')),
                                            lekhoni_acf_field('field_lk_hp_review_text', 'text', 'Text', 'textarea', array('required' => 1, 'rows' => 3, 'new_lines' => '', 'wrapper' => array('width' => '75'), 'parent_repeater' => 'field_lk_hp_reviews_items')),
                                            lekhoni_acf_field('field_lk_hp_review_photo', 'photo', 'Photo', 'image', array('return_format' => 'array', 'preview_size' => 'thumbnail', 'wrapper' => array('width' => '25'), 'parent_repeater' => 'field_lk_hp_reviews_items')),
                                        ),
                                    )
                                ),
                            ),
                        )
                    ),

                    // ------------------------------------------------------------ Brands strip
                    lekhoni_home_tab('field_lk_home_tab_brands', 'Brands'),
                    lekhoni_acf_field(
                        'field_lk_hp_brands',
                        'brands_section',
                        'Brands Strip',
                        'group',
                        array(
                            'layout'     => 'block',
                            'sub_fields' => array(
                                lekhoni_acf_field('field_lk_hp_brands_title', 'title', 'Section Title', 'text', array('instructions' => 'Leave empty to use "যেসব ব্র্যান্ড আমাদের সঙ্গে আছে".', 'placeholder' => 'যেসব ব্র্যান্ড আমাদের সঙ্গে আছে')),
                                lekhoni_acf_field(
                                    'field_lk_hp_brands_items',
                                    'brands',
                                    'Brands',
                                    'repeater',
                                    array(
                                        'instructions' => 'Leave empty to show the WooCommerce product brands (Products → Brands) that have products. The section is hidden when there are none.',
                                        'layout'       => 'table',
                                        'button_label' => 'Add Brand',
                                        'sub_fields'   => array(
                                            lekhoni_acf_field('field_lk_hp_brand_name', 'name', 'Name', 'text', array('required' => 1, 'parent_repeater' => 'field_lk_hp_brands_items')),
                                            lekhoni_acf_field('field_lk_hp_brand_logo', 'logo', 'Logo', 'image', array('instructions' => 'Optional. The name is shown when empty.', 'return_format' => 'array', 'preview_size' => 'thumbnail', 'parent_repeater' => 'field_lk_hp_brands_items')),
                                            lekhoni_acf_field('field_lk_hp_brand_link', 'link', 'Link', 'url', array('instructions' => 'Optional, e.g. /shop?q=…', 'parent_repeater' => 'field_lk_hp_brands_items')),
                                        ),
                                    )
                                ),
                            ),
                        )
                    ),

                    // ------------------------------------------------------------ From the blog
                    lekhoni_home_tab('field_lk_home_tab_blog', 'Blog'),
                    lekhoni_acf_field(
                        'field_lk_hc_blog',
                        'blog_section',
                        'From the Blog',
                        'group',
                        array(
                            'instructions' => 'The latest posts. The section is hidden when there are no posts to show.',
                            'layout'       => 'block',
                            'sub_fields'   => array(
                                lekhoni_acf_field('field_lk_hc_blog_title', 'title', 'Section Title', 'text', array('placeholder' => 'ব্লগ থেকে', 'wrapper' => array('width' => '50'))),
                                lekhoni_acf_field('field_lk_hc_blog_link_label', 'link_label', '"See All" Link Text', 'text', array('placeholder' => 'সব লেখা', 'instructions' => 'Links to the category (if chosen) or the posts page.', 'wrapper' => array('width' => '50'))),
                                lekhoni_acf_field(
                                    'field_lk_hc_blog_category',
                                    'category',
                                    'Category',
                                    'taxonomy',
                                    array(
                                        'instructions'  => 'Optional. Only posts from this category (e.g. a buying guide category).',
                                        'taxonomy'      => 'category',
                                        'field_type'    => 'select',
                                        'allow_null'    => 1,
                                        'add_term'      => 0,
                                        'save_terms'    => 0,
                                        'load_terms'    => 0,
                                        'return_format' => 'id',
                                        'multiple'      => 0,
                                        'wrapper'       => array('width' => '50'),
                                    )
                                ),
                                lekhoni_acf_field('field_lk_hc_blog_count', 'count', 'Number of Posts', 'number', array('placeholder' => '3', 'instructions' => 'Empty shows 3.', 'min' => 1, 'max' => 12, 'step' => 1, 'wrapper' => array('width' => '50'))),
                            ),
                        )
                    ),

                    // ------------------------------------------------------------ Newsletter band
                    lekhoni_home_tab('field_lk_home_tab_newsletter', 'Newsletter'),
                    lekhoni_acf_field(
                        'field_lk_hc_subscribe',
                        'subscribe_section',
                        'Newsletter Band',
                        'group',
                        array(
                            'instructions' => 'Sign-ups are stored under Subscribers in the admin menu.',
                            'layout'       => 'block',
                            'sub_fields'   => array(
                                lekhoni_acf_field('field_lk_hc_subscribe_title', 'title', 'Title', 'text', array('placeholder' => 'নতুন অফারের খবর সরাসরি ইনবক্সে')),
                                lekhoni_acf_field('field_lk_hc_subscribe_text', 'text', 'Text', 'textarea', array('rows' => 2, 'new_lines' => '')),
                                lekhoni_acf_field('field_lk_hc_subscribe_success', 'success_message', 'Success Message', 'text', array('placeholder' => 'সাবস্ক্রাইব করার জন্য ধন্যবাদ!', 'wrapper' => array('width' => '50'))),
                                lekhoni_acf_field('field_lk_hc_subscribe_note', 'note', 'Small Print', 'text', array('instructions' => 'Shown under the form. Optional.', 'wrapper' => array('width' => '50'))),
                            ),
                        )
                    ),
                ),
                'location'   => array(
                    array(
                        array(
                            'param'    => 'page_template',
                            'operator' => '==',
                            'value'    => 'templates/template-home.php',
                        ),
                    ),
                ),
                'menu_order'            => 0,
                'position'              => 'normal',
                'style'                 => 'default',
                'label_placement'       => 'top',
                'instruction_placement' => 'label',
                'active'                => true,
                'show_in_rest'          => 0,
                'show_in_graphql'       => 1,
                'graphql_field_name'    => 'homeShop',
                // Shown on the "Home Shop" template in the admin, but exposed on every Page in GraphQL
                'map_graphql_types_from_location_rules' => 0,
                'graphql_types'         => array('Page'),
            )
        );
    }
);
