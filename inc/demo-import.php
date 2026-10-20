<?php

/**
 * One-click demo import: the engine.
 *
 * Imports one demo package, demo/<id>/content.json with its images in
 * demo/<id>/images/ and demo/shared/ (built by the storefront's
 * scripts/build-demo.mjs from the design files), into this site. There is a
 * package per homepage design: stationery (Home Shop), ecommerce
 * (Home – E-commerce) and cosmetics (Home – Cosmetics). It imports:
 * images, authors, categories/tags/attributes, products (with variations and
 * reviews), posts, pages with their ACF fields, menus, Reading and Theme
 * Settings, and optionally WooCommerce store settings.
 *
 * - Runs in small steps (lekhoni_demo_steps()) so no request times out; the
 *   admin page (inc/demo-import-admin.php) and WP-CLI (inc/demo-import-cli.php)
 *   both call lekhoni_demo_run_step().
 * - Every created item is marked with LEKHONI_DEMO_META = "<kind>:<key>", so
 *   running the import again updates the demo items instead of duplicating
 *   them, and lekhoni_demo_remove() deletes exactly what the demo created.
 * - Pages that already exist with the same slug (home, about, contact, blog)
 *   are adopted: their template and fields are set ("site" option), but they
 *   are never deleted.
 * - Switching to another demo removes the installed one first ("replace"
 *   option, on by default), so the shop doesn't mix two demos.
 *
 * @package WordPress
 * @subpackage Lekhoni
 * @since Lekhoni 1.0
 */

const LEKHONI_DEMO_META    = '_lekhoni_demo';
const LEKHONI_DEMO_ADOPTED = '_lekhoni_demo_adopted';
const LEKHONI_DEMO_STATE   = 'lekhoni_demo_state';

/**
 * The available demo packages (demo/<id>/content.json): id => package, in the
 * order stationery, ecommerce, cosmetics, then any others.
 *
 * @return array<string, array>
 */
function lekhoni_demo_packages()
{
    static $packages = null;
    if (null !== $packages) {
        return $packages;
    }
    $packages = array();
    foreach ((array) glob(get_template_directory() . '/demo/*/content.json') as $file) {
        $data = json_decode((string) file_get_contents($file), true);
        $id   = basename(dirname($file));
        if (is_array($data) && preg_match('/^[a-z0-9-]+$/', $id)) {
            $packages[$id] = $data;
        }
    }
    $order = array_flip(array('stationery', 'ecommerce', 'cosmetics'));
    uksort(
        $packages,
        static function ($a, $b) use ($order) {
            return ($order[$a] ?? 99) <=> ($order[$b] ?? 99) ?: strcmp($a, $b);
        }
    );
    return $packages;
}

/**
 * One demo package, or null when it doesn't exist. Without an id: the
 * installed demo, else the first one.
 *
 * @param string|null $id Package id.
 * @return array|null
 */
function lekhoni_demo_package($id = null)
{
    $packages = lekhoni_demo_packages();
    if (null === $id) {
        $id = lekhoni_demo_installed() ?: (string) array_key_first($packages);
    }
    return $packages[$id] ?? null;
}

/**
 * The id of the demo that is installed, or '' (imports made before there
 * were several demos were the stationery one).
 *
 * @return string
 */
function lekhoni_demo_installed()
{
    $state = lekhoni_demo_state();
    if (! empty($state['demo'])) {
        return (string) $state['demo'];
    }
    return ! empty($state['imported']) ? 'stationery' : '';
}

/**
 * Import options with defaults.
 *
 * - demo:    package id (lekhoni_demo_packages()); unknown ids use the first one
 * - site:    use the demo homepage fields, Reading settings, menu locations and
 *            Theme Settings (overwrites those settings)
 * - store:   WooCommerce currency ৳, Bangladesh shipping zone, cash on delivery, demo coupon
 * - replace: remove an installed, different demo first
 *
 * @param array $options Raw options.
 * @return array{demo: string, site: bool, store: bool, replace: bool}
 */
function lekhoni_demo_options($options)
{
    $packages = lekhoni_demo_packages();
    $demo     = isset($options['demo']) ? sanitize_key((string) $options['demo']) : '';
    return array(
        'demo'    => isset($packages[$demo]) ? $demo : (string) array_key_first($packages),
        'site'    => ! empty($options['site']),
        'store'   => ! empty($options['store']),
        'replace' => ! empty($options['replace']),
    );
}

/**
 * Import state: what the demo created outside posts/terms/users (attribute
 * ids, shipping zone) and when it last ran.
 *
 * @return array
 */
function lekhoni_demo_state()
{
    $state = get_option(LEKHONI_DEMO_STATE, array());
    return is_array($state) ? $state : array();
}

/**
 * @param array $changes Keys to set.
 * @return void
 */
function lekhoni_demo_update_state($changes)
{
    update_option(LEKHONI_DEMO_STATE, array_merge(lekhoni_demo_state(), $changes), false);
}

// ---------------------------------------------------------------- Lookups

/**
 * Finds a demo item by "<kind>:<key>".
 *
 * @param string $kind     image, product, variation, post, page, coupon, menu_item, review, user, term, menu.
 * @param string $key      Item key from content.json.
 * @param string $taxonomy For terms.
 * @return int 0 when not imported yet.
 */
