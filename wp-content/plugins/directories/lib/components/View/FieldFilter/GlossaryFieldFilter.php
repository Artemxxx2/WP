<?php
namespace SabaiApps\Directories\Component\View\FieldFilter;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;

class GlossaryFieldFilter extends Field\Filter\AbstractFilter
{
    protected function _fieldFilterInfo()
    {
        return array(
            'field_types' => ['entity_title', 'string'],
            'label' => __('A to Z', 'directories'),
            'default_settings' => array(
                'type' => 'buttons',
                'columns' => 3,
                'hide_empty' => false,
                'hide_count' => false,
            ),
            'facetable' => true,
        );
    }

    public function fieldFilterSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        return array(
            'type' => [
                '#type' => 'select',
                '#title' => __('Form field type', 'directories'),
                '#options' => [
                    'buttons' => __('Buttons', 'directories'),
                    'select' => __('Select list', 'directories'),
                    'radios' => __('Radio buttons', 'directories'),
                ],
                '#default_value' => $settings['type'],
                '#weight' => 4,
            ],
            'columns' => [
                '#type' => 'select',
                '#title' => __('Number of columns', 'directories'),
                '#options' => [1 => 1, 2 => 2, 3 => 3, 4 => 4, 6 => 6, 12 => 12],
                '#default_value' => $settings['columns'],
                '#states' => [
                    'visible' => [
                        sprintf('[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['type']))) => ['value' => 'radios'],
                    ],
                ],
                '#weight' => 5,
            ],
            'hide_empty' => array(
                '#type' => 'checkbox',
                '#title' => __('Hide empty', 'directories'),
                '#default_value' => !empty($settings['hide_empty']),
                '#weight' => 6,
            ),
            'hide_count' => array(
                '#type' => 'checkbox',
                '#title' => __('Hide count', 'directories'),
                '#default_value' => !empty($settings['hide_count']),
                '#weight' => 7,
            ),
        );
    }

    public function fieldFilterIsFilterable(Field\IField $field, array $settings, &$value, array $requests = null)
    {
        return !empty($value);
    }

    public function fieldFilterDoFilter(Field\Query $query, Field\IField $field, array $settings, $value, array &$sorts)
    {
        $query->fieldStartsWith(($property = $field->isPropertyField()) ? $property : $field->getFieldName(), $value, $this->_getFacetColumn($field, $settings));
    }

    public function fieldFilterForm(Field\IField $field, $filterName, array $settings, $request = null, Entity\Type\Query $query = null, array $current = null, $autoSubmit = true, array $parents = [])
    {
        if (false === $facets = $this->_getFacets($field, $settings, $query)) return;

        if (!isset($current)) {
            $current = array(
                '#type' => $settings['type'],
                '#options' => [
                    '' => ['#title' => __('All', 'directories')]
                ] + $this->_getOptions($field, $settings),
                '#entity_filter_form_type' => $settings['type'] === 'buttons' ? 'radios' : $settings['type'],
                '#empty_value' => '',
                '#columns' => $settings['columns'],
            );
        }

        if (isset($facets)) {
            $this->_loadFacetCounts($current, $facets, $settings, $request);
        }

        return empty($current['#options']) ? null : $current;
    }

    protected function _getMatchAndOr(Field\IField $field, array $settings)
    {
        return 'OR';
    }

    protected function _getFacets(Field\IField $field, array $settings, Entity\Type\Query $query = null)
    {
        if (!$query->view_enable_facet_count
            || !empty($settings['hide_count'])
        ) return;

        $field_query = $query->getFieldQuery();
        if ($this->_getMatchAndOr($field, $settings) === 'OR') {
            // Clone field query and exclude queries for the taxonomy field and use it to fetch facets
            $field_query = clone $field_query;
            $field_query->removeNamedCriteria(($property = $field->isPropertyField()) ? $property : $field->getFieldName());
        }
        $facets = $this->_application->Entity_Facets(
            $field,
            $field_query,
            [
                'facet_type' => 'first_letter',
                'letters' => array_keys($this->_getOptions($field, $settings)),
                'column' => $this->_getFacetColumn($field, $settings),
            ]
        );

        if (!$facets) {
            return empty($settings['hide_empty']) ? [] : false;
        }

        return $facets;
    }

    protected function _getFacetColumn(Field\IField $field, array $settings)
    {
        return $field->isPropertyField() ? null : 'value';
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
                        $form['#options'][$value] = $form['#options'][$value] . '(0)';
                    } else {
                        $form['#options'][$value]['#count'] = 0;
                    }
                    if (!in_array($value, $_request)) {
                        // Disable only when the option is currently not selected
                        $form['#options_disabled'][] = $value;
                    }
                }
            } else {
                if (!is_array($form['#options'][$value])) {
                    $form['#options'][$value] = $form['#options'][$value] . '(' . $facets[$value] . ')';
                } else {
                    $form['#options'][$value]['#count'] = $facets[$value];
                }
            }
        }
    }

    protected function _getOptions(Field\IField $field, array $settings)
    {
        $ret = [];
        $chars = range('A', 'Z');
        foreach ($chars as $char) {
            $ret[strtolower($char)] = ['#title' => $char];
        }
        return $ret;
    }

    public function fieldFilterLabels(Field\IField $field, array $settings, $value, $form, $defaultLabel)
    {
        $ret = [];
        foreach ((array)$value as $_value) {
            if (is_array($form['#options'][$_value])) {
                $label = $form['#options'][$_value]['#title'];
            } else {
                $label = $form['#options'][$_value];
            }
            $ret[$_value] = $this->_application->H($defaultLabel . ': ' . $label);
        }

        return $ret;
    }
}
