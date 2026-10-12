<?php

/**
 * Newsletter sign-ups, stored in wp-admin (there is no email provider).
 *
 * - Post type `lk_subscriber` ("Subscribers" in the admin menu) with a badge
 *   for sign-ups since you last opened the list, list columns (email, date,
 *   source) and an "Export CSV" button.
 * - GraphQL mutation `subscribeNewsletter(input: { email, source })` used by
 *   the storefront's /api/subscribe route.
 *
 * @package WordPress
 * @subpackage Lekhoni
 * @since Lekhoni 1.0
 */

const LEKHONI_SUBSCRIBER_TYPE = 'lk_subscriber';

/**
 * Registers the private post type that stores subscribers.
 *
 * @return void
 */
function lekhoni_newsletter_register_type()
{
    register_post_type(
        LEKHONI_SUBSCRIBER_TYPE,
        array(
            'labels'              => array(
                'name'               => 'Subscribers',
                'singular_name'      => 'Subscriber',
                'menu_name'          => 'Subscribers',
                'all_items'          => 'All Subscribers',
                'edit_item'          => 'Subscriber',
                'search_items'       => 'Search Subscribers',
                'not_found'          => 'No subscribers yet.',
                'not_found_in_trash' => 'No subscribers in Trash.',
            ),
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_rest'        => false,
            'show_in_graphql'     => false,
            'exclude_from_search' => true,
            'menu_position'       => 27,
            'menu_icon'           => 'dashicons-groups',
            'supports'            => array('title'),
            'map_meta_cap'        => true,
            // Subscribers only come from the form: no "Add New"
            'capabilities'        => array('create_posts' => 'do_not_allow'),
        )
    );
}
add_action('init', 'lekhoni_newsletter_register_type');

/**
 * Normalises an email for storage and duplicate checks.
 *
 * @param string $email Raw email.
 * @return string
 */
function lekhoni_newsletter_normalize($email)
{
    return strtolower(trim(sanitize_email(wp_unslash($email))));
}

/**
 * Id of the (non-trashed) subscriber with this email.
 *
 * @param string $email Normalised email.
 * @return int Post id, or 0.
 */
function lekhoni_newsletter_find($email)
{
    $ids = get_posts(
        array(
            'post_type'      => LEKHONI_SUBSCRIBER_TYPE,
            'post_status'    => array('publish', 'draft', 'pending', 'private'),
            'meta_key'       => '_lk_email',
            'meta_value'     => $email,
            'fields'         => 'ids',
            'posts_per_page' => 1,
            'no_found_rows'  => true,
        )
    );
    return $ids ? (int) $ids[0] : 0;
}

/**
 * Number of subscribers that joined after the current user last opened the list.
 *
 * @return int
 */
function lekhoni_newsletter_new_count()
{
    $seen  = (int) get_user_meta(get_current_user_id(), 'lk_subscribers_seen', true);
    $query = new WP_Query(
        array(
            'post_type'      => LEKHONI_SUBSCRIBER_TYPE,
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'posts_per_page' => 1,
            'no_found_rows'  => false,
            'date_query'     => $seen ? array(array('column' => 'post_date_gmt', 'after' => gmdate('Y-m-d H:i:s', $seen))) : array(),
        )
    );
    return (int) $query->found_posts;
}

/**
 * Adds the "new subscribers" badge to the admin menu item.
 *
 * @return void
 */
function lekhoni_newsletter_menu_badge()
{
    global $menu;
    if (! is_array($menu)) {
        return;
    }
    $count = lekhoni_newsletter_new_count();
    if (! $count) {
        return;
    }
    foreach ($menu as $index => $item) {
        if (isset($item[2]) && 'edit.php?post_type=' . LEKHONI_SUBSCRIBER_TYPE === $item[2]) {
            $menu[$index][0] .= sprintf(' <span class="awaiting-mod count-%1$d" title="New since your last visit"><span class="pending-count">%1$d</span></span>', $count);
        }
    }
}
add_action('admin_menu', 'lekhoni_newsletter_menu_badge', 99);

/**
 * Opening the list clears the badge for the current user.
 *
 * @return void
 */