function lekhoni_demo_find($kind, $key, $taxonomy = '')
{
    $mark = $kind . ':' . $key;

    // Terms and users: created by the demo, else adopted (someone's own item
    // with the same slug/login, used but never deleted)
    if ('term' === $kind || 'menu' === $kind) {
        foreach (array(LEKHONI_DEMO_META, LEKHONI_DEMO_ADOPTED) as $meta) {
            // meta_query + orderby: WooCommerce replaces meta_key on its sortable
            // taxonomies (product_cat, pa_*) with its own "order" key
            $terms = get_terms(
                array(
                    'taxonomy'   => 'menu' === $kind ? 'nav_menu' : $taxonomy,
                    'hide_empty' => false,
                    'meta_query' => array(array('key' => $meta, 'value' => $mark)), // phpcs:ignore WordPress.DB.SlowDBQuery
                    'orderby'    => 'term_id',
                    'fields'     => 'ids',
                    'number'     => 1,
                )
            );
            if (is_array($terms) && $terms) {
                return (int) $terms[0];
            }
        }
        return 0;
    }

    if ('user' === $kind) {
        foreach (array(LEKHONI_DEMO_META, LEKHONI_DEMO_ADOPTED) as $meta) {
            $users = get_users(array('meta_key' => $meta, 'meta_value' => $mark, 'fields' => 'ID', 'number' => 1)); // phpcs:ignore WordPress.DB.SlowDBQuery
            if ($users) {
                return (int) $users[0];
            }
        }
        return 0;
    }

    if ('review' === $kind) {
        // type => review: WooCommerce hides product reviews from other comment queries
        $comments = get_comments(array('type' => 'review', 'meta_query' => array(array('key' => LEKHONI_DEMO_META, 'value' => $mark)), 'fields' => 'ids', 'number' => 1, 'status' => 'all')); // phpcs:ignore WordPress.DB.SlowDBQuery
        return $comments ? (int) $comments[0] : 0;
    }

    $posts = get_posts(
        array(
            'post_type'        => 'any',
            'post_status'      => 'any',
            'meta_key'         => LEKHONI_DEMO_META, // phpcs:ignore WordPress.DB.SlowDBQuery
            'meta_value'       => $mark, // phpcs:ignore WordPress.DB.SlowDBQuery
            'fields'           => 'ids',
            'numberposts'      => 1,
            'suppress_filters' => true,
        )
    );
    if ($posts) {
        return (int) $posts[0];
    }
    // 'any' leaves out attachments, variations and menu items
    $types = array('image' => 'attachment', 'variation' => 'product_variation', 'menu_item' => 'nav_menu_item', 'coupon' => 'shop_coupon');
    if (isset($types[$kind])) {
        $posts = get_posts(
            array(
                'post_type'        => $types[$kind],
                'post_status'      => 'any',
                'meta_key'         => LEKHONI_DEMO_META, // phpcs:ignore WordPress.DB.SlowDBQuery
                'meta_value'       => $mark, // phpcs:ignore WordPress.DB.SlowDBQuery
                'fields'           => 'ids',
                'numberposts'      => 1,
                'suppress_filters' => true,
            )
        );
        return $posts ? (int) $posts[0] : 0;
    }
    return 0;
}

/**
 * The page used for a demo page key: a demo page, else an adopted one.
 *
 * @param string $key Page key.
 * @return int
 */
function lekhoni_demo_page_id($key)
{
    $id = lekhoni_demo_find('page', $key);
    if ($id) {
        return $id;
    }
    $adopted = get_posts(array('post_type' => 'page', 'post_status' => 'any', 'meta_key' => LEKHONI_DEMO_ADOPTED, 'meta_value' => $key, 'fields' => 'ids', 'numberposts' => 1)); // phpcs:ignore WordPress.DB.SlowDBQuery
    return $adopted ? (int) $adopted[0] : 0;
}

/**
 * Replaces @image:/@product:/@page:/@term:/@date: references (and
 * {{image-url:key}} inside text) with real ids, dates and URLs.
 *
 * @param mixed $value Value from content.json.
 * @return mixed
 */
function lekhoni_demo_resolve($value)
{
    if (is_array($value)) {
        return array_map('lekhoni_demo_resolve', $value);
    }
    if (! is_string($value)) {
        return $value;
    }

    if (preg_match('/^@(image|product|page):(.+)$/', $value, $match)) {
        return 'page' === $match[1] ? lekhoni_demo_page_id($match[2]) : lekhoni_demo_find($match[1], $match[2]);
    }
    if (preg_match('/^@term:([a-z_]+):(.+)$/', $value, $match)) {
        return lekhoni_demo_find('term', $match[2], $match[1]);
    }
    if (preg_match('/^@date:\+(\d+)$/', $value, $match)) {
        return wp_date('Y-m-d H:i:s', time() + (int) $match[1] * DAY_IN_SECONDS);
    }

    return preg_replace_callback(
        '/\{\{image-url:([a-z0-9-]+)\}\}/',
        static function ($m) {
            $id = lekhoni_demo_find('image', $m[1]);
            return $id ? (string) wp_get_attachment_url($id) : '';
        },
        $value
    );
}

/**
 * Field name → field key for the top-level fields of an ACF group.
 *
 * @param string $group Field group key.
 * @return array<string, string>
 */
function lekhoni_demo_acf_keys($group)
{
    $keys = array();
    if (function_exists('acf_get_fields')) {
        foreach ((array) acf_get_fields($group) as $field) {
            if (! empty($field['name'])) {
                $keys[$field['name']] = $field['key'];
            }
        }
    }
    return $keys;
}

/**
 * Saves ACF values (by field name) on a post, user ('user_<id>') or 'option'.
 *
 * @param string     $group  Field group key.
 * @param array      $values Values keyed by field name (references allowed).
 * @param int|string $target ACF post id.
 * @return void
 */
