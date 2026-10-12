<?php

/**
 * Contact form: stores every submission in wp-admin and emails it.
 *
 * - Post type `lk_contact_message` ("Contact Messages" in the admin menu) with
 *   an unread badge, list columns, a detail box and a Dashboard widget.
 * - GraphQL mutation `sendContactMessage(input: { name, email, subject, message })`
 *   used by the storefront's /contact page.
 *
 * @package WordPress
 * @subpackage Lekhoni
 * @since Lekhoni 1.0
 */

const LEKHONI_CONTACT_TYPE = 'lk_contact_message';

/**
 * Registers the private post type that stores messages.
 *
 * @return void
 */
function lekhoni_contact_register_type()
{
    register_post_type(
        LEKHONI_CONTACT_TYPE,
        array(
            'labels'              => array(
                'name'               => 'Contact Messages',
                'singular_name'      => 'Contact Message',
                'menu_name'          => 'Contact Messages',
                'all_items'          => 'All Messages',
                'edit_item'          => 'Contact Message',
                'search_items'       => 'Search Messages',
                'not_found'          => 'No messages yet.',
                'not_found_in_trash' => 'No messages in Trash.',
            ),
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_rest'        => false,
            'show_in_graphql'     => false,
            'exclude_from_search' => true,
            'menu_position'       => 26,
            'menu_icon'           => 'dashicons-email-alt',
            'supports'            => array('title'),
            'map_meta_cap'        => true,
            // Messages only come from the form: no "Add New"
            'capabilities'        => array('create_posts' => 'do_not_allow'),
        )
    );
}
add_action('init', 'lekhoni_contact_register_type');

/**
 * Number of unread messages.
 *
 * @return int
 */
function lekhoni_contact_unread_count()
{
    $query = new WP_Query(
        array(
            'post_type'      => LEKHONI_CONTACT_TYPE,
            'post_status'    => 'publish',
            'meta_key'       => '_lk_read',
            'meta_value'     => '0',
            'fields'         => 'ids',
            'posts_per_page' => 1,
            'no_found_rows'  => false,
        )
    );
    return (int) $query->found_posts;
}

/**
 * Adds the unread count badge to the admin menu item.
 *
 * @return void
 */
function lekhoni_contact_menu_badge()
{
    global $menu;
    $unread = lekhoni_contact_unread_count();
    if (! $unread || ! is_array($menu)) {
        return;
    }
    foreach ($menu as $index => $item) {
        if (isset($item[2]) && 'edit.php?post_type=' . LEKHONI_CONTACT_TYPE === $item[2]) {
            $menu[$index][0] .= sprintf(' <span class="awaiting-mod count-%1$d"><span class="pending-count">%1$d</span></span>', $unread);
        }
    }
}
add_action('admin_menu', 'lekhoni_contact_menu_badge', 99);

/**
 * List table columns.
 *
 * @param array $columns Default columns.
 * @return array
 */
function lekhoni_contact_columns($columns)
{
    return array(
        'cb'         => $columns['cb'],
        'title'      => 'Subject',
        'lk_name'    => 'Name',
        'lk_email'   => 'Email',
        'lk_excerpt' => 'Message',
        'date'       => 'Received',
    );
}
add_filter('manage_' . LEKHONI_CONTACT_TYPE . '_posts_columns', 'lekhoni_contact_columns');

/**
 * List table cells.
 *
 * @param string $column  Column key.
 * @param int    $post_id Message id.
 * @return void
 */
function lekhoni_contact_column_content($column, $post_id)
{
    if ('lk_name' === $column) {
        echo esc_html(get_post_meta($post_id, '_lk_name', true));
    } elseif ('lk_email' === $column) {
        $email = get_post_meta($post_id, '_lk_email', true);
        printf('<a href="mailto:%1$s">%2$s</a>', esc_attr($email), esc_html($email));
    } elseif ('lk_excerpt' === $column) {
        echo esc_html(wp_trim_words(get_post_field('post_content', $post_id), 16));
    }
}
add_action('manage_' . LEKHONI_CONTACT_TYPE . '_posts_custom_column', 'lekhoni_contact_column_content', 10, 2);

/**
 * Unread messages are shown in bold in the list.
 *
 * @param array $classes Row classes.
 * @param array $class   Extra classes.
 * @param int   $post_id Message id.
 * @return array
 */
function lekhoni_contact_row_class($classes, $class, $post_id)
{
    if (LEKHONI_CONTACT_TYPE === get_post_type($post_id) && '0' === get_post_meta($post_id, '_lk_read', true)) {
        $classes[] = 'lk-unread';
    }
    return $classes;
}
add_filter('post_class', 'lekhoni_contact_row_class', 10, 3);

/**
 * Admin styles for unread rows and the detail box.
 *
 * @return void
 */
