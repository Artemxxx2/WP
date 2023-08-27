<?php
namespace SabaiApps\Directories\Component\Field\Filter;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\Type\ColorType;

class ColorFilter extends AbstractFilter
{
    protected $_valueColumn = 'name';
    
    protected function _fieldFilterInfo()
    {
        return array(
            'field_types' => array($this->_name),
            'default_settings' => array(
                'type' => 'checkboxes',
                'andor' => 'OR',
                'hide_count' => false,
            ),
            'facetable' => true,
        );
    }

    public function fieldFilterSettingsForm(IField $field, array $settings, array $parents = [])
    {
        $form = array(
            'type' => array(
                '#title' => __('Form field type', 'directories'),
                '#type' => 'select',
                '#options' => array(
                    'checkboxes' => __('Checkboxes', 'directories'),
                    'colorpalette' => __('Color palette', 'directories'),
                ),
                '#default_value' => $settings['type'],
                '#weight' => 5,
            ),
            'andor' => array(
                '#title' => __('Match any or all', 'directories'),
                '#type' => 'select',
                '#options' => array('OR' => __('Match any', 'directories'), 'AND' => __('Match all', 'directories')),
                '#default_value' => $settings['andor'],
                '#states' => array(
                    'visible' => array(
                        sprintf('input[name="%s[type]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'checkboxes'),
                    ), 
                ),
                '#weight' => 10,
            ),
        );
        if ($this->_application->getComponent('View')->getConfig('filters', 'facet_count')) {
            $form['hide_count'] = [
                '#type' => 'checkbox',
                '#title' => __('Hide count', 'directories'),
                '#default_value' => $settings['hide_count'],
                '#weight' => 30,
            ];
        }
        return $form;
    }
    
    public function fieldFilterForm(IField $field, $filterName, array $settings, $request = null, Entity\Type\Query $query = null, array $current = null, $autoSubmit = true, array $parents = [])
    {
        if ($settings['type'] === 'checkboxes'
            && $query->view_enable_facet_count
            && empty($settings['hide_count'])
        ) {
            $field_query = $query->getFieldQuery();
            if ($settings['andor'] === 'OR') {
                // Clone field query and exclude queries for the current choice field and fetch facets
                $field_query = clone $field_query;
                $field_query->removeNamedCriteria($field->getFieldName());
            }
            $facets = $this->_application->Entity_Facets($field, $field_query, ['column' => $this->_valueColumn]);
        }
        
        if (!isset($current)) {
            switch ($settings['type']) {
                case 'colorpalette':
                    $current = array(
                        '#type' => $settings['type'],
                        '#colors' => array_keys(ColorType::colors()),
                        '#default_value' => null,
                        '#entity_filter_form_type' => 'checkboxes',
                    );
                    break;
                default:
                    $current = array(
                        '#type' => $settings['type'],
                        '#options' => $this->_getOptions(),
                        '#option_no_escape' => true,
                        '#options_disabled' => [],
                        '#default_value' => null,
                        '#entity_filter_form_type' => 'checkboxes',
                    );
            }
            
        }
        
        if ($settings['type'] !== 'checkboxes') return $current;
        
        if (isset($facets)) {
            $_request = isset($request) ? (array)$request : [];
            foreach (array_keys($current['#options']) as $value) {
                if (empty($facets[$value])) {
                    if (!in_array($value, $_request)) {
                        // Disable only when the option is currently not selected
                        $current['#options_disabled'][] = $value;
                    }
                    $current['#options'][$value] = array(
                        '#title' => $current['#options'][$value],
                        '#count' => 0,
                    );
                } else {
                    $current['#options'][$value] = array(
                        '#title' => $current['#options'][$value],
                        '#count' => $facets[$value],
                    );
                }
            }
        }
        
        return empty($current['#options']) ? null : $current;
    }
    
    protected function _getOptions()
    {
        $options = [];
        $colors = ColorType::colors();
        $labels = ColorType::labels();
        foreach (array_keys($colors) as $name) {
            $options[$name] = $this->_getBadge($name, $labels[$name]);
        }
        return $options;
    }
    
    public function fieldFilterIsFilterable(IField $field, array $settings, &$value, array $requests = null)
    {
        return !empty($value);
    }
    
    public function fieldFilterDoFilter(Query $query, IField $field, array $settings, $value, array &$sorts)
    {
        $value = (array)$value;
        if (count($value) === 1) {
            $query->fieldIs($field, array_shift($value), $this->_valueColumn);
        } elseif ($settings['andor'] === 'OR') {
            $query->fieldIsIn($field, $value, $this->_valueColumn);
        } else {
            $query->startCriteriaGroup('AND')->fieldIs($field, array_shift($value), $this->_valueColumn);
            $i = 1;
            foreach ($value as $_value) {
                $query->fieldIs($field, $_value, $this->_valueColumn, $field->getFieldName() . ++$i);
            }
            $query->finishCriteriaGroup();
        }
    }
    
    public function fieldFilterLabels(IField $field, array $settings, $value, $form, $defaultLabel)
    {
        $ret = [];
        if ($form['#type'] === 'colorpalette') {
            foreach ((array)$value as $_value) {
                $ret[$_value] = $this->_application->H($defaultLabel) . ': ' . $this->_getBadge($_value);
            }
        } else {
            foreach ((array)$value as $_value) {
                $ret[$_value] = $this->_application->H($form['#options'][$_value]);
            }
        }
        
        return $ret;
    }
    
    protected function _getBadge($value, $label = null)
    {
        $value = $this->_application->H($value);
        $badge = '<i class="fas fa-circle fa-fw" style="color:' . $value . ';" data-color="' . $value . '"></i>';
        if (isset($label)) $badge .= ' ' . $this->_application->H($label);
        return $badge;
    }
}