function lekhoni_demo_update_fields($group, $values, $target)
{
    if (! function_exists('update_field')) {
        return;
    }
    $keys = lekhoni_demo_acf_keys($group);
    foreach (lekhoni_demo_resolve($values) as $name => $value) {
        if (isset($keys[$name])) {
            update_field($keys[$name], $value, $target);
        }
    }
}

// ---------------------------------------------------------------- Steps

/**
 * The import steps in order: name => label. Steps marked batched run several
 * times with an increasing offset.
 *
 * @return array<string, string>
 */
function lekhoni_demo_steps()
{
    return array(
        'cleanup'  => 'Removing the previous demo',
        'images'   => 'Images',
        'terms'    => 'Authors, categories, tags and attributes',
        'products' => 'Products, variations and reviews',
        'posts'    => 'Blog posts',
        'pages'    => 'Pages',
        'menus'    => 'Menus',
        'settings' => 'Settings',
        'finish'   => 'Finishing',
    );
}

/**
 * Runs one step (or one batch of it).
 *
 * @param string $step    Step name from lekhoni_demo_steps().
 * @param int    $offset  Batch offset.
 * @param array  $options lekhoni_demo_options().
 * @return array{done: bool, next: int, total: int, message: string}
 */
function lekhoni_demo_run_step($step, $offset, $options)
{
    $options = lekhoni_demo_options($options);
    $package = lekhoni_demo_package($options['demo']);
    if (! $package) {
        throw new RuntimeException('No demo package found in the theme\'s demo/ folder.');
    }
    if (function_exists('set_time_limit')) {
        set_time_limit(120); // phpcs:ignore Squiz.PHP.DiscouragedFunctions
    }
    // Imported content shouldn't send notification emails or pings
    add_filter('pre_option_default_pingback_flag', '__return_zero');
    add_filter('pre_option_comments_notify', '__return_zero');
    add_filter('pre_option_moderation_notify', '__return_zero');

    switch ($step) {
        case 'cleanup':
            return lekhoni_demo_step_cleanup($options);
        case 'images':
            return lekhoni_demo_step_images($package, $offset);
        case 'terms':
            return lekhoni_demo_step_terms($package);
        case 'products':
            return lekhoni_demo_step_products($package, $offset);
        case 'posts':
            return lekhoni_demo_step_posts($package);
        case 'pages':
            return lekhoni_demo_step_pages($package, $options);
        case 'menus':
            return lekhoni_demo_step_menus($package, $options);
        case 'settings':
            return lekhoni_demo_step_settings($package, $options);
        case 'finish':
            return lekhoni_demo_step_finish($options);
    }
    throw new InvalidArgumentException('Unknown step: ' . $step);
}

/**
 * @param string $message Message.
 * @param int    $total   Items handled.
 * @return array
 */
function lekhoni_demo_done($message, $total = 0)
{
    return array('done' => true, 'next' => 0, 'total' => $total, 'message' => $message);
}

/**
 * Removes the installed demo when another one is being imported ("replace").
 *
 * @param array $options Options.
 * @return array
 */
function lekhoni_demo_step_cleanup($options)
{
    $installed = lekhoni_demo_installed();
    if (! $options['replace'] || '' === $installed || $installed === $options['demo']) {
        return lekhoni_demo_done('Nothing to remove first');
    }
    lekhoni_demo_remove();
    return lekhoni_demo_done(sprintf('Removed the previous demo (%s)', $installed));
}

/**
 * Images, 6 per request (thumbnails take a moment each).
 *
 * @param array $package Package.
 * @param int   $offset  Offset.
 * @return array
 */
function lekhoni_demo_step_images($package, $offset)
{
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $images = $package['images'];
    $keys   = array_keys($images);
    $batch  = array_slice($keys, $offset, 6);
    $added  = 0;

    foreach ($batch as $key) {
        if (lekhoni_demo_find('image', $key)) {
            continue;
        }
        $image  = $images[$key];
        // "<id>/images/x.jpg" or "shared/x.jpg", relative to demo/
        $source = get_template_directory() . '/demo/' . ltrim(str_replace('..', '', (string) $image['file']), '/');
        if (! is_readable($source)) {
            throw new RuntimeException('Missing demo image: ' . $image['file']);
        }
        $upload = wp_upload_bits('lekhoni-' . basename($image['file']), null, (string) file_get_contents($source));
        if (! empty($upload['error'])) {
            throw new RuntimeException('Could not save ' . $image['file'] . ': ' . $upload['error']);
        }
        $type = wp_check_filetype($upload['file']);
        $id   = wp_insert_attachment(
            array(
                'post_mime_type' => $type['type'],
                'post_title'     => $image['alt'],
                'post_status'    => 'inherit',
            ),
            $upload['file']
        );
        if (is_wp_error($id) || ! $id) {
            throw new RuntimeException('Could not add ' . $image['file'] . ' to the media library.');
        }
        wp_update_attachment_metadata($id, wp_generate_attachment_metadata($id, $upload['file']));
        update_post_meta($id, '_wp_attachment_image_alt', $image['alt']);
        update_post_meta($id, LEKHONI_DEMO_META, 'image:' . $key);
        $added++;
    }

    $next = $offset + count($batch);
    $done = $next >= count($keys);
    return array(
        'done'    => $done,
        'next'    => $next,
        'total'   => count($keys),
        'message' => sprintf('Images %d of %d', $next, count($keys)) . ($added ? '' : ' (already there)'),
    );
}

