<?php
namespace SabaiApps\Directories\Component\Entity\FieldWidget;

use SabaiApps\Directories\Request;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class ParentFieldWidget extends Field\Widget\AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('Autocomplete text field', 'directories'),
            'field_types' => array($this->_name, 'wp_post_parent'),
            'accept_multiple' => false,
            'repeatable' => false,
        );
    }

    public function fieldWidgetForm(Field\IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        if (!$bundle = $this->_getParentBundle($field)) return;

        $default_value = isset($value) ? (is_object($value) ? $value->getId() : $value) : null;

        // Do not show if on parent page
        if (isset($GLOBALS['drts_entity'])
            && $GLOBALS['drts_entity']->getBundleName() === $bundle->name
            && (empty($default_value) || $default_value == $GLOBALS['drts_entity']->getId())
        ) return;

        $field_title = $field->getFieldData('hide_label') ? null : $bundle->getLabel('singular', $language);

        if ($default_value
            && ($parent_entity = $this->_application->Entity_Entity($bundle->entitytype_name, $default_value))
            && $parent_entity->isPublished()
        ) {
            // Do not allow changing already set parent entity
            if ($is_admin = $this->_application->getPlatform()->isAdmin()) {
                $link = '<a href="' . $this->_application->Entity_AdminUrl($parent_entity) . '">' . $this->_application->H($this->_application->Entity_Title($parent_entity)) . '</a>';
            } else {
                $link = $this->_application->Entity_Permalink($parent_entity);
            }
            return array(
                '#type' => 'item',
                '#title' => $field_title,
                '#markup' => $link . sprintf(
                    '<input type="hidden" name="%s" value="%s" />',
                    $this->_application->Form_FieldName($parents),
                    $value
                ),
                '#admin_only' => $is_admin, // this will render the field in sidebar when in admin 
            );
        }

        return array(
            '#type' => 'autocomplete',
            '#title' => $field_title,
            '#default_value' => null,
            '#select2' => true,
            '#select2_ajax' => true,
            '#select2_ajax_url' => $this->_getAjaxUrl($bundle, $language),
            '#select2_item_text_key' => 'title',
            '#options' => [],
            '#default_options_callback' => array(array($this, '_getDefaultOptions'), array($bundle->entitytype_name, $bundle->name)),
            '#required' => true,
            '#admin_only' => $this->_application->getPlatform()->isAdmin(), // this will render the field in sidebar when in admin 
        );
    }
    
    public function _getDefaultOptions($defaultValue, array &$options, $entityType, $bundleName)
    {
        foreach ($this->_application->Entity_Types_impl($entityType)->entityTypeEntitiesByIds($defaultValue, $bundleName) as $entity) {
            $options[$entity->getId()] = $this->_application->Entity_Title($entity);
        }
    }
    
    private function _getParentBundle($field)
    {        
        if (empty($field->Bundle->info['parent'])
            || (!$parent_bundle = $field->Bundle->info['parent'])
        ) {
            return false;
        }
        return $this->_application->Entity_Bundle($parent_bundle);
    }
    
    protected function _getAjaxUrl(Entity\Model\Bundle $bundle, $language = null)
    {
        return $this->_application->MainUrl(
            '/_drts/entity/' . $bundle->type . '/query',
            array('bundle' => $bundle->name, Request::PARAM_CONTENT_TYPE => 'json', 'language' => $language),
            '',
            '&'
        );
    }
}