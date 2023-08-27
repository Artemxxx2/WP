<?php
namespace SabaiApps\Directories\Component\Display\Element;

use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Display\Model\Display;
use SabaiApps\Directories\Component\Display\Model\Element;

class TabElement extends AbstractElement
{
    protected function _displayElementInfo(Bundle $bundle)
    {
        return [
            'type' => 'utility',
            'label' => $label = _x('Tab', 'display element name', 'directories'),
            'default_settings' => [
                'label' => $label,
                'hash' => null,
                'hide_empty' => true,
            ],
            'containable' => true,
            'parent_element_name' => 'tabs',
            'icon' => 'far fa-folder',
            'headingable' => false,
            'designable' => ['padding'],
        ];
    }
    
    public function displayElementSettingsForm(Bundle $bundle, array $settings, Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        return [
            'label' => [
                '#title' => __('Tab label', 'directories'),
                '#type' => 'textfield',
                '#default_value' => $settings['label'],
                '#horizontal' => true,
                '#required' => true,
                '#description' => $display->type === 'entity' ? $this->_application->System_Util_availableTags($this->_application->Entity_Tokens($bundle, true)) : null,
                '#description_no_escape' => true,
            ],
            'hash' => [
                '#title' => __('URL hash', 'directories'),
                '#type' => 'textfield',
                '#default_value' => $settings['hash'],
                '#horizontal' => true,
                '#field_prefix' => '#',
                '#description' => __('Enter a custom URL hash or leave empty for default.', 'directories'),
            ],
            'hide_empty' => [
                '#type' => 'checkbox',
                '#title' => __('Hide if empty', 'directories'),
                '#default_value' => !empty($settings['hide_empty']),
                '#horizontal' => true,
            ],
        ];
    }

    public function displayElementTitle(Bundle $bundle, array $element)
    {
        return $this->_application->H($element['settings']['label']);
    }
    
    public function displayElementOnSaved(Bundle $bundle, Element $element)
    {
        $this->_registerString($element->data['settings']['label'], 'label',  $element->element_id);
        $this->_unregisterString('label', $element->id); // for old versions
    }
    
    public function displayElementRender(Bundle $bundle, array $element, $var)
    {
        $force_output = isset($element['settings']['hide_empty']) && empty($element['settings']['hide_empty']);
        if (!$html = $this->_renderChildren($bundle, $element['children'], $var)) {
            if (!$force_output) return;

            $html = '';
        } else {
            $html = implode(PHP_EOL, $html);
        }

        return [
            'html' => $html,
            'force_output' => $force_output,
        ];
    }
}