<?php
namespace SabaiApps\Directories\Component\Display\Element;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display;

class SeparatorElement extends AbstractElement
{
    protected function _displayElementInfo(Entity\Model\Bundle $bundle)
    {
        return array(
            'type' => 'utility',
            'label' => _x('Separator', 'display element name', 'directories'),
            'description' => 'Horizontal separator line',
            'default_settings' => array(
                'border' => array(
                    'style' => 'solid',
                    'color' => '#999999',
                    'secondary_color' => null,
                    'width' => 5,
                    'radius' => 0,
                ),
            ),
            'icon' => 'fas fa-minus',
            'headingable' => false,
            'designable' => ['margin'],
        );
    }
    
    public function displayElementSettingsForm(Entity\Model\Bundle $bundle, array $settings, Display\Model\Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        $border_settings = $this->_application->Display_BorderSettingsForm($settings['border'], array_merge($parents, array('border')));
        unset($border_settings['style']['#options']['']);
        return array(
            'border' => $border_settings,
        );
    }
    
    public function displayElementRender(Entity\Model\Bundle $bundle, array $element, $var)
    {
        $settings = $element['settings'];
        switch ($settings['border']['style']) {
            case 'solid':
                $style = sprintf('border-top:%dpx solid %s;', $settings['border']['width'], $settings['border']['color']);
                break;
            case 'dotted':
            case 'dashed':
                $style = sprintf('border-top:%dpx %s %s;', $settings['border']['width'], $settings['border']['style'], $settings['border']['color']);
                if (!empty($settings['border']['secondary_color'])) {
                    $style .= 'background-color:' . $settings['border']['secondary_color'];
                }
                break;
            case 'double':
                $style = sprintf('border-top:%dpx double %s;', $settings['border']['width'] + 2, $settings['border']['color']);
                if (!empty($settings['border']['secondary_color'])) {
                    $style .= 'background-color:' . $settings['border']['secondary_color'];
                }
                break;
            case 'gradient':
                $style = sprintf(
                    'height:%1$dpx; background-image:-webkit-linear-gradient(right, %2$s, %3$s); background-image:linear-gradient(to right, %2$s, %3$s);',
                    $settings['border']['width'],
                    $settings['border']['color'],
                    empty($settings['border']['secondary_color']) ? $settings['border']['color'] : $settings['border']['secondary_color']
                );
                break;
        }
        return sprintf('<hr style="border:0;border-radius:' . (int)$settings['border']['radius'] . 'px;' . $style . '" />');
    }

    protected function _displayElementReadableInfo(Entity\Model\Bundle $bundle, Display\Model\Element $element)
    {
        $settings = $element->data['settings'];
        $styles = $this->_application->Display_BorderSettingsForm_styleOptions();
        $color_format = '<span style="width:15px;background-color:%1$s;display:inline-block;">&nbsp;</span>';
        $ret = [
            'style' => [
                'label' => __('Border style', 'directories'),
                'value' => isset($settings['border']['style']) ? $styles[$settings['border']['style']] : null,
            ],
            'color' => [
                'label' => __('Border color', 'directories'),
                'value' => !empty($settings['border']['color'])
                    ? sprintf($color_format, $this->_application->H($settings['border']['color']))
                    : null,
                'is_html' => true,
            ],
            'width' => [
                'label' => __('Border width', 'directories'),
                'value' => isset($settings['border']['width']) ? $settings['border']['width'] . 'px' : null,
            ],
            'radius' => [
                'label' => __('Border radius', 'directories'),
                'value' => isset($settings['border']['radius']) ? $settings['border']['radius'] . 'px' : null,
            ],
        ];
        if ($settings['border']['style'] !== 'solid'
            && !empty($settings['border']['secondary_color'])
        ) {
            $ret['color']['value'] .= sprintf($color_format, $this->_application->H($settings['border']['secondary_color']));
        }

        return ['settings' => ['value' => $ret]];
    }
}