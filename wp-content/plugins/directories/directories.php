<?php
/**
 * Plugin Name: Directories
 * Plugin URI: https://directoriespro.com/
 * Description: Directories plugin for WordPress.
 * Author: SabaiApps
 * Author URI: https://codecanyon.net/user/onokazu/portfolio?ref=onokazu
 * Text Domain: directories
 * Domain Path: /languages
 * Version: 1.3.108
 */

if (!class_exists('\SabaiApps\Directories\Platform\WordPress\Loader', false)) {
    require __DIR__ . '/lib/application/Platform/WordPress/Loader.php';
}
\SabaiApps\Directories\Platform\WordPress\Loader::register(__DIR__, '1.3.108');

add_filter('drts_core_component_paths', function ($paths) {
    $paths['directories'] = [__DIR__ . '/lib/components', '1.3.108'];
    return $paths;
});

if (!function_exists('drts')) {
    function drts() {
        return \SabaiApps\Directories\Platform\WordPress\Loader::getPlatform()->getApplication();
    }
}

if (is_admin()) {
    add_action('plugin_row_meta', function($meta, $file, $data, $status) {
        if ($file === 'directories/directories.php') {
            $meta['documentation'] = '<a href="https://directoriespro.com/documentation/" target="_blank">Documentation</a>';
            $meta['changelog'] = '<a href="https://directoriespro.com/category/releases/" target="_blank">Change log</a>';
        }
        return $meta;
    }, 10, 4);
    add_action('in_plugin_update_message-directories/directories.php', function ($plugin, $response) {
        printf(
            ' ' . esc_html__('Make sure to bulk update all Directories plugins at once. See %s for more details.', 'directories'),
            '<a href="https://directoriespro.com/documentation/updating-and-uninstalling/updating-directories-pro.html#automatic-update" target="_blank">Updating Directories Pro</a>'
        );
        if (($new_version_parts = explode('.', $response->new_version))
            && ($old_version_parts = explode('.', $plugin['Version']))
            && ($new_version_parts[1] > $old_version_parts[1] || $new_version_parts[0] > $old_version_parts[0])
        ) {
            echo '<br /><br /><strong>' . esc_html__('Attention!', 'directories') . '</strong> ' . esc_html__('The update available is a major update of the plugin. Please be sure to backup your site before updating since there is no going back.', 'directories');
        }
        if ($pos = strpos($response->new_version, '-dev')) {
            printf(
                '<br /><br /><strong>' . esc_html__('Attention!', 'directories') . '</strong> ' . esc_html__('The update available is a development version of %s. If you do not feel comfortable to use a development version, please wait for the stable release.', 'directories'),
                substr($response->new_version, 0, $pos)
            );
        }
    }, 10, 2);
}