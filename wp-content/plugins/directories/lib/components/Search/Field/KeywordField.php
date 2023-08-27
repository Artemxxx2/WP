<?php
namespace SabaiApps\Directories\Component\Search\Field;

use SabaiApps\Directories\Component\Search\SearchComponent;
use SabaiApps\Directories\Request;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Entity\Type\Query;

class KeywordField extends AbstractField
{
    protected static $_suggestUrlVersion;

    protected function _searchFieldInfo()
    {
        return [
            'label' => __('Keyword Search', 'directories'),
            'weight' => 1,
            'default_settings' => [
                'min_length' => 2,
                'match' => 'all',
                'child_bundle_types' => null,
                'taxonomies' => null,
                'extra_fields' => null,
                'suggest' => [
                    'enable' => true,
                    'settings' => [
                        'min_length' => 2,
                        'post_num' => 5,
                        'post_num_cache' => 100,
                        'post_jump' => true,
                    ],
                ],
                'form' => [
                    'icon' => 'fas fa-search',
                    'placeholder' => __('Search for...', 'directories'),
                    'order' => 1,
                ],
                'exclude_content' => false,
            ],
        ];
    }
    
    public function searchFieldSupports(Bundle $bundle)
    {
        return empty($bundle->info['parent']);
    }
    