/**
 * Creates or updates a term and marks it.
 *
 * @param string $taxonomy Taxonomy.
 * @param array  $term     { key, name, slug, description?, image? }.
 * @return int Term id.
 */
function lekhoni_demo_save_term($taxonomy, $term)
{
    $id   = lekhoni_demo_find('term', $term['key'], $taxonomy);
    $args = array('slug' => $term['slug'], 'description' => $term['description'] ?? '');

    if (! $id) {
        $existing = get_term_by('slug', $term['slug'], $taxonomy);
        if ($existing) {
            // Someone's own term with the same slug: use it, but never rename or delete it
            update_term_meta($existing->term_id, LEKHONI_DEMO_ADOPTED, 'term:' . $term['key']);
            return (int) $existing->term_id;
        }
        $result = wp_insert_term($term['name'], $taxonomy, $args);
        if (is_wp_error($result)) {
            throw new RuntimeException($taxonomy . ' "' . $term['name'] . '": ' . $result->get_error_message());
        }
        $id = (int) $result['term_id'];
        update_term_meta($id, LEKHONI_DEMO_META, 'term:' . $term['key']);
    } else {
        wp_update_term($id, $taxonomy, array_merge($args, array('name' => $term['name'])));
    }

    if (! empty($term['image'])) {
        update_term_meta($id, 'thumbnail_id', lekhoni_demo_find('image', $term['image']));
    }
    // inc/shop-fields.php: department icon and swatch colour
    if (! empty($term['icon'])) {
        update_term_meta($id, 'lekhoni_icon', sanitize_key($term['icon']));
    }
    if (! empty($term['swatch']) && preg_match('/^#[0-9a-f]{3,8}$/i', $term['swatch'])) {
        update_term_meta($id, 'lekhoni_swatch', $term['swatch']);
    }
    return $id;
}

/**
 * Authors, product/post categories, tags and product attributes.
 *
 * @param array $package Package.
 * @return array
 */
function lekhoni_demo_step_terms($package)
{
    // Authors (no login use intended: random passwords, example.com emails)
    foreach ($package['authors'] as $author) {
        $id = lekhoni_demo_find('user', $author['key']);
        if (! $id) {
            $existing = get_user_by('login', $author['login']) ?: get_user_by('email', $author['email']);
            if ($existing) {
                $id = $existing->ID;
                update_user_meta($id, LEKHONI_DEMO_ADOPTED, 'user:' . $author['key']);
            } else {
                $id = wp_insert_user(
                    array(
                        'user_login'   => $author['login'],
                        'user_email'   => $author['email'],
                        'user_pass'    => wp_generate_password(32, true, true),
                        'first_name'   => $author['first'],
                        'last_name'    => $author['last'],
                        'display_name' => $author['first'] . ' ' . $author['last'],
                        'description'  => $author['bio'],
                        'role'         => 'author',
                    )
                );
                if (is_wp_error($id)) {
                    throw new RuntimeException('Author ' . $author['login'] . ': ' . $id->get_error_message());
                }
                update_user_meta($id, LEKHONI_DEMO_META, 'user:' . $author['key']);
            }
        }
        lekhoni_demo_update_fields('group_lk_author_profile', $author['profile'], 'user_' . $id);
    }

    foreach ($package['terms'] as $taxonomy => $terms) {
        foreach ($terms as $term) {
            lekhoni_demo_save_term($taxonomy, $term);
        }
    }

    // Global product attributes (variations need taxonomy terms)
    $created = lekhoni_demo_state()['attributes'] ?? array();
    foreach ($package['attributes'] as $attribute) {
        $taxonomy = wc_attribute_taxonomy_name($attribute['slug']);
        if (! wc_attribute_taxonomy_id_by_name($attribute['slug'])) {
            $id = wc_create_attribute(array('name' => $attribute['name'], 'slug' => $attribute['slug'], 'type' => 'select', 'order_by' => 'menu_order'));
            if (is_wp_error($id)) {
                throw new RuntimeException('Attribute ' . $attribute['slug'] . ': ' . $id->get_error_message());
            }
            $created[] = (int) $id;
        }
        // WooCommerce registers attribute taxonomies on init; a new one needs it now
        if (! taxonomy_exists($taxonomy)) {
            register_taxonomy($taxonomy, array('product'), array('hierarchical' => false, 'show_ui' => false, 'query_var' => true, 'rewrite' => false));
        }
        foreach ($attribute['terms'] as $order => $term) {
            $id = lekhoni_demo_save_term($taxonomy, array('key' => $attribute['key'] . '-' . $term['slug'], 'name' => $term['name'], 'slug' => $term['slug'], 'swatch' => $term['swatch'] ?? ''));
            update_term_meta($id, 'order', $order);
        }
    }
    lekhoni_demo_update_state(array('attributes' => array_values(array_unique($created))));

    return lekhoni_demo_done('Authors, categories, tags and attributes');
}

/**
 * Products, 3 per request.
 *
 * @param array $package Package.
 * @param int   $offset  Offset.
 * @return array
 */
function lekhoni_demo_step_products($package, $offset)
{
    $products = $package['products'];
    $batch    = array_slice($products, $offset, 3);
    foreach ($batch as $data) {
        lekhoni_demo_save_product($data);
    }
    $next = $offset + count($batch);
    return array(
        'done'    => $next >= count($products),
        'next'    => $next,
        'total'   => count($products),
        'message' => sprintf('Products %d of %d', $next, count($products)),
    );
}

/**
 * Sets the SKU unless another (non-demo) product already uses it.
 *
 * @param WC_Product $product Product.
 * @param string     $sku     SKU.
 * @return void
 */
