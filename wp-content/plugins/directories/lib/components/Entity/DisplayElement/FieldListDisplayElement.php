<?php
namespace SabaiApps\Directories\Component\Entity\DisplayElement;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display;

class FieldListDisplayElement extends Display\Element\AbstractElement
{
    protected function _displayElementInfo(Entity\Model\Bundle $bundle)
    {
        return array(
            'type' => 'utility',
            'label' => _x('Field List', 'display element name', 'directories'),
            'description' => __('Display fields as a list', 'directories'),
            'default_settings' => [
                'size' => '',
                'no_border' => false,
                'inline' => true,
            ],
            'child_element_type' => 'field',
            'add_child_label' => __('Add Field', 'directories'),
            'containable' => true,
            'icon' => 'fas fa-list',
            'designable' => ['margin', 'padding', 'font'],
        );
    }
    
    protected function _displayElementSupports(Entity\Model\Bundle $bundle, Display\Model\Display $display)
    {
        return $display->type === 'entity';
    }
    
    protected function _getSizeOptions()
    {
        return [
            'sm' => __('Small', 'directories'),
            '' => __('Medium', 'directories'),
        ];
    }
    
    public function displayElementSettingsForm(Entity\Model\Bundle $bundle, array $settings, Display\Model\Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        return [
            'size' => [
                '#type' => 'select',
                '#title' => __('Size', 'directories'),
                '#options' => $this->_getSizeOptions(),
                '#default_value' => $settings['size'],
                '#horizontal' => true,
            ],
            'no_border' => [
                '#type' => 'checkbox',
                '#title' => __('No border', 'directories'),
                '#default_value' => !empty($settings['no_border']),
                '#horizontal' => true,
                '#states' => [
                    'invisible' => [
                        sprintf('select[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['size']))) => ['value' => 'sm'],
                    ],
                ],
            ],
            'inline' => [
                '#type' => 'checkbox',
                '#title' => __('Display fields inline', 'directories'),
                '#default_value' => !empty($settings['inline']),
                '#horizontal' => true,
            ],
        ];
    }

    public function displayElementRender(Entity\Model\Bundle $bundle, array $element, $var)
    {   
        if (empty($element['children'])) return;

        $bundle = $this->_application->Entity_Bundle($var);        
        $html = [];
        foreach ($element['children'] as $child) {
            if ($child['type'] !== 'field') continue;

            $label_type = $child['settings']['label'];
            $label_is_no_text = $label_type === 'icon' || $label_type === 'none';
            $child['settings']['label'] = 'none';
            if (!$field_value = $this->_application->callHelper('Display_Render_element', array($bundle, $child, $var))) continue;

            if (strpos($child['name'], 'entity_field_') === 0) {
                $child_field_name = substr($child['name'], 13); // remove entity_field_ part
                if (!$child_field = $this->_application->Entity_Field($bundle, $child_field_name)) continue; // this should not happen
            }

            $html[] = '<div class="' . DRTS_BS_PREFIX . 'list-group-item ' . DRTS_BS_PREFIX . 'px-0 ' . DRTS_BS_PREFIX . 'border-0">';
            if ($element['settings']['size'] !== 'sm'
                && $label_type !== 'icon'
                && $label_type !== 'none'
            ) {
                $html[] = '<div class="drts-entity-field ' . DRTS_BS_PREFIX . 'justify-content-between">';
            } else {
                $html[] = '<div class="drts-entity-field">';
            }
            if (!$label_is_no_text) {
                $label = $this->_application->Display_ElementLabelSettingsForm_label(
                    ['label' => $label_type] + $child['settings'],
                    $this->displayElementStringId('label', $child['_element_id'], $child['name']),
                    isset($child_field) ? $child_field->getFieldLabel(true) : ''
                );
            } else {
                $label = $child['title'];
            }

            $html[] = '<div class="drts-entity-field-label drts-entity-field-label-type-' . $label_type . '">' . $label . '</div>';
            $html[] = '<div class="drts-entity-field-value"' . (empty($child['inlineable']) ? ' style="flex-grow:1;"' : '') . '>' . $field_value . '</div>';
            $html[] = '</div></div>';
        }
        if (empty($html)) return '';
        
        $class = 'drts-entity-fieldlist';
        if (isset($element['settings']['inline']) && !$element['settings']['inline']) {
            $class .= ' drts-entity-fieldlist-no-inline';
        }
        if ($element['settings']['size'] === 'sm') {
            $class .= ' drts-entity-fieldlist-sm';
        } elseif (!empty($element['settings'])) {
            $class .= ' drts-entity-fieldlist-no-border';
        }
        
        return '<div class="' . DRTS_BS_PREFIX . 'list-group ' . DRTS_BS_PREFIX . 'list-group-flush ' . $class . '">' . implode(PHP_EOL, $html) . '</div>';
    }

    protected function _displayElementReadableInfo(Entity\Model\Bundle $bundle, Display\Model\Element $element)
    {
        $settings = $element->data['settings'];
        $ret = [
            'size' => [
                'label' => __('Size', 'directories'),
                'value' => $this->_getSizeOptions()[$settings['size']],
            ],
        ];
        if (!empty($settings['no_border'])) {
            $ret['no_border'] = [
                'label' => __('No border', 'directories'),
                'value' => true,
                'is_bool' => true,
            ];
        }

        return ['settings' => ['value' => $ret]];
    }
}
