<?php
namespace SabaiApps\Directories\Component\Entity\FieldWidget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Form;

class TermParentFieldWidget extends Field\Widget\AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('Select list', 'directories'),
            'field_types' => array('entity_term_parent'),
            'accept_multiple' => false,
            'default_settings' => [],
        );
    }

    public function fieldWidgetForm(Field\IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        $taxonomy_bundle = $field->Bundle;
        
        if (empty($taxonomy_bundle->info['is_hierarchical'])) {
            return array(
                '#type' => 'hidden',
                '#value' => 0,
            );
        }
        $label = $taxonomy_bundle->getLabel('singular');
        
        return array(
            '#type' => 'select',
            '#default_value' => $value,
            '#multiple' => false,
            '#options' => $this->_application->Entity_TaxonomyTerms_html(
                $taxonomy_bundle->name,
                array(
                    'prefix' => '—',
                    'count_no_html' => true,
                    'language' => $language,
                ), 
                array('' => __('— Select —', 'directories'))
            ),
            '#title' => sprintf(__('Parent %s', 'directories'), $label),
            '#element_validate' => array(array(array($this, 'validateValue'), array($entity))),
        );
    }
    
    public function validateValue(Form\Form $form, &$value, $element, $entity)
    {
        if (isset($entity) && $value == $entity->getId()) {
            $form->setError(__('May not select self as parent.', 'directories'), $element);
        }
    }
}