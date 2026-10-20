<?php

/**
 * Functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package WordPress
 * @subpackage Lekhoni
 * @since Lekhoni 1.0
 */
if (! function_exists('lekhoni_setup')) {
    /**
     * Sets up theme defaults and registers support for various WordPress features.
     *
     * Note that this function is hooked into the after_setup_theme hook, which
     * runs before the init hook. The init hook is too late for some features, such
     * as indicating support for post thumbnails.
     *
     * @since Lekhoni 1.0
     *
     * @return void
     */
    function lekhoni_setup()
    {

        // Add default posts and comments RSS feed links to head.
        add_theme_support('automatic-feed-links');

        /*
		 * Let WordPress manage the document title.
		 * This theme does not use a hard-coded <title> tag in the document head,
		 * WordPress will provide it for us.
		 */
        add_theme_support('title-tag');

        /**
         * Add post-formats support.
         */
        add_theme_support(
            'post-formats',
            array(
                'link',
                'aside',
                'gallery',
                'image',
                'quote',
                'status',
                'video',
                'audio',
                'chat',
            )
        );

        /*
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		 */
        add_theme_support('post-thumbnails');
        set_post_thumbnail_size(1568, 9999);

        register_nav_menus(
            array(
                'primary' => esc_html__('Primary menu', 'lekhoni'),
                'footer'  => esc_html__('Footer menu', 'lekhoni'),
            )
        );

        /*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
        add_theme_support(
            'html5',
            array(
                'comment-form',
                'comment-list',
                'gallery',
                'caption',
                'style',
                'script',
                'navigation-widgets',
            )
        );

        /*
		 * Add support for core custom logo.
		 *
		 * @link https://codex.wordpress.org/Theme_Logo
		 */
        $logo_width  = 300;
        $logo_height = 100;

        add_theme_support(
            'custom-logo',
            array(
                'height'               => $logo_height,
                'width'                => $logo_width,
                'flex-width'           => true,
                'flex-height'          => true,
                'unlink-homepage-logo' => true,
            )
        );

        // Add theme support for selective refresh for widgets.
        add_theme_support('customize-selective-refresh-widgets');

        // Remove feed icon link from legacy RSS widget.
        add_filter('rss_widget_feed_link', '__return_empty_string');
    }
}
add_action('after_setup_theme', 'lekhoni_setup');

/**
 * Registers widget area.
 *
 * @since Lekhoni 1.0
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 *
 * @return void
 */
function Lekhoni_widgets_init()
{

    register_sidebar(
        array(
            'name'          => esc_html__('Footer', 'lekhoni'),
            'id'            => 'sidebar-1',
            'description'   => esc_html__('Add widgets here to appear in your footer.', 'lekhoni'),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        )
    );
}
add_action('widgets_init', 'Lekhoni_widgets_init');

/**
 * Enqueues scripts and styles.
 *
 * @since Lekhoni 1.0
 *
 * @return void
 */
function lekhoni_scripts()
{
    // The standard stylesheet.
    wp_enqueue_style('lekhoni-style', get_template_directory_uri() . '/style.css', array(), wp_get_theme()->get('Version'));

    // RTL styles.
    wp_style_add_data('lekhoni-style', 'rtl', 'replace');
}
add_action('wp_enqueue_scripts', 'lekhoni_scripts');

/**
 * Enable SVG uploads for authorized users.
 * 
 * @param $mimes array
 */
function mytheme_allow_svg_uploads($mimes)
{
    if (!current_user_can('manage_options')) {
        return $mimes;
    }

    $mimes['svg'] = 'image/svg+xml';

    return $mimes;
}
add_filter('upload_mimes', 'mytheme_allow_svg_uploads');

include_once get_template_directory() . '/inc/install.php';
include_once get_template_directory() . '/inc/acf-helpers.php';
include_once get_template_directory() . '/inc/acf-settings.php';
include_once get_template_directory() . '/inc/acf-home.php';
include_once get_template_directory() . '/inc/acf-home-ecommerce.php';
include_once get_template_directory() . '/inc/acf-home-cosmetics.php';
include_once get_template_directory() . '/inc/shop-fields.php';
include_once get_template_directory() . '/inc/routines.php';
include_once get_template_directory() . '/inc/acf-about.php';
include_once get_template_directory() . '/inc/acf-contact.php';
include_once get_template_directory() . '/inc/acf-author.php';
include_once get_template_directory() . '/inc/newsletter.php';
include_once get_template_directory() . '/inc/contact.php';
include_once get_template_directory() . '/inc/payments.php';
include_once get_template_directory() . '/inc/email-login.php';
include_once get_template_directory() . '/inc/demo-import.php';
include_once get_template_directory() . '/inc/demo-import-admin.php';
include_once get_template_directory() . '/inc/demo-import-cli.php';
include_once get_template_directory() . '/inc/preview.php';
include_once get_template_directory() . '/inc/headless.php';
