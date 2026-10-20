<?php

/**
 * One-click demo import: WP-CLI commands (same steps as the admin page).
 *
 *     wp lekhoni demo list
 *     wp lekhoni demo import [--demo=<id>] [--no-site] [--no-store] [--keep]
 *     wp lekhoni demo remove [--yes]
 *
 * @package WordPress
 * @subpackage Lekhoni
 * @since Lekhoni 1.0
 */

if (! defined('WP_CLI') || ! WP_CLI) {
    return;
}

/**
 * Imports or removes the লেখনী demo content.
 */
class Lekhoni_Demo_Command
{
    /**
     * Lists the homepage demos.
     *
     * @return void
     */
    public function list()
    {
        $installed = lekhoni_demo_installed();
        $rows      = array();
        foreach (lekhoni_demo_packages() as $id => $package) {
            $counts = $package['meta']['counts'] ?? array();
            $rows[] = array(
                'id'        => $id,
                'name'      => $package['meta']['name'] ?? $id,
                'products'  => $counts['products'] ?? count($package['products']),
                'posts'     => $counts['posts'] ?? count($package['posts']),
                'installed' => $id === $installed ? 'yes' : '',
            );
        }
        \WP_CLI\Utils\format_items('table', $rows, array('id', 'name', 'products', 'posts', 'installed'));
    }

    /**
     * Imports a homepage demo.
     *
     * ## OPTIONS
     *
     * [--demo=<id>]
     * : stationery, ecommerce or cosmetics (see `wp lekhoni demo list`). Default: the installed demo, else stationery.
     *
     * [--keep]
     * : When a different demo is installed, keep it instead of removing it first.
     *
     * [--no-site]
     * : Don't change the homepage fields, Reading settings, menu locations or Theme Settings.
     *
     * [--no-store]
     * : Don't change WooCommerce store settings (currency, shipping zone, cash on delivery, coupon).
     *
     * @param array $args       Positional arguments.
     * @param array $assoc_args Flags.
     * @return void
     */
    public function import($args, $assoc_args)
    {
        $demo = (string) \WP_CLI\Utils\get_flag_value($assoc_args, 'demo', lekhoni_demo_installed());
        if ('' !== $demo && ! isset(lekhoni_demo_packages()[$demo])) {
            WP_CLI::error(sprintf('Unknown demo "%s". Available: %s', $demo, implode(', ', array_keys(lekhoni_demo_packages()))));
        }
        $options = array(
            'demo'    => $demo,
            'site'    => \WP_CLI\Utils\get_flag_value($assoc_args, 'site', true),
            'store'   => \WP_CLI\Utils\get_flag_value($assoc_args, 'store', true),
            'replace' => ! \WP_CLI\Utils\get_flag_value($assoc_args, 'keep', false),
        );
        foreach (lekhoni_demo_steps() as $step => $label) {
            $offset = 0;
            do {
                try {
                    $result = lekhoni_demo_run_step($step, $offset, $options);
                } catch (Throwable $e) {
                    WP_CLI::error($label . ': ' . $e->getMessage());
                }
                $offset = $result['next'];
            } while (! $result['done']);
            WP_CLI::log('✓ ' . $result['message']);
        }
        WP_CLI::success('Demo content imported.');
    }

    /**
     * Removes everything the demo created.
     *
     * ## OPTIONS
     *
     * [--yes]
     * : Skip the confirmation.
     *
     * @param array $args       Positional arguments.
     * @param array $assoc_args Flags.
     * @return void
     */
    public function remove($args, $assoc_args)
    {
        WP_CLI::confirm('Delete all demo content? Your own content is kept.', $assoc_args);
        foreach (lekhoni_demo_remove() as $kind => $count) {
            WP_CLI::log(sprintf('%s: %d', $kind, $count));
        }
        WP_CLI::success('Demo content removed.');
    }
}

WP_CLI::add_command('lekhoni demo', 'Lekhoni_Demo_Command');
