<?php
namespace SabaiApps\Directories\Component\Field\Filter;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Entity;

class KeywordFilter extends AbstractFilter implements IConditionable
{
    use ConditionableStringTrait;

    protected function _fieldFilterInfo()
    {
        return array(
            'label' => __('Keyword input field', 'directories'),
            'field_types' => array('string', 'text', 'wp_post_content', 'entity_title'),
            'default_settings' => array(
                'min_length' => 3,
                'match' => 'all',
                'placeholder' => null,
                'inc_title' => false,
                'extra_fields' => null,
            ),
        );
    }

    public function fieldFilterSettingsForm(IField $field, array $settings, array $parents = [])
    {
        $form = [
            'min_length' => [
                '#type' => 'slider',
                '#title' => __('Min. length of keywords in characters', 'directories'),
                '#default_value' => $settings['min_length'],
                '#integer' => true,
                '#min_value' => 1,
                '#max_value' => 10,
            ],
            'match' => [
                '#type' => 'select',
                '#title' => __('Match type', 'directories'),
                '#options' => [
                    'any' => __('Match any', 'directories'),
                    'all' => __('Match all', 'directories'),
                ],
                '#default_value' => $settings['match'],
            ],
            'placeholder' => [
                '#type' => 'textfield',
                '#title' => __('Placeholder text', 'directories'),
                '#default_value' => $settings['placeholder'],
            ],
        ];
        if ($field->getFieldType() !== 'entity_title') {
            $form['inc_title'] = [
                '#type' => 'checkbox',
                '#title' => __('Include content item title in search', 'directories'),
                '#default_value' => !empty($settings['inc_title']),
            ];
        }
        // Add extra fields to include in search
        if (($bundle = $field->Bundle)
            && ($_fields = $this->_application->Entity_Field($bundle))
        ) {
            $searchable_fields = [
                'string' => ['value' => null],
                'email' => ['value' => null],
                'url' => ['value' => null],
                'phone' => ['value' => null],
                'text' => ['value' => null],
                'name' => [
                    'first_name' => __('First Name', 'directories'),
                    'middle_name' => __('Middle Name', 'directories'),
                    'last_name' => __('Last Name', 'directories'),
                    'display_name' => null,
                ],
            ];
            $extra_field_options = [];
            foreach ($_fields as $field_name => $_field) {
                if ($_field->isPropertyField()
                    || $field_name === $field->getFieldName()
                ) continue;

                if (isset($searchable_fields[$_field->getFieldType()])) {
                    foreach ($searchable_fields[$_field->getFieldType()] as $column => $column_label) {
                        $label = $_field->getFieldLabel();
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
            ];
        }
        return $form;
    }
    
    public function fieldFilterForm(IField $field, $filterName, array $settings, $request = null, Entity\Type\Query $query = null, array $current = null, $autoSubmit = true, array $parents = [])
    {
        return [
            '#type' => 'search',
            '#placeholder' => $settings['placeholder'],
            '#entity_filter_form_type' => 'textfield',
            '#skip_validate_text' => true,
        ];
    }
    
    public function fieldFilterIsFilterable(IField $field, array $settings, &$value, array $requests = null)
    {
        if (!is_string($value) || !strlen($value)) return false;
        
        $keywords = $this->_application->Keywords($value, $settings['min_length']);
        
        if (empty($keywords[0])) return false; // no valid keywords
        
        $value = implode(' ', $keywords[0]);
        
        return true;
    }
    
    public function fieldFilterDoFilter(Query $query, IField $field, array $settings, $value, array &$sorts)
    {
        $field_name = ($property = $field->isPropertyField()) ? $property : $field->getFieldName();
        $value = explode(' ', $value);
        $search_title = !empty($settings['inc_title']) && $field->getFieldType() !== 'entity_title';
        $bundle = null;
        if (!empty($settings['extra_fields'])) {
            if (!$bundle = $field->Bundle) {
                $settings['extra_fields'] = null;
            }
        } else {
            $settings['extra_fields'] = null;
        }
        if ($settings['match'] === 'any' && count($value) > 1) {
            $query->startCriteriaGroup('OR');
            foreach ($value as $keyword) {
                $this->_queryKeyword($query, $keyword, $field_name, $search_title, $settings['extra_fields'], $bundle);
            }
            $query->finishCriteriaGroup();
        } else {
            foreach ($value as $keyword) {
                $this->_queryKeyword($query, $keyword, $field_name, $search_title, $settings['extra_fields'], $bundle);
            }
        }
    }

    protected function _queryKeyword(Query $query, $keyword, $fieldName, $searchTitle, array $extraFields = null, Entity\Model\Bundle $bundle = null)
    {
        $query->startCriteriaGroup('OR')
            ->fieldContains($fieldName, $keyword);
        if ($searchTitle) {
            $query->fieldContains('title', $keyword);
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
        $query->finishCriteriaGroup();
    }
    
    public function fieldFilterLabels(IField $field, array $settings, $value, $form, $defaultLabel)
    {
        return array('' => $this->_application->H($value));
    }
}