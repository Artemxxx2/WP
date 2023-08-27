<?php
namespace SabaiApps\Directories\Component\Voting\FieldFilter;

use SabaiApps\Directories\Component\Entity\Type\Query;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Filter\IConditionable;
use SabaiApps\Directories\Component\Field\Filter\ConditionableNumberTrait;

class RatingFieldFilter extends AbstractFieldFilter implements IConditionable
{
    use ConditionableNumberTrait;

    protected function _fieldFilterInfo()
    {
        return parent::_fieldFilterInfo() + array(
            'default_settings' => array(
                'type' => 'radios',
                'columns' => 1,
                'hide_count' => false,
            ),
            'facetable' => true,
        );
    }

    public function fieldFilterSettingsForm(IField $field, array $settings, array $parents = [])
    {
        $form = [
            'type' => array(
                '#title' => __('Form field type', 'directories'),
                '#type' => 'select',
                '#options' => array(
                    'checkboxes' => __('Checkboxes', 'directories'),
                    'radios' => __('Radio buttons', 'directories'),
                    'select' => __('Select list', 'directories')
                ),
                '#default_value' => $settings['type'],
            ),
            'columns'  => [
                '#type' => 'select',
                '#title' => __('Number of columns', 'directories'),
                '#options' => [1 => 1, 2 => 2, 3 => 3, 6 => 6],
                '#default_value' => $settings['columns'],
                '#states' => [
                    'invisible' => [
                        sprintf('[name="%s[type]"]', $this->_application->Form_FieldName($parents)) => ['value' => 'select'],
                    ],
                ],
            ],
        ];
        if ($this->_application->getComponent('View')->getConfig('filters', 'facet_count')) {
            $form['hide_count'] = [
                '#type' => 'checkbox',
                '#title' => __('Hide count', 'directories'),
                '#default_value' => $settings['hide_count'],
            ];
        }
        return $form;
    }
    
    public function fieldFilterForm(IField $field, $filterName, array $settings, $request = null, Query $query = null, array $current = null, $autoSubmit = true, array $parents = [])
    {        
        if ($query->view_enable_facet_count
            && empty($settings['hide_count'])
        ) {
            // Clone field query and exclude queries for the rating field and use it to fetch facets
            $field_query = clone $query->getFieldQuery();
            $vote_name = $this->_getVoteName($settings);
            $field_query->removeNamedCriteria($field->getFieldName() . '__' . $vote_name);
            $facets = $this->_application->Entity_Facets(
                $field,
                $field_query,
                [
                    'column' => $this->_valueColumn,
                    'filters' => [
                        'name' => $vote_name,
                    ],
                ]
            );
        }
        
        if (!isset($current)) {
            switch ($settings['type']) {
                case 'select':
                    $options = $this->_application->Voting_RenderRating_options(true, '');
                    break;
                case 'checkboxes':
                    $options = $this->_application->Voting_RenderRating_options(false, null, false);
                    break;
                default:
                    $options = $this->_application->Voting_RenderRating_options(false);
            }
            $current = [
                '#type' => $settings['type'],
                '#options' => $options,
                '#option_no_escape' => true,
                '#columns' => $settings['columns'],
                '#empty_value' => '',
                '#entity_filter_form_type' => $settings['type'],
            ];
        }
        
        if (isset($facets)) {
            $request = (array)$request;
            if ($settings['type'] === 'checkboxes') {
                for ($i = 5; $i >= 1; $i--) {
                    $current['#options'][$i] = array(
                        '#title' => $current['#options'][$i],
                        '#count' => empty($facets[$i]) ? 0 : $facets[$i],
                    );
                    if (empty($facets[$i])
                        && !in_array($i, $request)
                    ) {
                        $current['#options_disabled'][] = $i;
                    }
                }
            } else {
                $request = (int)$request;
                $sum = 0;
                for ($i = 5; $i >= 1; $i--) {
                    if (!empty($facets[$i])) {
                        $sum += $facets[$i];
                    }
                    $current['#options'][$i] = array(
                        '#title' => $current['#options'][$i],
                        '#count' => $sum,
                    );
                    if (empty($sum)
                        && $i !== $request
                    ) {
                        $current['#options_disabled'][] = $i;
                    }
                }
            }
        }
        
        return $current;
    }
    
    public function fieldFilterSupports(IField $field)
    {
        return $field->getFieldName() === 'voting_rating';
    }
}
