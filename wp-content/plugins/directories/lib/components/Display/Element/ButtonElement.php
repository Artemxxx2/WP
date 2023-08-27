<?php
namespace SabaiApps\Directories\Component\Display\Element;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display;

class ButtonElement extends AbstractElement
{
    protected function _displayElementInfo(Entity\Model\Bundle $bundle)
    {
        return array(
            'type' => 'content',
            'label' => _x('Button', 'display element name', 'directories'),
            'description' => __('Call to action button', 'directories'),
            'default_settings' => array(
                'size' => '',
                'btn' => true,
                'dropdown' => false,
                'dropdown_icon' => 'fas fa-cog',
                'dropdown_label' => '',
                'dropdown_right' => false,
                'separate' => true,
                'arrangement' => null,
                'tooltip' => true,
                'buttons' => [],
            ),
            'icon' => 'far fa-hand-pointer',
            'inlineable' => true,
            'headingable' => false,
            'designable' => ['margin'],
        );
    }    
    
    protected function _getButtonSizeOptions()
    {
        return [
            'sm' => __('Small', 'directories'),
            '' => __('Medium', 'directories'),
            'lg' => __('Large', 'directories'),
        ];
    }
    
    public function displayElementSettingsForm(Entity\Model\Bundle $bundle, array $settings, Display\Model\Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        switch ($tab) {
            case 'buttons':
                return $this->_application->Display_Buttons_settingsForm($bundle, $settings['buttons'], $parents, ['general', 'settings', 'arrangement']);
            default:
                $options = $defaults = [];
                foreach (array_keys($this->_application->Display_Buttons($bundle)) as $btn_name) {
                    if (!$btn = $this->_application->Display_Buttons_impl($bundle, $btn_name, true)) continue;
                    
                    $info = $btn->displayButtonInfo($bundle);
                    if (!empty($info['multiple'])) {
                        foreach ($info['multiple'] as $_btn_name => $_btn_info) {
                            $_btn_name = $btn_name . '-' . $_btn_name;
                            $options[$_btn_name] = $_btn_info['label'];
                            if (!empty($_btn_info['default_checked'])) {
                                $defaults[] = $_btn_name;
                            }
                        } 
                    } else {
                        $options[$btn_name] = $info['label'];
                        if (!empty($info['default_checked'])) {
                            $defaults[] = $btn_name;
                        }
                    }
                }
                return array(
                    '#tabs' => array(
                        'buttons' => _x('Buttons', 'settings tab', 'directories'),
                    ),
                    'size'=> array(
                        '#type' => 'select',
                        '#title' => __('Button size', 'directories'),
                        '#options' => $this->_getButtonSizeOptions(),
                        '#horizontal' => true,
                        '#default_value' => $settings['size'],
                    ),
                    'dropdown' => array(
                        '#type' => 'checkbox',
                        '#title' => __('Display as single dropdown', 'directories'),
                        '#horizontal' => true,
                        '#default_value' => !empty($settings['dropdown']),
                    ),
                    'dropdown_icon' => array(
                        '#type' => 'iconpicker',
                        '#title' => __('Dropdown icon', 'directories'),
                        '#horizontal' => true,
                        '#default_value' => $settings['dropdown_icon'],
                        '#states' => array(
                            'visible' => array(
                                sprintf('input[name="%s[dropdown]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'checked', 'value' => true),
                            ),
                        ),
                    ),
                    'dropdown_label' => array(
                        '#type' => 'textfield',
                        '#title' => __('Dropdown label', 'directories'),
                        '#horizontal' => true,
                        '#default_value' => $settings['dropdown_label'],
                        '#states' => array(
                            'visible' => array(
                                sprintf('input[name="%s[dropdown]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'checked', 'value' => true),
                            ),
                        ),
                    ),
                    'dropdown_right' => array(
                        '#type' => 'checkbox',
                        '#title' => __('Right align dropdown items', 'directories'),
                        '#horizontal' => true,
                        '#default_value' => !empty($settings['dropdown_right']),
                        '#states' => array(
                            'visible' => array(
                                sprintf('input[name="%s[dropdown]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'checked', 'value' => true),
                            ),
                        ),
                    ),
                    'separate' => array(
                        '#type' => 'checkbox',
                        '#title' => __('Separate buttons', 'directories'),
                        '#horizontal' => true,
                        '#default_value' => !empty($settings['separate']),
                        '#states' => array(
                            'visible' => array(
                                sprintf('input[name="%s[dropdown]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'checked', 'value' => false),
                            ),
                        ),
                    ),
                    'tooltip' => array(
                        '#type' => 'checkbox',
                        '#title' => __('Show tooltip if no label', 'directories'),
                        '#horizontal' => true,
                        '#default_value' => !empty($settings['tooltip']),
                        '#states' => array(
                            'visible' => array(
                                sprintf('input[name="%s[dropdown]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'checked', 'value' => false),
                            ),
                        ),
                    ),
                    'arrangement' => array(
                        '#type' => 'sortablecheckboxes',
                        '#title' => __('Select buttons', 'directories'),
                        '#horizontal' => true,
                        '#options' => $options,
                        '#default_value' => isset($settings['arrangement']) ? $settings['arrangement'] : $defaults,
                    ),
                );
        }
    }
            
    protected function _displayElementSupports(Entity\Model\Bundle $bundle, Display\Model\Display $display)
    {
        if ($display->type !== 'entity') return false;
        
        $buttons = $this->_application->Display_Buttons($bundle);
        return !empty($buttons);
    }
    
    public function displayElementRender(Entity\Model\Bundle $bundle, array $element, $var)
    {
        $display_name = isset($element['display']) ? $element['display'] : 'detailed';
        return $this->_application->Display_Buttons_renderButtons($bundle, $element['settings'], $element['settings']['buttons'], $display_name, $var);
    }
    
    public function displayElementIsPreRenderable(Entity\Model\Bundle $bundle, array &$element)
    {
        foreach ($element['settings']['arrangement'] as $btn_name) {
            if (($btn = $this->_application->Display_Buttons_impl($bundle, $btn_name, true))
                && ($btn->displayButtonIsPreRenderable($bundle, (array)@$element['settings']['buttons'][$btn_name]['settings']))
            ) {
                return true;
            }
        }
        return false;
    }
    
    public function displayElementPreRender(Entity\Model\Bundle $bundle, array $element, &$var)
    {
        foreach ($element['settings']['arrangement'] as $btn_name) {
            if (($btn = $this->_application->Display_Buttons_impl($bundle, $btn_name, true))
                && ($btn->displayButtonIsPreRenderable($bundle, $btn_settings = (array)@$element['settings']['buttons'][$btn_name]['settings']))
            ) {
                $btn->displayButtonPreRender($bundle, $btn_settings, $var['entities']);
            }
        }
    }
    
    public function displayElementOnCreate(Entity\Model\Bundle $bundle, array &$data, $weight, Display\Model\Display $display, $elementName, $elementId)
    {
        $this->_unsetDisabledButtonSettings($data);
    }
    
    public function displayElementOnUpdate(Entity\Model\Bundle $bundle, array &$data, Display\Model\Element $element)
    {
        $this->_unsetDisabledButtonSettings($data);
    }
    
    protected function _unsetDisabledButtonSettings(array &$data)
    {
        if (empty($data['settings']['buttons'])) return;
        
        foreach (array_keys($data['settings']['buttons']) as $btn_name) {
            if (!in_array($btn_name, $data['settings']['arrangement'])) {
                unset($data['settings']['buttons'][$btn_name]);
            }
        }
    }

    protected function _displayElementReadableInfo(Entity\Model\Bundle $bundle, Display\Model\Element $element)
    {
        $settings = $element->data['settings'];
        if (empty($settings['arrangement'])) return;

        $ret = [
            'buttons' => [
                'label' => __('Buttons', 'directories'),
                'value' => implode(', ', $this->_application->Display_Buttons_buttonLabels($bundle, $settings['arrangement'])),
            ],
            'button_size' => [
                'label' => __('Button size', 'directories'),
                'value' => $this->_getButtonSizeOptions()[$settings['size']],
            ],
        ];
        return ['settings' => ['value' => $ret]];
    }
}