function lekhoni_demo_set_sku($product, $sku)
{
    try {
        $product->set_sku($sku);
    } catch (WC_Data_Exception $e) {
        // Duplicate SKU: leave it empty rather than fail the import
        unset($e);
    }
}

/**
 * Prices, sale end and stock on a product or variation.
 *
 * @param WC_Product $product Product or variation.
 * @param array      $data    { regular, sale?, saleDays?, stock }.
 * @return void
 */
function lekhoni_demo_set_prices($product, $data)
{
    $product->set_regular_price((string) $data['regular']);
    $product->set_sale_price(isset($data['sale']) ? (string) $data['sale'] : '');
    $product->set_date_on_sale_from(null);
    $product->set_date_on_sale_to(! empty($data['sale']) && ! empty($data['saleDays']) ? time() + (int) $data['saleDays'] * DAY_IN_SECONDS : null);
    $product->set_manage_stock(true);
    $product->set_stock_quantity((int) $data['stock']);
    $product->set_stock_status((int) $data['stock'] > 0 ? 'instock' : 'outofstock');
}

/**
 * Creates or updates one product with its variations and reviews.
 *
 * @param array $data Product from content.json.
 * @return int Product id.
 */
function lekhoni_demo_save_product($data)
{
    $variable = 'variable' === ($data['type'] ?? 'simple');
    $id       = lekhoni_demo_find('product', $data['key']);
    $product  = $id ? wc_get_product($id) : null;
    if (! $product || $product->is_type('variable') !== $variable) {
        $product = $variable ? new WC_Product_Variable($product ? $product->get_id() : 0) : new WC_Product_Simple($product ? $product->get_id() : 0);
    }

    $product->set_name($data['name']);
    $product->set_slug($data['slug']);
    $product->set_status('publish');
    $product->set_catalog_visibility('visible');
    $product->set_short_description($data['short']);
    $product->set_description($data['description']);
    $product->set_weight($data['weight'] ?? '');
    $product->set_featured(! empty($data['featured']));
    $product->set_reviews_allowed(true);
    $product->set_total_sales((int) ($data['sales'] ?? 0));
    $product->set_category_ids(array_filter(array_map(static fn ($key) => lekhoni_demo_find('term', $key, 'product_cat'), $data['categories'])));
    $product->set_tag_ids(array_filter(array_map(static fn ($key) => lekhoni_demo_find('term', $key, 'product_tag'), $data['tags'] ?? array())));
    $product->set_image_id(lekhoni_demo_find('image', $data['image']));
    $product->set_gallery_image_ids(array_filter(array_map(static fn ($key) => lekhoni_demo_find('image', $key), $data['gallery'] ?? array())));
    lekhoni_demo_set_sku($product, $data['sku']);
    if (! $variable) {
        lekhoni_demo_set_prices($product, $data);
    }

    // Variation attributes (global) first, then the spec table (custom, visible)
    $attributes = array();
    foreach ($data['attributes'] ?? array() as $position => $item) {
        $taxonomy  = wc_attribute_taxonomy_name($item['attribute']);
        $attribute = new WC_Product_Attribute();
        $attribute->set_id(wc_attribute_taxonomy_id_by_name($item['attribute']));
        $attribute->set_name($taxonomy);
        $attribute->set_options(array_filter(array_map(static fn ($slug) => (int) (get_term_by('slug', $slug, $taxonomy)->term_id ?? 0), $item['terms'])));
        $attribute->set_position($position);
        $attribute->set_visible(true);
        $attribute->set_variation(true);
        $attributes[] = $attribute;
    }
    foreach ($data['specs'] ?? array() as $position => $spec) {
        $attribute = new WC_Product_Attribute();
        $attribute->set_id(0);
        $attribute->set_name($spec[0]);
        $attribute->set_options(array($spec[1]));
        $attribute->set_position(count($data['attributes'] ?? array()) + $position);
        $attribute->set_visible(true);
        $attribute->set_variation(false);
        $attributes[] = $attribute;
    }
    $product->set_attributes($attributes);

    $id = $product->save();
    update_post_meta($id, LEKHONI_DEMO_META, 'product:' . $data['key']);

    if ($variable) {
        foreach ($data['variations'] as $index => $item) {
            $variation_id = lekhoni_demo_find('variation', $data['key'] . ':' . $index);
            $variation    = new WC_Product_Variation($variation_id);
            $variation->set_parent_id($id);
            $variation->set_status('publish');
            // Variation order (as when dragged in the editor); the storefront orders options by it
            $variation->set_menu_order($index);
            $values = array();
            foreach ($item['attributes'] as $attribute => $slug) {
                $values[wc_attribute_taxonomy_name($attribute)] = $slug;
            }
            $variation->set_attributes($values);
            lekhoni_demo_set_prices($variation, $item);
            lekhoni_demo_set_sku($variation, $item['sku']);
            $variation->set_image_id(! empty($item['image']) ? lekhoni_demo_find('image', $item['image']) : 0);
            $variation_id = $variation->save();
            update_post_meta($variation_id, LEKHONI_DEMO_META, 'variation:' . $data['key'] . ':' . $index);
        }
        WC_Product_Variable::sync($id);
    }

    // Approved, verified reviews; ratings are recalculated afterwards
    foreach ($data['reviews'] ?? array() as $index => $review) {
        $mark = $data['key'] . ':' . $index;
        if (lekhoni_demo_find('review', $mark)) {
            continue;
        }
        $comment_id = wp_insert_comment(
            array(
                'comment_post_ID'      => $id,
                'comment_author'       => $review['author'],
                'comment_author_email' => $review['email'],
                'comment_content'      => $review['text'],
                'comment_type'         => 'review',
                'comment_approved'     => 1,
                'comment_date'         => wp_date('Y-m-d H:i:s', time() - (int) $review['days'] * DAY_IN_SECONDS),
                'comment_date_gmt'     => gmdate('Y-m-d H:i:s', time() - (int) $review['days'] * DAY_IN_SECONDS),
            )
        );
        if ($comment_id) {
            update_comment_meta($comment_id, 'rating', (int) $review['rating']);
            update_comment_meta($comment_id, 'verified', 1);
            update_comment_meta($comment_id, LEKHONI_DEMO_META, 'review:' . $mark);
        }
    }
    WC_Comments::clear_transients($id);

    return $id;
}

