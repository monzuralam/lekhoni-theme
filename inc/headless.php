<?php

/**
 * Headless setup: send visitors of the WordPress front end to the Next.js site.
 *
 * Set the front-end URL in wp-config.php:
 *     define('LEKHONI_FRONTEND_URL', 'https://example.com');
 *
 * @package WordPress
 * @subpackage Lekhoni
 * @since Lekhoni 1.0
 */

/**
 * Returns the Next.js front-end URL without a trailing slash.
 *
 * @return string
 */
function lekhoni_frontend_url()
{
    $url = defined('LEKHONI_FRONTEND_URL') ? LEKHONI_FRONTEND_URL : 'http://localhost:3000';

    return untrailingslashit(apply_filters('lekhoni_frontend_url', $url));
}

/**
 * Tells the storefront to drop cached data (POST {LEKHONI_FRONTEND_URL}/api/revalidate).
 *
 * Needs the same secret in wp-config.php and the storefront's .env.local:
 *     define('LEKHONI_REVALIDATE_SECRET', '…');   // wp-config.php
 *     LEKHONI_REVALIDATE_SECRET=…                 // lekhoni/.env.local
 * Without it nothing is sent and the storefront refreshes on its own within
 * a few minutes. The request doesn't wait for an answer, so saving stays fast.
 *
 * @param string[] $tags Cache tags, e.g. 'theme-settings', 'menus'.
 * @return void
 */
function lekhoni_revalidate($tags)
{
    if (! defined('LEKHONI_REVALIDATE_SECRET') || '' === LEKHONI_REVALIDATE_SECRET) {
        return;
    }

    wp_remote_post(
        lekhoni_frontend_url() . '/api/revalidate',
        array(
            'blocking' => false,
            'timeout'  => 3,
            'headers'  => array(
                'Content-Type'                => 'application/json',
                'X-Lekhoni-Revalidate-Secret' => LEKHONI_REVALIDATE_SECRET,
            ),
            'body'     => wp_json_encode(array('tags' => array_values(array_unique($tags)))),
        )
    );
}

/**
 * Refreshes the storefront's header and footer when Theme Settings are saved.
 *
 * @param int|string $post_id ACF post id; 'options' for options pages.
 * @return void
 */
function lekhoni_revalidate_theme_settings($post_id)
{
    if ('options' === $post_id) {
        lekhoni_revalidate(array('theme-settings'));
    }
}
add_action('acf/save_post', 'lekhoni_revalidate_theme_settings', 20);

/**
 * Refreshes the storefront's menus when a menu or the menu locations change.
 *
 * @return void
 */
function lekhoni_revalidate_menus()
{
    lekhoni_revalidate(array('menus'));
}
add_action('wp_update_nav_menu', 'lekhoni_revalidate_menus');
add_action('wp_delete_nav_menu', 'lekhoni_revalidate_menus');
add_action('update_option_theme_mods_' . get_option('stylesheet'), 'lekhoni_revalidate_menus');

/**
 * Refreshes the logo text, tagline and favicon when Settings → General changes.
 *
 * @return void
 */
function lekhoni_revalidate_site_identity()
{
    lekhoni_revalidate(array('theme-settings', 'blog-settings'));
}
foreach (array('blogname', 'blogdescription', 'site_icon') as $lekhoni_option) {
    add_action("update_option_{$lekhoni_option}", 'lekhoni_revalidate_site_identity');
}

/**
 * GraphQL field `siteIcon { small large }`: the Site Icon (Settings →
 * General) for the storefront's favicon; null when none is set.
 *
 * @return void
 */
function lekhoni_register_site_icon_field()
{
    register_graphql_object_type(
        'LekhoniSiteIcon',
        array(
            'description' => 'The Site Icon in two sizes.',
            'fields'      => array(
                'small' => array('type' => 'String', 'description' => '32 x 32 (browser tab).'),
                'large' => array('type' => 'String', 'description' => '180 x 180 (home screen).'),
            ),
        )
    );

    register_graphql_field(
        'RootQuery',
        'siteIcon',
        array(
            'type'        => 'LekhoniSiteIcon',
            'description' => 'The Site Icon from Settings → General.',
            'resolve'     => function () {
                if (! has_site_icon()) {
                    return null;
                }
                return array(
                    'small' => get_site_icon_url(32),
                    'large' => get_site_icon_url(180),
                );
            },
        )
    );
}
add_action('graphql_register_types', 'lekhoni_register_site_icon_field');

/**
 * Points the admin bar "Visit Site" links to the front end.
 *
 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
 * @return void
 */
function lekhoni_admin_bar_frontend_links($wp_admin_bar)
{
    foreach (array('site-name', 'view-site') as $id) {
        $node = $wp_admin_bar->get_node($id);

        if ($node) {
            $node->href = lekhoni_frontend_url() . '/';
            $wp_admin_bar->add_node($node);
        }
    }
}
add_action('admin_bar_menu', 'lekhoni_admin_bar_frontend_links', 100);

/**
 * Redirects front-end requests to the same path on the Next.js site.
 *
 * Previews and the Customizer stay on WordPress.
 *
 * @return void
 */
function lekhoni_redirect_to_frontend()
{
    if (is_preview() || is_customize_preview() || wp_doing_ajax() || wp_doing_cron()) {
        return;
    }

    if (defined('REST_REQUEST') || defined('GRAPHQL_REQUEST') || is_feed() || is_robots()) {
        return;
    }

    $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '/';

    wp_redirect(lekhoni_frontend_url() . $request_uri, 302, 'Lekhoni');
    exit;
}
add_action('template_redirect', 'lekhoni_redirect_to_frontend', 1);

/**
 * Points the password reset email at the Next.js reset page.
 *
 * WordPress links to wp-login.php?action=rp&key=…&login=…; customers reset
 * their password on the front end instead (/reset-password?key=…&login=…).
 *
 * @param string  $message    Email body.
 * @param string  $key        Password reset key.
 * @param string  $user_login Username.
 * @return string
 */
function lekhoni_password_reset_message($message, $key, $user_login)
{
    $wp_link = network_site_url('wp-login.php?action=rp&key=' . $key . '&login=' . rawurlencode($user_login), 'login');
    $front_link = lekhoni_frontend_url() . '/reset-password?' . http_build_query(array(
        'key'   => $key,
        'login' => $user_login,
    ));

    return str_replace($wp_link, $front_link, $message);
}
add_filter('retrieve_password_message', 'lekhoni_password_reset_message', 10, 3);

/**
 * Points WooCommerce's "lost password" endpoint (used by its reset email,
 * which adds ?key=…&id=…&login=…) at the Next.js reset page.
 *
 * @param string $url      Endpoint URL.
 * @param string $endpoint Endpoint slug.
 * @return string
 */
function lekhoni_lost_password_endpoint($url, $endpoint)
{
    return 'lost-password' === $endpoint ? lekhoni_frontend_url() . '/reset-password' : $url;
}
add_filter('woocommerce_get_endpoint_url', 'lekhoni_lost_password_endpoint', 10, 2);
