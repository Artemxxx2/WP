<?php
namespace SabaiApps\Directories\Component\Location\FieldFilter;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Request;
use SabaiApps\Directories\Component\Search\SearchComponent;

class AddressFieldFilter extends Field\Filter\AbstractFilter
{    
    protected function _fieldFilterInfo()
    {
        return array(
            'label' => __('Location', 'directories-pro'),
            'field_types' => array($this->_name),
            'default_settings' => array(
                'disable_input' => false,
                'radius' => 10,
                'disable_radius' => false,
                'placeholder' => null,
                'search_this_area' => true,
                'search_my_loc' => true,
                'search_my_loc_radius' => 1,
            ),
        );
    }

    public function fieldFilterSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {        
        $states_field_selector_prefix = $this->_application->Form_FieldName($parents);
        $input_visible_states = array(
            'visible' => array(
                sprintf('input[name="%s[disable_input]"]', $states_field_selector_prefix) => array('type' => 'checked', 'value' => false),
            ),
        );
        return array(
            'radius' => array(
                '#type' => 'slider',
                '#min_value' => 0,
                '#max_value' => $this->_application->Filter('location_address_search_max_radius', 100),
                '#min_text' => __('Auto', 'directories-pro'),
                '#field_suffix' => $this->_application->getComponent('Map')->getConfig('map', 'distance_unit') === 'mi' ? 'mi' : 'km',
                '#title' => __('Default search radius', 'directories-pro'),
                '#default_value' => $settings['radius'],
            ),
            'disable_input' => array(
                '#type' => 'checkbox',
                '#title' => __('Disable location input', 'directories-pro'),
                '#default_value' => !empty($settings['disable_input']),
            ),
            'disable_radius' => array(
                '#type' => 'checkbox',
                '#title' => __('Disable search radius selection', 'directories-pro'),
                '#default_value' => !empty($settings['disable_radius']),
                '#states' => $input_visible_states,
            ),
            'placeholder' => array(
                '#type' => 'textfield',
                '#title' => __('Placeholder text', 'directories-pro'),
                '#default_value' => $settings['placeholder'],
                '#states' => $input_visible_states,
            ),
            'search_this_area' => array(
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['search_this_area']),
                '#title' => __('Add "Search this area" button to map', 'directories-pro'),
            ),
            'search_this_area_drag' => [
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['search_this_area_drag']),
                '#title' => __('Search displayed area when map is dragged', 'directories-pro'),
                '#states' => [
                    'visible' => [
                        sprintf('input[name="%s[search_this_area]"', $states_field_selector_prefix) => ['type' => 'checked', 'value' => true],
                    ],
                ],
            ],
            'search_my_loc' => array(
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['search_my_loc']),
                '#title' => __('Add "Search my location" button to map', 'directories-pro'),
            ),
            'search_my_loc_auto' => array(
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['search_my_loc_auto']),
                '#title' => __('Click "Search my location" button automatically on page load', 'directories-pro'),
                '#states' => [
                    'visible' => [
                        sprintf('input[name="%s[search_my_loc]"]', $states_field_selector_prefix) => ['type' => 'checked', 'value' => true],
                    ],
                ],
            ),
            'search_my_loc_auto_ts' => array(
                '#type' => 'slider',
                '#integer' => true,
                '#min_value' => 0,
                '#max_value' => 500,
                '#default_value' => isset($settings['search_my_loc_auto_ts']) ? $settings['search_my_loc_auto_ts'] : 100,
                '#title' => __('Delay in milliseconds before automatically clicking the "Search my location" button', 'directories-pro'),
                '#states' => [
                    'visible' => [
                        sprintf('input[name="%s[search_my_loc]"]', $states_field_selector_prefix) => ['type' => 'checked', 'value' => true],
                        sprintf('input[name="%s[search_my_loc_auto]"]', $states_field_selector_prefix) => ['type' => 'checked', 'value' => true],
                    ],
                ],
            ),
        );

    }
    
    public function fieldFilterForm(Field\IField $field, $filterName, array $settings, $request = null, Entity\Type\Query $query = null, array $current = null, $autoSubmit = true, array $parents = [])
    {
        $form = array(
            '#type' => 'location_text',
            '#disable_input' => !empty($settings['disable_input']) || $this->_application->Location_IsSearchRequested(),
            '#radius' => $settings['radius'],
            '#disable_radius' => !empty($settings['disable_radius']),
            '#placeholder' => $settings['placeholder'],
            '#geolocation' => true,
            '#class' => 'drts-view-filter-ignore',
            '#data' => array(
                'ignore-element-name' => $filterName . '[text]',
                'ignore-element-value' => '',
                'search-this-area' => empty($settings['search_this_area']) ? 0 : 1,
                'search-this-area-drag' => empty($settings['search_this_area_drag']) ? 0 : 1,
                'search-this-area-label' => __('Current Map View', 'directories-pro'),
                'search-my-loc' => empty($settings['search_my_loc']) ? 0 : 1,
                'search-my-loc-label' => __('Current location', 'directories-pro'),
            ),
        );
        if (!empty($settings['search_my_loc'])
            && !empty($settings['search_my_loc_auto'])
            && !Request::isXhr()
            && empty($_REQUEST['filter'])
            && empty($_REQUEST[SearchComponent::FORM_SEARCH_PARAM_NAME])
        ) {
            $form['#js_ready'] = sprintf(
                'setTimeout(function () {
    $(".drts-location-map-control[data-action=\'geolocate\']").first().click();
}, %d);',
                $settings['search_my_loc_auto_ts']
            );
        }

        return $form;
    }
    
    public function fieldFilterIsFilterable(Field\IField $field, array $settings, &$value, array $requests = null)
    {
        return false !== ($value = $this->_application->Location_FilterField_preFilter($value, $settings['radius']));
    }
    
    public function fieldFilterDoFilter(Field\Query $query, Field\IField $field, array $settings, $value, array &$sorts)
    {
        $this->_application->callHelper(
            'Location_FilterField',
            array($field, $query, $value, array('default_radius' => $settings['radius']), &$sorts)
        );
    }
    
    public function fieldFilterLabels(Field\IField $field, array $settings, $value, $form, $defaultLabel)
    {
        return array('' => $this->_application->H(isset($value['text']) ? $value['text'] : __('Current Map View', 'directories-pro')));
    }
}
