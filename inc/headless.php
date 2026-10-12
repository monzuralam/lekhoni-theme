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
