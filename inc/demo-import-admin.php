<?php

/**
 * One-click demo import: the admin page (Theme Settings → Demo Import), its
 * AJAX endpoints and the "import the demo" notice after theme activation.
 * The work itself is in inc/demo-import.php.
 *
 * @package WordPress
 * @subpackage Lekhoni
 * @since Lekhoni 1.0
 */

const LEKHONI_DEMO_PAGE = 'lekhoni-demo-import';

/**
 * Requirement checks shown on the page: label => [ok, help].
 *
 * @return array<string, array{0: bool, 1: string}>
 */
function lekhoni_demo_requirements()
{
    $uploads = wp_upload_dir();
    $memory  = wp_convert_hr_to_bytes((string) ini_get('memory_limit'));

    return array(
        'WooCommerce'                      => array(class_exists('WooCommerce'), 'Install and activate WooCommerce.'),
        'Advanced Custom Fields PRO'       => array(function_exists('acf_get_fields'), 'Activate ACF PRO (Appearance → Install Plugins).'),
        'WPGraphQL'                        => array(class_exists('WPGraphQL'), 'Activate WPGraphQL.'),
        'WPGraphQL WooCommerce'            => array(class_exists('WP_GraphQL_WooCommerce') || defined('WPGRAPHQL_WOOCOMMERCE_VERSION'), 'Activate WPGraphQL WooCommerce (WooGraphQL).'),
        'Demo package'                     => array((bool) lekhoni_demo_package(), 'The theme\'s demo/content.json is missing. Reinstall the theme.'),
        'Uploads folder writable'          => array(empty($uploads['error']) && wp_is_writable($uploads['basedir']), 'WordPress can\'t write to wp-content/uploads.'),
        'Image processing (GD or Imagick)' => array(wp_image_editor_supports(array('mime_type' => 'image/jpeg')), 'Ask your host to enable the PHP GD or Imagick extension.'),
        'PHP memory (128 MB or more)'      => array(-1 === $memory || $memory >= 128 * MB_IN_BYTES, 'Raise memory_limit to at least 128M.'),
    );
}

/**
 * Adds the page under Theme Settings (after ACF has added its menu).
 *
 * @return void
 */
function lekhoni_demo_admin_menu()
{
    add_submenu_page(
        defined('LEKHONI_SETTINGS_SLUG') ? LEKHONI_SETTINGS_SLUG : 'themes.php',
        'Demo Import',
        'Demo Import',
        'manage_options',
        LEKHONI_DEMO_PAGE,
        'lekhoni_demo_render_page'
    );
}
add_action('admin_menu', 'lekhoni_demo_admin_menu', 100);

/**
 * Renders the page.
 *
 * @return void
 */
