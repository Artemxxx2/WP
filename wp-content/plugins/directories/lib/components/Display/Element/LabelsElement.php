<?php
namespace SabaiApps\Directories\Component\Display\Element;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display;

class LabelsElement extends AbstractElement
{
    protected function _displayElementInfo(Entity\Model\Bundle $bundle)
    {
        return [
            'type' => 'content',
            'label' => _x('Labels', 'display element name', 'directories'),
            'description' => __('Small tags for adding context', 'directories'),
            'default_settings' => array(
                'labels' => [],
                'style' => null,
            ),
            'inlineable' => true,
            'icon' => 'fas fa-tags',
            'designable' => ['margin', 'font'],
        ];
    }

    public function displayElementSettingsForm(Entity\Model\Bundle $bundle, array $settings, Display\Model\Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        switch ($tab) {
            case 'labels':
                return  $this->_application->Display_Labels_settingsForm($bundle, $settings['labels'], $parents, ['general', 'settings', 'arrangement']);
            default:
                return [
                    '#tabs' => [
                        'labels' => _x('Labels', 'settings tab', 'directories'),
                    ],
                    'arrangement' => [
                        '#type' => 'sortablecheckboxes',
                        '#title' => __('Label display order', 'directories'),
                        '#horizontal' => true,
                        '#options' => $options = $this->_application->Display_Labels_options($bundle),
                        '#default_value' => isset($settings['arrangement']) ? $settings['arrangement'] : array_keys($options),
                    ],
                    'style' => [
                        '#title' => __('Label style', 'directories'),
                        '#type' => 'select',
                        '#horizontal' => true,
                        '#options' => [
                            '' => __('Default', 'directories'),
                            'pill' => __('Oval', 'directories'),
                        ],
                        '#default_value' => $settings['style'],
                    ],
                ];
        }
    }

    protected function _displayElementSupports(Entity\Model\Bundle $bundle, Display\Model\Display $display)
    {
        if ($display->type !== 'entity'
            || !empty($bundle->info['is_taxonomy'])
        ) return false;

        $labels = $this->_application->Display_Labels($bundle);
        return !empty($labels);
    }

    public function displayElementRender(Entity\Model\Bundle $bundle, array $element, $var)
    {
        $settings = $element['settings'];
        return $this->_application->Display_Labels_renderLabels($bundle, $settings, $settings['labels'], $var);
    }

    protected function _displayElementReadableInfo(Entity\Model\Bundle $bundle, Display\Model\Element $element)
    {
        $settings = $element->data['settings'];
        if (empty($settings['arrangement'])) return;

        $ret = [
            'labels' => [
                'label' => __('Labels', 'directories'),
                'value' => implode(', ', $this->_application->Display_Labels_labelLabels($bundle, $settings['arrangement'])),
            ],
        ];
        return ['settings' => ['value' => $ret]];
    }
}