function lekhoni_contact_admin_styles()
{
    $screen = get_current_screen();
    if (! $screen || LEKHONI_CONTACT_TYPE !== $screen->post_type) {
        return;
    }
    echo '<style>
        tr.lk-unread td, tr.lk-unread .row-title { font-weight: 600; }
        .lk-message-table th { width: 110px; text-align: left; padding: 8px 10px 8px 0; vertical-align: top; }
        .lk-message-table td { padding: 8px 0; }
        .lk-message-body { white-space: pre-wrap; font-size: 14px; line-height: 1.7; background: #f6f7f7; padding: 14px 16px; border-radius: 4px; }
        #titlediv #title { pointer-events: none; background: #f6f7f7; }
    </style>';
}
add_action('admin_head', 'lekhoni_contact_admin_styles');

/**
 * Replaces the editor with a read-only message box.
 *
 * @return void
 */
function lekhoni_contact_meta_boxes()
{
    add_meta_box('lk_contact_message', 'Message', 'lekhoni_contact_render_box', LEKHONI_CONTACT_TYPE, 'normal', 'high');
}
add_action('add_meta_boxes', 'lekhoni_contact_meta_boxes');

/**
 * Message detail box.
 *
 * @param WP_Post $post Message.
 * @return void
 */
function lekhoni_contact_render_box($post)
{
    $name    = get_post_meta($post->ID, '_lk_name', true);
    $email   = get_post_meta($post->ID, '_lk_email', true);
    $subject = get_post_meta($post->ID, '_lk_subject', true);
    $mailed  = get_post_meta($post->ID, '_lk_mailed', true);
    $reply   = 'mailto:' . rawurlencode($email) . '?subject=' . rawurlencode('Re: ' . ($subject ? $subject : get_bloginfo('name')));
    ?>
    <table class="lk-message-table">
        <tr><th>Name</th><td><?php echo esc_html($name); ?></td></tr>
        <tr><th>Email</th><td><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></td></tr>
        <?php if ($subject) : ?>
            <tr><th>Subject</th><td><?php echo esc_html($subject); ?></td></tr>
        <?php endif; ?>
        <tr><th>Received</th><td><?php echo esc_html(get_the_date('j F Y, g:i a', $post)); ?></td></tr>
        <tr><th>Email sent</th><td><?php echo '1' === $mailed ? 'Yes' : 'No (the message is still saved here)'; ?></td></tr>
    </table>
    <div class="lk-message-body"><?php echo esc_html($post->post_content); ?></div>
    <p><a class="button button-primary" href="<?php echo esc_url($reply); ?>">Reply by email</a></p>
    <?php
}

/**
 * Hides the editor UI that does not apply to messages.
 *
 * @return void
 */
function lekhoni_contact_remove_boxes()
{
    remove_meta_box('slugdiv', LEKHONI_CONTACT_TYPE, 'normal');
}
add_action('admin_menu', 'lekhoni_contact_remove_boxes');

/**
 * Marks a message as read when it is opened in the admin.
 *
 * @return void
 */
function lekhoni_contact_mark_read()
{
    $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ($post_id && LEKHONI_CONTACT_TYPE === get_post_type($post_id) && current_user_can('edit_post', $post_id)) {
        update_post_meta($post_id, '_lk_read', '1');
    }
}
add_action('load-post.php', 'lekhoni_contact_mark_read');

/**
 * Dashboard widget with the latest messages.
 *
 * @return void
 */
function lekhoni_contact_dashboard_widget()
{
    if (! current_user_can('edit_posts')) {
        return;
    }
    wp_add_dashboard_widget('lk_contact_messages', 'Recent contact messages', 'lekhoni_contact_render_widget');
}
add_action('wp_dashboard_setup', 'lekhoni_contact_dashboard_widget');

/**
 * Dashboard widget content.
 *
 * @return void
 */
function lekhoni_contact_render_widget()
{
    $messages = get_posts(array('post_type' => LEKHONI_CONTACT_TYPE, 'numberposts' => 5));
    $unread   = lekhoni_contact_unread_count();
    if (! $messages) {
        echo '<p>No messages yet.</p>';
        return;
    }
    printf('<p><strong>%d</strong> unread</p><ul>', (int) $unread);
    foreach ($messages as $message) {
        $is_unread = '0' === get_post_meta($message->ID, '_lk_read', true);
        printf(
            '<li style="margin-bottom:8px">%1$s<a href="%2$s">%3$s</a>%4$s<br><span style="color:#646970">%5$s &middot; %6$s</span></li>',
            $is_unread ? '<strong>' : '',
            esc_url(get_edit_post_link($message->ID)),
            esc_html(get_the_title($message)),
            $is_unread ? '</strong>' : '',
            esc_html(get_post_meta($message->ID, '_lk_name', true)),
            esc_html(human_time_diff(get_post_time('U', true, $message), time()) . ' ago')
        );
    }
    printf('</ul><p><a href="%s">All messages &rarr;</a></p>', esc_url(admin_url('edit.php?post_type=' . LEKHONI_CONTACT_TYPE)));
}

/**
 * The page using the Contact template (holds the form settings).
 *
 * @return int Page id, or 0.
 */
function lekhoni_contact_page_id()
{
    $pages = get_posts(
        array(
            'post_type'   => 'page',
            'post_status' => 'publish',
            'meta_key'    => '_wp_page_template',
            'meta_value'  => 'templates/template-contact.php',
            'numberposts' => 1,
            'fields'      => 'ids',
        )
    );
    return $pages ? (int) $pages[0] : 0;
}

/**
 * Validates, stores and emails a contact message.
 *
 * @param array $input name, email, subject, message.
 * @return array{success: bool, code: string}
 */
function lekhoni_contact_submit($input)
{
    $name    = sanitize_text_field(wp_unslash($input['name'] ?? ''));
    $email   = sanitize_email(wp_unslash($input['email'] ?? ''));
    $subject = sanitize_text_field(wp_unslash($input['subject'] ?? ''));
    $message = sanitize_textarea_field(wp_unslash($input['message'] ?? ''));

    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        return array('success' => false, 'code' => 'invalid_name');
    }
    if (! is_email($email)) {
        return array('success' => false, 'code' => 'invalid_email');
    }
    if (mb_strlen($message) < 20 || mb_strlen($message) > 5000) {
        return array('success' => false, 'code' => 'invalid_message');
    }

    $page_id = lekhoni_contact_page_id();
    $form    = $page_id && function_exists('get_field') ? (array) get_field('form', $page_id) : array();

    // When subjects are configured, only those are accepted
    $subjects = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) ($form['subjects'] ?? ''))));
    if ($subjects && ! in_array($subject, $subjects, true)) {
        return array('success' => false, 'code' => 'invalid_subject');
    }

    // Throttle: one message per email per minute, and at most 30 an hour overall
    $email_key = 'lk_contact_' . md5(strtolower($email));
    if (get_transient($email_key)) {
        return array('success' => false, 'code' => 'too_many');
    }
    $hourly = (int) get_transient('lk_contact_hourly');
    if ($hourly >= 30) {
        return array('success' => false, 'code' => 'too_many');
    }
    set_transient($email_key, 1, MINUTE_IN_SECONDS);
    set_transient('lk_contact_hourly', $hourly + 1, HOUR_IN_SECONDS);

    $post_id = wp_insert_post(
        array(
            'post_type'    => LEKHONI_CONTACT_TYPE,
            'post_status'  => 'publish',
            'post_title'   => $subject ? $subject : sprintf('Message from %s', $name),
            'post_content' => $message,
            'meta_input'   => array(
                '_lk_name'    => $name,
                '_lk_email'   => $email,
                '_lk_subject' => $subject,
                '_lk_read'    => '0',
            ),
        ),
        true
    );
    if (is_wp_error($post_id)) {
        return array('success' => false, 'code' => 'server_error');
    }

    $to     = ! empty($form['recipient']) && is_email($form['recipient']) ? $form['recipient'] : get_option('admin_email');
    $body   = sprintf(
        "Name: %s\nEmail: %s\nSubject: %s\n\n%s\n\n--\nAll messages: %s",
        $name,
        $email,
        $subject ? $subject : '-',
        $message,
        admin_url('edit.php?post_type=' . LEKHONI_CONTACT_TYPE)
    );
    $mailed = wp_mail(
        $to,
        sprintf('[%s] %s', wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES), $subject ? $subject : 'New contact message'),
        $body,
        array(sprintf('Reply-To: %s <%s>', str_replace(array("\r", "\n", '<', '>'), '', $name), $email))
    );
    update_post_meta($post_id, '_lk_mailed', $mailed ? '1' : '0');

    // Saved either way; a failed email is shown on the message in wp-admin
    return array('success' => true, 'code' => 'sent');
}

/**
 * GraphQL: sendContactMessage(input: { name, email, subject, message }) { success code }
 *
 * `code` is one of: sent, invalid_name, invalid_email, invalid_message,
 * invalid_subject, too_many, server_error. The storefront maps it to Bangla.
 *
 * @return void
 */
function lekhoni_contact_register_mutation()
{
    if (! function_exists('register_graphql_mutation')) {
        return;
    }
    register_graphql_mutation(
        'sendContactMessage',
        array(
            'inputFields'         => array(
                'name'    => array('type' => array('non_null' => 'String')),
                'email'   => array('type' => array('non_null' => 'String')),
                'subject' => array('type' => 'String'),
                'message' => array('type' => array('non_null' => 'String')),
            ),
            'outputFields'        => array(
                'success' => array('type' => array('non_null' => 'Boolean')),
                'code'    => array('type' => array('non_null' => 'String')),
            ),
            'mutateAndGetPayload' => function ($input) {
                return lekhoni_contact_submit($input);
            },
        )
    );
}
add_action('graphql_register_types', 'lekhoni_contact_register_mutation');