    public function searchFieldSettingsForm(Bundle $bundle, array $settings, array $parents = [])
    {
        $form = [
            'min_length' => [
                '#type' => 'slider',
                '#title' => __('Min. length of keywords in characters', 'directories'),
                '#default_value' => $settings['min_length'],
                '#integer' => true,
                '#min_value' => 1,
                '#max_value' => 10,
                '#horizontal' => true,
                '#weight' => 2,
            ],
            'match' => [
                '#type' => 'select',
                '#title' => __('Match type', 'directories'),
                '#options' => [
                    'any' => __('Match any', 'directories'),
                    'all' => __('Match all', 'directories'),
                    'exact' => __('Exact match', 'directories'),
                ],
                '#default_value' => $settings['match'],
                '#horizontal' => true,
                '#weight' => 1,
            ],
        ];
        $properties = $this->_application->Entity_Types_impl($bundle->entitytype_name)->entityTypeInfo('properties');
        if (!empty($properties['content'])) {
            $form['exclude_content'] = [
                '#type' => 'checkbox',
                '#title' => __('Do not search post content', 'directories'),
                '#default_value' => !empty($settings['exclude_content']),
                '#horizontal' => true,
                '#weight' => 3,
            ];
        }
            
        $child_bundle_types = [];
        foreach ($this->_application->Entity_BundleTypes_children($bundle->type) as $child_bundle_type) {
            if (!$child_bundle = $this->_application->Entity_Bundle($child_bundle_type, $bundle->component, $bundle->group)) continue;
            
            $child_bundle_types[$child_bundle_type] = $child_bundle->getLabel();
        }
        if (!empty($child_bundle_types)) {
            $form['child_bundle_types'] = [
                '#type' => 'checkboxes',
                '#title' => __('Search child content items', 'directories'),
                '#options' => $child_bundle_types,
                '#default_value' => $settings['child_bundle_types'],
                '#horizontal' => true,
                '#weight' => 5,
            ];
        }
        if (!empty($bundle->info['taxonomies'])) {
            $form['taxonomies'] = [
                '#type' => 'checkboxes',
                '#title' => __('Search taxonomy term names', 'directories'),
                '#options' => [],
                '#default_value' => $settings['taxonomies'],
                '#horizontal' => true,
                '#weight' => 6,
                '#description' => __('WARNING! This could slow down the performance of search considerably when there are a large number of taxonomy terms.', 'directories'),
            ];
        }
            
        // Add extra fields to include in search
        if ($fields = $this->_application->Entity_Field($bundle)) {
            $searchable_fields = [
                'string' => ['value' => null],
                'email' => ['value' => null],
                'url' => ['value' => null],
                'phone' => ['value' => null],
                'text' => ['value' => null],
                'choice' => ['value' => null],
                'number' => ['value' => null],
                'name' => [
                    'first_name' => __('First Name', 'directories'),
                    'middle_name' => __('Middle Name', 'directories'),
                    'last_name' => __('Last Name', 'directories'),
                    'display_name' => null,
                ],
            ];
            $extra_field_options = [];
            foreach ($fields as $field_name => $field) {
                if ($field->isPropertyField()) continue;

                if (isset($searchable_fields[$field->getFieldType()])) {
                    foreach ($searchable_fields[$field->getFieldType()] as $column => $column_label) {
                        $label = $field->getFieldLabel();
                        if (strlen($column_label)) {
                            $label = sprintf(__('%s (%s)', 'directories'), $label, $column_label);
                        }
                        $extra_field_options[$column === 'value' ? $field_name : $field_name . ',' . $column] = $label . ' - ' . $field_name;
                    }
                }
            }
        }
        if (!empty($extra_field_options)) {
            asort($extra_field_options);
            $form['extra_fields'] = [
                '#type' => 'checkboxes',
                '#title' => __('Extra fields to include in search', 'directories'),
                '#default_value' => $settings['extra_fields'],
                '#options' => $extra_field_options,
                '#weight' => 15,
                '#horizontal' => true,
            ];
        }
            
        $suggest_prefix = $this->_application->Form_FieldName(array_merge($parents, ['suggest', 'enable']));
        $suggest_states = $suggest_post_states = [
            'visible' => [
                'input[name="' . $suggest_prefix . '"]' => ['type' => 'checked', 'value' => 1],
            ],
        ];
        $suggest_post_selector = sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['suggest', 'settings', 'post'])));
        $suggest_post_states['visible'][$suggest_post_selector] = ['value' => 1];
        $form += [
            'suggest' => [
                '#title' => __('Auto-Suggest Settings', 'directories'),
                '#weight' => 15,
                '#class' => 'drts-form-label-lg',
                'enable' => [
                    '#type' => 'checkbox',
                    '#default_value' => !empty($settings['suggest']['enable']),
                    '#title' => __('Enable auto suggestions', 'directories'),
                    '#horizontal' => true,
                ],
                'settings' => [
                    '#states' => $suggest_states,
                    'min_length' => [
                        '#type' => 'slider',
                        '#title' => __('Minimum character length needed before triggering auto suggestions', 'directories'),
                        '#default_value' => $settings['suggest']['settings']['min_length'],
                        '#integer' => true,
                        '#min_value' => 1,
                        '#max_value' => 10,
                        '#states' => $suggest_post_states,
                        '#horizontal' => true,
                        '#weight' => 1,
                    ],
                    'post_jump' => [
                        '#type' => 'checkbox',
                        '#default_value' => !empty($settings['suggest']['settings']['post_jump']),
                        '#title' => __('Redirect to suggested item page when clicked', 'directories'),
                        '#states' => $suggest_post_states,
                        '#horizontal' => true,
                        '#weight' => 2,
                    ],
                    'post_num' => [
                        '#type' => 'slider',
                        '#min_value' => 1,
                        '#max_value' => 20,
                        '#title' => __('Number of auto suggested items to display', 'directories'),
                        '#integer' => true,
                        '#default_value' => $settings['suggest']['settings']['post_num'],
                        '#horizontal' => true,
                        '#states' => $suggest_post_states,
                        '#weight' => 3,
                    ],
                    'post_num_cache' => [
                        '#type' => 'slider',
                        '#min_value' => 0,
                        '#max_value' => 200,
                        '#step' => 10,
                        '#title' => __('Number of auto suggested items to cache', 'directories'),
                        '#integer' => true,
                        '#default_value' => isset($settings['suggest']['settings']['post_num_cache']) ? $settings['suggest']['settings']['post_num_cache'] : 100,
                        '#horizontal' => true,
                        '#states' => $suggest_post_states,
                        '#weight' => 4,
                    ],
                ],
            ],
        ];

        $has_taxonomy = false;
        if (!empty($bundle->info['taxonomies'])) {
            foreach ($bundle->info['taxonomies'] as $taxonomy_bundle_type => $taxonomy_name) {
                if (!$taxonomy_bundle = $this->_application->Entity_Bundle($taxonomy_name)) continue;

                $form['taxonomies']['#options'][$taxonomy_name] = $taxonomy_bundle->getLabel();
                $taxonomy_label = $taxonomy_bundle->getLabel('singular');
                $is_hierarchical = !empty($taxonomy_bundle->info['is_hierarchical']);
                $suggest_taxonomy_states = $suggest_states;
                $suggest_taxonomy_selector = sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['suggest', $taxonomy_bundle_type])));
                $suggest_taxonomy_states['visible'][$suggest_taxonomy_selector] = ['type' => 'checked', 'value' => true];
                $form['suggest'] += [
                    $taxonomy_bundle_type => [
                        '#type' => 'checkbox',
                        '#default_value' => !empty($settings['suggest'][$taxonomy_bundle_type]),
                        '#title' => $taxonomy_label . ' - ' . __('Enable auto suggestions', 'directories'),
                        '#states' => $suggest_states,
                        '#horizontal' => true,
                    ],
                    $taxonomy_bundle_type . '_num' => [
                        '#type' => 'slider',
                        '#min_value' => 1,
                        '#max_value' => 20,
                        '#title' => $taxonomy_label . ' - ' . __('Number of auto suggested terms to display', 'directories'),
                        '#integer' => true,
                        '#default_value' => isset($settings['suggest'][$taxonomy_bundle_type . '_num']) ? $settings['suggest'][$taxonomy_bundle_type . '_num'] : 3,
                        '#states' => $suggest_taxonomy_states,
                        '#horizontal' => true,
                    ],
                    $taxonomy_bundle_type . '_hide_empty' => [
                        '#type' => 'checkbox',
                        '#title' => $taxonomy_label . ' - ' . __('Hide empty terms', 'directories'),
                        '#default_value' => !empty($settings['suggest'][$taxonomy_bundle_type . '_hide_empty']),
                        '#horizontal' => true,
                        '#states' => $suggest_taxonomy_states,
                    ],
                    $taxonomy_bundle_type . '_hide_count' => [
                        '#type' => 'checkbox',
                        '#title' => $taxonomy_label . ' - ' . __('Hide post counts', 'directories'),
                        '#default_value' => !empty($settings['suggest'][$taxonomy_bundle_type . '_hide_count']),
                        '#horizontal' => true,
                        '#states' => $suggest_taxonomy_states,
                    ],
                    $taxonomy_bundle_type . '_jump' => [
                        '#type' => 'checkbox',
                        '#title' => $taxonomy_label . ' - ' . __('Redirect to suggested item page when clicked', 'directories'),
                        '#default_value' => !empty($settings['suggest'][$taxonomy_bundle_type . '_jump']),
                        '#horizontal' => true,
                        '#states' => $suggest_taxonomy_states,
                    ],
                ];
                if ($is_hierarchical) {
                    $form['suggest'] += [
                        $taxonomy_bundle_type . '_depth' => [
                            '#type' => 'slider',
                            '#title' => $taxonomy_label . ' - ' . __('Depth of term hierarchy tree', 'directories'),
                            '#default_value' => isset($settings['suggest'][$taxonomy_bundle_type . '_depth']) ? $settings['suggest'][$taxonomy_bundle_type . '_depth'] : 1,
                            '#min_value' => 0,
                            '#max_value' => 10,
                            '#min_text' => __('Unlimited', 'directories'),
                            '#integer' => true,
                            '#horizontal' => true,
                            '#states' => $suggest_taxonomy_states,
                        ],
                        $taxonomy_bundle_type . '_inc_parents' => [
                            '#type' => 'checkbox',
                            '#title' => $taxonomy_label . ' - ' . __('Include parent term paths in term title', 'directories'),
                            '#default_value' => !empty($settings['suggest'][$taxonomy_bundle_type . '_inc_parents']),
                            '#horizontal' => true,
                            '#states' => $suggest_taxonomy_states,
                        ],
                    ];
                }

                $has_taxonomy = true;
            }
        }

        if ($has_taxonomy) {
            $form['suggest']['settings']['post'] = [
                '#type' => 'checkbox',
                '#default_value' => !isset($settings['suggest']['settings']['post']) || $settings['suggest']['settings']['post'],
                '#title' => __('Suggest posts', 'directories'),
                '#horizontal' => true,
                '#weight' => 0,
            ];
        } else {
            $form['suggest']['settings']['post'] = [
                '#type' => 'hidden',
                '#default_value' => 1,
            ];
        }
            
        return $form;
    }
    
    public function searchFieldForm(Bundle $bundle, array $settings, $request = null, array $requests = null, array $parents = [])
    {        
        $data = [];

        // Enable auto suggestions?
        if (!empty($settings['suggest']['enable'])) {
            $suggest_no_url = empty($settings['suggest']['settings']['post_jump']);
            $data['suggest-post'] = !isset($settings['suggest']['settings']['post']) || $settings['suggest']['settings']['post'] ? 'true' : 'false';
            $data['suggest-post-url'] = $this->_getSuggestUrl($bundle, $settings['suggest']['settings']['post_num'], $suggest_no_url, 'QUERY');
            $data['suggest-post-icon'] = $this->_application->Entity_BundleTypeInfo($bundle->type, 'icon');
            $data['suggest-min-length'] = $settings['suggest']['settings']['min_length'];
            if (!empty($settings['suggest']['settings']['post_num_cache'])) {
                $data['suggest-post-prefetch-url'] = $this->_getSuggestUrl($bundle, $settings['suggest']['settings']['post_num_cache'], $suggest_no_url);
            }

            //$data['suggest-post-header'] = $bundle->getLabel('singular');
            if (!empty($bundle->info['taxonomies'])) {
                $taxonomies = $bundle->info['taxonomies'];
                foreach ($taxonomies as $taxonomy_bundle_type => $taxonomy_bundle_name) {
                    if (!empty($settings['suggest'][$taxonomy_bundle_type])
                        && ($taxonomy_bundle = $this->_application->Entity_Bundle($taxonomy_bundle_name))
                    ) {
                        $suggest_no_url = empty($settings['suggest'][$taxonomy_bundle_type . '_jump']);
                        $data['suggest-taxonomy-' . $taxonomy_bundle_type . '-url'] = $this->_getSuggestTaxonomyUrl(
                            $taxonomy_bundle_type,
                            [$taxonomies[$taxonomy_bundle_type]],
                            isset($settings['suggest'][$taxonomy_bundle_type . '_depth']) ? (int)$settings['suggest'][$taxonomy_bundle_type . '_depth'] : null,
                            !empty($settings['suggest'][$taxonomy_bundle_type . '_hide_empty']),
                            $suggest_no_url
                        );
                        $data['suggest-taxonomy-top-' . $taxonomy_bundle_type . '-url'] = $this->_getSuggestTaxonomyUrl(
                            $taxonomy_bundle_type,
                            [$taxonomies[$taxonomy_bundle_type]],
                            1,
                            !empty($settings['suggest'][$taxonomy_bundle_type . '_hide_empty']),
                            $suggest_no_url
                        );
                        $data['suggest-taxonomy-' . $taxonomy_bundle_type . '-header'] = $taxonomy_bundle->getLabel('singular');
                        $data['suggest-taxonomy-' . $taxonomy_bundle_type . '-icon'] = $this->_application->Entity_BundleTypeInfo($taxonomy_bundle_type, 'icon');
                        $data['suggest-taxonomy-' . $taxonomy_bundle_type . '-num'] = isset($settings['suggest'][$taxonomy_bundle_type . '_num']) ? $settings['suggest'][$taxonomy_bundle_type . '_num'] : 3;
                        if (empty($settings['suggest'][$taxonomy_bundle_type . '_hide_count'])) {
                            $data['suggest-taxonomy-' . $taxonomy_bundle_type . '-count'] = $bundle->type;
                        }
                        $data['suggest-taxonomy-' . $taxonomy_bundle_type . '-parents'] = empty($settings['suggest'][$taxonomy_bundle_type . '_inc_parents']) ? 'false' : 'true';
                    } else {
                        unset($taxonomies[$taxonomy_bundle_type]);
                    }
                }
                $data['suggest-taxonomy'] = implode(',', array_keys($taxonomies));
            }
        }
        
        $form = [
            '#data' => $data,
            '#default_value' => $request,
            'text' => [
                '#type' => 'textfield',
                '#placeholder' => $settings['form']['placeholder'],
                '#data' => ['clear-placeholder' => 1],
                '#attributes' => [
                    'class' => 'drts-search-keyword-text',
                    'autocomplete' => 'off',
                    'id' => '__FORM_ID__-search-keyword-text',
                ],
                '#add_clear' => true,
                '#field_prefix' => empty($settings['form']['icon']) ? null : '<label for="__FORM_ID__-search-keyword-text" class="' . $settings['form']['icon'] . '"></label>',
                '#field_prefix_no_addon' => true,
                '#required' => !empty($settings['required']),
                '#error_no_output' => true,
            ],
            'id' => [
                '#type' => 'hidden',
                '#class' => 'drts-search-keyword-id',
            ],
            'taxonomy' => [
                '#type' => 'hidden',
                '#class' => 'drts-search-keyword-taxonomy',
            ],
        ];
        
        if (!empty($settings['suggest']['enable'])) {
            $form['#pre_render'][__CLASS__] = [$this, 'preRenderCallback'];
            $form['#id'] = '__FORM_ID__-search-keyword';
            $form['#js_ready'] = 'DRTS.Search.keyword("#__FORM_ID__-search-keyword");';
        }
        
        return $form;
    }
    
    protected function _getSuggestUrl($bundle, $num, $noUrl = false, $query = null)
    {
        return (string)$this->_application->MainUrl(
            '/_drts/entity/' . $bundle->type . '/query/' . $bundle->name,
            [
                'query' => $query,
                'num' => $num,
                'no_url' => (int)$noUrl,
                'v' => $this->_getSuggestUrlVersion($noUrl),
                Request::PARAM_CONTENT_TYPE => 'json',
            ],
            '',
            '&'
        );        
    }
    
    protected function _getSuggestTaxonomyUrl($taxonomyBundleType, $taxonomyBundles, $depth = null, $hideEmpty = false, $noUrl = true)
    {
        return (string)$this->_application->MainUrl(
            '/_drts/entity/' . $taxonomyBundleType . '/taxonomy_terms/' . implode(',', $taxonomyBundles),
            [
                'depth' => empty($depth) ? null : $depth,
                'hide_empty' => $hideEmpty ? 1 : null,
                'no_url' => (int)$noUrl,
                'no_depth' => 1,
                'all_count_only' => 1,
                'v' => $this->_getSuggestUrlVersion($noUrl),
                Request::PARAM_CONTENT_TYPE => 'json',
            ],
            '',
            '&'
        );
    }

    protected function _getSuggestUrlVersion($noUrl)
    {
        if (!isset(self::$_suggestUrlVersion)) {
            self::$_suggestUrlVersion = $this->_application->Filter('search_field_keyword_suggest_url_version', SearchComponent::VERSION . '-' . (new \DateTime())->format('Y-W') . '-' . (int)$noUrl);
        }
        return self::$_suggestUrlVersion;
    }
    
    public function searchFieldIsSearchable(Bundle $bundle, array $settings, &$value, array $requests = null)
    {        
        // Allow request value sent as string instead of array
        if (is_string($value)) {
            $value = ['text' => $value];
        }
        
        if (!empty($value['id'])) {
            if (!empty($value['taxonomy'])) {
                if (!$this->_application->Entity_Bundle($value['taxonomy'], $bundle->component, $bundle->group)) {
                    return false;
                }
            }
            return true;
        }
        
        unset($value['id']);
        
        if (!isset($value['text'])
            || (!$value['text'] = trim((string)$value['text']))
        ) {
            return false;
        }
        
        $keywords = $this->_application->Keywords($value['text'], $settings['min_length'], $settings['match'] !== 'all', $settings['match'] !== 'exact');
        
        if (empty($keywords[0])) return false; // no valid keywords
        
        $value['keywords'] = $keywords[0];
        $value['keywords_ignored'] = $keywords[1];
        
        return true;
    }
    
    public function searchFieldSearch(Bundle $bundle, Query $query, array $settings, $value, $sort, array &$sorts)
    {
        if (!empty($value['id'])) {
            if (!empty($value['taxonomy'])) {
                $taxonomy_bundle = $this->_application->Entity_Bundle($value['taxonomy'], $bundle->component, $bundle->group);
                $query->taxonomyTermIdIs(
                    $taxonomy_bundle->type,
                    $value['id'],
                    $taxonomy_bundle->info['is_hierarchical'] ? false : true,
                    $taxonomy_bundle->type . '_search_keyword', // table alias
                    $taxonomy_bundle->type . '_search_keyword' // criteria name
                );
            } else {
                $query->fieldIs('id', $value['id']);
            }
            return;
        }
        
        // Search child content types?
        $on = null;
        $bundle_names = [$bundle->name];
        if (!empty($settings['child_bundle_types'])) {
            if ($child_bundles = $this->_application->Entity_Bundles($settings['child_bundle_types'], $bundle->component, $bundle->group)) {
                foreach (array_keys($child_bundles) as $child_bundle_name) {
                    $bundle_names[] = $child_bundle_name;
                }
            }
            $entity_type_info = $this->_application->Entity_Types_impl($bundle->entitytype_name)->entityTypeInfo();
            if (!empty($entity_type_info['properties']['parent'])) {
                $parent_prop = $entity_type_info['properties']['parent'];
                if (isset($parent_prop['field_name'])) {
                    // parent field is in another table
                    $on = 'entity_id = %3$s AND %1$s.entity_type = ' . $this->_application->getDB()->escapeString($bundle->entitytype_name);
                    $query->addTableJoin('entity_field_' . $parent_prop['field_name'], $parent_prop['field_name'], $on)
                        ->setTableIdColumn('COALESCE(NULLIF(' . $parent_prop['field_name'] . '.' . $parent_prop['column'] . ', 0), %s)');
                } else {
                    $query->setTableIdColumn('COALESCE(NULLIF(' . $parent_prop['column'] . ', 0), %s)');
                }
            }
        }
        if (count($bundle_names) > 1) {
            $query->removeNamedCriteria('bundle_name')->fieldIsIn('bundle_name', $bundle_names);
        }
        
        if ($settings['match'] === 'any') {
            $query->startCriteriaGroup('OR');
            $this->_queryKeywords($bundle, $query, $value['keywords'], $on, $settings['extra_fields'], $settings['taxonomies'], !empty($settings['exclude_content']));
            $query->finishCriteriaGroup();
        } else {
            $this->_queryKeywords($bundle, $query, $value['keywords'], $on, $settings['extra_fields'], $settings['taxonomies'], !empty($settings['exclude_content']));
        }
    }
    
    protected function _queryKeywords(Bundle $bundle, Query $query, array $keywords, $on, array $extraFields = null, array $taxonomies = null, $excludeContent = false)
    {
        foreach ($keywords as $keyword) {
            $query->startCriteriaGroup('OR')->fieldContains('title', $keyword);
            if (empty($excludeContent)) {
                $query->fieldContains('content', $keyword, 'value', null, $on); // need this to join content field table with child entities as well
            }
            if (!empty($extraFields)) {
                foreach ($extraFields as $field_name) {
                    if (strpos($field_name, ',')) {
                        list($field_name, $column) = explode(',', $field_name);
                    } else {
                        $column = 'value';
                    }
                    if ($_field = $this->_application->Entity_Field($bundle, $field_name)) {
                        $query->fieldContains($_field, $keyword, $column);
                    }
                }
            }
            if (!empty($taxonomies)) {
                foreach ($taxonomies as $taxonomy) {
                    if (!$taxonomy_bundle = $this->_application->Entity_Bundle($taxonomy)) continue;

                    $query->taxonomyTermTitleContains($taxonomy_bundle->name, $taxonomy_bundle->type, $keyword, $taxonomy_bundle->type . '_search_term_title');
                }
            }
            $query->finishCriteriaGroup();
        }
    }
    
    public function searchFieldLabels(Bundle $bundle, array $settings, $value)
    {
        $labels = empty($value['keywords']) ? [$value['text']] : $value['keywords'];
        $ignored_labels = empty($value['keywords_ignored']) ? null : $value['keywords_ignored'];
        return [$labels, $ignored_labels];
    }

    public function searchFieldUnsearchableLabel(Bundle $bundle, array $settings, $value)
    {
        return is_string($value) ? $value : (isset($value['text']) ? $value['text'] : null);
    }

    public function preRenderCallback($form)
    {
        $this->_application->Form_Scripts_typeahead();
    }
}
