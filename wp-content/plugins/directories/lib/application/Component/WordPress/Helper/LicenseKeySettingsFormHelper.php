<?php
namespace SabaiApps\Directories\Component\WordPress\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Form\Form;

class LicenseKeySettingsFormHelper
{   
    public function help(Application $application, array $parents = [])
    {  
        $form = [
            '#title' => __('License Keys', 'directories'),
        ];
        if ($plugins = $application->getPlatform()->getSabaiPlugins(true, true)) {
            $license_keys = $application->getPlatform()->getOption('license_keys', []);
            foreach (array_keys($plugins) as $plugin_name) {
                if (isset($plugins[$plugin_name]['SabaiApps License Package']) && strlen($plugins[$plugin_name]['SabaiApps License Package'])) {
                    $plugins[$plugin_name] = $plugins[$plugin_name]['SabaiApps License Package'];
                    continue;
                }
 
                $form[$plugin_name] = [
                    '#type' => 'textfield',
                    '#min_length' => 36,
                    '#max_length' => 36,
                    '#regex' => '/^[a-z0-9-]+$/',
                    '#default_value' => isset($license_keys[$plugin_name]) && $license_keys[$plugin_name]['type'] === 'envato' ? $license_keys[$plugin_name]['value'] : null,
                    '#title' => $plugins[$plugin_name]['Name'],
                    '#size' => 30,
                    '#horizontal' => true,
                    '#placeholder' => 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX',
                    '#weight' => strpos($plugins[$plugin_name]['Name'], '-') ? 1 : 0,
                ];
                $plugins[$plugin_name] = $plugin_name;
            }
            $form['#submit'][0] = [
                [[$this, '_submitForm'], [$application, $parents, $plugins]],
            ];
        }
        
        return $form;
    }
    
    public function _submitForm(Form $form, Application $application, $parents, $plugins)
    {
        $current = (array)$application->getPlatform()->getOption('license_keys');
        $license_keys = [];
        $value = $form->getValue($parents);
        foreach ($plugins as $plugin_name => $license_plugins) {
            foreach (explode(',', $license_plugins) as $license_plugin) {
                if (!empty($value[$license_plugin])) {
                    $license_keys[$plugin_name] = array(
                        'type' => 'envato',
                        'value' => $value[$license_plugin],
                        'package' => $license_plugin,
                    );
                    if (!isset($current[$plugin_name])
                        || $value[$license_plugin] !== $current[$plugin_name]
                    ) {
                        $application->getPlatform()->deleteOption('_' . md5(site_url() . $plugin_name));
                    }
                    continue 2;
                }
            }
        }
        $application->getPlatform()->setOption('license_keys', $license_keys, false);
    }
}
