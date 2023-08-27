<?php
namespace SabaiApps\Directories\Component\Entity\FieldWidget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class TermMultiSelectFieldWidget extends Field\Widget\AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return [
            'label' => __('Multi-select field', 'directories'),
            'field_types' => ['entity_terms'],
            'accept_multiple' => true,
            'default_settings' => [
                'num' => 30,
                'depth' => 5,
                'columns' => 1,
                'height' => 0,
            ],
            'repeatable' => false,
            'max_num_items' => 0, // unlimited
        ];
    }
    
    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        if (!$taxonomy_bundle = $this->_getTaxonomyBundle($fieldType)) return;
        
        if (empty($taxonomy_bundle->info['is_hierarchical'])) {
            $form = [
                'num' => [
                    '#type' => 'slider',
                    '#title' => __('Number of term options', 'directories'),
                    '#default_value' => $settings['num'],
                    '#min_value' => 1,
                    '#max_value' => 200,
                    '#integer' => true,
                    '#weight' => 1,
                ],
            ];
        } else {
            $form = [
                'depth' => [
                    '#type' => 'slider',
                    '#title' => __('Depth of term hierarchy tree', 'directories'),
                    '#default_value' => $settings['depth'],
                    '#min_value' => 1,
                    '#max_value' => $this->_application->Entity_Types_impl($taxonomy_bundle->entitytype_name)->entityTypeHierarchyDepth($taxonomy_bundle),
                    '#integer' => true,
                    '#weight' => 1,
                ],
            ];
        }
        $form['columns'] = [
            '#title' => __('Number of columns', 'directories'),
            '#type' => 'slider',
            '#min_value' => 1,
            '#max_value' => 4,
            '#integer' => true,
            '#default_value' => $settings['columns'],
            '#weight' => 2,
        ];
        $form['height'] = [
            '#title' => __('Option list height', 'directories'),
            '#type' => 'slider',
            '#min_value' => 0,
            '#max_value' => 500,
            '#min_text' => __('Auto', 'directories'),
            '#field_suffix' => 'px',
            '#integer' => true,
            '#default_value' => $settings['height'],
            '#weight' => 3,
            '#states' => [
                'visible' => [
                    sprintf('[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['columns']))) => ['value' => 1],
                ],
            ],
        ];
        
        return $form;
    }

    public function fieldWidgetForm(Field\IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        if (!$taxonomy_bundle = $this->_getTaxonomyBundle($field)) return;

        if (empty($taxonomy_bundle->info['is_hierarchical'])) {
            $options = $this->_application->Entity_TaxonomyTerms_html(
                $taxonomy_bundle->name,
                [
                    'limit' => $settings['num'],
                    'depth' => 1,
                    'parent' => isset($settings['parent']) ? (int)$settings['parent'] : 0,
                    'language' => $language,
                    'return_array' => true,
                ]
            );
        } else {
            $options = $this->_application->Entity_TaxonomyTerms_html(
                $taxonomy_bundle->name,
                [
                    'prefix' => '—',
                    'depth' => $settings['depth'],
                    'language' => $language,
                    'return_array' => true,
                ]
            );
        }
        if (count($options) <= 1) return;

        if (!empty($value)) {
            foreach (array_keys($value) as $i) {
                if (is_object($value[$i])) {
                    $value[$i] = $value[$i]->getId();
                } else {
                    unset($value[$i]);
                }
            }
        }
        $can_assign = $this->_application->HasPermission('entity_assign_' . $taxonomy_bundle->name);

        return [
            '#type' => 'select',
            '#empty_value' => '',
            '#default_value' => empty($value) ? null : $value,
            '#multiple' => true,
            '#multiselect' => true,
            '#multiselect_height' => !empty($settings['height']) && $settings['columns'] == 1 ? $settings['height'] : 0,
            '#disabled' => !$can_assign,
            '#skip_validate_option' => $can_assign && $this->_application->getPlatform()->isAdmin(),
            '#options' => $options,
            '#placeholder' => $taxonomy_bundle->getLabel('select'),
            '#columns' => $settings['columns'],
            '#max_selection' => $field->getFieldMaxNumItems(),
        ];
    }

    protected function _getTaxonomyBundle($field)
    {
        // $fieldType is a field object when editing only
        if (!$field instanceof \SabaiApps\Directories\Component\Entity\Model\Field
            || (!$taxonomy_bundle = $field->getTaxonomyBundle())
        ) return;

        return $taxonomy_bundle;
    }
}
