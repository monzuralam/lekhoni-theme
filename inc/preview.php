<?php

/**
 * Storefront previews of drafts, scheduled, pending and private posts and
 * pages, and of unsaved changes ("Preview" in the editor).
 *
 * 1. "Preview" (and "View" on a private post) links to
 *    wp-admin/admin-post.php?action=lekhoni_preview&post=ID&_wpnonce=…
 * 2. That checks the editor may edit the post, stores a one-time handoff
 *    token (2 minutes) and redirects to {LEKHONI_FRONTEND_URL}/api/preview?token=….
 * 3. The storefront trades it (mutation lekhoniStartPreview) for a session
 *    token (1 hour) bound to that editor and that one post, keeps it in an
 *    httpOnly cookie and opens /preview/{post|page}/ID.
 * 4. The preview page asks for the content with the session token
 *    (query lekhoniPreview), which returns the latest autosave when it is
 *    newer, rendered like the live post. Nothing else is unlocked: the
 *    GraphQL request itself stays anonymous.
 *
 * @package WordPress
 * @subpackage Lekhoni
 * @since Lekhoni 1.0
 */

define('LEKHONI_PREVIEW_HANDOFF_TTL', 2 * MINUTE_IN_SECONDS);
define('LEKHONI_PREVIEW_SESSION_TTL', HOUR_IN_SECONDS);

/**
 * Post types the storefront can preview.
 *
 * @return string[]
 */
function lekhoni_preview_types()
{
    return array('post', 'page');
}

/**
 * The admin link that starts a storefront preview of a post.
 *
 * @param WP_Post $post Post.
 * @return string
 */
function lekhoni_preview_start_url($post)
{
    return add_query_arg(
        array(
            'action'   => 'lekhoni_preview',
            'post'     => $post->ID,
            '_wpnonce' => wp_create_nonce('lekhoni_preview_' . $post->ID),
        ),
        admin_url('admin-post.php')
    );
}

/**
 * Transient key for a token (only a keyed hash of the token is stored).
 *
 * @param string $prefix 'h' (handoff) or 's' (session).
 * @param string $token  Token.
 * @return string
 */
function lekhoni_preview_key($prefix, $token)
{
    return 'lekhoni_pv' . $prefix . '_' . hash_hmac('sha256', $token, wp_salt('auth'));
}

/**
 * A random URL-safe token (32 bytes, 43 characters).
 *
 * @return string
 */
function lekhoni_preview_token()
{
    return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
}

/**
 * Whether a string looks like one of our tokens.
 *
 * @param mixed $token Token.
 * @return bool
 */
function lekhoni_preview_token_ok($token)
{
    return is_string($token) && 1 === preg_match('/^[A-Za-z0-9_-]{43}$/', $token);
}

/**
 * "Preview" in the editor and the admin lists opens the storefront.
 * Other post types (products) keep WordPress's own preview.
 *
 * @param string  $url  Preview link.
 * @param WP_Post $post Post.
 * @return string
 */
function lekhoni_preview_post_link($url, $post)
{
    if (lekhoni_is_graphql_request() || ! $post instanceof WP_Post) {
        return lekhoni_preview_link_on_wordpress($url);
    }
    if (in_array($post->post_type, lekhoni_preview_types(), true) && current_user_can('edit_post', $post->ID)) {
        return lekhoni_preview_start_url($post);
    }

    return lekhoni_preview_link_on_wordpress($url);
}
add_filter('preview_post_link', 'lekhoni_preview_post_link', 20, 2);

/**
 * Keeps a preview link on WordPress (it is built from the permalink, which
 * points at the storefront for published posts).
 *
 * @param string $url Preview link.
 * @return string
 */
function lekhoni_preview_link_on_wordpress($url)
{
    $front = lekhoni_frontend_url();
    if (0 === stripos($url, $front . '/') || 0 === strcasecmp($url, $front)) {
        return untrailingslashit(home_url()) . substr($url, strlen($front));
    }

    return $url;
}

/**
 * "View" on a private post or page: a storefront preview for those who may
 * edit it (the storefront shows public content only).
 *
 * @param string[] $actions Row actions.
 * @param WP_Post  $post    Post.
 * @return string[]
 */
function lekhoni_preview_private_row_action($actions, $post)
{
    if (
        isset($actions['view']) && 'private' === $post->post_status
        && in_array($post->post_type, lekhoni_preview_types(), true) && current_user_can('edit_post', $post->ID)
    ) {
        $actions['view'] = sprintf(
            '<a href="%s" rel="bookmark" aria-label="%s">%s</a>',
            esc_url(lekhoni_preview_start_url($post)),
            /* translators: %s: Post title. */
            esc_attr(sprintf(__('Preview &#8220;%s&#8221;'), _draft_or_post_title($post))),
            __('Preview')
        );
    }

    return $actions;
}
add_filter('post_row_actions', 'lekhoni_preview_private_row_action', 20, 2);
add_filter('page_row_actions', 'lekhoni_preview_private_row_action', 20, 2);