/**
 * Blog posts.
 *
 * @param array $package Package.
 * @return array
 */
function lekhoni_demo_step_posts($package)
{
    foreach ($package['posts'] as $post) {
        $id     = lekhoni_demo_find('post', $post['key']);
        $author = lekhoni_demo_find('user', $post['author']);
        if (! $author) {
            $user   = get_user_by('login', $package['authors'][0]['login'] ?? '');
            $author = $user ? $user->ID : get_current_user_id();
        }
        $date = wp_date('Y-m-d H:i:s', time() - (int) $post['days'] * DAY_IN_SECONDS);
        $args = array(
            'ID'            => $id,
            'post_type'     => 'post',
            'post_status'   => 'publish',
            'post_title'    => $post['title'],
            'post_name'     => $post['slug'],
            'post_excerpt'  => $post['excerpt'],
            'post_content'  => lekhoni_demo_resolve($post['content']),
            'post_author'   => $author,
            'post_date'     => $date,
            'post_date_gmt' => get_gmt_from_date($date),
        );
        $id = wp_insert_post(wp_slash($args), true);
        if (is_wp_error($id)) {
            throw new RuntimeException('Post "' . $post['title'] . '": ' . $id->get_error_message());
        }
        update_post_meta($id, LEKHONI_DEMO_META, 'post:' . $post['key']);
        wp_set_post_terms($id, array(lekhoni_demo_find('term', $post['category'], 'category')), 'category');
        wp_set_post_terms($id, array_filter(array_map(static fn ($key) => lekhoni_demo_find('term', $key, 'post_tag'), $post['tags'] ?? array())), 'post_tag');
        set_post_thumbnail($id, lekhoni_demo_find('image', $post['image']));
        if (! empty($post['sticky'])) {
            stick_post($id);
        }
    }
    return lekhoni_demo_done(sprintf('%d blog posts', count($package['posts'])));
}

/**
 * Pages: creates the missing ones; adopts existing pages with the same slug.
 * Templates and ACF fields of adopted pages are only set with the "site"
 * option, so someone's own pages stay as they are otherwise.
 *
 * @param array $package Package.
 * @param array $options Options.
 * @return array
 */
function lekhoni_demo_step_pages($package, $options)
{
    foreach ($package['pages'] as $page) {
        $id      = lekhoni_demo_page_id($page['key']);
        $adopted = false;
        if (! $id) {
            $existing = get_page_by_path($page['slug']);
            if ($existing) {
                $id      = $existing->ID;
                $adopted = true;
                update_post_meta($id, LEKHONI_DEMO_ADOPTED, $page['key']);
            }
        } else {
            $adopted = (bool) get_post_meta($id, LEKHONI_DEMO_ADOPTED, true);
        }

        if (! $id) {
            $id = wp_insert_post(
                wp_slash(
                    array(
                        'post_type'    => 'page',
                        'post_status'  => 'publish',
                        'post_title'   => $page['title'],
                        'post_name'    => $page['slug'],
                        'post_content' => lekhoni_demo_resolve($page['content']),
                    )
                ),
                true
            );
            if (is_wp_error($id)) {
                throw new RuntimeException('Page "' . $page['title'] . '": ' . $id->get_error_message());
            }
            update_post_meta($id, LEKHONI_DEMO_META, 'page:' . $page['key']);
        } elseif ($adopted && '' === trim((string) get_post_field('post_content', $id)) && '' !== $page['content']) {
            wp_update_post(wp_slash(array('ID' => $id, 'post_content' => lekhoni_demo_resolve($page['content']))));
        }

        if ($adopted && ! $options['site']) {
            continue;
        }
        if ('' !== $page['template']) {
            update_post_meta($id, '_wp_page_template', $page['template']);
        }
        if (! empty($page['acf'])) {
            lekhoni_demo_update_fields($page['acfGroup'], $page['acf'], $id);
        }
    }
    return lekhoni_demo_done(sprintf('%d pages', count($package['pages'])));
}

/**
 * Menus; their locations are set with the "site" option.
 *
 * @param array $package Package.
 * @param array $options Options.
 * @return array
 */
