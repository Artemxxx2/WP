<?php
namespace SabaiApps\Directories\Component\View\DisplayElement;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

class ReferencingEntitiesDisplayElement extends AbstractEntitiesDisplayElement
{
    protected $_bundleName, $_fieldName;

    public function __construct(Application $application, $name)
    {
        parent::__construct($application, $name);
        $_name = substr($name, strlen('view_referencing_entities_'));
        list($bundle_name, $field_name) = explode('-', $_name);
        $this->_bundleName = $bundle_name;
        $this->_fieldName = $field_name;
    }

    protected function _displayElementInfo(Bundle $bundle)
    {
        if ((!$bundle = $this->_application->Entity_Bundle($this->_bundleName))
            || (!$field = $this->_application->Entity_Field($bundle, $this->_fieldName))
        ) return;

        return array(
            'label' => $bundle->getGroupLabel() . ' - ' . $bundle->getLabel(),
            'description' => sprintf(__('Referenced as %s', 'directories'), $field->getFieldLabel()),
            'default_settings' => [],
        ) + parent::_displayElementInfo($bundle);
    }

    public function displayElementSettingsForm(Bundle $bundle, array $settings, Display\Model\Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        $form = parent::displayElementSettingsForm($bundle, $settings, $display, $parents, $tab, $isEdit, $submitValues);
        if ($bundle->name === $this->_bundleName) {
            $form['add_referenced'] = array(
                '#type' => 'checkbox',
                '#title' => __('Include referenced items', 'directories'),
                '#default_value' => !empty($settings['add_referenced']),
                '#horizontal' => true,
            );
        }

        return $form;
    }

    protected function _getListEntitiesSettings(Bundle $bundle, array $element, IEntity $entity)
    {
        if (!$entity->isPublished()) return;

        $settings = parent::_getListEntitiesSettings($bundle, $element, $entity);
        if (empty($element['settings']['add_referenced'])
            || (!$referenced_items = $entity->getFieldValue($this->_fieldName))
        ) {
            $settings['settings']['query']['fields'][$this->_fieldName] = $entity->getId();
        } else {
            $referenced_item_ids = [];
            foreach ($referenced_items as $referenced_item) {
                $referenced_item_ids[] = $referenced_item->getId();
            }
            // Pass array for OR query
            $settings['settings']['query']['fields'][] = array(
                $this->_fieldName => $entity->getId(),
                $bundle->entitytype_name . '_id' => implode(',', $referenced_item_ids),
            );
        }
        $settings['settings']['other']['add']['entity_reference_field'] = $this->_fieldName;
        $settings['settings']['other']['add']['entity_reference_id'] = $entity->getId();

        return $settings;
    }

    protected function _getEntitiesBundle($entityOrBundle, array $settings)
    {
        return $this->_application->Entity_Bundle($this->_bundleName);
    }
}
