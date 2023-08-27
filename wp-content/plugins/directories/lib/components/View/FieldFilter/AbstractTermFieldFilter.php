<?php
namespace SabaiApps\Directories\Component\View\FieldFilter;

use SabaiApps\Directories\Component\Field\Filter\AbstractFilter;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Field\Filter\IConditionable;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Framework\Criteria;

abstract class AbstractTermFieldFilter extends AbstractFilter implements IConditionable
{
    protected function _fieldFilterInfo()
    {
        return [
            'field_types' => ['entity_terms'],
            'default_settings' => [
                'hide_empty' => false,
                'hide_count' => false,
                'num' => 30,
                'depth' => 0,
                'exclude' => [],
            ],
            'facetable' => true,
        ];
    }

    public function fieldFilterSettingsForm(IField $field, array $settings, array $parents = [])
    {
        // Get taxonomy bundle associated with the field
        if (!$bundle = $field->getTaxonomyBundle()) {
            return;
        }

        $form = [
            'hide_empty' => [
                '#type' => 'checkbox',
                '#title' => __('Hide empty terms', 'directories'),
                '#default_value' => !empty($settings['hide_empty']),
                '#weight' => 5,
            ],
            'exclude' => [
                '#type' => 'textfield',
                '#title' => __('Exclude terms', 'directories'),
                '#default_value' => $settings['exclude'],
                '#description' => __('Enter term slugs separated with commas.', 'directories'),
                '#weight' => 7,
                '#separator' => ',',
            ],
        ];
        if ($this->_application->getComponent('View')->getConfig('filters', 'facet_count')) {
            $form += [
                'hide_count' => [
                    '#type' => 'checkbox',
                    '#title' => __('Hide count', 'directories'),
                    '#default_value' => !empty($settings['hide_count']),
                    '#weight' => 6,
                ],
            ];
        }
        if (empty($bundle->info['is_hierarchical'])) {
            return $form + [
                'num' => [
                    '#type' => 'slider',
                    '#title' => __('Number of term options', 'directories'),
                    '#default_value' => $settings['num'],
                    '#min_value' => 1,
                    '#max_value' => 500,
                    '#step' => 5,
                    '#integer' => true,
                    '#weight' => 1,
                ],
            ];
        } else {
            return $form + [
                'depth' => [
                    '#type' => 'slider',
                    '#title' => __('Depth of term hierarchy tree', 'directories'),
                    '#default_value' => $settings['depth'],
                    '#min_value' => 0,
                    '#max_value' => 10,
                    '#min_text' => __('Unlimited', 'directories'), 
                    '#integer' => true,
                    '#weight' => 1,
                ],
            ];
        }
    }

    public function fieldFilterIsFilterable(IField $field, array $settings, &$value, array $requests = null)
    {
        return !empty($value);
    }

    public function fieldFilterDoFilter(Query $query, IField $field, array $settings, $value, array &$sorts)
    {
        // Get taxonomy bundle associated with the field
        if (!$bundle = $field->getTaxonomyBundle()) return;

        $term_ids = is_array($value) ? $value : [$value];
        $taxonomy_bundle_type = $field->getFieldName();
        $ignore_auto = empty($bundle->info['is_hierarchical']);
        if ($this->_getMatchAndOr($field, $settings) !== 'AND') {
            // OR query
            $query->taxonomyTermIdIn($taxonomy_bundle_type, $term_ids, $ignore_auto);
        } else {
            // AND query

            // Do not use taxonomyTermIdIs() since we do not want to merge criteria

            $query->startCriteriaGroup('AND');
            foreach ($term_ids as $term_id) {
                $query->fieldIs($taxonomy_bundle_type, $term_id, 'value', $taxonomy_bundle_type . $term_id);
            }
            $query->finishCriteriaGroup();
            if ($ignore_auto) {
                $query->fieldIsNot($taxonomy_bundle_type, true, 'auto');
            }
        }
    }

    protected function _getExcludedTerms(IField $field, array $settings)
    {
        if (empty($settings['exclude'])) return [];

        foreach (array_keys($settings['exclude']) as $key) {
            $settings['exclude'][$key] = strtolower(urlencode($settings['exclude'][$key] ));
        }

        return $settings['exclude'];
    }

    protected function _getMatchAndOr(IField $field, array $settings)
    {
        return 'OR';
    }

