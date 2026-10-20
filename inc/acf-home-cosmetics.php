<?php

/**
 * ACF fields for the "Home – Cosmetics" page template
 * (templates/template-home-cosmetics.php): a beauty store home, one tab per
 * section in homepage order. Design: lekhoni/design/home-cosmetics.html.
 *
 * Exposed to GraphQL as `page { homeCosmetics { … } }`. Every section has an
 * on/off switch (never saved = on) and is hidden when it has nothing to show.
 * Routine bundles are discounted in the cart by inc/routines.php.
 *
 * Field keys and names are permanent: ACF stores values by them, so renaming
 * one loses its saved content.
 *
 * @package WordPress
 * @subpackage Lekhoni
 * @since Lekhoni 1.0
 */

/**
 * Section heading fields: a small eyebrow line and the title.
 *
 * @param string $prefix      Field key prefix.
 * @param string $eyebrow     Eyebrow placeholder.
 * @param string $title       Title placeholder.
 * @return array[]
 */
function lekhoni_beauty_heading($prefix, $eyebrow, $title)
{
    return array(
        lekhoni_acf_field($prefix . '_eyebrow', 'eyebrow', 'Eyebrow', 'text', array('placeholder' => $eyebrow, 'wrapper' => array('width' => '25'))),
        lekhoni_acf_field($prefix . '_title', 'title', 'Section Title', 'text', array('placeholder' => $title, 'wrapper' => array('width' => '50'))),
    );
}

/**
 * The "Home – Cosmetics" field group.
 *
 * @return void
 */
