<?php
namespace SabaiApps\Directories\Component\View\FieldFilter;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class TermListFieldFilter extends AbstractTermFieldFilter
{
    protected function _fieldFilterInfo()
    {
        $info = parent::_fieldFilterInfo();
        $info['label'] = __('Checkboxes', 'directories');
        $info['default_settings'] += array(
            'icon' => true,
            'icon_size' => 'sm',
            'andor' => 'OR',
            'columns' => 1,
            'visible_count' => 15,
            'scroll' => true,
        );
        return $info;
    }

    public function fieldFilterSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        $ret = parent::fieldFilterSettingsForm($field, $settings, $parents);
        
        if (!is_array($ret)) return;
        
        $taxonomy_bundle = $field->getTaxonomyBundle();
        if ($this->_application->Entity_BundleTypeInfo($taxonomy_bundle, 'entity_image')) {
            $ret += [
                'icon' => [
                    '#type' => 'checkbox',
                    '#title' => __('Show icon', 'directories'),
                    '#default_value' => !empty($settings['icon']),
                    '#weight' => 6,
                ],
                'icon_size' => [
                    '#type' => 'select',
                    '#title' => __('Icon size', 'directories'),
                    '#options' => $this->_application->System_Util_iconSizeOptions(),
                    '#default_value' => $settings['icon_size'],
                    '#states' => [
                        'visible' => [
                            sprintf('input[name="%s[icon]"]', $this->_application->Form_FieldName($parents)) => [
                                'type' => 'checked', 
                                'value' => true,
                            ],
                        ],
                    ],
                    '#weight' => 7,
                ],
            ];
        }

        $ret += [
            'andor' => [
                '#title' => __('Match any or all', 'directories'),
                '#type' => 'select',
                '#options' => ['OR' => __('Match any', 'directories'), 'AND' => __('Match all', 'directories')],
                '#default_value' => $settings['andor'],
                '#states' => [
                    'visible' => [
                        sprintf('[name="%s[type]"]', $this->_application->Form_FieldName($parents)) => ['value' => 'checkboxes'],
                    ],
                ],
                '#weight' => 20,
            ],
            'visible_count' => [
                '#type' => 'slider',
                '#integer' => true,
                '#min_value' => 0,
                '#max_value' => 50,
                '#min_text' => __('Show all', 'directories'),
                '#title' => __('Number of options to display', 'directories'),
                '#description' => __('If there are more options than the number specified, those options are hidden until "more" link is clicked.', 'directories'),
                '#default_value' => $settings['visible_count'],
                '#weight' => 30,
                '#states' => [
                    'visible' => [
                        sprintf('[name="%s[columns]"]', $this->_application->Form_FieldName($parents)) => ['value' => 1],
                    ],
                ],
            ],
            'columns' => [
                '#type' => 'select',
                '#title' => __('Number of columns', 'directories'),
                '#options' => [1 => 1, 2 => 2, 3 => 3, 4 => 4],
                '#default_value' => $settings['columns'],
                '#weight' => 29,
            ],
            'scroll' => [
                '#type' => 'checkbox',
                '#title' => __('Show scroll bar', 'directories'),
                '#weight' => 31,
                '#default_value' => !empty($settings['scroll']),
                '#states' => [
                    'visible_or' => [
                        sprintf('[name="%s[visible_count]"]', $this->_application->Form_FieldName($parents)) => ['value' => 0],
                        sprintf('[name="%s[columns]"]', $this->_application->Form_FieldName($parents)) => ['type' => '!value', 'value' => 1],
                    ],
                ],
            ],
        ];
        
        return $ret;
    }

    public function fieldFilterForm(Field\IField $field, $filterName, array $settings, $request = null, Entity\Type\Query $query = null, array $current = null, $autoSubmit = true, array $parents = [])
    {
        // Get taxonomy bundle associated with the field
        if (!$bundle = $field->getTaxonomyBundle()) {
            return;
        }

        $queried_terms = $this->_getQueriedTerms($field, $bundle, $query, $request);

        if (false === $facets = $this->_getFacets($field, $settings, $query)) return;
        
        if (!isset($current)) {
            $options = [
                'content_bundle' => $field->Bundle->type,
                'hide_empty' => !empty($settings['hide_empty']),
                'hide_count' => !$query->view_enable_facet_count || !empty($settings['hide_count']),
                'link' => false,
                'icon' => !empty($settings['icon']),
                'icon_size' => $settings['icon_size'],
                'return_array' => true,
                'prefix' => '',
                'exclude' => $this->_getExcludedTerms($field, $settings),
            ];
            if (empty($bundle->info['is_hierarchical'])) {
                $options += [
                    'limit' => $settings['num'],
                    'depth' => 1,
                ];
            } else {                
                // Hierarchical taxonomy
                $options += [
                    'depth' => $settings['depth'],
                    'parent' => !empty($queried_terms) && count($queried_terms) === 1 ? current(array_keys($queried_terms)) : 0,
                ];
            }
            if (!$list = $this->_application->Entity_TaxonomyTerms_html($bundle->name, $options)) {
                if (empty($queried_terms)) return;

                // Render current term IDs as hidden values for conditional rules to work properly
                return $this->_getTermsHiddenField($queried_terms);
            }
        
            $current = [
                '#type' => 'checkboxes',
                '#options' => $list,
                '#option_no_escape' => true,
                '#options_visible_count' => $settings['visible_count'],
                '#options_scroll' => !empty($settings['scroll']),
                '#entity_filter_form_type' => 'checkboxes',
                '#columns' => !$this->_application->isRunning() || empty($settings['columns']) ? 1 : $settings['columns'],
            ];
            if (!empty($queried_terms)) {
                // render current term IDs as hidden values for conditional rules to work properly
                foreach ($queried_terms as $queried_term_id => $queried_term_slug) {
                    $current['#attributes'][$queried_term_id]['data-alt-value'] = $queried_term_slug;
                    $current['#options_disabled'][] = $current['#options_hidden'][] = $queried_term_id;
                }
            }
        }

        if ($current['#type'] === 'hidden') return $current;
        
        if (isset($facets)) {
            $this->_loadFacetCounts($current, $facets, $settings, $request);
        }
        
        return empty($current['#options']) ? null : $current;
    }

    public function fieldFilterLabels(Field\IField $field, array $settings, $value, $form, $defaultLabel)
    {
        // Get taxonomy bundle associated with the field
        if (!$bundle = $field->getTaxonomyBundle()) {
            return;
        }
        
        $ret = [];
        foreach ($this->_application->Entity_Entities($bundle->entitytype_name, (array)$value, false) as $entity) {
            $ret[$entity->getId()] = $this->_application->H($entity->getTitle());
        }
        
        return $ret;
    }
    
    protected function _getMatchAndOr(Field\IField $field, array $settings)
    {
        return $settings['andor'];
    }
}