function lekhoni_newsletter_mark_seen()
{
    $screen = get_current_screen();
    if ($screen && 'edit-' . LEKHONI_SUBSCRIBER_TYPE === $screen->id) {
        update_user_meta(get_current_user_id(), 'lk_subscribers_seen', time());
    }
}
add_action('current_screen', 'lekhoni_newsletter_mark_seen');

/**
 * List table columns.
 *
 * @param array $columns Default columns.
 * @return array
 */
function lekhoni_newsletter_columns($columns)
{
    return array(
        'cb'        => $columns['cb'],
        'title'     => 'Email',
        'lk_source' => 'Source',
        'date'      => 'Subscribed',
    );
}
add_filter('manage_' . LEKHONI_SUBSCRIBER_TYPE . '_posts_columns', 'lekhoni_newsletter_columns');

/**
 * List table cells.
 *
 * @param string $column  Column key.
 * @param int    $post_id Subscriber id.
 * @return void
 */
function lekhoni_newsletter_column_content($column, $post_id)
{
    if ('lk_source' === $column) {
        $source = get_post_meta($post_id, '_lk_source', true);
        echo $source ? esc_html($source) : '&mdash;';
    }
}
add_action('manage_' . LEKHONI_SUBSCRIBER_TYPE . '_posts_custom_column', 'lekhoni_newsletter_column_content', 10, 2);

/**
 * "Export CSV" button next to the list's filters.
 *
 * @param string $which top or bottom.
 * @return void
 */
function lekhoni_newsletter_export_button($which)
{
    global $typenow;
    if ('top' !== $which || LEKHONI_SUBSCRIBER_TYPE !== $typenow) {
        return;
    }
    $url = wp_nonce_url(admin_url('admin-post.php?action=lekhoni_newsletter_export'), 'lekhoni_newsletter_export');
    printf('<div class="alignleft actions"><a class="button" href="%s">Export CSV</a></div>', esc_url($url));
}
add_action('manage_posts_extra_tablenav', 'lekhoni_newsletter_export_button');

/**
 * The subscriber list as CSV (email, subscribed date in the site timezone, source).
 *
 * @return string
 */
function lekhoni_newsletter_csv()
{
    $ids = get_posts(
        array(
            'post_type'      => LEKHONI_SUBSCRIBER_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'fields'         => 'ids',
        )
    );

    // Spreadsheet apps run cells starting with these as formulas
    $cell = function ($value) {
        $value = (string) $value;
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    };

    $out = fopen('php://temp', 'r+');
    fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads Bangla correctly
    fputcsv($out, array('email', 'subscribed', 'source'), ',', '"', '\\');
    foreach ($ids as $id) {
        fputcsv(
            $out,
            array(
                $cell(get_post_meta($id, '_lk_email', true)),
                get_the_date('Y-m-d H:i:s', $id),
                $cell(get_post_meta($id, '_lk_source', true)),
            ),
            ',',
            '"',
            '\\'
        );
    }
    rewind($out);
    $csv = stream_get_contents($out);
    fclose($out);
    return $csv;
}

/**
 * Downloads the CSV (admin-post.php?action=lekhoni_newsletter_export).
 *
 * @return void
 */