function lekhoni_demo_render_page()
{
    if (! current_user_can('manage_options')) {
        return;
    }
    $package      = lekhoni_demo_package();
    $requirements = lekhoni_demo_requirements();
    $ready        = ! in_array(false, array_column($requirements, 0), true);
    $state        = lekhoni_demo_state();
    $preview      = get_template_directory_uri() . '/demo/images/hero-1.png';
    $steps        = lekhoni_demo_steps();

    $counts = $package ? array(
        'Products'      => count($package['products']),
        'Categories'    => count($package['terms']['product_cat']),
        'Blog posts'    => count($package['posts']),
        'Pages'         => count($package['pages']),
        'Menus'         => count($package['menus']),
        'Images'        => count($package['images']),
    ) : array();
    ?>
    <div class="wrap lekhoni-demo">
        <h1>Demo Import</h1>
        <p class="description" style="max-width:760px">Fill the store with the লেখনী demo: Bangla stationery products with images, prices in ৳, variations and reviews, blog posts, the Home, About and Contact pages with all their sections, and the menus. Your own products, posts and pages are never deleted.</p>

        <?php if (! empty($state['imported'])) : ?>
            <div class="notice notice-success inline"><p>
                The demo was imported on <strong><?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), (int) $state['imported'])); ?></strong>.
                Importing again updates the demo items instead of adding copies.
                <a href="<?php echo esc_url(function_exists('lekhoni_frontend_url') ? lekhoni_frontend_url() . '/' : home_url('/')); ?>" target="_blank" rel="noopener">View the storefront →</a>
            </p></div>
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:24px;max-width:1100px;margin-top:16px">
            <div class="card" style="max-width:none;margin:0;padding:20px 24px">
                <h2 style="margin-top:0">What will be imported</h2>
                <ul style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px 16px;margin:0 0 16px">
                    <?php foreach ($counts as $label => $count) : ?>
                        <li style="margin:0"><strong style="font-size:18px"><?php echo esc_html((string) $count); ?></strong> <?php echo esc_html($label); ?></li>
                    <?php endforeach; ?>
                </ul>

                <form id="lekhoni-demo-form">
                    <fieldset style="margin:0 0 16px">
                        <label style="display:block;margin-bottom:10px">
                            <input type="checkbox" name="site" value="1" checked>
                            <strong>Use the demo homepage, menus and Theme Settings</strong><br>
                            <span class="description">Fills the Home, About and Contact pages (replacing their current fields), makes Home the front page and ব্লগ the posts page, assigns the menus and adds social links.</span>
                        </label>
                        <label style="display:block">
                            <input type="checkbox" name="store" value="1" checked>
                            <strong>Store settings for Bangladesh</strong><br>
                            <span class="description">Currency ৳ (BDT), a Bangladesh shipping zone (৳60, free over ৳১,০০০), cash on delivery and the demo coupon LEKHONI10.</span>
                        </label>
                    </fieldset>
                    <p>
                        <button type="submit" class="button button-primary button-hero" <?php disabled(! $ready); ?>>
                            <?php echo empty($state['imported']) ? 'Import demo' : 'Import again'; ?>
                        </button>
                    </p>
                </form>

                <div id="lekhoni-demo-progress" hidden>
                    <progress max="<?php echo esc_attr((string) count($steps)); ?>" value="0" style="width:100%;height:14px"></progress>
                    <p id="lekhoni-demo-status" aria-live="polite" style="font-weight:600"></p>
                    <ol id="lekhoni-demo-log" style="margin-left:20px;color:#50575e"></ol>
                </div>
            </div>

            <div>
                <img src="<?php echo esc_url($preview); ?>" alt="" style="width:100%;height:auto;border-radius:8px;border:1px solid #dcdcde">
                <div class="card" style="margin:16px 0 0;padding:12px 16px">
                    <h2 style="margin:0 0 8px;font-size:14px">Requirements</h2>
                    <ul style="margin:0">
                        <?php foreach ($requirements as $label => $check) : ?>
                            <li style="margin:4px 0">
                                <span class="dashicons <?php echo $check[0] ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>" style="color:<?php echo $check[0] ? '#00a32a' : '#d63638'; ?>" aria-hidden="true"></span>
                                <?php echo esc_html($label); ?>
                                <?php if (! $check[0]) : ?><br><span class="description"><?php echo esc_html($check[1]); ?></span><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php if (! empty($state['imported'])) : ?>
                    <div class="card" style="margin:16px 0 0;padding:12px 16px">
                        <h2 style="margin:0 0 8px;font-size:14px">Remove demo content</h2>
                        <p class="description" style="margin-top:0">Deletes the demo products, posts, pages it created, images, menus, categories, authors and coupon. Your own content and the settings stay.</p>
                        <button type="button" id="lekhoni-demo-remove" class="button button-link-delete">Remove demo content</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
    (function () {
        var ajax = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
        var nonce = <?php echo wp_json_encode(wp_create_nonce('lekhoni_demo')); ?>;
        var steps = <?php echo wp_json_encode($steps); ?>;
        var form = document.getElementById('lekhoni-demo-form');
        var box = document.getElementById('lekhoni-demo-progress');
        var bar = box.querySelector('progress');
        var status = document.getElementById('lekhoni-demo-status');
        var log = document.getElementById('lekhoni-demo-log');

        function post (data) {
            var body = new FormData();
            body.append('_ajax_nonce', nonce);
            Object.keys(data).forEach(function (key) { body.append(key, data[key]); });
            return fetch(ajax, { method: 'POST', body: body, credentials: 'same-origin' }).then(function (res) {
                return res.json().catch(function () { throw new Error('The server returned an error (' + res.status + ').'); });
            }).then(function (json) {
                if (!json.success) throw new Error(json.data && json.data.message ? json.data.message : 'Unknown error');
                return json.data;
            });
        }

        function addLog (text) {
            var li = document.createElement('li');
            li.textContent = text;
            log.appendChild(li);
        }

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            var button = form.querySelector('button');
            button.disabled = true;
            box.hidden = false;
            log.innerHTML = '';
            var options = { site: form.site.checked ? 1 : 0, store: form.store.checked ? 1 : 0 };
            var names = Object.keys(steps);
            try {
                for (var i = 0; i < names.length; i++) {
                    var offset = 0;
                    var result;
                    do {
                        status.textContent = steps[names[i]] + '…';
                        result = await post({ action: 'lekhoni_demo_step', step: names[i], offset: offset, site: options.site, store: options.store });
                        offset = result.next;
                        if (!result.done) status.textContent = result.message + '…';
                    } while (!result.done);
                    addLog(result.message);
                    bar.value = i + 1;
                }
                status.textContent = 'The demo is ready. Reloading…';
                setTimeout(function () { window.location.reload(); }, 1200);
            } catch (error) {
                status.textContent = 'Import stopped: ' + error.message + ' You can run it again; finished items are kept.';
                button.disabled = false;
            }
        });

        var remove = document.getElementById('lekhoni-demo-remove');
        if (remove) {
            remove.addEventListener('click', function () {
                if (!window.confirm('Delete all demo content? Your own products, posts and pages are kept.')) return;
                remove.disabled = true;
                remove.textContent = 'Removing…';
                post({ action: 'lekhoni_demo_remove' }).then(function () {
                    window.location.reload();
                }).catch(function (error) {
                    window.alert(error.message);
                    remove.disabled = false;
                    remove.textContent = 'Remove demo content';
                });
            });
        }
    })();
    </script>
    <?php
}

