<?php
namespace SabaiApps\Directories\Component\Entity\DisplayElement;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display;

class FieldTemplateDisplayElement extends Display\Element\TemplateElement
{
    protected function _displayElementInfo(Entity\Model\Bundle $bundle)
    {
        return array(
            'type' => 'field',
            'label' => _x('Field Template', 'display element name', 'directories'),
            'description' => __('Load and display template file', 'directories'),
            'default_settings' => array(
                'template' => null,
                'label' => 'none',
                'label_custom' => null,
                'label_icon' => null,
                'label_icon_size' => null,
                'label_as_heading' => false,
            ),
            'icon' => 'fab fa-php',
            'inlineable' => true,
            'headingable' => false,
            'designable' => ['margin', 'padding'],
        );
    }
    
    public function displayElementSettingsForm(Entity\Model\Bundle $bundle, array $settings, Display\Model\Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        return parent::displayElementSettingsForm($bundle, $settings, $display, $parents, $tab, $isEdit, $submitValues)
            + $this->_application->Display_ElementLabelSettingsForm($settings, $parents, false, 5)
            + array(
                'label_as_heading' => array(
                    '#title' => __('Show label as heading', 'directories'),
                    '#type' => 'checkbox',
                    '#default_value' => !empty($settings['label_as_heading']),
                    '#horizontal' => true,
                    '#states' => array(
                        'invisible' => array(
                            sprintf('select[name="%s[label]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'none'),
                        ),
                    ),
                    '#weight' => 10,
                ),
            );
    }
    
    public function displayElementRender(Entity\Model\Bundle $bundle, array $element, $var)
    {
        if (!$rendered = parent::displayElementRender($bundle, $element, $var)) return;
        
        $settings = $element['settings'];
        $label_type = $settings['label'];
        $label = $this->_application->Display_ElementLabelSettingsForm_label($element['settings'], $this->displayElementStringId('label', $element['_element_id']));
        if (!strlen($label)) {
            $rendered['html'] = '<div class="drts-entity-field-value">' . $rendered['html'] . '</div>';
            return $rendered;
        }
        
        if (empty($settings['label_as_heading'])) {
            $heading_class = '';
        } else {
            $heading_class = ' drts-display-element-header';
            $label = '<span>' . $label . '</span>';
        }
        $rendered['html'] = '<div class="drts-entity-field-label drts-entity-field-label-type-' . $label_type . $heading_class . '">' . $label . '</div>'
            . '<div class="drts-entity-field-value">' . $rendered['html'] . '</div>';
        
        return $rendered;
    }
    
    public function displayElementTitle(Entity\Model\Bundle $bundle, array $element)
    {
        return $this->_application->Display_ElementLabelSettingsForm_label($element['settings']);
    }
}