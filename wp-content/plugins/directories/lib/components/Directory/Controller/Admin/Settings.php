<?php
namespace SabaiApps\Directories\Component\Directory\Controller\Admin;

use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Component\System\Controller\Admin\AbstractSettings;
use SabaiApps\Directories\Context;

class Settings extends AbstractSettings
{
    protected function _getSettingsForm(Context $context, array &$formStorage)
    {
        $form = [
            '#tabs' => [],
            '#tab_style' => 'pill_less_margin',
            '#name' => 'directory_admin_settings_form',
        ];
        $settings_form = $this->Filter('directory_admin_settings_form', ['tabs' => [], 'fields' => []]);
        $settings_form['tabs']['Directory'] = [
            '#title' => _x('Directory', 'settings tab',  'directories'),
            '#weight' => 1,
        ];
        $settings_form['tabs']['System'] = [
            '#title' => __('System', 'directories'),
            '#weight' => 99,
        ];
        foreach ($settings_form['tabs'] as $tab_name => $tab) {
            $form['#tabs'][$tab_name] = $tab;
            $form[$tab_name] = [
                '#tree' => false,
                '#tab' => $tab_name,
            ];
        }
        foreach (array_keys($settings_form['fields']) as $key) {
            if (!isset($settings_form['fields'][$key]['#tab'])) continue;

            $tab = $settings_form['fields'][$key]['#tab'];
            if (!isset($form[$tab])) continue;

            unset($settings_form['fields'][$key]['#tab']);
            $form[$tab][$key] = $settings_form['fields'][$key];
            if (!isset($form[$tab][$key]['#tree'])) $form[$tab][$key]['#tree'] = true;
        }

        return $form;
    }

    protected function _getComponentConfigFormSettings(Form\Form $form)
    {
        $settings = [];
        foreach (array_keys($form->settings) as $tab_name) {
            if (strpos($tab_name, '#') === 0) continue;

            foreach (array_keys($form->settings[$tab_name]) as $key) {
                if (!empty($form->settings[$tab_name][$key]['#component'])) {
                    $settings[$key] = $form->settings[$tab_name][$key];
                }
            }
        }
        return $settings;
    }
}