function lekhoni_demo_step_menus($package, $options)
{
    $locations = get_theme_mod('nav_menu_locations', array());

    foreach ($package['menus'] as $menu) {
        $menu_id = lekhoni_demo_find('menu', $menu['key']);
        if (! $menu_id) {
            $name = $menu['name'];
            if (wp_get_nav_menu_object($name)) {
                $name .= ' (ডেমো)';
            }
            $menu_id = wp_create_nav_menu($name);
            if (is_wp_error($menu_id)) {
                throw new RuntimeException('Menu ' . $name . ': ' . $menu_id->get_error_message());
            }
            update_term_meta($menu_id, LEKHONI_DEMO_META, 'menu:' . $menu['key']);
        }

        // Rebuild the items so re-running reflects content.json exactly. All items
        // in the menu, including ones whose page or term no longer exists (those
        // are left out of wp_get_nav_menu_items).
        foreach ((array) get_objects_in_term($menu_id, 'nav_menu') as $item_id) {
            wp_delete_post((int) $item_id, true);
        }
        $position = 0;
        lekhoni_demo_add_menu_items($menu_id, $menu['items'], 0, $menu['key'], $position);

        if ($options['site']) {
            $locations[$menu['location']] = $menu_id;
        }
    }

    if ($options['site']) {
        set_theme_mod('nav_menu_locations', $locations);
    }
    return lekhoni_demo_done(sprintf('%d menus', count($package['menus'])));
}

/**
 * Adds items depth-first. Positions count across the whole menu, so each
 * submenu sits right after its parent.
 *
 * @param int    $menu_id  Menu.
 * @param array  $items    Items.
 * @param int    $parent   Parent item id.
 * @param string $prefix   Mark prefix.
 * @param int    $position Running position (by reference).
 * @return void
 */
function lekhoni_demo_add_menu_items($menu_id, $items, $parent, $prefix, &$position)
{
    foreach ($items as $index => $item) {
        $position++;
        $args = array('menu-item-status' => 'publish', 'menu-item-parent-id' => $parent, 'menu-item-position' => $position);
        if (! empty($item['page'])) {
            $args += array('menu-item-type' => 'post_type', 'menu-item-object' => 'page', 'menu-item-object-id' => lekhoni_demo_page_id($item['page']), 'menu-item-title' => $item['title'] ?? '');
        } elseif (! empty($item['term'])) {
            list($taxonomy, $key) = explode(':', $item['term'], 2);
            $args += array('menu-item-type' => 'taxonomy', 'menu-item-object' => $taxonomy, 'menu-item-object-id' => lekhoni_demo_find('term', $key, $taxonomy));
        } else {
            $args += array('menu-item-type' => 'custom', 'menu-item-url' => $item['url'], 'menu-item-title' => $item['title']);
        }
        $item_id = wp_update_nav_menu_item($menu_id, 0, $args);
        if (is_wp_error($item_id)) {
            continue;
        }
        update_post_meta($item_id, LEKHONI_DEMO_META, 'menu_item:' . $prefix . ':' . $parent . ':' . $index);
        if (! empty($item['children'])) {
            lekhoni_demo_add_menu_items($menu_id, $item['children'], $item_id, $prefix, $position);
        }
    }
}

/**
 * Reading + Theme Settings ("site") and WooCommerce store settings ("store").
 *
 * @param array $package Package.
 * @param array $options Options.
 * @return array
 */
function lekhoni_demo_step_settings($package, $options)
{
    $done = array();

    if ($options['site']) {
        $front = lekhoni_demo_page_id($package['reading']['front']);
        $posts = lekhoni_demo_page_id($package['reading']['posts']);
        if ($front) {
            update_option('show_on_front', 'page');
            update_option('page_on_front', $front);
        }
        if ($posts) {
            update_option('page_for_posts', $posts);
        }
        lekhoni_demo_update_fields('group_lk_theme_settings', $package['themeSettings'], 'option');
        $done[] = 'Reading and Theme Settings';
    }

    if ($options['store'] && class_exists('WooCommerce')) {
        $store = $package['store'];
        update_option('woocommerce_currency', $store['currency']);
        update_option('woocommerce_currency_pos', $store['currency_position']);
        update_option('woocommerce_price_num_decimals', $store['decimals']);
        update_option('woocommerce_price_thousand_sep', $store['thousand_separator']);
        update_option('woocommerce_price_decimal_sep', $store['decimal_separator']);
        update_option('woocommerce_default_country', $store['default_country']);

        // Shipping zone (created once; its id is remembered for removal)
        $zone_id = (int) (lekhoni_demo_state()['shipping_zone'] ?? 0);
        $zone    = $zone_id ? WC_Shipping_Zones::get_zone($zone_id) : false;
        if (! $zone || ! $zone->get_id()) {
            $zone = new WC_Shipping_Zone();
            $zone->set_zone_name($store['shipping_zone']['name']);
            $zone->add_location($store['shipping_zone']['country'], 'country');
            $zone->save();
            $flat = $zone->add_shipping_method('flat_rate');
            update_option('woocommerce_flat_rate_' . $flat . '_settings', array('title' => $store['shipping_zone']['flat_rate']['title'], 'tax_status' => 'none', 'cost' => (string) $store['shipping_zone']['flat_rate']['cost']));
            $free = $zone->add_shipping_method('free_shipping');
            update_option('woocommerce_free_shipping_' . $free . '_settings', array('title' => $store['shipping_zone']['free_shipping']['title'], 'requires' => 'min_amount', 'min_amount' => (string) $store['shipping_zone']['free_shipping']['min_amount'], 'ignore_discounts' => 'no'));
            lekhoni_demo_update_state(array('shipping_zone' => $zone->get_id()));
        }

        $cod = get_option('woocommerce_cod_settings', array());
        update_option('woocommerce_cod_settings', array_merge(is_array($cod) ? $cod : array(), array('enabled' => 'yes', 'title' => $store['cod']['title'], 'description' => $store['cod']['description'])));

        $coupon_id = lekhoni_demo_find('coupon', $store['coupon']['code']);
        if ($coupon_id || ! wc_get_coupon_id_by_code($store['coupon']['code'])) {
            $coupon = new WC_Coupon($coupon_id);
            $coupon->set_code($store['coupon']['code']);
            $coupon->set_discount_type($store['coupon']['type']);
            $coupon->set_amount($store['coupon']['amount']);
            $coupon->set_minimum_amount($store['coupon']['minimum']);
            $coupon->set_description($store['coupon']['description']);
            $coupon_id = $coupon->save();
            update_post_meta($coupon_id, LEKHONI_DEMO_META, 'coupon:' . $store['coupon']['code']);
        }
        $done[] = 'store settings (৳, shipping, cash on delivery, coupon)';
    }

    return lekhoni_demo_done($done ? 'Settings: ' . implode(', ', $done) : 'Settings left as they are');
}

