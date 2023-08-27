<?php
namespace SabaiApps\Directories\Component\Location\SearchField;

use SabaiApps\Directories\Component\Search\Field\AbstractField;
use SabaiApps\Directories\Request;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Entity\Type\Query;

class AddressSearchField extends AbstractField
{
    protected function _searchFieldInfo()
    {
        return [
            'label' => __('Location Search', 'directories-pro'),
            'weight' => 2,
            'default_settings' => [
                'coordinates_field' => 'location_address',
                'geolocation' => true,
                'radius' => 0,
                'disable_radius' => true,
                'suggest' => [
                    'enable' => true,
                    'settings' => [
                        'hide_empty' => false,
                        'hide_count' => false,
                        'inc_parents' => true,
                        'depth' => 0,
                    ],
                ],
                'form' => [
                    'icon' => 'fas fa-map-marker-alt',
                    'placeholder' => _x('Near...', 'search form', 'directories-pro'),
                    'order' => 2,
                ],
            ],
        ];
    }
    
    public function searchFieldSupports(Bundle $bundle)
    {
        return $this->_application->Entity_Field_options($bundle, ['interface' => 'Map\FieldType\ICoordinates']) ? true : false;
    }
    
    public function searchFieldSettingsForm(Bundle $bundle, array $settings, array $parents = [])
    {
        $coordinate_field_options = $this->_application->Entity_Field_options($bundle, ['interface' => 'Map\FieldType\ICoordinates', 'return_disabled' => true]);
        $form = [
            'coordinates_field' => [
                '#type' => 'select',
                '#title' => __('Map coordinates field', 'directories-pro'),
                '#horizontal' => true,
                '#options' => $coordinate_field_options[0],
                '#options_disabled' => array_keys($coordinate_field_options[1]),
                '#default_value' => isset($settings['coordinates_field']) ? $settings['coordinates_field'] : 'location_address',
                '#required' => true,
                '#horizontal' => true,
            ],
            'radius' => [
                '#type' => 'slider',
                '#min_value' => 0,
                '#max_value' => $this->_application->Filter('location_address_search_max_radius', 100),
                '#min_text' => __('Auto', 'directories-pro'),
                '#field_suffix' => $this->_application->getComponent('Map')->getConfig('map', 'distance_unit'),
                '#title' => __('Default search radius', 'directories-pro'),
                '#default_value' => $settings['radius'],
                '#horizontal' => true,
                '#description' => __('Select "Auto" to let the map API calculate the optimal search radius based on the location value entered in the field.', 'directories-pro'),
            ],
            'disable_radius' => [
                '#type' => 'checkbox',
                '#title' => __('Disable search radius selection', 'directories-pro'),
                '#default_value' => !empty($settings['disable_radius']),
                '#horizontal' => true,
            ],
            'geolocation' => [
                '#type' => 'checkbox',
                '#title' => __("Enable search by user's current location", 'directories-pro'),
                '#default_value' => !empty($settings['geolocation']),
                '#horizontal' => true,
            ],
        ];
        if (!empty($bundle->info['location_enable'])) {
            $form['suggest'] = [
                '#title' => __('Auto-Suggest Settings', 'directories-pro'),
                '#class' => 'drts-form-label-lg',
                'enable' => [
                    '#type' => 'checkbox',
                    '#default_value' => !empty($settings['suggest']['enable']),
                    '#title' => __('Auto-suggest terms', 'directories-pro'),
                    '#horizontal' => true,
                ],
                'settings' => [
                    '#states' => [
                        'visible' => [
                            sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['suggest', 'enable']))) => ['type' => 'checked', 'value' => true],
                        ],
                    ],
                    'depth' => [
                        '#type' => 'slider',
                        '#title' => __('Depth of term hierarchy tree', 'directories-pro'),
                        '#min_text' => __('Unlimited', 'directories-pro'),
                        '#default_value' => $settings['suggest']['settings']['depth'],
                        '#min_value' => 0,
                        '#max_value' => 10,
                        '#integer' => true,
                        '#horizontal' => true,
                    ],
                    'hide_empty' => [
                        '#type' => 'checkbox',
                        '#title' => __('Hide empty terms', 'directories-pro'),
                        '#default_value' => !empty($settings['suggest']['settings']['hide_empty']),
                        '#horizontal' => true,
                    ],
                    'hide_count' => [
                        '#type' => 'checkbox',
                        '#title' => __('Hide post counts', 'directories-pro'),
                        '#default_value' => !empty($settings['suggest']['settings']['hide_count']),
                        '#horizontal' => true,
                    ],
                    'inc_parents' => [
                        '#type' => 'checkbox',
                        '#title' => __('Include parent term paths in term title', 'directories-pro'),
                        '#default_value' => !empty($settings['suggest']['settings']['inc_parents']),
                        '#horizontal' => true,
                    ],
                ],
            ];
        }
        return $form;
    }
    
    public function searchFieldForm(Bundle $bundle, array $settings, $request = null, array $requests = null, array $parents = [])
    {        
        $form = [
            '#type' => 'location_text',
            '#default_value' => $request,
            '#radius' => $settings['radius'],
            '#disable_radius' => !empty($settings['disable_radius']),
            '#placeholder' => $settings['form']['placeholder'],
            '#data' => ['clear-placeholder' => 1],
            '#geolocation' => !empty($settings['geolocation']),
            '#required' => !empty($settings['required']),
            '#error_no_output' => true,
            '#suggest_place_geocode' => $this->_application->Filter('location_suggest_place_geocode', true, [$bundle]),
        ];
        if (!empty($settings['form']['icon'])) {
            $form['#text_field_prefix'] = '<label for="__FORM_ID__-location-search-address-text" class="' . $settings['form']['icon'] . '"></label>';
            $form['#text_id'] = '__FORM_ID__-location-search-address-text';
        }

        if (!empty($settings['suggest']['enable'])) {
            if (!empty($bundle->info['location_enable'])
                && ($taxonomy_bundle = $this->_application->Entity_Bundle('location_location', $bundle->component, $bundle->group))
            ) {
                $form['#suggest_location'] = $taxonomy_bundle->name;
                $form['#suggest_location_url'] = $this->_getSuggestTaxonomyUrl([$taxonomy_bundle->name], $settings['suggest']['settings']);
                $form['#suggest_location_count'] = empty($settings['suggest']['settings']['hide_count']) ? '_' . $bundle->type : false;
                $form['#suggest_location_parents'] = !empty($settings['suggest']['settings']['inc_parents']);
                //$form['#suggest_location_header'] = $taxonomy_bundle->getLabel('singular');
                $form['#suggest_location_icon'] = $this->_application->Entity_BundleTypeInfo($taxonomy_bundle->type, 'icon');
            }
        }


        return $form;
    }
    
    public function searchFieldIsSearchable(Bundle $bundle, array $settings, &$value, array $requests = null)
    {
        if (empty($bundle->info['location_enable'])) {
            unset($value['term_id'], $value['taxonomy']);
        }

        return false !== ($value = $this->_application->Location_FilterField_preFilter($value, $settings['radius']));
    }
    
    public function searchFieldSearch(Bundle $bundle, Query $query, array $settings, $value, $sort, array &$sorts)
    {
        if (!$field = $this->_application->Entity_Field($bundle, $settings['coordinates_field'])) return;

        $this->_application->callHelper(
            'Location_FilterField',
            [$field, $query->getFieldQuery(), $value, ['default_radius' => $settings['radius']], &$sorts]
        );
    }
    
    public function searchFieldLabels(Bundle $bundle, array $settings, $value)
    {
        return [[$value['text']]];
    }

    public function searchFieldUnsearchableLabel(Bundle $bundle, array $settings, $value)
    {
        if (isset($value['text'])) return $value['text'];
    }

    protected function _getSuggestTaxonomyUrl(array $taxonomyBundles, array $settings)
    {
        return $this->_application->MainUrl(
            '/_drts/entity/location_location/taxonomy_terms/' . implode(',', $taxonomyBundles),
            [
                'depth' => empty($settings['depth']) ? null : (int)$settings['depth'],
                'hide_empty' => empty($settings['hide_empty']) ? null : 1,
                'no_url' => 1,
                'no_depth' => 1,
                'all_count_only' => 1,
                Request::PARAM_CONTENT_TYPE => 'json',
            ],
            '',
            '&'
        );
    }
}