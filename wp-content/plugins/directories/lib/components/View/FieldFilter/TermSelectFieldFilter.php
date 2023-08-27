<?php
namespace SabaiApps\Directories\Component\View\FieldFilter;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Request;

class TermSelectFieldFilter extends AbstractTermFieldFilter
{
    protected function _fieldFilterInfo()
    {
        $info = parent::_fieldFilterInfo();
        $info['label'] = __('Select list', 'directories');
        $info['default_settings'] += [
            'default_text' => null,
            'select_hierarchical' => false,
            'no_fancy' => true,
        ];
        return $info;
    }

    public function fieldFilterSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        $ret = parent::fieldFilterSettingsForm($field, $settings, $parents);
        
        if (!is_array($ret)) return;
        
        $bundle = $field->getTaxonomyBundle();
        if (!empty($bundle->info['is_hierarchical'])) {
            $ret['select_hierarchical'] = [
                '#type' => 'checkbox',
                '#title' => __('Enable hierarchical dropdown', 'directories'),
                '#default_value' => !empty($settings['select_hierarchical']),
                '#description' => sprintf(
                    __('This setting does not have any effect when "%s" is switched off.', 'directories'),
                    __('Auto submit filter form', 'directories')
                ),
                '#weight' => 3,
            ];
        }
        $ret += [
            'default_text' => [
                '#type' => 'textfield',
                '#title'=> __('Default text', 'directories'),
                '#default_value' => $this->_getDefaultText($bundle, $settings),
                '#weight' => 4,
                '#placeholder' => __('— Select —', 'directories'),
            ],
            'no_fancy' => [
                '#type' => 'checkbox',
                '#title' => __('Disable fancy dropdown', 'directories'),
                '#default_value' => !empty($settings['no_fancy']),
                '#weight' => 5,
            ],
        ];
        
