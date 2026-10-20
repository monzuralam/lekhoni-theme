<?php

/**
 * Routine bundles (Home – Cosmetics → Routines): when the cart holds every
 * product of a routine, the cart gets a negative fee of the bundle discount
 * on one set of those products, e.g. "রুটিন ছাড়: সকালের রুটিন −৳৫২৫".
 *
 * Routines are read from published pages using the "Home – Cosmetics"
 * template. A product shared by two routines counts for both only when the
 * cart has enough of it (one per routine).
 *
 * @package WordPress
 * @subpackage Lekhoni
 * @since Lekhoni 1.0
 */

const LEKHONI_COSMETICS_TEMPLATE = 'templates/template-home-cosmetics.php';

/**
 * The routines with their discount, from the cosmetics home pages.
 *
 * @return array<int, array{name: string, discount: float, products: int[]}>
 */
function lekhoni_routines()
{
    static $routines = null;
    if (null !== $routines) {
        return $routines;
    }
    $routines = array();
    if (! function_exists('get_field')) {
        return $routines;
    }

    $pages = get_posts(
        array(
            'post_type'   => 'page',
            'post_status' => 'publish',
            'numberposts' => 5,
            'fields'      => 'ids',
            'meta_key'    => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
            'meta_value'  => LEKHONI_COSMETICS_TEMPLATE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
        )
    );
    foreach ($pages as $page_id) {
        // Only an explicit "off" hides the section (a never-saved switch is on)
        if ('0' === get_post_meta($page_id, 'hb_routines_section_enabled', true)) {
            continue;
        }
        $section = get_field('hb_routines_section', $page_id);
        if (! is_array($section) || empty($section['routines'])) {
            continue;
        }
        $discount = isset($section['discount']) && '' !== $section['discount'] && null !== $section['discount'] ? (float) $section['discount'] : 15.0;
        if ($discount <= 0) {
            continue;
        }
        foreach ((array) $section['routines'] as $routine) {
            $products = array();
            foreach ((array) ($routine['steps'] ?? array()) as $step) {
                $id = is_array($step['product']) ? (int) reset($step['product']) : (int) $step['product'];
                if ($id) {
                    $products[] = $id;
                }
            }
            if (count($products) >= 2) {
                $routines[] = array(
                    'name'     => (string) $routine['name'],
                    'discount' => min(50.0, $discount),
                    'products' => $products,
                );
            }
        }
    }

    return $routines;
}

/**
 * Adds a negative fee per complete routine in the cart.
 *
 * @param WC_Cart $cart Cart.
 * @return void
 */
function lekhoni_routine_fees($cart)
{
    if (is_admin() && ! wp_doing_ajax()) {
        return;
    }
    $routines = lekhoni_routines();
    if (! $routines) {
        return;
    }

    // Units and unit price per product (variations count for their parent)
    $stock = array();
    foreach ($cart->get_cart() as $item) {
        $id = (int) ($item['product_id'] ?? 0);
        if (! $id || empty($item['data'])) {
            continue;
        }
        if (! isset($stock[$id])) {
            $stock[$id] = array('qty' => 0, 'price' => (float) $item['data']->get_price());
        }
        $stock[$id]['qty'] += (int) $item['quantity'];
    }

    foreach ($routines as $routine) {
        $needed = array_count_values($routine['products']);
        foreach ($needed as $id => $count) {
            if (empty($stock[$id]) || $stock[$id]['qty'] < $count) {
                continue 2;
            }
        }
        $total = 0.0;
        foreach ($needed as $id => $count) {
            $stock[$id]['qty'] -= $count;
            $total             += $stock[$id]['price'] * $count;
        }
        $amount = round($total * $routine['discount'] / 100, wc_get_price_decimals());
        if ($amount > 0) {
            /* translators: %s: routine name. */
            $cart->add_fee(sprintf('রুটিন ছাড়: %s', $routine['name']), -$amount, false);
        }
    }
}
add_action('woocommerce_cart_calculate_fees', 'lekhoni_routine_fees');
