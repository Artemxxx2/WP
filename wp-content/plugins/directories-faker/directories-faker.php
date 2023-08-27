<?php
/**
 * Plugin Name: Directories - Faker
 * Plugin URI: https://directoriespro.com/
 * Description: Dummy content generator add-on for Directories.
 * Author: SabaiApps
 * Author URI: https://codecanyon.net/user/onokazu/portfolio?ref=onokazu
 * Text Domain: directories-faker
 * Domain Path: /languages
 * Version: 1.3.108
 */

add_filter('drts_core_component_paths', function ($paths) {
    $paths['directories-faker'] = [__DIR__ . '/lib/components', '1.3.108'];
    return $paths;
});

if (is_admin()) {
    register_activation_hook(__FILE__, function () {
        if (!function_exists('drts')) die('The directories plugin needs to be activated first before activating this plugin!');
    });
    add_action('in_plugin_update_message-directories-faker/directories-faker.php', function ($plugin, $response) {
        printf(
            ' ' . esc_html__('Make sure to bulk update all Directories plugins at once. See %s for more details.', 'directories-faker'),
            '<a href="https://directoriespro.com/documentation/updating-and-uninstalling/updating-directories-pro.html#automatic-update" target="_blank">Updating Directories Pro</a>'
        );
        if (($new_version_parts = explode('.', $response->new_version))
            && ($old_version_parts = explode('.', $plugin['Version']))
            && ($new_version_parts[1] > $old_version_parts[1] || $new_version_parts[0] > $old_version_parts[0])
        ) {
            echo '<br /><br /><strong>' . esc_html__('Attention!', 'directories-faker') . '</strong> ' . esc_html__('The update available is a major update of the plugin. Please be sure to backup your site before updating since there is no going back.', 'directories-faker');
        }
        if ($pos = strpos($response->new_version, '-dev')) {
            printf(
                '<br /><br /><strong>' . esc_html__('Attention!', 'directories-faker') . '</strong> ' . esc_html__('The update available is a development version of %s. If you do not feel comfortable to use a development version, please wait for the stable release.', 'directories-faker'),
                substr($response->new_version, 0, $pos)
            );
        }
    }, 10, 2);
}
