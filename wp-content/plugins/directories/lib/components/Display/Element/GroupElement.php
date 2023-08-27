<?php
namespace SabaiApps\Directories\Component\Display\Element;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display;

class GroupElement extends AbstractElement
{
    protected function _displayElementInfo(Entity\Model\Bundle $bundle)
    {
        return array(
            'type' => 'utility',
            'label' => _x('Group', 'display element name', 'directories'),
            'description' => __('Group multiple display elements', 'directories'),
            'default_settings' => array(
                'inline' => false,
                'separator' => null,
            ),
            'containable' => true,
            'icon' => 'far fa-object-group',
            'designable' => ['margin', 'padding', 'font'],
        );
    }
    
    public function displayElementSettingsForm(Entity\Model\Bundle $bundle, array $settings, Display\Model\Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        return array(
            'inline' => array(
                '#type' => 'checkbox',
                '#title' => __('Display inline', 'directories'),
                '#default_value' => !empty($settings['inline']),
                '#horizontal' => true,
            ),
            'separator' => array(
                '#type' => 'textfield',
                '#title' => __('Element separator', 'directories'),
                '#default_value' => false !== strpos($settings['separator'], '&nbsp;') ? strtr($settings['separator'], ['&nbsp;' => '&amp;nbsp;']) : $settings['separator'],
                '#horizontal' => true,
                '#no_trim' => true,
            ),
            'separator_margin' => [
                '#type' => 'checkbox',
                '#title' => __('Add horizontal margin to separator', 'directories'),
                '#default_value' => !isset($settings['separator_margin']) || $settings['separator_margin'],
                '#horizontal' => true,
                '#states' => $inline_separator_states = [
                    'visible' => [
                        sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['inline']))) => ['type' => 'checked', 'value' => true],
                        sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['separator']))) => ['type' => 'filled_no_trim', 'value' => true],
                    ],
                ],
            ],
            'nowrap' => [
                '#type' => 'checkbox',
                '#title' => __('Do not wrap elements', 'directories'),
                '#default_value' => $settings['nowrap'],
                '#horizontal' => true,
                '#states' => $inline_separator_states,
            ],
        );
    }
    
    public function displayElementRender(Entity\Model\Bundle $bundle, array $element, $var)
    {
        if (!$html = $this->_renderChildren($bundle, $element['children'], $var)) return;
        
        $settings = $element['settings'];
        $separator = PHP_EOL;
        $separator_class = 'drts-display-group-element-separator';
        if (!empty($settings['inline'])) {
            $class = 'drts-display-group-inline';
            if (!empty($settings['nowrap'])) {
                $class .= ' drts-display-group-inline-nowrap';
            }
            if (isset($settings['separator_margin'])
                && !$settings['separator_margin']
            ) {
                $separator_class .= ' ' . DRTS_BS_PREFIX . 'mx-0';
            }
            $separator = '<div class="' . $separator_class . '">' . $settings['separator'] . '</div>';
        } else {
            $class = '';
            if (strlen($settings['separator'])) {
                $separator = '<div class="' . $separator_class . '">' . $settings['separator'] . '</div>';
            }
        }
        
        return array(
            'html' => implode($separator, $html),
            'style' => '',
            'class' => $class,
        );
    }

    protected function _displayElementReadableInfo(Entity\Model\Bundle $bundle, Display\Model\Element $element)
    {
        $settings = $element->data['settings'];
        $ret = [
            'inline' => [
                'label' => __('Display inline', 'directories'),
                'value' => !empty($settings['inline']),
                'is_bool' => true,
            ],
            'separator' => [
                'label' => __('Element separator', 'directories'),
                'value' => $settings['separator'],
            ],
        ];
        return ['settings' => ['value' => $ret]];
    }
}
