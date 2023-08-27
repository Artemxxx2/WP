<?php
namespace SabaiApps\Directories\Component\Display\Helper;

use SabaiApps\Directories\Application;

class ElementLabelSettingsFormHelper
{
    public function help(Application $application, array $settings, array $parents, $isFormField = true, $weight = -10)
    {
        $label_options = array(
            'custom' => __('Custom label', 'directories'),
            'custom_icon' => __('Icon', 'directories') . ' + ' . __('Custom label', 'directories'),
            'icon' => __('Icon', 'directories'),
            'none' => __('None', 'directories')
        );
        if ($isFormField) {
            $label_options = array(
                'form' => __('Default label', 'directories'),
                'form_icon' => __('Icon', 'directories') . ' + ' . __('Default label', 'directories'),
            ) + $label_options;
            if (!isset($settings['label'])) $settings['label'] = 'form';
        } else {
            if (!isset($settings['label'])) $settings['label'] = 'none';
        }
        $settings += array(
            'label_custom' => null,
            'label_icon' => null,
            'label_icon_size' => '',
        );

        return array(
            '#tree' => true,
            '#tree_allow_override' => false,
            'label' => array(
                '#title' => __('Label', 'directories'),
                '#type' => 'select',
                '#options' => $label_options,
                '#default_value' => $settings['label'],
                '#horizontal' => true,
                '#weight' => $weight,
            ),
            'label_custom' => array(
                '#title' => __('Custom label', 'directories'),
                '#type' => 'textfield',
                '#default_value' => $settings['label_custom'],
                '#states' => array(
                    'visible' => array(
                        sprintf('select[name="%s[label]"]', $application->Form_FieldName($parents)) => array('type' => 'one', 'value' => ['custom', 'custom_icon'])
                    ),
                ),
                '#required' => array(array(__CLASS__, '_isLabelRequired'), array($parents, array('custom', 'custom_icon'))),
                '#horizontal' => true,
                '#weight' => ++$weight,
            ),
            'label_icon' => array(
                '#title' => __('Icon', 'directories'),
                '#type' => 'iconpicker',
                '#default_value' => $settings['label_icon'],
                '#states' => array(
                    'visible' => array(
                        sprintf('select[name="%s[label]"]', $application->Form_FieldName($parents)) => array('type' => 'one', 'value' => ['form_icon', 'custom_icon', 'icon'])
                    ),
                ),
                '#required' => array(array(__CLASS__, '_isLabelRequired'), array($parents, array('form_icon', 'custom_icon', 'icon'))),
                '#horizontal' => true,
                '#weight' => ++$weight,
            ),
            'label_icon_size' => array(
                '#title' => __('Icon size', 'directories'),
                '#type' => 'select',
                '#options' => array(
                    '' => __('Normal', 'directories'),
                    'fa-lg' => __('Large', 'directories'),
                    'fa-2x' => '2x',
                    'fa-3x' => '3x',
                    'fa-5x' => '5x',
                    'fa-7x' => '7x',
                    'fa-10x' => '10x',
                ),
                '#default_value' => $settings['label_icon_size'],
                '#horizontal' => true,
                '#weight' => ++$weight,
                '#states' => array(
                    'visible' => array(
                        sprintf('select[name="%s[label]"]', $application->Form_FieldName($parents)) => array('type' => 'one', 'value' => ['form_icon', 'custom_icon', 'icon'])
                    ),
                ),
            ),
        );
    }

    public static function _isLabelRequired($form, $parents, $types)
    {
        $form_values = $form->getValue($parents);
        return in_array($form_values['label'], $types);
    }

    public function label(Application $application, array $settings, $stringId = null, $formFieldLabel = '')
    {
        if (!isset($settings['label'])) return '';

        switch ($settings['label']) {
            case 'custom':
                return $stringId ? $application->H($application->System_TranslateString($settings['label_custom'], $stringId, 'display_element')) : $settings['label_custom'];
            case 'custom_icon':
                $label = $stringId ? $application->H($application->System_TranslateString($settings['label_custom'], $stringId, 'display_element')) : $settings['label_custom'];
                if ($icon = $this->_getIcon($settings)) {
                    $label = $icon . ' ' . $label;
                }
                return $label;
            case 'icon':
                return $this->_getIcon($settings);
            case 'form':
                return $application->H($formFieldLabel);
            case 'form_icon':
                $label = $application->H($formFieldLabel);
                if ($icon = $this->_getIcon($settings)) {
                    $label = $icon . ' ' . $label;
                }
                return $label;
            default:
                return '';
        }
    }

    public function registerLabel(Application $application, array $settings, $stringId)
    {
        if (!in_array($settings['label'], array('custom', 'custom_icon'))) return;

        $application->getPlatform()->registerString($settings['label_custom'], $stringId, 'display_element');
    }

    protected function _getIcon(array $settings)
    {
        return $settings['label_icon'] ? '<i class="fa-fw ' . $settings['label_icon'] . ' ' . $settings['label_icon_size'] . '"></i>' : '';
    }
}
