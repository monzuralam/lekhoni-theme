<?php

/**
 * Email link login ("magic link") for the storefront.
 *
 * Switched on under Theme Settings → Login. Two GraphQL mutations, used by the
 * storefront's /api/auth/email-link routes:
 *
 * - `sendLoginLink(input: { email, next })` emails a one-time link to
 *   {LEKHONI_FRONTEND_URL}/login/link?token=…. It answers the same whether or
 *   not the email has an account, so it can't be used to find customers.
 * - `loginWithEmailLink(input: { token })` swaps a valid token for the same
 *   JWT auth + refresh tokens a password login returns.
 *
 * Security:
 * - Customer accounts only. Admins, editors and shop managers never get a
 *   link (a token for them would carry their admin rights).
 * - The token is 256 random bits. Only its HMAC is stored (transient keyed by
 *   the hash), so the database alone can't be used to log in.
 * - Single use, expires after the configured minutes, and a new link cancels
 *   the previous one (the latest hash is kept in user meta).
 * - At most 1 link per email per minute, 5 per email per hour and 100 per
 *   hour site-wide. Both mutations refuse while the setting is off.
 *
 * @package WordPress
 * @subpackage Lekhoni
 * @since Lekhoni 1.0
 */

const LEKHONI_EMAIL_LOGIN_META = '_lk_email_login_hash';

/**
 * The Theme Settings → Login values, with defaults.
 *
 * @return array{enabled: bool, lifetime: int, subject: string, intro: string}
 */
function lekhoni_email_login_settings()
{
    $login = function_exists('get_field') ? get_field('field_lk_ts_login', 'option') : null;
    $login = is_array($login) ? $login : array();

    $lifetime = (int) ($login['link_lifetime'] ?? 0);

    return array(
        'enabled'  => ! empty($login['email_link']),
        'lifetime' => $lifetime >= 5 && $lifetime <= 60 ? $lifetime : 15,
        'subject'  => trim((string) ($login['email_subject'] ?? '')) ?: '{site}-এ লগইন করার লিংক',
        'intro'    => trim((string) ($login['email_intro'] ?? '')) ?: 'আপনার অ্যাকাউন্টে লগইন করতে নিচের বোতামে চাপ দিন।',
    );
}

/**
 * Whether a user may log in by email link: customers only, never staff.
 *
 * @param WP_User $user User.
 * @return bool
 */
function lekhoni_email_login_eligible($user)
{
    foreach (array('edit_posts', 'manage_woocommerce', 'edit_users', 'manage_options') as $capability) {
        if (user_can($user, $capability)) {
            return false;
        }
    }
    return true;
}

/**
 * Stored form of a token.
 *
 * @param string $token Raw token from the link.
 * @return string
 */
function lekhoni_email_login_hash($token)
{
    return hash_hmac('sha256', $token, wp_salt('auth'));
}

/**
 * A same-site path to return to after login ("/my-account/orders"), or ''.
 *
 * @param string $next Requested path.
 * @return string
 */
function lekhoni_email_login_next($next)
{
    $next = trim((string) $next);
    if ('' === $next || strlen($next) > 200 || '/' !== $next[0] || str_starts_with($next, '//') || str_starts_with($next, '/\\')) {
        return '';
    }
    return $next;
}

/**
 * Emails a login link.
 *
 * @param array $input { email, next }.
 * @return array{success: bool, code: string}
 */
function lekhoni_email_login_send($input)
{
    $settings = lekhoni_email_login_settings();
    if (! $settings['enabled']) {
        return array('success' => false, 'code' => 'disabled');
    }

    $email = sanitize_email(wp_unslash($input['email'] ?? ''));
    if (! is_email($email)) {
        return array('success' => false, 'code' => 'invalid_email');
    }

    // Throttle before looking the email up, so every address behaves the same
    $key    = 'lk_email_login_' . md5(strtolower($email));
    $hourly = (int) get_transient($key . '_h');
    $site   = (int) get_transient('lk_email_login_site_h');
    if (get_transient($key . '_m') || $hourly >= 5 || $site >= 100) {
        return array('success' => false, 'code' => 'too_many');
    }
    set_transient($key . '_m', 1, MINUTE_IN_SECONDS);
    set_transient($key . '_h', $hourly + 1, HOUR_IN_SECONDS);
    set_transient('lk_email_login_site_h', $site + 1, HOUR_IN_SECONDS);

    $user = get_user_by('email', $email);
    if (! $user || ! lekhoni_email_login_eligible($user)) {
        // Same answer as for a real customer
        return array('success' => true, 'code' => 'sent');
    }

    $token   = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    $hash    = lekhoni_email_login_hash($token);
    $seconds = $settings['lifetime'] * MINUTE_IN_SECONDS;

    set_transient(
        'lk_email_login_t_' . $hash,
        array('user' => $user->ID, 'expires' => time() + $seconds),
        $seconds
    );
    // Only the newest link is valid
    update_user_meta($user->ID, LEKHONI_EMAIL_LOGIN_META, $hash);

    $query = array('token' => $token);
    $next  = lekhoni_email_login_next($input['next'] ?? '');
    if ('' !== $next) {
        $query['next'] = $next;
    }
    $url = lekhoni_frontend_url() . '/login/link?' . http_build_query($query);

    lekhoni_email_login_mail($user, $url, $settings);

    return array('success' => true, 'code' => 'sent');
}

