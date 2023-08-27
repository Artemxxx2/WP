<?php
namespace SabaiApps\Directories\Component\Entity\FieldWidget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Request;

class TermSelectFieldWidget extends Field\Widget\AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('Select list', 'directories'),
            'field_types' => array('entity_terms'),
            'accept_multiple' => false,
            'default_settings' => array(
                'num' => 30,
                'select_hierarchical' => false,
                'depth' => 5,
                'no_fancy' => true,
            ),
            'repeatable' => true,
            'max_num_items' => 0, // unlimited
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
                    '#max_value' => 200,
                    '#integer' => true,
                    '#weight' => 1,
                ),
            ); 
        }
        
        return array(
            'select_hierarchical' => [
                '#type' => 'checkbox',
                '#title' => __('Enable hierarchical dropdown', 'directories'),
                '#default_value' => !empty($settings['select_hierarchical']),
                '#weight' => 1,
            ],
            'depth' => array(
                '#type' => 'slider',
                '#title' => __('Depth of term hierarchy tree', 'directories'),
                '#default_value' => $settings['depth'],
                '#min_value' => 1,
                '#max_value' => $this->_application->Entity_Types_impl($taxonomy_bundle->entitytype_name)->entityTypeHierarchyDepth($taxonomy_bundle),
                '#integer' => true,
                '#weight' => 2,
            ),
            'no_fancy' => [
                '#type' => 'checkbox',
                '#title' => __('Disable fancy dropdown', 'directories'),
                '#default_value' => !empty($settings['no_fancy']),
                '#weight' => 3,
                '#states' => [
                    'invisible' => [
                        sprintf('[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['select_hierarchical']))) => ['type' => 'checked', 'value' => true],
                    ],
                ],
            ],
        );
    }

    public function fieldWidgetForm(Field\IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        if (!$taxonomy_bundle = $this->_getTaxonomyBundle($field)) return;
        
        $default_text = __('— Select —', 'directories');
        $can_assign = $this->_application->HasPermission('entity_assign_' . $taxonomy_bundle->name);

        $term_id = $this->_getTaxonomyTermId($value);
        
        if (empty($taxonomy_bundle->info['is_hierarchical'])) {
            $options = $this->_application->Entity_TaxonomyTerms_html(
                $taxonomy_bundle->name,
                array(
                    'limit' => $settings['num'],
                    'depth' => 1,
                    'parent' => isset($settings['parent']) ? (int)$settings['parent'] : 0,
                    'language' => $language,
                    'return_array' => true,
                ),
                array('' => $default_text)
            );
        } else {
            if (!empty($settings['select_hierarchical'])
                && !($this->_application->getPlatform()->isAdmin() && $this->_application->Filter('entity_admin_term_select_disable_hierarchical', false !== $this->_application->Entity_BundleTypeInfo($taxonomy_bundle, 'taxonomy_assignable'), [$taxonomy_bundle]))
            ) {
                $options = $this->_application->Entity_TaxonomyTerms_html(
                    $taxonomy_bundle->name,
                    [
                        'parent' => 0,
                        'depth' => 1,
                        'language' => $language,
                        'return_array' => true,
                    ],
                    ['' => $default_text]
                );
                if (count($options) <= 1) return;

                if (isset($value)
                    && ($term_entity = $this->_application->Entity_Entity($taxonomy_bundle->entitytype_name, $term_id))
                ) {
                    $values = $this->_application->Entity_Types_impl($taxonomy_bundle->entitytype_name)->entityTypeParentEntityIds($term_entity);
                    $values[] = $term_id;
                } else {
                    $values = [];
                }
                return [
                    '#type' => 'selecthierarchical',
                    '#load_options_url' => $this->_application->MainUrl(
                        '/_drts/entity/' . $taxonomy_bundle->type . '/taxonomy_terms',
                        ['bundle' => $taxonomy_bundle->name, Request::PARAM_CONTENT_TYPE => 'json', 'language' => $language, 'depth' => 1]
                    ),
                    '#default_value' => $values,
                    '#disabled' => !$can_assign,
                    '#options' => $options,
                    '#max_depth' => empty($settings['depth']) ?
                        $this->_application->Entity_Types_impl($taxonomy_bundle->entitytype_name)->entityTypeHierarchyDepth($taxonomy_bundle) :
                        $settings['depth'],
                    '#no_fancy' => true, // @todo Add support for cloning hierarchical dropdown field with select2
                    '#empty_value' => '',
                ];
            }

            $options = $this->_application->Entity_TaxonomyTerms_html(
                $taxonomy_bundle->name,
                array(
                    'prefix' => '—',
                    'depth' => $settings['depth'],
                    'language' => $language,
                    'return_array' => true,
                ),
                array('' => $default_text)
            );
            if (count($options) <= 1) return;
        }

        return [
            '#type' => 'select',
            '#select2' => empty($settings['no_fancy']),
            '#empty_value' => '',
            '#default_value' => $term_id,
            '#multiple' => false,
            '#disabled' => !$can_assign,
            '#skip_validate_option' => $can_assign && $this->_application->getPlatform()->isAdmin(),
            '#options' => $options,
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

    protected function _getTaxonomyTermId($value)
    {
        return isset($value) && is_object($value) ? $value->getId() : null;
    }
}