        return $ret;
    }
    
    protected function _getDefaultText($bundle, array $settings)
    {
        return strlen((string)$settings['default_text'])
            ? $settings['default_text']
            : __('— Select —', 'directories');
    }

    public function fieldFilterForm(Field\IField $field, $filterName, array $settings, $request = null, Entity\Type\Query $query = null, array $current = null, $autoSubmit = true, array $parents = [])
    {
        // Get taxonomy bundle associated with the field
        if (!$bundle = $field->getTaxonomyBundle()) {
            return;
        }

        $queried_terms = $this->_getQueriedTerms($field, $bundle, $query, $request);
        
        $list_options = [
            'content_bundle' => $field->Bundle->type,
            'hide_empty' => !empty($settings['hide_empty']),
            'hide_count' => !$query->view_enable_facet_count || !empty($settings['hide_count']),
            'count_no_html' => true,
            'return_array' => true,
            'exclude' => $this->_getExcludedTerms($field, $settings),
        ];

        if (empty($bundle->info['is_hierarchical'])) {
            if (false === $facets = $this->_getFacets($field, $settings, $query)) return;
            
            if (!isset($current)) {
                if (!empty($queried_terms)) {
                    // Do not show select form since one or more terms are already specified
                    $data_attr = [];
                    foreach ($queried_terms as $queried_term_id => $queried_term_slug) {
                        $data_attr[$queried_term_id]['alt-value'] = $queried_term_slug;
                    }
                    return [
                        '#type' => 'hidden',
                        '#attributes' => ['disabled' => 'disabled'],
                        '#default_value' => array_keys($queried_terms),
                        '#multiple' => true,
                        '#data' => $data_attr,
                    ];
                }

                $options = $this->_application->Entity_TaxonomyTerms_html(
                    $bundle->name,
                    $list_options + [
                        'limit' => $settings['num'],
                        'depth' => 1,
                    ]
                );
                if (empty($options)) return;
                
                $current = [
                    '#type' => 'select',
                    '#options' => ['' => $this->_getDefaultText($bundle, $settings)] + $options,
                    '#select2' => empty($settings['no_fancy']),
                    '#empty_value' => '',
                    '#multiple' => false,
                    '#entity_filter_form_type' => 'select',
                ];
            }

            if ($current['#type'] === 'hidden') return $current;
            
            if (isset($facets)) {
                $this->_loadFacetCounts($current, $facets, $settings, $request);
            }

            return $current;
        }
                
        // Hierarchical taxonomy

        if (false === $facets = $this->_getFacets($field, $settings, $query)) return;
            
        if (!isset($current)) {
            if (!empty($queried_terms)
                && count($queried_terms) > 1
            ) {
                // Do not show select form since one or more terms are already specified
                return $this->_getTermsHiddenField($queried_terms);
            }

            if (empty($settings['select_hierarchical'])) {
                $options = $this->_application->Entity_TaxonomyTerms_html(
                    $bundle->name,
                    $list_options + [
                        'prefix' => '—',
                        'parent' => !empty($queried_terms) && count($queried_terms) === 1 ? current(array_keys($queried_terms)) : 0,
                        'depth' => $settings['depth'],
                    ]
                );
                if (empty($options)) {
                    if (empty($queried_terms)) return;

                    return $this->_getTermsHiddenField($queried_terms);
                }

                $current = [
                    '#type' => 'select',
                    '#options' => ['' => $this->_getDefaultText($bundle, $settings)] + $options,
                    '#select2' => empty($settings['no_fancy']),
                    //'#select2_minimum_results_for_search' => 0,
                    '#empty_value' => '',
                    '#multiple' => false,
                    '#entity_filter_form_type' => 'select',
                ];
            } else {
                $options = $this->_application->Entity_TaxonomyTerms_html(
                    $bundle->name,
                    $list_options + [
                        'parent' => !empty($queried_terms) && count($queried_terms) === 1 ? current(array_keys($queried_terms)) : 0,
                        'depth' => 1,
                    ]
                );
                if (empty($options)) {
                    if (empty($queried_terms)) return;

                    return $this->_getTermsHiddenField($queried_terms);
                }

                if (!empty($request)) {
                    foreach ((array)$request as $_requested_term_id) {
                        $_child_options = $this->_application->Entity_TaxonomyTerms_html(
                            $bundle->name,
                            $list_options + [
                                'parent' => $_requested_term_id,
                                'depth' => 1,
                            ]
                        );
                        if (empty($_child_options)) break;

                        $child_options[] = $_child_options;
                    }
                }
                $current = [
                    '#type' => 'selecthierarchical',
                    '#load_options_url' => $this->_application->MainUrl(
                        '/_drts/entity/' . $bundle->type . '/taxonomy_terms',
                        [
                            'bundle' => $bundle->name,
                            Request::PARAM_CONTENT_TYPE => 'json',
                            'depth' => 1,
                            'hide_count' => 1,
                        ]
                    ),
                    '#default_value' => $request,
                    '#options' => ['' => $this->_getDefaultText($bundle, $settings)] + $options,
                    '#max_depth' => empty($settings['depth']) ? $this->_application->Entity_Types_impl($bundle->entitytype_name)->entityTypeHierarchyDepth($bundle) : $settings['depth'],
                    '#entity_filter_form_type' => 'select',
                    '#child_options' => empty($child_options) ? null : $child_options,
                    '#no_fancy' => !empty($settings['no_fancy']),
                ];
            }
        }

        if (isset($current['#type'])
            && $current['#type'] === 'hidden'
        ) return $current;

        if (isset($facets)) {
            $this->_loadFacetCounts($current, $facets, $settings, $request);
            if (isset($current['#child_options'])) {
                $this->_loadChildFacetCounts($current, $facets, $settings, $request);
            }
        }
            
        return $current;
    }

    protected function _loadChildFacetCounts(array &$form, array $facets, array $settings, array $request)
    {
        foreach (array_keys($form['#child_options']) as $key) {
            foreach (array_keys($form['#child_options'][$key]) as $value) {
                if ($value === '') continue;

                if (empty($facets[$value])) {
                    if (!empty($settings['hide_empty'])) {
                        unset($form['#child_options'][$key][$value]);
                    } else {
                        $form['#child_options'][$key][$value]['#count'] = 0;

                        if (!in_array($value, $request)) {
                            // Disable only when the option is currently not selected
                            $form['#options_disabled'][] = $value;
                        }
                    }
                } else {
                    $form['#child_options'][$key][$value]['#count'] = $facets[$value];
                }
            }
        }
    }

    public function fieldFilterIsFilterable(Field\IField $field, array $settings, &$value, array $requests = null)
    {
        if (is_array($value)) {
            $value = array_filter($value);
            $v = $value;
            $v = array_pop($v);
            return !empty($v);
        }
        return !empty($value);
    }
    
    public function fieldFilterLabels(Field\IField $field, array $settings, $value, $form, $defaultLabel)
    {
        // Get taxonomy bundle associated with the field
        if (!$bundle = $field->getTaxonomyBundle()) return;

        if (is_array($value)) {
            $value = array_filter($value);
            $value = array_pop($value);
        }

        if (!$entity = $this->_application->Entity_Entity($bundle->entitytype_name, $value, false)) return;
        
        return [$entity->getId() => $this->_application->H($entity->getTitle())];
    }
}