/**
 * admin-post.php?action=lekhoni_preview: hands the editor over to the storefront.
 *
 * @return void
 */
function lekhoni_preview_start()
{
    $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;
    $post    = $post_id ? get_post($post_id) : null;

    if (! $post || ! in_array($post->post_type, lekhoni_preview_types(), true)) {
        wp_die(esc_html__('This item can\'t be previewed on the storefront.', 'lekhoni'), '', array('response' => 404));
    }
    check_admin_referer('lekhoni_preview_' . $post->ID);
    if (! current_user_can('edit_post', $post->ID)) {
        wp_die(esc_html__('Sorry, you are not allowed to preview this item.', 'lekhoni'), '', array('response' => 403));
    }

    $token = lekhoni_preview_token();
    set_transient(
        lekhoni_preview_key('h', $token),
        array('user' => get_current_user_id(), 'post' => $post->ID),
        LEKHONI_PREVIEW_HANDOFF_TTL
    );

    nocache_headers();
    wp_redirect(lekhoni_frontend_url() . '/api/preview?token=' . rawurlencode($token), 302, 'Lekhoni');
    exit;
}
add_action('admin_post_lekhoni_preview', 'lekhoni_preview_start');
// Logged out (e.g. an old link): log in, then come back here
add_action('admin_post_nopriv_lekhoni_preview', 'auth_redirect');

/**
 * Trades a handoff token (single use) for a session token.
 *
 * @param string $token Handoff token.
 * @return array|null { session, kind, id }
 */
function lekhoni_preview_exchange($token)
{
    if (! lekhoni_preview_token_ok($token)) {
        return null;
    }
    $key  = lekhoni_preview_key('h', $token);
    $data = get_transient($key);
    delete_transient($key);

    if (! is_array($data) || ! user_can((int) $data['user'], 'edit_post', (int) $data['post'])) {
        return null;
    }
    $post = get_post((int) $data['post']);
    if (! $post) {
        return null;
    }

    $session = lekhoni_preview_token();
    set_transient(
        lekhoni_preview_key('s', $session),
        array('user' => (int) $data['user'], 'post' => $post->ID),
        LEKHONI_PREVIEW_SESSION_TTL
    );

    return array('session' => $session, 'kind' => $post->post_type, 'id' => $post->ID);
}

/**
 * The post a session token may preview, if the session is still valid.
 *
 * @param string $session Session token.
 * @param int    $post_id Requested post.
 * @return array|null { user, post: WP_Post }
 */
function lekhoni_preview_session($session, $post_id)
{
    if (! lekhoni_preview_token_ok($session)) {
        return null;
    }
    $data = get_transient(lekhoni_preview_key('s', $session));
    if (! is_array($data) || (int) $data['post'] !== (int) $post_id) {
        return null;
    }
    // Rights can change during the hour (role removed, post trashed)
    $post = get_post((int) $data['post']);
    if (! $post || 'trash' === $post->post_status || ! user_can((int) $data['user'], 'edit_post', $post->ID)) {
        return null;
    }

    return array('user' => (int) $data['user'], 'post' => $post);
}

/**
 * A home-relative path, the way WPGraphQL builds `uri` (/hello-world/).
 *
 * @param string|WP_Error $url Link.
 * @return string|null
 */
function lekhoni_preview_path($url)
{
    if (! is_string($url) || '' === $url) {
        return null;
    }

    return str_ireplace(untrailingslashit(home_url()), '', $url);
}

/**
 * GMT date in WPGraphQL's format; unsaved drafts have none, so "now".
 *
 * @param string $gmt post_date_gmt.
 * @return string
 */
function lekhoni_preview_date($gmt)
{
    if (! $gmt || '0000-00-00 00:00:00' === $gmt) {
        return gmdate('Y-m-d\TH:i:s');
    }

    return str_replace(' ', 'T', $gmt);
}

/**
 * Featured image in the storefront's shape.
 *
 * @param int $post_id Post.
 * @return array|null
 */
function lekhoni_preview_image($post_id)
{
    $id = (int) get_post_thumbnail_id($post_id);
    $src = $id ? wp_get_attachment_image_src($id, 'full') : false;
    if (! $src) {
        return null;
    }

    return array(
        'node' => array(
            'sourceUrl'    => $src[0],
            'altText'      => (string) get_post_meta($id, '_wp_attachment_image_alt', true),
            'caption'      => wp_get_attachment_caption($id) ? wpautop(wp_get_attachment_caption($id)) : null,
            'mediaDetails' => array('width' => $src[1] ?: null, 'height' => $src[2] ?: null),
        ),
    );
}

/**
 * The previewed post, shaped like the storefront's GraphQL queries
 * (SinglePost / WordPressPage). The editor's latest autosave wins when it is
 * newer than the post, like WordPress's own preview.
 *
 * @param array $session From lekhoni_preview_session().
 * @return array
 */
