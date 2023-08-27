<?php
namespace SabaiApps\Directories\Component\Entity\FieldWidget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class TermListFieldWidget extends Field\Widget\AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('Checkboxes', 'directories'),
            'field_types' => array('entity_terms'),
            'accept_multiple' => true,
            'max_num_items' => 0, // unlimited
            'default_settings' => array(
                'num' => 60,
                'columns' => 3,
                'depth' => 0,
                'leaf_only' => false,
            ),
        );
    }
    
    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        if (!$taxonomy_bundle = $this->_getTaxonomyBundle($fieldType)) return;
        
        if (empty($taxonomy_bundle->info['is_hierarchical'])) {
            return array(
                'num' => array(
                    '#type' => 'slider',
                    '#title' => __('Number of term options', 'directories'),
                    '#default_value' => $settings['num'],
                    '#min_value' => 1,
                    '#max_value' => 240,
                    '#integer' => true,
                    '#weight' => 1,
                ),
                'columns'  => array(
                    '#type' => 'select',
                    '#title' => __('Number of columns', 'directories'),
                    '#options' => array(1 => 1, 2 => 2, 3 => 3, 4 => 4, 6 => 6, 12 => 12),
                    '#default_value' => $settings['columns'],
                    '#weight' => 1,
                ),
            ); 
        }
        
        return array(
            'depth' => array(
                '#type' => 'slider',
                '#title' => __('Depth of term hierarchy tree', 'directories'),
                '#default_value' => $settings['depth'],
                '#min_value' => 0,
                '#max_value' => 10,
                '#min_text' => __('Unlimited', 'directories'), 
                '#integer' => true,
                '#weight' => 1,
            ),
            'leaf_only' => [
                '#type' => 'checkbox',
                '#title' => __('Allow selection of leaf terms only', 'directories'),
                '#default_value' => !empty($settings['leaf_only']),
                '#weight' => 2,
            ],
        );
    }

    public function fieldWidgetForm(Field\IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        if (!$taxonomy_bundle = $this->_getTaxonomyBundle($field)) return;
        
        $default_value = null;
        if (!empty($value)) {
            $default_value = [];
            foreach (array_keys($value) as $key) {
                $default_value[] = $this->_getTaxonomyTermId($value[$key]);
            }
        }

        $can_assign = $this->_application->HasPermission('entity_assign_' . $taxonomy_bundle->name);
        $ret = array(
            '#type' => 'checkboxes',
            '#option_no_escape' => true,
            '#default_value' => $default_value,
            '#multiple' => false,
            '#disabled' => !$can_assign,
            '#skip_validate_option' => $can_assign && $this->_application->getPlatform()->isAdmin(),
        );
        
        if (empty($taxonomy_bundle->info['is_hierarchical'])) {
            $ret['#options'] = $this->_application->Entity_TaxonomyTerms_html(
                $taxonomy_bundle->name,
                array(
                    'limit' => $settings['num'],
                    'depth' => 1,
                    'return_array' => true,
                    'language' => $language,
                )
            );
            $ret['#columns'] = $settings['columns'];
            return $ret;
        }
        
        $ret['#options'] = $this->_application->Entity_TaxonomyTerms_html(
            $taxonomy_bundle->name,
            array(
                'depth' => $settings['depth'],
                'return_array' => true,
                'language' => $language,
            )
        );
        if (!empty($settings['leaf_only'])) {
            $ret['#leaf_only'] = true;
        }
        return $ret;
    }

    protected function _getTaxonomyBundle($field)
    {
        // $fieldType is a field object when editing only
        if (!$field instanceof \SabaiApps\Directories\Component\Entity\Model\Field
            || (!$taxonomy_bundle = $field->getTaxonomyBundle())
        ) return;

        return $taxonomy_bundle;
    }

    protected function _getTaxonomyTermId($value)
    {
        return isset($value) && is_object($value) ? $value->getId() : null;
    }
}