function lekhoni_newsletter_export()
{
    $type = get_post_type_object(LEKHONI_SUBSCRIBER_TYPE);
    if (! $type || ! current_user_can($type->cap->edit_posts)) {
        wp_die('You are not allowed to export subscribers.', 403);
    }
    check_admin_referer('lekhoni_newsletter_export');

    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="subscribers-' . gmdate('Y-m-d') . '.csv"');
    echo lekhoni_newsletter_csv(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    exit;
}
add_action('admin_post_lekhoni_newsletter_export', 'lekhoni_newsletter_export');

/**
 * Detail box on the subscriber screen.
 *
 * @return void
 */
function lekhoni_newsletter_meta_boxes()
{
    add_meta_box('lk_subscriber', 'Subscriber', 'lekhoni_newsletter_render_box', LEKHONI_SUBSCRIBER_TYPE, 'normal', 'high');
    remove_meta_box('slugdiv', LEKHONI_SUBSCRIBER_TYPE, 'normal');
}
add_action('add_meta_boxes', 'lekhoni_newsletter_meta_boxes');

/**
 * Subscriber detail box.
 *
 * @param WP_Post $post Subscriber.
 * @return void
 */
function lekhoni_newsletter_render_box($post)
{
    $email  = get_post_meta($post->ID, '_lk_email', true);
    $source = get_post_meta($post->ID, '_lk_source', true);
    ?>
    <table class="form-table" role="presentation">
        <tr><th>Email</th><td><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></td></tr>
        <tr><th>Subscribed</th><td><?php echo esc_html(get_the_date('j F Y, g:i a', $post)); ?></td></tr>
        <tr><th>Source</th><td><?php echo $source ? esc_html($source) : '&mdash;'; ?></td></tr>
    </table>
    <p class="description">To unsubscribe someone, move them to the Trash.</p>
    <?php
}

/**
 * The title (the email) is read-only on the subscriber screen.
 *
 * @return void
 */
function lekhoni_newsletter_admin_styles()
{
    $screen = get_current_screen();
    if ($screen && LEKHONI_SUBSCRIBER_TYPE === $screen->post_type) {
        echo '<style>#titlediv #title { pointer-events: none; background: #f6f7f7; }</style>';
    }
}
add_action('admin_head', 'lekhoni_newsletter_admin_styles');

/**
 * Validates and stores a sign-up.
 *
 * An email that is already subscribed returns the same success as a new one,
 * so the form can't be used to find out who is subscribed.
 *
 * @param array $input email, source (optional: where the form was, e.g. "home").
 * @return array{success: bool, code: string}
 */
function lekhoni_newsletter_subscribe($input)
{
    $email  = lekhoni_newsletter_normalize($input['email'] ?? '');
    $source = sanitize_key(substr((string) ($input['source'] ?? ''), 0, 40));

    if (! is_email($email) || strlen($email) > 254) {
        return array('success' => false, 'code' => 'invalid_email');
    }

    // Throttle (before the duplicate check, so both cases answer alike):
    // one sign-up per email per minute, and at most 60 an hour overall
    $email_key = 'lk_subscribe_' . md5($email);
    if (get_transient($email_key)) {
        return array('success' => false, 'code' => 'too_many');
    }
    $hourly = (int) get_transient('lk_subscribe_hourly');
    if ($hourly >= 60) {
        return array('success' => false, 'code' => 'too_many');
    }
    set_transient($email_key, 1, MINUTE_IN_SECONDS);
    set_transient('lk_subscribe_hourly', $hourly + 1, HOUR_IN_SECONDS);

    if (lekhoni_newsletter_find($email)) {
        return array('success' => true, 'code' => 'subscribed');
    }

    $post_id = wp_insert_post(
        array(
            'post_type'   => LEKHONI_SUBSCRIBER_TYPE,
            'post_status' => 'publish',
            'post_title'  => $email,
            'meta_input'  => array(
                '_lk_email'  => $email,
                '_lk_source' => $source,
            ),
        ),
        true
    );
    if (is_wp_error($post_id)) {
        return array('success' => false, 'code' => 'server_error');
    }

    return array('success' => true, 'code' => 'subscribed');
}

/**
 * GraphQL: subscribeNewsletter(input: { email, source }) { success code }
 *
 * `code` is one of: subscribed, invalid_email, too_many, server_error.
 * The storefront maps it to Bangla.
 *
 * @return void
 */
function lekhoni_newsletter_register_mutation()
{
    if (! function_exists('register_graphql_mutation')) {
        return;
    }
    register_graphql_mutation(
        'subscribeNewsletter',
        array(
            'inputFields'         => array(
                'email'  => array('type' => array('non_null' => 'String')),
                'source' => array(
                    'type'        => 'String',
                    'description' => 'Where the form was shown, e.g. "home" or "blog".',
                ),
            ),
            'outputFields'        => array(
                'success' => array('type' => array('non_null' => 'Boolean')),
                'code'    => array('type' => array('non_null' => 'String')),
            ),
            'mutateAndGetPayload' => function ($input) {
                return lekhoni_newsletter_subscribe($input);
            },
        )
    );
}
add_action('graphql_register_types', 'lekhoni_newsletter_register_mutation');