/**
 * AJAX: one import step.
 *
 * @return void
 */
function lekhoni_demo_ajax_step()
{
    check_ajax_referer('lekhoni_demo');
    if (! current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'You are not allowed to import content.'), 403);
    }
    $step    = sanitize_key(wp_unslash($_POST['step'] ?? ''));
    $offset  = absint($_POST['offset'] ?? 0);
    $options = array('site' => ! empty($_POST['site']), 'store' => ! empty($_POST['store']));
    try {
        wp_send_json_success(lekhoni_demo_run_step($step, $offset, $options));
    } catch (Throwable $e) {
        wp_send_json_error(array('message' => $e->getMessage()), 500);
    }
}
add_action('wp_ajax_lekhoni_demo_step', 'lekhoni_demo_ajax_step');

/**
 * AJAX: remove the demo content.
 *
 * @return void
 */
function lekhoni_demo_ajax_remove()
{
    check_ajax_referer('lekhoni_demo');
    if (! current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'You are not allowed to remove content.'), 403);
    }
    try {
        wp_send_json_success(array('removed' => lekhoni_demo_remove()));
    } catch (Throwable $e) {
        wp_send_json_error(array('message' => $e->getMessage()), 500);
    }
}
add_action('wp_ajax_lekhoni_demo_remove', 'lekhoni_demo_ajax_remove');

/**
 * After the theme is activated, suggest importing the demo.
 *
 * @return void
 */
function lekhoni_demo_on_activation()
{
    if (empty(lekhoni_demo_state()['imported'])) {
        update_option('lekhoni_demo_notice', 1, false);
    }
}
add_action('after_switch_theme', 'lekhoni_demo_on_activation');

/**
 * The notice (until dismissed or imported).
 *
 * @return void
 */
function lekhoni_demo_admin_notice()
{
    if (! get_option('lekhoni_demo_notice') || ! current_user_can('manage_options')) {
        return;
    }
    $screen = get_current_screen();
    if ($screen && false !== strpos((string) $screen->id, LEKHONI_DEMO_PAGE)) {
        return;
    }
    if (! empty(lekhoni_demo_state()['imported'])) {
        delete_option('lekhoni_demo_notice');
        return;
    }
    $dismiss = wp_nonce_url(add_query_arg('lekhoni-demo-dismiss', '1'), 'lekhoni_demo_dismiss');
    ?>
    <div class="notice notice-info">
        <p><strong>লেখনী theme:</strong> start with the demo store, products, pages and menus in one click.</p>
        <p>
            <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=' . LEKHONI_DEMO_PAGE)); ?>">Import demo content</a>
            <a class="button-link" style="margin-left:8px" href="<?php echo esc_url($dismiss); ?>">Dismiss</a>
        </p>
    </div>
    <?php
}
add_action('admin_notices', 'lekhoni_demo_admin_notice');

/**
 * Handles "Dismiss" on the notice.
 *
 * @return void
 */
function lekhoni_demo_dismiss_notice()
{
    if (isset($_GET['lekhoni-demo-dismiss']) && current_user_can('manage_options') && check_admin_referer('lekhoni_demo_dismiss')) {
        delete_option('lekhoni_demo_notice');
        wp_safe_redirect(remove_query_arg(array('lekhoni-demo-dismiss', '_wpnonce')));
        exit;
    }
}
add_action('admin_init', 'lekhoni_demo_dismiss_notice');