/**
 * Clears caches and tells the storefront to refresh.
 *
 * @param array $options Options.
 * @return array
 */
function lekhoni_demo_step_finish($options)
{
    if (function_exists('wc_delete_product_transients')) {
        wc_delete_product_transients();
    }
    delete_transient('wc_term_counts');
    flush_rewrite_rules(false);
    lekhoni_demo_update_state(array('imported' => time(), 'demo' => $options['demo'], 'options' => $options));
    lekhoni_demo_revalidate_all();
    return lekhoni_demo_done('Done');
}

/**
 * Asks the storefront to drop every cached tag (see inc/headless.php).
 *
 * @return void
 */
function lekhoni_demo_revalidate_all()
{
    if (function_exists('lekhoni_revalidate')) {
        lekhoni_revalidate(array('theme-settings', 'menus', 'blog-settings', 'posts', 'pages', 'products', 'home-content', 'home-products', 'payment-gateways', 'search'));
    }
}

// ---------------------------------------------------------------- Removal

/**
 * Deletes everything the demo created: products (with variations and
 * reviews), posts, pages it created, images, coupon, menus, terms, authors,
 * attributes and the shipping zone. Adopted pages, your own content and
 * changed settings (currency, Theme Settings) are kept; a homepage or posts
 * page that pointed at a deleted page is reset.
 *
 * @return array<string, int> Counts per kind.
 */
function lekhoni_demo_remove()
{
    global $wpdb;
    $counts = array();

    $ids = $wpdb->get_col($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s", LEKHONI_DEMO_META)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    foreach (array_map('intval', $ids) as $id) {
        $type = get_post_type($id);
        if (! $type) {
            continue;
        }
        if ('product' === $type || 'product_variation' === $type) {
            $product = wc_get_product($id);
            if ($product) {
                $product->delete(true);
            }
        } elseif ('attachment' === $type) {
            wp_delete_attachment($id, true);
        } else {
            wp_delete_post($id, true);
        }
        $counts[$type] = ($counts[$type] ?? 0) + 1;
    }

    // Reviews on products that are not demo products (none expected) and leftovers
    $comments = get_comments(array('type' => 'review', 'meta_query' => array(array('key' => LEKHONI_DEMO_META, 'compare' => 'EXISTS')), 'fields' => 'ids', 'status' => 'all')); // phpcs:ignore WordPress.DB.SlowDBQuery
    foreach ($comments as $comment_id) {
        wp_delete_comment($comment_id, true);
    }

    $terms = get_terms(array('hide_empty' => false, 'taxonomy' => get_taxonomies(), 'orderby' => 'term_id', 'meta_query' => array(array('key' => LEKHONI_DEMO_META, 'compare' => 'EXISTS')))); // phpcs:ignore WordPress.DB.SlowDBQuery
    foreach (is_array($terms) ? $terms : array() as $term) {
        if ('nav_menu' === $term->taxonomy) {
            wp_delete_nav_menu($term->term_id);
        } else {
            wp_delete_term($term->term_id, $term->taxonomy);
        }
        $counts[$term->taxonomy] = ($counts[$term->taxonomy] ?? 0) + 1;
    }

    require_once ABSPATH . 'wp-admin/includes/user.php';
    foreach (get_users(array('meta_key' => LEKHONI_DEMO_META, 'fields' => 'ID')) as $user_id) { // phpcs:ignore WordPress.DB.SlowDBQuery
        wp_delete_user((int) $user_id);
        $counts['user'] = ($counts['user'] ?? 0) + 1;
    }

    $state = lekhoni_demo_state();
    foreach ($state['attributes'] ?? array() as $attribute_id) {
        wc_delete_attribute((int) $attribute_id);
    }
    if (! empty($state['shipping_zone'])) {
        $zone = WC_Shipping_Zones::get_zone((int) $state['shipping_zone']);
        if ($zone && $zone->get_id()) {
            $zone->delete();
        }
    }

    // Adopted pages, terms and authors stay; forget the adoption
    delete_post_meta_by_key(LEKHONI_DEMO_ADOPTED);
    delete_metadata('term', 0, LEKHONI_DEMO_ADOPTED, '', true);
    delete_metadata('user', 0, LEKHONI_DEMO_ADOPTED, '', true);

    // A homepage or posts page that no longer exists
    foreach (array('page_on_front', 'page_for_posts') as $option) {
        $page = (int) get_option($option);
        if ($page && ! get_post($page)) {
            update_option($option, 0);
        }
    }
    if ('page' === get_option('show_on_front') && ! (int) get_option('page_on_front')) {
        update_option('show_on_front', 'posts');
    }

    delete_option(LEKHONI_DEMO_STATE);
    if (function_exists('wc_delete_product_transients')) {
        wc_delete_product_transients();
    }
    lekhoni_demo_revalidate_all();
    return $counts;
}