function lekhoni_preview_data($session)
{
    global $post;

    $original = $session['post'];
    $current  = clone $original;
    $autosave = wp_get_post_autosave($original->ID, $session['user']);
    if ($autosave && mysql2date('U', $autosave->post_modified_gmt, false) > mysql2date('U', $original->post_modified_gmt, false)) {
        foreach (array('post_title', 'post_content', 'post_excerpt', 'post_modified', 'post_modified_gmt') as $field) {
            $current->$field = $autosave->$field;
        }
    }

    // Render as the editor (blocks and shortcodes may check rights)
    $previous_user = get_current_user_id();
    $previous_post = $post;
    wp_set_current_user($session['user']);
    $post = $current; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
    setup_postdata($current);

    $content = apply_filters('the_content', $current->post_content);
    // Not get_the_title(): that adds "Private: " to private posts
    $title   = apply_filters('the_title', $current->post_title, $original->ID);
    $uri     = 'publish' === $original->post_status ? lekhoni_preview_path(get_permalink($original)) : null;
    $data    = array(
        'kind'          => $original->post_type,
        'status'        => $original->post_status,
        'databaseId'    => $original->ID,
        'title'         => $title,
        'content'       => $content,
        'uri'           => $uri ?: '/',
        'link'          => $uri,
        'modifiedGmt'   => lekhoni_preview_date($current->post_modified_gmt),
        'featuredImage' => lekhoni_preview_image($original->ID),
    );

    if ('page' === $original->post_type) {
        $data['isFrontPage'] = (int) get_option('page_on_front') === $original->ID;
        $data['modified']    = str_replace(' ', 'T', $current->post_modified);
        $data['template']    = (string) get_page_template_slug($original);
        $data['ancestors']   = array(
            'nodes' => array_map(
                function ($id) {
                    return array('title' => get_the_title($id), 'uri' => (string) lekhoni_preview_path(get_permalink($id)));
                },
                get_post_ancestors($original)
            ),
        );
    } else {
        $author = get_userdata((int) $original->post_author);
        $terms  = function ($taxonomy) use ($original) {
            $list = get_the_terms($original, $taxonomy);
            return array(
                'nodes' => is_array($list) ? array_map(
                    function ($term) {
                        return array('databaseId' => $term->term_id, 'name' => $term->name, 'uri' => (string) lekhoni_preview_path(get_term_link($term)));
                    },
                    $list
                ) : array(),
            );
        };
        $data += array(
            'excerpt'       => apply_filters('the_excerpt', get_the_excerpt($current)),
            'dateGmt'       => lekhoni_preview_date($original->post_date_gmt),
            'commentStatus' => 'closed', // no comments on a preview
            'author'        => $author ? array(
                'node' => array(
                    'databaseId'  => $author->ID,
                    'name'        => $author->display_name,
                    'uri'         => lekhoni_preview_path(get_author_posts_url($author->ID)),
                    'description' => $author->description,
                    'avatar'      => array('url' => get_avatar_url($author->ID, array('size' => 160))),
                ),
            ) : null,
            'categories'    => $terms('category'),
            'tags'          => $terms('post_tag'),
        );
    }

    wp_reset_postdata();
    $post = $previous_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
    wp_set_current_user($previous_user);

    return $data;
}

/**
 * GraphQL: mutation lekhoniStartPreview(input: { token }) { session kind id }
 * and query lekhoniPreview(session, id): the post as JSON (null when the
 * session is invalid, expired or for another post).
 *
 * @return void
 */
function lekhoni_preview_register_graphql()
{
    register_graphql_mutation(
        'lekhoniStartPreview',
        array(
            'inputFields'         => array(
                'token' => array('type' => array('non_null' => 'String'), 'description' => 'Handoff token from the preview link.'),
            ),
            'outputFields'        => array(
                'session' => array('type' => 'String', 'description' => 'Session token (1 hour, this post only).'),
                'kind'    => array('type' => 'String', 'description' => 'post or page.'),
                'id'      => array('type' => 'Int', 'description' => 'Post ID.'),
            ),
            'mutateAndGetPayload' => function ($input) {
                $result = lekhoni_preview_exchange($input['token'] ?? '');
                return $result ?: array('session' => null, 'kind' => null, 'id' => null);
            },
        )
    );

    register_graphql_field(
        'RootQuery',
        'lekhoniPreview',
        array(
            'type'        => 'String',
            'description' => 'A storefront preview of a post or page (JSON), for a preview session.',
            'args'        => array(
                'session' => array('type' => array('non_null' => 'String')),
                'id'      => array('type' => array('non_null' => 'Int')),
            ),
            'resolve'     => function ($root, $args) {
                $session = lekhoni_preview_session($args['session'], $args['id']);
                return $session ? wp_json_encode(lekhoni_preview_data($session)) : null;
            },
        )
    );
}
add_action('graphql_register_types', 'lekhoni_preview_register_graphql');