/**
 * Sends the email, in WooCommerce's email template when available.
 *
 * @param WP_User $user     Recipient.
 * @param string  $url      Login link.
 * @param array   $settings lekhoni_email_login_settings().
 * @return void
 */
function lekhoni_email_login_mail($user, $url, $settings)
{
    $site    = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
    $subject = str_replace('{site}', $site, $settings['subject']);
    $name    = trim($user->first_name) ?: $user->display_name;
    $heading = 'আপনার লগইন লিংক';
    // WooCommerce → Settings → Emails → "Base colour", so it matches the store's emails
    $color   = sanitize_hex_color(get_option('woocommerce_email_base_color', '')) ?: '#7f54b3';
    $button  = 'display:inline-block;padding:12px 28px;border-radius:999px;background:' . $color . ';color:#ffffff;font-weight:bold;text-decoration:none;';

    $body  = '<p>' . esc_html(sprintf('প্রিয় %s,', $name)) . '</p>';
    $body .= '<p>' . esc_html($settings['intro']) . '</p>';
    $body .= '<p style="margin:28px 0;"><a href="' . esc_url($url) . '" style="' . esc_attr($button) . '">লগইন করুন</a></p>';
    $body .= '<p>' . esc_html(sprintf('লিংকটি %d মিনিট পর্যন্ত কাজ করবে এবং একবারই ব্যবহার করা যাবে।', $settings['lifetime'])) . '</p>';
    $body .= '<p>আপনি লগইন লিংক না চেয়ে থাকলে ইমেইলটি উপেক্ষা করুন; আপনার অ্যাকাউন্ট নিরাপদ আছে।</p>';
    $body .= '<p style="font-size:12px;color:#777;">বোতাম কাজ না করলে এই ঠিকানাটি ব্রাউজারে খুলুন:<br>' . esc_html($url) . '</p>';

    if (function_exists('WC') && WC()->mailer()) {
        $mailer = WC()->mailer();
        $mailer->send($user->user_email, $subject, $mailer->wrap_message($heading, $body));
        return;
    }

    wp_mail($user->user_email, $subject, $body, array('Content-Type: text/html; charset=UTF-8'));
}

/**
 * Swaps a token for JWT tokens.
 *
 * @param array $input { token }.
 * @return array{authToken: ?string, refreshToken: ?string, code: string}
 */
function lekhoni_email_login_verify($input)
{
    $fail = static function ($code) {
        return array('authToken' => null, 'refreshToken' => null, 'code' => $code);
    };

    if (! lekhoni_email_login_settings()['enabled']) {
        return $fail('disabled');
    }

    $token = (string) ($input['token'] ?? '');
    if (! preg_match('/^[A-Za-z0-9_-]{43}$/', $token)) {
        return $fail('invalid');
    }

    $hash = lekhoni_email_login_hash($token);
    $data = get_transient('lk_email_login_t_' . $hash);
    // Single use: gone as soon as it is looked at
    delete_transient('lk_email_login_t_' . $hash);

    if (! is_array($data) || empty($data['user']) || (int) ($data['expires'] ?? 0) < time()) {
        return $fail('invalid');
    }

    $user = get_user_by('id', (int) $data['user']);
    if (! $user || ! lekhoni_email_login_eligible($user)) {
        return $fail('invalid');
    }
    // A newer link replaced this one
    if (! hash_equals((string) get_user_meta($user->ID, LEKHONI_EMAIL_LOGIN_META, true), $hash)) {
        return $fail('invalid');
    }
    delete_user_meta($user->ID, LEKHONI_EMAIL_LOGIN_META);

    if (! class_exists('\WPGraphQL\JWT_Authentication\Auth')) {
        return $fail('server_error');
    }

    // The JWT plugin issues tokens for the current user only
    wp_set_current_user($user->ID);
    $auth    = \WPGraphQL\JWT_Authentication\Auth::get_token($user);
    $refresh = \WPGraphQL\JWT_Authentication\Auth::get_refresh_token($user);
    if (is_wp_error($auth) || is_wp_error($refresh) || empty($auth) || empty($refresh)) {
        return $fail('server_error');
    }

    // Opening the link proves the customer owns the address
    update_user_meta($user->ID, 'lk_last_email_login', time());

    return array('authToken' => $auth, 'refreshToken' => $refresh, 'code' => 'ok');
}

/**
 * Registers the two mutations.
 *
 * @return void
 */
function lekhoni_email_login_register_mutations()
{
    if (! function_exists('register_graphql_mutation')) {
        return;
    }

    register_graphql_mutation(
        'sendLoginLink',
        array(
            'inputFields'         => array(
                'email' => array('type' => array('non_null' => 'String')),
                'next'  => array('type' => 'String', 'description' => 'Storefront path to open after login.'),
            ),
            'outputFields'        => array(
                'success' => array('type' => array('non_null' => 'Boolean')),
                'code'    => array('type' => array('non_null' => 'String')),
            ),
            'mutateAndGetPayload' => 'lekhoni_email_login_send',
        )
    );

    register_graphql_mutation(
        'loginWithEmailLink',
        array(
            'inputFields'         => array(
                'token' => array('type' => array('non_null' => 'String')),
            ),
            'outputFields'        => array(
                'authToken'    => array('type' => 'String'),
                'refreshToken' => array('type' => 'String'),
                'code'         => array('type' => array('non_null' => 'String')),
            ),
            'mutateAndGetPayload' => 'lekhoni_email_login_verify',
        )
    );
}
add_action('graphql_register_types', 'lekhoni_email_login_register_mutations');