    protected function _getQueriedTerms(IField $field, Entity\Model\Bundle $bundle, Entity\Type\Query $query = null, $request = null)
    {
        $ret = [];
        if (isset($GLOBALS['drts_entity'])
            && $GLOBALS['drts_entity']->isTaxonomyTerm()
        ) {
            // Is on single taxonomy term page

            if ($GLOBALS['drts_entity']->getBundleName() === $bundle->name) {
                $ret[$GLOBALS['drts_entity']->getId()] = $GLOBALS['drts_entity']->getSlug();
            }
        } else {
            // Check term IDs are specified through custom query or search

            if (isset($query)) {
                $term_ids = [];
                settype($request, 'array');
                foreach ([
                    $field->getFieldName() . '_entity_query', // custom query
                    $field->getFieldName() . '_search_keyword', // keyword search
                    $field->getFieldName() . '_search_term', // select term search
                ] as $criteria_name) {
                    if ($query->getFieldQuery()->hasNamedCriteria($criteria_name)) {
                        foreach ($query->getFieldQuery()->getNamedCriteria($criteria_name) as $criteria) {
                            if ($criteria instanceof Criteria\InCriteria) {
                                foreach ($criteria->getArray() as $_term_id) {
                                    if ((!$_term_id = (int)$_term_id)
                                        || in_array($_term_id, $request)  // ignore since requested through current filter
                                    ) continue;

                                    $term_ids[$_term_id] = $_term_id;
                                }
                            } elseif ($criteria instanceof Criteria\IsCriteria) {
                                if ((!$_term_id = (int)$criteria->getValue())
                                    || in_array($_term_id, $request)  // ignore since requested through current filter
                                ) continue;

                                $term_ids[$_term_id] = $_term_id;
                            }
                        }
                    }
                }
                if (!empty($term_ids)) {
                    $terms = $this->_application->Entity_Entities($bundle->entitytype_name, $term_ids, false);
                    foreach (array_keys($terms) as $term_id) {
                        $ret[$term_id] = $terms[$term_id]->getSlug();
                    }
                }
            }
        }
        
        return $this->_application->Filter('view_current_term', $ret, [$bundle]);
    }

    protected function _getFacets(IField $field, array $settings, Entity\Type\Query $query = null)
    {
        if (!$query->view_enable_facet_count) return;

        $field_query = $query->getFieldQuery();
        if ($this->_getMatchAndOr($field, $settings) === 'OR') {
            // Clone field query and exclude queries for the taxonomy field and use it to fetch facets
            $field_query = clone $field_query;
            $field_query->removeNamedCriteria($field->getFieldName());
        }
        $facets = $this->_application->Entity_Facets($field, $field_query, [
            'column' => 'value',
        ]);

        if (!$facets) {
            return empty($settings['hide_empty']) ? [] : false;
        }

        return $facets;
    }

    protected function _loadFacetCounts(array &$form, array $facets, array $settings, $request = null)
    {
        if (empty($form['#options'])) return;

        $_request = isset($request) ? (array)$request : [];
        foreach (array_keys($form['#options']) as $value) {
            if ($value === '') continue;

            if (empty($facets[$value])) {
                if (!empty($settings['hide_empty'])) {
                    unset($form['#options'][$value]);
                } else {
                    if (!is_array($form['#options'][$value])) {
                        if (empty($settings['hide_count'])) {
                            $form['#options'][$value] = $form['#options'][$value] . ' (0)';
                        }
                    } else {
                        if (empty($settings['hide_count'])) {
                            $form['#options'][$value]['#count'] = 0;
                        } else {
                            unset($form['#options'][$value]['#count']);
                        }
                    }

                    if (!in_array($value, $_request)) {
                        // Disable only when the option is currently not selected
                        $form['#options_disabled'][] = $value;
                    }
                }
            } else {
                if (!is_array($form['#options'][$value])) {
                    if (empty($settings['hide_count'])) {
                        $form['#options'][$value] = $form['#options'][$value] . ' (' . $facets[$value] . ')';
                    }
                } else {
                    if (empty($settings['hide_count'])) {
                        $form['#options'][$value]['#count'] = $facets[$value];
                    } else {
                        unset($form['#options'][$value]['#count']);
                    }
                }
            }
        }
    }

    public function fieldFilterConditionableInfo(IField $field)
    {
        return [
            '' => [
                'compare' => ['value', '!value', 'one', 'empty', 'filled'],
                'tip' => __('Enter taxonomy term IDs and/or slugs separated with commas.', 'directories'),
                'example' => '1,5,arts,17',
            ],
        ];
    }

    public function fieldFilterConditionableRule(IField $field, $filterName, array $settings, $compare, $value = null, $name = '')
    {
        switch ($compare) {
            case 'value':
            case '!value':
            case 'one':
                $value = trim($value);
                if (strpos($value, ',')) {
                    if (!$value = explode(',', $value)) return;

                    $value = array_map('trim', $value);
                }
                return ['type' => $compare, 'value' => $value];
            case 'empty':
                return ['type' => 'filled', 'value' => false];
            case 'filled':
                return ['type' => 'empty', 'value' => false];
            default:
        }
    }

    protected function _getTermsHiddenField(array $terms)
    {
        $data_attr = [];
        foreach ($terms as $term_id => $term_slug) {
            $data_attr[$term_id]['alt-value'] = $term_slug;
        }
        return [
            '#type' => 'hidden',
            '#attributes' => ['disabled' => 'disabled'],
            '#default_value' => array_keys($terms),
            '#multiple' => true,
            '#data' => $data_attr,
        ];
    }
}
