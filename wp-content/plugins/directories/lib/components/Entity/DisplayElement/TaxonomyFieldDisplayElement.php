<?php
namespace SabaiApps\Directories\Component\Entity\DisplayElement;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Display\Model\Display;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Component\Entity\Model\Bundle;

class TaxonomyFieldDisplayElement extends FieldDisplayElement
{
    protected $_taxonomy;

    public function __construct(Application $application, $name, $taxonomy)
    {
        parent::__construct($application, $name);
        $this->_taxonomy = $taxonomy;
    }

    protected function _doGetField($bundle)
    {
        if (!$taxonomy_bundle = $this->_application->Entity_Bundle($this->_taxonomy, $bundle->component, $bundle->group)) {
            throw new Exception\RuntimeException('Invalid taxonomy bundle');
        }
        $field_name = substr($this->_name, 17); // remove entity_tax_field_ part
        return $this->_application->Entity_Field($taxonomy_bundle, $field_name);
    }
    
    protected function _displayElementInfo(Bundle $bundle)
    {
        if (!$taxonomy_bundle = $this->_application->Entity_Bundle($this->_taxonomy, $bundle->component, $bundle->group)) {
            throw new Exception\RuntimeException('Invalid taxonomy bundle: ' . $this->_taxonomy);
        }
        $field = $this->_getField($bundle);
        
        return [
            'type' => 'taxonomy',
            'label' => $taxonomy_bundle->getLabel('singular') . ' - ' . $field->getFieldLabel(),
            'description' => sprintf(__('Field name: %s', 'directories'), $field->getFieldName()),
            'class' => 'drts-field-type-' . str_replace('_', '-', $field->getFieldType()) . ' drts-field-name-' . str_replace('_', '-', $field->getFieldName()),
            'default_settings' => array(
                'label' => 'none',
                'label_custom' => null,
                'label_icon' => null,
                'label_icon_size' => null,
                'renderer' => null,
                'renderer_settings' => [],
                'separator' => '&nbsp;',
            ),
            'icon' => $this->_application->Field_Type($field->getFieldType())->fieldTypeInfo('icon'),
            'headingable' => false,
            'cacheable' => $this->_application->Field_Type($field->getFieldType())->fieldTypeInfo('cacheable'),
            'designable' => ['margin', 'font'],
        ];
    }

    public function displayElementSettingsForm(Bundle $bundle, array $settings, Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        $form = (array)parent::displayElementSettingsForm($bundle, $settings, $display, $parents, $tab, $isEdit, $submitValues);
        $form['separator'] = [
            '#title' => __('Separator', 'directories'),
            '#type' => 'textfield',
            '#default_value' => $settings['separator'],
            '#horizontal' => true,
        ];
        return $form;
    }

    public function displayElementRender(Bundle $bundle, array $element, $var)
    {
        if (!$entities = $var->getFieldValue($this->_taxonomy)) return;

        $this->_application->Entity_Field_load('term', $entities);
        $ret = [];
        foreach (array_keys($entities) as $key) {
            $rendered = parent::displayElementRender($bundle, $element, $entities[$key]);
            if (is_array($rendered)) {
                if (isset($rendered['raw'])) {
                    $rendered = $rendered['raw'];
                } elseif (isset($rendered['html'])) {
                    $rendered = $rendered['html'];
                } else {
                    continue;
                }
            } else {
                $rendered = (string)$rendered;
            }
            $ret[] = $rendered;
        }
        return implode($element['settings']['separator'], $ret);
    }
    
    public function displayElementTitle(Bundle $bundle, array $element)
    {
        $field = $this->_getField($bundle);
        return $this->_application->Display_ElementLabelSettingsForm_label(
            $element['settings'],
            null,
            $this->_application->Entity_Bundle($this->_taxonomy, $bundle->component, $bundle->group)->getLabel('singular') . ' - ' . $field->getFieldLabel()
        );
    }
}
