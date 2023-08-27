<?php
namespace SabaiApps\Directories\Component\Entity\DisplayElement;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Exception;

class ParentFieldDisplayElement extends FieldDisplayElement
{    
    protected function _doGetField($bundle)
    {
        if (!$parent_bundle = $this->_application->Entity_Bundle($bundle->info['parent'])) {
            throw new Exception\RuntimeException('Invalid entity parent bundle');
        }
        $field_name = substr($this->_name, 20); // remove entity_parent_field_ part
        return $this->_application->Entity_Field($parent_bundle, $field_name);
    }
    
    protected function _displayElementInfo(Entity\Model\Bundle $bundle)
    {
        if (!$parent_bundle = $this->_application->Entity_Bundle($bundle->info['parent'])) {
            throw new Exception\RuntimeException('Invalid entity parent bundle');
        }
        $field = $this->_getField($bundle);
        
        return array(
            'type' => 'parent',
            'label' => $parent_bundle->getLabel('singular') . ' - ' . $field->getFieldLabel(),
            'description' => sprintf(__('Field name: %s', 'directories'), $field->getFieldName()),
            'class' => 'drts-field-type-' . str_replace('_', '-', $field->getFieldType()) . ' drts-field-name-' . str_replace('_', '-', $field->getFieldName()),
            'default_settings' => array(
                'label' => 'none',
                'label_custom' => null,
                'label_icon' => null,
                'label_icon_size' => null,
                'renderer' => null,
                'renderer_settings' => [],
            ),
            'icon' => $this->_application->Field_Type($field->getFieldType())->fieldTypeInfo('icon'),
            'headingable' => false,
            'cacheable' => $this->_application->Field_Type($field->getFieldType())->fieldTypeInfo('cacheable'),
            'designable' => ['margin', 'font'],
        );
    }

    public function displayElementRender(Entity\Model\Bundle $bundle, array $element, $var)
    {
        if (!$entity = $this->_application->Entity_ParentEntity($var)) return;
        
        return parent::displayElementRender($bundle, $element, $entity);
    }
    
    public function displayElementPreRender(Entity\Model\Bundle $bundle, array $element, &$var)
    {
        $entities = [];
        foreach ($var['entities'] as $entity) {
            if (!$parent_id = $entity->getParentId()) continue;
            
            if (isset($entities[$parent_id])) {
                // Re-assign parent with already fetched parent so that parent entities with same IDs are updated at once
                $entity->setParent($entities[$parent_id]);
                continue;
            }
            if ($parent = $this->_application->Entity_ParentEntity($entity)) {
                $entities[$parent_id] = $parent;
            }
        }
        if (empty($entities)) return;

        $_var['entities'] = $entities;
        parent::displayElementPreRender($bundle, $element, $_var);
    }
    
    public function displayElementTitle(Entity\Model\Bundle $bundle, array $element)
    {
        $field = $this->_getField($bundle);
        return $this->_application->Display_ElementLabelSettingsForm_label(
            $element['settings'],
            null,
            $this->_application->Entity_Bundle($bundle->info['parent'])->getLabel('singular') . ' - ' . $field->getFieldLabel()
        );
    }
}