function lekhoni_register_home_cosmetics_fields()
{
    if (! function_exists('acf_add_local_field_group')) {
        return;
    }

    $fields = array(

        // ------------------------------------------------------------ Hero
        lekhoni_home_tab('field_lk_hb_tab_hero', 'Hero'),
        lekhoni_acf_field(
            'field_lk_hb_hero',
            'hero_section',
            'Hero',
            'group',
            array(
                'layout'     => 'block',
                'sub_fields' => array(
                    lekhoni_acf_field('field_lk_hb_hero_badge', 'badge', 'Image Badge', 'text', array('instructions' => 'Small label over the image. Empty hides it.', 'placeholder' => 'ডার্মাটোলজিস্ট পরীক্ষিত', 'wrapper' => array('width' => '35'))),
                    lekhoni_acf_field('field_lk_hb_hero_rating', 'rating_text', 'Rating Line', 'text', array('instructions' => 'Shown after the store\'s average review rating, e.g. "১২ হাজার+ খুশি ক্রেতা". Empty hides the line.', 'placeholder' => '১২ হাজার+ খুশি ক্রেতা', 'wrapper' => array('width' => '65'))),
                    lekhoni_acf_field(
                        'field_lk_hb_hero_slides',
                        'slides',
                        'Slides',
                        'repeater',
                        array(
                            'instructions' => 'At least one slide shows the hero.',
                            'layout'       => 'block',
                            'button_label' => 'Add Slide',
                            'sub_fields'   => array(
                                lekhoni_acf_field('field_lk_hb_slide_eyebrow', 'eyebrow', 'Eyebrow', 'text', array('placeholder' => 'নতুন · গ্লো কালেকশন', 'wrapper' => array('width' => '35'), 'parent_repeater' => 'field_lk_hb_hero_slides')),
                                lekhoni_acf_field('field_lk_hb_slide_title', 'title', 'Title', 'text', array('required' => 1, 'wrapper' => array('width' => '65'), 'parent_repeater' => 'field_lk_hb_hero_slides')),
                                lekhoni_acf_field('field_lk_hb_slide_text', 'text', 'Text', 'textarea', array('rows' => 2, 'new_lines' => '', 'parent_repeater' => 'field_lk_hb_hero_slides')),
                                lekhoni_acf_field('field_lk_hb_slide_primary', 'primary_link', 'Main Button', 'link', array('instructions' => 'Empty text shows "এখনই কিনুন".', 'return_format' => 'array', 'wrapper' => array('width' => '33'), 'parent_repeater' => 'field_lk_hb_hero_slides')),
                                lekhoni_acf_field('field_lk_hb_slide_secondary', 'secondary_link', 'Second Button', 'link', array('instructions' => 'Optional.', 'return_format' => 'array', 'wrapper' => array('width' => '33'), 'parent_repeater' => 'field_lk_hb_hero_slides')),
                                lekhoni_acf_field('field_lk_hb_slide_image', 'image', 'Image', 'image', array('required' => 1, 'instructions' => 'Portrait 4:5, about 800 x 1000. Shown in an arch.', 'return_format' => 'array', 'preview_size' => 'thumbnail', 'wrapper' => array('width' => '34'), 'parent_repeater' => 'field_lk_hb_hero_slides')),
                            ),
                        )
                    ),
                ),
            )
        ),

        // ------------------------------------------------------------ Promises
        lekhoni_home_tab('field_lk_hb_tab_promises', 'Promises'),
        lekhoni_acf_field(
            'field_lk_hb_promises',
            'promises_section',
            'Promise Strip',
            'group',
            array(
                'layout'     => 'block',
                'sub_fields' => array(
                    lekhoni_home_enabled('field_lk_hb_promises_on'),
                    lekhoni_acf_field(
                        'field_lk_hb_promises_items',
                        'items',
                        'Items',
                        'repeater',
                        array(
                            'instructions' => 'Up to four. Empty shows genuine, cruelty-free, paraben-free and easy returns.',
                            'layout'       => 'table',
                            'max'          => 4,
                            'button_label' => 'Add Item',
                            'sub_fields'   => array(
                                lekhoni_acf_field('field_lk_hb_promise_icon', 'icon', 'Icon', 'select', array('choices' => lekhoni_shop_icon_choices(), 'default_value' => 'award', 'return_format' => 'value', 'parent_repeater' => 'field_lk_hb_promises_items')),
                                lekhoni_acf_field('field_lk_hb_promise_text', 'text', 'Text', 'text', array('required' => 1, 'parent_repeater' => 'field_lk_hb_promises_items')),
                            ),
                        )
                    ),
                ),
            )
        ),

        // ------------------------------------------------------------ Categories
        lekhoni_home_tab('field_lk_hb_tab_categories', 'Categories'),
        lekhoni_acf_field(
            'field_lk_hb_categories',
            'categories_section',
            'Category Circles',
            'group',
            array(
                'instructions' => 'Images come from each category\'s Thumbnail (Products → Categories).',
                'layout'       => 'block',
                'sub_fields'   => array_merge(
                    array(lekhoni_home_enabled('field_lk_hb_categories_on')),
                    lekhoni_beauty_heading('field_lk_hb_categories', 'ক্যাটাগরি', 'আপনার পছন্দের জগৎ বেছে নিন'),
                    array(lekhoni_home_category_field('field_lk_hb_categories_cats', 'categories', 'Categories', 'In display order (6 fit one row). Leave empty to show the top-level categories.'))
                ),
            )
        ),

        // ------------------------------------------------------------ Shop by concern
        lekhoni_home_tab('field_lk_hb_tab_concerns', 'Skin Concerns'),
        lekhoni_acf_field(
            'field_lk_hb_concerns',
            'concerns_section',
            'Shop by Concern',
            'group',
            array(
                'instructions' => 'Each concern is a product tag (Products → Tags), e.g. "dry-skin". Tag your products, then list the concerns here. The chips filter the products on the page.',
                'layout'       => 'block',
                'sub_fields'   => array_merge(
                    array(lekhoni_home_enabled('field_lk_hb_concerns_on')),
                    lekhoni_beauty_heading('field_lk_hb_concerns', 'ত্বকের সমস্যা অনুযায়ী', 'আপনার ত্বকের যা দরকার'),
                    array(
                        lekhoni_acf_field('field_lk_hb_concerns_all', 'all_label', '"All" Chip', 'text', array('placeholder' => 'সব', 'wrapper' => array('width' => '25'))),
                        lekhoni_acf_field(
                            'field_lk_hb_concerns_items',
                            'concerns',
                            'Concerns',
                            'repeater',
                            array(
                                'instructions' => 'In chip order. The section is hidden without concerns.',
                                'layout'       => 'table',
                                'button_label' => 'Add Concern',
                                'sub_fields'   => array(
                                    lekhoni_acf_field('field_lk_hb_concern_label', 'label', 'Chip Label', 'text', array('required' => 1, 'placeholder' => 'শুষ্ক ত্বক', 'parent_repeater' => 'field_lk_hb_concerns_items')),
                                    lekhoni_acf_field(
                                        'field_lk_hb_concern_tag',
                                        'tag',
                                        'Product Tag',
                                        'taxonomy',
                                        array(
                                            'required'                => 1,
                                            'taxonomy'                => 'product_tag',
                                            'field_type'              => 'select',
                                            'allow_null'              => 0,
                                            'add_term'                => 1,
                                            'save_terms'              => 0,
                                            'load_terms'              => 0,
                                            'return_format'           => 'id',
                                            'multiple'                => 0,
                                            'graphql_connection_type' => 'one_to_one',
                                            'parent_repeater'         => 'field_lk_hb_concerns_items',
                                        )
                                    ),
                                ),
                            )
                        ),
                        lekhoni_home_products_field('field_lk_hb_concerns_products', 'products', 'Products', 12, 'Optional, in display order. Leave empty to show up to 8 products tagged with any of the concerns (best sellers first).'),
                    )
                ),
            )
        ),

        // ------------------------------------------------------------ Best sellers
        lekhoni_home_tab('field_lk_hb_tab_best', 'Best Sellers'),
        lekhoni_acf_field(
            'field_lk_hb_best',
            'bestsellers_section',
            'Best Sellers',
            'group',
            array(
                'instructions' => 'Variable products show their colour swatches (set a Swatch Colour on each attribute term, e.g. Products → Attributes → Shade → Configure terms).',
                'layout'       => 'block',
                'sub_fields'   => array_merge(
                    array(lekhoni_home_enabled('field_lk_hb_best_on')),
                    lekhoni_beauty_heading('field_lk_hb_best', 'সবার পছন্দ', 'বেস্টসেলার'),
                    array(lekhoni_home_products_field('field_lk_hb_best_products', 'products', 'Products', 12, 'In display order. Leave empty to show the 8 best-selling products.'))
                ),
            )
        ),

        // ------------------------------------------------------------ Routine builder
        lekhoni_home_tab('field_lk_hb_tab_routines', 'Routines'),
        lekhoni_acf_field(
            'field_lk_hb_routines',
            'routines_section',
            'Routine Builder',
            'group',
            array(
                'instructions' => 'When a customer has every product of a routine in the cart, the cart takes the bundle discount off those products (one set).',
                'layout'       => 'block',
                'sub_fields'   => array_merge(
                    array(lekhoni_home_enabled('field_lk_hb_routines_on')),
                    lekhoni_beauty_heading('field_lk_hb_routines', '৪ ধাপের রুটিন', 'আপনার রুটিন তৈরি করুন'),
                    array(
                        lekhoni_acf_field('field_lk_hb_routines_text', 'text', 'Text', 'textarea', array('rows' => 2, 'new_lines' => '', 'wrapper' => array('width' => '75'))),
                        lekhoni_acf_field('field_lk_hb_routines_discount', 'discount', 'Bundle Discount (%)', 'number', array('placeholder' => '15', 'instructions' => '0 turns the bundle price off.', 'min' => 0, 'max' => 50, 'step' => 1, 'wrapper' => array('width' => '25'))),
                        lekhoni_acf_field(
                            'field_lk_hb_routines_items',
                            'routines',
                            'Routines',
                            'repeater',
                            array(
                                'instructions' => 'One tab each, e.g. morning and night.',
                                'layout'       => 'block',
                                'max'          => 4,
                                'button_label' => 'Add Routine',
                                'sub_fields'   => array(
                                    lekhoni_acf_field('field_lk_hb_routine_name', 'name', 'Tab Name', 'text', array('required' => 1, 'placeholder' => 'সকালের রুটিন', 'wrapper' => array('width' => '50'), 'parent_repeater' => 'field_lk_hb_routines_items')),
                                    lekhoni_acf_field('field_lk_hb_routine_icon', 'icon', 'Icon', 'select', array('choices' => array('sun' => 'Sun (morning)', 'moon' => 'Moon (night)', 'sparkles' => 'Sparkles', 'droplet' => 'Droplet'), 'default_value' => 'sun', 'return_format' => 'value', 'wrapper' => array('width' => '50'), 'parent_repeater' => 'field_lk_hb_routines_items')),
                                    lekhoni_acf_field(
                                        'field_lk_hb_routine_steps',
                                        'steps',
                                        'Steps',
                                        'repeater',
                                        array(
                                            'layout'          => 'table',
                                            'min'             => 2,
                                            'max'             => 6,
                                            'button_label'    => 'Add Step',
                                            'parent_repeater' => 'field_lk_hb_routines_items',
                                            'sub_fields'      => array(
                                                lekhoni_acf_field('field_lk_hb_step_label', 'label', 'Step', 'text', array('required' => 1, 'placeholder' => 'পরিষ্কার', 'parent_repeater' => 'field_lk_hb_routine_steps')),
                                                lekhoni_home_products_field('field_lk_hb_step_product', 'product', 'Product', 1, 'A simple product.', array('required' => 1, 'parent_repeater' => 'field_lk_hb_routine_steps')),
                                            ),
                                        )
                                    ),
                                ),
                            )
                        ),
                    )
                ),
            )
        ),

        // ------------------------------------------------------------ Ingredients
        lekhoni_home_tab('field_lk_hb_tab_ingredients', 'Ingredients'),
        lekhoni_acf_field(
            'field_lk_hb_ingredients',
            'ingredients_section',
            'Ingredient Spotlight',
            'group',
            array(
                'layout'     => 'block',
                'sub_fields' => array_merge(
                    array(lekhoni_home_enabled('field_lk_hb_ingredients_on')),
                    lekhoni_beauty_heading('field_lk_hb_ingredients', 'উপাদান', 'কী আছে ভেতরে, জেনে কিনুন'),
                    array(
                        lekhoni_acf_field(
                            'field_lk_hb_ingredients_items',
                            'ingredients',
                            'Ingredients',
                            'repeater',
                            array(
                                'instructions' => 'Three per row. The section is hidden without ingredients.',
                                'layout'       => 'block',
                                'button_label' => 'Add Ingredient',
                                'sub_fields'   => array(
                                    lekhoni_acf_field('field_lk_hb_ing_name', 'name', 'Name', 'text', array('required' => 1, 'wrapper' => array('width' => '40'), 'parent_repeater' => 'field_lk_hb_ingredients_items')),
                                    lekhoni_acf_field('field_lk_hb_ing_icon', 'icon', 'Icon', 'select', array('choices' => lekhoni_shop_icon_choices(), 'default_value' => 'sparkles', 'return_format' => 'value', 'wrapper' => array('width' => '30'), 'parent_repeater' => 'field_lk_hb_ingredients_items')),
                                    lekhoni_acf_field('field_lk_hb_ing_tone', 'tone', 'Icon Background', 'select', array('choices' => lekhoni_tone_choices(), 'default_value' => 'accent', 'return_format' => 'value', 'wrapper' => array('width' => '30'), 'parent_repeater' => 'field_lk_hb_ingredients_items')),
                                    lekhoni_acf_field('field_lk_hb_ing_text', 'text', 'What It Does', 'textarea', array('required' => 1, 'rows' => 2, 'new_lines' => '', 'parent_repeater' => 'field_lk_hb_ingredients_items')),
                                    lekhoni_acf_field('field_lk_hb_ing_good', 'good_for', 'Good For', 'text', array('instructions' => 'Comma separated, e.g. "শুষ্ক ত্বক, সূক্ষ্ম রেখা".', 'wrapper' => array('width' => '50'), 'parent_repeater' => 'field_lk_hb_ingredients_items')),
                                    lekhoni_acf_field('field_lk_hb_ing_link', 'link', 'Link', 'link', array('instructions' => 'e.g. a search or tag page. Empty text shows "<name> আছে এমন পণ্য".', 'return_format' => 'array', 'wrapper' => array('width' => '50'), 'parent_repeater' => 'field_lk_hb_ingredients_items')),
                                ),
                            )
                        ),
                    )
                ),
            )
        ),

        // ------------------------------------------------------------ Gift offer
        lekhoni_home_tab('field_lk_hb_tab_offer', 'Offer'),
        lekhoni_acf_field(
            'field_lk_hb_offer',
            'offer_section',
            'Offer Banner',
            'group',
            array(
                'instructions' => 'Hidden while the title is empty. The offer itself (e.g. a free gift) is set up in WooCommerce, for instance as a coupon.',
                'layout'       => 'block',
                'sub_fields'   => array(
                    lekhoni_home_enabled('field_lk_hb_offer_on'),
                    lekhoni_acf_field('field_lk_hb_offer_eyebrow', 'eyebrow', 'Eyebrow', 'text', array('placeholder' => 'উপহার অফার', 'wrapper' => array('width' => '35'))),
                    lekhoni_acf_field('field_lk_hb_offer_ends', 'ends_at', 'Ends At', 'date_time_picker', array('instructions' => 'Optional countdown (site timezone). The banner hides itself after this time.', 'display_format' => 'd/m/Y g:i a', 'return_format' => 'Y-m-d H:i:s', 'first_day' => 6, 'wrapper' => array('width' => '40'))),
                    lekhoni_acf_field('field_lk_hb_offer_title', 'title', 'Title', 'text', array('placeholder' => '৳২,০০০-এর বেশি কেনাকাটায় ফ্রি ট্রাভেল কিট')),
                    lekhoni_acf_field('field_lk_hb_offer_text', 'text', 'Text', 'textarea', array('rows' => 2, 'new_lines' => '')),
                    lekhoni_acf_field('field_lk_hb_offer_link', 'link', 'Button', 'link', array('instructions' => 'Empty text shows "কেনাকাটা শুরু করুন".', 'return_format' => 'array', 'wrapper' => array('width' => '50'))),
                    lekhoni_acf_field('field_lk_hb_offer_image', 'image', 'Image', 'image', array('instructions' => 'Square; shown in a circle.', 'return_format' => 'array', 'preview_size' => 'thumbnail', 'wrapper' => array('width' => '50'))),
                ),
            )
        ),

        // ------------------------------------------------------------ Reviews
        lekhoni_home_tab('field_lk_hb_tab_reviews', 'Reviews'),
        lekhoni_acf_field(
            'field_lk_hb_reviews',
            'reviews_section',
            'Reviews',
            'group',
            array(
                'layout'     => 'block',
                'sub_fields' => array_merge(
                    array(lekhoni_home_enabled('field_lk_hb_reviews_on')),
                    lekhoni_beauty_heading('field_lk_hb_reviews', 'রিভিউ', 'তাঁরা যা বলছেন'),
                    array(
                        lekhoni_acf_field(
                            'field_lk_hb_reviews_source',
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
                            )
                        ),
                        lekhoni_acf_field(
                            'field_lk_hb_reviews_items',
                            'testimonials',
                            'Testimonials',
                            'repeater',
                            array(
                                'layout'       => 'block',
                                'button_label' => 'Add Testimonial',
                                'sub_fields'   => array(
                                    lekhoni_acf_field('field_lk_hb_review_name', 'name', 'Name', 'text', array('required' => 1, 'wrapper' => array('width' => '30'), 'parent_repeater' => 'field_lk_hb_reviews_items')),
                                    lekhoni_acf_field('field_lk_hb_review_skin', 'skin_type', 'Skin Type', 'text', array('placeholder' => 'তৈলাক্ত', 'wrapper' => array('width' => '25'), 'parent_repeater' => 'field_lk_hb_reviews_items')),
                                    lekhoni_acf_field('field_lk_hb_review_rating', 'rating', 'Rating', 'number', array('min' => 1, 'max' => 5, 'step' => 1, 'default_value' => 5, 'wrapper' => array('width' => '20'), 'parent_repeater' => 'field_lk_hb_reviews_items')),
                                    lekhoni_acf_field('field_lk_hb_review_verified', 'verified', 'Verified Buyer', 'true_false', array('ui' => 1, 'default_value' => 1, 'wrapper' => array('width' => '25'), 'parent_repeater' => 'field_lk_hb_reviews_items')),
                                    lekhoni_acf_field('field_lk_hb_review_text', 'text', 'Text', 'textarea', array('required' => 1, 'rows' => 3, 'new_lines' => '', 'wrapper' => array('width' => '60'), 'parent_repeater' => 'field_lk_hb_reviews_items')),
                                    lekhoni_home_products_field('field_lk_hb_review_product', 'product', 'Product', 1, 'Optional.', array('wrapper' => array('width' => '40'), 'parent_repeater' => 'field_lk_hb_reviews_items')),
                                ),
                            )
                        ),
                    )
                ),
            )
        ),

        // ------------------------------------------------------------ Customer gallery
        lekhoni_home_tab('field_lk_hb_tab_gallery', 'Photo Gallery'),
        lekhoni_acf_field(
            'field_lk_hb_gallery',
            'gallery_section',
            'Customer Photos',
            'group',
            array(
                'layout'     => 'block',
                'sub_fields' => array_merge(
                    array(lekhoni_home_enabled('field_lk_hb_gallery_on')),
                    lekhoni_beauty_heading('field_lk_hb_gallery', '#লেখনীবিউটি', 'আপনাদের তোলা ছবি'),
                    array(
                        lekhoni_acf_field('field_lk_hb_gallery_text', 'text', 'Text', 'text', array('wrapper' => array('width' => '60'))),
                        lekhoni_acf_field('field_lk_hb_gallery_link', 'link', 'Photos Link To', 'url', array('instructions' => 'e.g. your Instagram profile. Empty: photos aren\'t links.', 'wrapper' => array('width' => '40'))),
                        lekhoni_acf_field('field_lk_hb_gallery_images', 'images', 'Photos', 'gallery', array('instructions' => 'Square photos; six fit one row. The section is hidden without photos.', 'return_format' => 'array', 'preview_size' => 'thumbnail', 'library' => 'all', 'max' => 12)),
                    )
                ),
            )
        ),

        // ------------------------------------------------------------ Journal (blog)
        lekhoni_home_tab('field_lk_hb_tab_blog', 'Beauty Tips'),
        lekhoni_acf_field(
            'field_lk_hb_blog',
            'blog_section',
            'Beauty Tips (Blog)',
            'group',
            array(
                'instructions' => 'The latest posts. Hidden when there are no posts to show.',
                'layout'       => 'block',
                'sub_fields'   => array(
                    lekhoni_home_enabled('field_lk_hb_blog_on'),
                    lekhoni_acf_field('field_lk_hb_blog_title', 'title', 'Section Title', 'text', array('placeholder' => 'বিউটি টিপস', 'wrapper' => array('width' => '40'))),
                    lekhoni_acf_field('field_lk_hb_blog_link_label', 'link_label', '"See All" Link Text', 'text', array('placeholder' => 'সব লেখা', 'wrapper' => array('width' => '35'))),
                    lekhoni_acf_field(
                        'field_lk_hb_blog_category',
                        'category',
                        'Category',
                        'taxonomy',
                        array(
                            'instructions'  => 'Optional. Only posts from this category.',
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
                    lekhoni_acf_field('field_lk_hb_blog_count', 'count', 'Number of Posts', 'number', array('placeholder' => '3', 'min' => 1, 'max' => 12, 'step' => 1, 'wrapper' => array('width' => '50'))),
                ),
            )
        ),

        // ------------------------------------------------------------ Beauty club (newsletter)
        lekhoni_home_tab('field_lk_hb_tab_newsletter', 'Newsletter'),
        lekhoni_acf_field(
            'field_lk_hb_subscribe',
            'subscribe_section',
            'Beauty Club (Newsletter)',
            'group',
            array(
                'instructions' => 'Sign-ups are stored under Subscribers in the admin menu.',
                'layout'       => 'block',
                'sub_fields'   => array(
                    lekhoni_home_enabled('field_lk_hb_subscribe_on'),
                    lekhoni_acf_field('field_lk_hb_subscribe_title', 'title', 'Title', 'text', array('placeholder' => 'বিউটি ক্লাবে যোগ দিন', 'wrapper' => array('width' => '75'))),
                    lekhoni_acf_field('field_lk_hb_subscribe_text', 'text', 'Text', 'textarea', array('rows' => 2, 'new_lines' => '')),
                    lekhoni_acf_field('field_lk_hb_subscribe_success', 'success_message', 'Success Message', 'text', array('placeholder' => 'স্বাগতম! বিউটি ক্লাবে যোগ দেওয়ার জন্য ধন্যবাদ।', 'wrapper' => array('width' => '50'))),
                    lekhoni_acf_field('field_lk_hb_subscribe_note', 'note', 'Small Print', 'text', array('wrapper' => array('width' => '50'))),
                ),
            )
        ),
    );

    // Stored names get a "hb_" prefix (sub fields are saved under their
    // parent's name), so this group never reads another home template's
    // values on the same page. GraphQL names keep the unprefixed form.
    $fields = array_map(
        function ($field) {
            if ('tab' !== $field['type']) {
                $field['name'] = 'hb_' . $field['name'];
            }
            return $field;
        },
        $fields
    );

    acf_add_local_field_group(
        array(
            'key'                   => 'group_lk_home_cosmetics',
            'title'                 => 'Home – Cosmetics',
            'fields'                => $fields,
            'location'              => array(
                array(
                    array(
                        'param'    => 'page_template',
                        'operator' => '==',
                        'value'    => 'templates/template-home-cosmetics.php',
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
            'graphql_field_name'    => 'homeCosmetics',
            'map_graphql_types_from_location_rules' => 0,
            'graphql_types'         => array('Page'),
        )
    );
}
add_action('acf/include_fields', 'lekhoni_register_home_cosmetics_fields');
