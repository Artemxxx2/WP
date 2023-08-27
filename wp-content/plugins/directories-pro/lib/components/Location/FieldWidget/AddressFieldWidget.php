<?php
namespace SabaiApps\Directories\Component\Location\FieldWidget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Component\Map;
use SabaiApps\Directories\Request;

class AddressFieldWidget extends Field\Widget\AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return array(
            'field_types' => array($this->_name),
            'default_settings' => array(
                'map_type' => 'roadmap',
                'map_height' => 300,
                'center_latitude' => null,
                'center_longitude' => null,
                'zoom' => 10,
                'custom_input_fields' => false,
                'input_fields' => array(
                    'options' => array(
                        'street' => __('Address Line 1', 'directories-pro'),
                        'street2' => __('Address Line 2', 'directories-pro'),
                        'zip' => __('Postal / Zip Code', 'directories-pro'),
                        'city' => __('City', 'directories-pro'),
                        'province' => __('State / Province / Region', 'directories-pro'),
                        'country' => __('Country', 'directories-pro'),
                    ),
                    'default' => array('street', 'zip', 'city', 'province'),
                ),
                'input_country' => null,
                'hide_timezone_if_no_map' => false,
                'hide_timezone_if' => false,
                'find_btn_overwrite' => false,
                'latlng_required' => true,
            ),
            'repeatable' => true,
        );
    }

    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        $ret = [];
        $default_settings = $this->fieldWidgetInfo('default_settings');
        $settings['input_fields']['options'] += $default_settings['input_fields']['options'];
        $input_field_options_disabled = [];
        $input_field_title = $input_field_description = null;
        $input_field_states = [];
        if (is_object($fieldType)
            && !$fieldType->isCustomField()
            && ($location_bundle = $this->_getLocationBundle($fieldType))
            && ($hierarchy = $this->_application->Location_Hierarchy($location_bundle))
            && $this->_hasTopLevelLocations($location_bundle)
        ) {
            if (!isset($hierarchy['country'])) {
                // Define country if hierarchy does not contain countries
                $ret['input_country'] = array(
                    '#type' => 'select',
                    '#title' => __('Default country', 'directories-pro'),
                    '#default_value' => $settings['input_country'],
                    '#options' => ['' => __('— Select —', 'directories-pro')] + array_combine($countries = $this->_application->System_Countries(), $countries),
                    '#weight' => 2,
                );
            }
            foreach (array_keys($hierarchy) as $location_level_key) {
                if (!isset($settings['input_fields']['options'][$location_level_key])) continue;

                $input_field_options_disabled[] = $location_level_key;
                if (isset($settings['input_fields']['default'][$location_level_key])) {
                    unset($settings['input_fields']['default'][$location_level_key]);
                }
            }
            if (!empty($input_field_options_disabled)) {
                $admin_path = $this->_application->Entity_BundleTypeInfo($bundle, 'admin_path');
                $admin_path = strtr($admin_path, [
                    ':bundle_name' => $location_bundle->name,
                    ':directory_name' => $location_bundle->group,
                    ':bundle_group' => $location_bundle->group,
                ]);
                $input_field_description = sprintf(
                    $this->_application->H(__('%s: Already in use in %s.')),
                    '<em>' . implode('</em>, <em>', $input_field_options_disabled) . '</em>',
                    '<a href="' . $this->_application->Url($admin_path, [], 'drts-location-hierarchy-settings') . '">' . $this->_application->H(__('Location Hierarchy Settings', 'directories-pro')) . '</a>'
                );
            }
            $input_field_title = __('Address fields', 'directories-pro');
        } else {
            $ret['custom_input_fields'] = array(
                '#title' => __('Customize address fields', 'directories-pro'),
                '#type' => 'checkbox',
                '#default_value' => !isset($settings['custom_input_fields']) || $settings['custom_input_fields'],
                '#weight' => 0,
            );
            $input_field_states = [
                'visible' => [
                    sprintf('[name="%s[custom_input_fields]"]', $this->_application->Form_FieldName($parents)) => ['type' => 'checked', 'value' => true],
                ],
            ];
            $ret['input_country'] = array(
                '#type' => 'select',
                '#default_value' => $settings['input_country'],
                '#options' => ['' => __('— Select —', 'directories-pro')] + array_combine($countries = $this->_application->System_Countries(), $countries),
                '#states' => $input_field_states,
                '#weight' => 2,
                '#field_prefix' => __('Default country', 'directories-pro'),
            );
        }

        $ret['input_fields'] = array(
            '#multiple' => true,
            '#title' => $input_field_title,
            '#type' => 'options',
            '#default_value' => $settings['input_fields'],
            '#disable_add' => true,
            '#disable_icon' => true,
            '#options_value_disabled' => true,
            '#options_disabled' => $input_field_options_disabled,
            '#description' => $input_field_description,
            '#description_no_escape' => true,
            '#states' => $input_field_states,
            '#weight' => 1,
        );

        if (isset($settings['input_fields_required'])) {
            $required_fields = $settings['input_fields_required'];
        } else {
            $required_fields = $settings['input_fields']['options'];
            unset($required_fields['street2']);
            $required_fields = array_keys($required_fields);
        }
        $require_field_options = $settings['input_fields']['options'];
        if (!empty($hierarchy)) $require_field_options += $hierarchy;
        $ret['input_fields_required'] = [
            '#title' => __('Required fields', 'directories-pro'),
            '#type' => 'checkboxes',
            '#options' => $require_field_options,
            '#default_value' => $required_fields,
            '#states' => [
                'visible' => [
                    sprintf('[name="%s[required]"]', $this->_application->Form_FieldName($rootParents)) => ['type' => 'checked', 'value' => true],
                ],
            ],
            '#columns' => 3,
            '#weight' => 3,
        ];
        $ret['latlng_required'] = [
            '#title' => __('Require location on map', 'directories-pro'),
            '#type' => 'checkbox',
            '#default_value' => !empty($settings['latlng_required']),
            '#states' => [
                'visible' => [
                    sprintf('[name="%s[required]"]', $this->_application->Form_FieldName($rootParents)) => ['type' => 'checked', 'value' => true],
                ],
            ],
            '#weight' => 3,
        ];

        $ret += [
            'map_height' => array(
                '#type' => 'textfield',
                '#size' => 4,
                '#maxlength' => 3,
                '#field_suffix' => 'px',
                '#title' => __('Map height', 'directories-pro'),
                '#description' => __('Enter the height of map in pixels.', 'directories-pro'),
                '#default_value' => $settings['map_height'],
                '#numeric' => true,
                '#weight' => 5,
            ),
            'center_latitude' => array(
                '#type' => 'textfield',
                '#maxlength' => 20,
                '#title' => __('Default latitude', 'directories-pro'),
                '#description' => __('Enter the latitude of the default map location in decimals.', 'directories-pro'),
                '#default_value' => $settings['center_latitude'],
                '#regex' => Map\MapComponent::LAT_REGEX,
                '#numeric' => true,
                '#weight' => 10,
            ),
            'center_longitude' => array(
                '#type' => 'textfield',
                '#maxlength' => 20,
                '#title' => __('Default longitude', 'directories-pro'),
                '#description' => __('Enter the longitude of the default map location in decimals.', 'directories-pro'),
                '#default_value' => $settings['center_longitude'],
                '#regex' => Map\MapComponent::LNG_REGEX,
                '#numeric' => true,
                '#weight' => 11,
            ),
            'zoom' => array(
                '#type' => 'slider',
                '#min_value' => 0,
                '#max_value' => 19,
                '#title' => __('Default zoom level', 'directories-pro'),
                '#default_value' => $settings['zoom'],
                '#integer' => true,
                '#weight' => 15,
            ),
        ];

        if (!$this->_application->Map_Api()) {
            $ret += [
                'hide_timezone_if_no_map' => [
                    '#type' => 'checkbox',
                    '#title' => __('Disable timezone selection field', 'directories-pro'),
                    '#default_value' => !empty($settings['hide_timezone_if_no_map']),
                    '#weight' => 25,
                ],
            ];
            foreach (['map_height', 'center_latitude', 'center_longitude', 'zoom', 'latlng_required'] as $key) {
                $ret[$key]['#type'] = 'hidden';
            }
        } else {
            $ret += [
                'hide_timezone' => [
                    '#type' => 'checkbox',
                    '#title' => __('Disable timezone selection field', 'directories-pro'),
                    '#default_value' => !empty($settings['hide_timezone']),
                    '#weight' => 25,
                ],
            ];
        }

        if ($this->_application->Location_Api('Geocoding')) {
            $ret['find_btn_overwrite'] = [
                '#type' => 'checkbox',
                '#title' => __('Overwrite address fields on "Find on map" click', 'directories-pro'),
                '#default_value' => !empty($settings['find_btn_overwrite']),
                '#weight' => 3,
                '#states' => $input_field_states,
            ];
        }

        return $ret;
    }

    public function fieldWidgetForm(Field\IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        $map_config = $this->_application->getComponent('Map')->getConfig('map');
        $fields_required = $field->isFieldRequired() && isset($settings['input_fields_required']) ? $settings['input_fields_required'] : null;
        $ret = array(
            // Group and add class for cloning the field
            '#group' => true,
            '#class' => 'drts-location-address-container',
            'location' => [
                'address' => [
                    '#type' => 'location_address',
                    '#map_type' => isset($map_config['type']) ? $map_config['type'] : null,
                    '#map_height' => $settings['map_height'],
                    '#center_latitude' => empty($settings['center_latitude']) ? $map_config['default_location']['lat'] : $settings['center_latitude'],
                    '#center_longitude' => empty($settings['center_longitude']) ? $map_config['default_location']['lng'] : $settings['center_longitude'],
                    '#zoom' => $settings['zoom'],
                    '#default_value' => $value,
                    '#weight' => 1,
                    '#hide_timezone_if_no_map' => !empty($settings['hide_timezone_if_no_map']) && !$this->_application->Map_Api(),
                    '#hide_timezone' => !empty($settings['hide_timezone']) && $this->_application->Map_Api(),
                    '#input_fields' => [],
                    '#input_fields_required' => $fields_required,
                    '#latlng_required' => $field->isFieldRequired() && isset($settings['latlng_required']) ? $settings['latlng_required'] : null,
                    '#input_country' => $settings['input_country'],
                    '#find_btn_overwrite' => !empty($settings['find_btn_overwrite']),
                ],
            ],
        );

        if (!$field->isCustomField()
            && ($location_bundle = $this->_getLocationBundle($field))
            && ($hierarchy = $this->_application->Location_Hierarchy($location_bundle))
            && ($taxonomy_select_widget = $this->_getSelectLocationForm(
                $location_bundle,
                $field,
                $value,
                $entity,
                $parents,
                $language,
                $taxonomy_select_disabled = !$this->_application->HasPermission('entity_assign_' . $location_bundle->name),
                $hierarchy,
                $fields_required))
        ) {
            if (!empty($settings['input_fields']['default'])) {
                foreach ($settings['input_fields']['default'] as $key) {
                    if (!isset($hierarchy[$key]) // make sure it is not already selectable via taxonomy select dropdown field
                        && isset($settings['input_fields']['options'][$key])
                    ) {
                        $ret['location']['address']['#input_fields'][$key] = $this->_application->System_TranslateString(
                            $settings['input_fields']['options'][$key],
                            'address_field_input_label_' . $key,
                            'location'
                        );
                    }
                }
            }
            $ret['location']['term_id'] = ['#weight' => 0] + $taxonomy_select_widget;
            $ret['#element_validate'] = [
                [
                    [$this, '_validateFormWithTerm'],
                    [
                        $taxonomy_select_disabled,
                        $entity && ($current_term = $entity->getSingleFieldValue($location_bundle->type)) ? $current_term->getId() : null,
                        $fields_required ? array_intersect_key($hierarchy, array_flip($fields_required)) : []
                    ]
                ],
            ];
        } else {
            if ((!empty($settings['custom_input_fields']) || !$this->_application->Map_Api())
                && !empty($settings['input_fields']['default'])
            ) {
                foreach ($settings['input_fields']['default'] as $key) {
                    if (isset($settings['input_fields']['options'][$key])) {
                        $ret['location']['address']['#input_fields'][$key] = $this->_application->System_TranslateString(
                            $settings['input_fields']['options'][$key],
                            'address_field_input_label_' . $key,
                            'location'
                        );
                    }
                }
            }
            $ret['#element_validate'] = [
                [$this, '_validateForm'],
            ];
        }

        return $ret;
    }

    public function _validateForm(Form\Form $form, &$value, $element)
    {
        if ($value === null) return;

        $value = $value['location']['address'];
    }

    public function _validateFormWithTerm(Form\Form $form, &$value, $element, $taxonomySelectDisabled, $currentTermId, $fieldsRequired)
    {
        if ($value === null) return;

        if ($taxonomySelectDisabled) {
            $term_id = $currentTermId;
        } else {
            if (!empty($fieldsRequired)) {
                foreach (array_keys($fieldsRequired) as $required_field_key) {
                    if (empty($value['location']['term_id'][$required_field_key])
                        && !$form->isInvisibleField($element['#name'] . '[location][term_id][' . $required_field_key . ']')
                    ) {
                        $form->setError(sprintf(__('Selection is required for %s.', 'directories-pro'), $fieldsRequired[$required_field_key]), $element);
                        return;
                    }
                }
            }
            $term_id = 0;
            if (!empty($value['location']['term_id'])) {
                while (null !== $_term_id = array_pop($value['location']['term_id'])) {
                    if ($_term_id !== '') {
                        $term_id = $_term_id;
                        break;
                    }
                }
            }
        }
        $value = $value['location']['address'] + array('term_id' => $term_id);
    }

    protected function _getSelectLocationForm($locationBundle, Field\IField $field, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null, $disabled = false, array $hierarchy = [], array $fieldsRequired = null)
    {
        if (!$top_level_locations = $this->_getTopLevelLocations($locationBundle, $language)) return;

        $default_text = __('— Select —', 'directories-pro');
        $hierarchy_keys = array_keys($hierarchy);
        if (!empty($value['term_id'])
            && ($term_entity = $this->_application->Entity_Entity($locationBundle->entitytype_name, $value['term_id']))
        ) {
            $values = $this->_application->Entity_Types_impl($locationBundle->entitytype_name)->entityTypeParentEntityIds($term_entity);
            $values[] = $value['term_id'];
        } else {
            $values = [];
        }
        $disabled = !$this->_application->HasPermission('entity_assign_' . $locationBundle->name);
        $ret = [
            $hierarchy_keys[0] => [
                '#type' => 'select',
                '#title' => $hierarchy[$hierarchy_keys[0]],
                '#horizontal' => true,
                '#weight' => 0,
                '#class' => 'drts-form-field-select-0',
                '#options' => ['' => $default_text] + $top_level_locations,
                '#multiple' => false,
                '#attributes' => [
                    'class' => 'drts-location-term-select drts-location-find-address-component drts-form-selecthierarchical drts-location-address-' . $hierarchy_keys[0],
                ],
                '#default_value' => isset($values[0]) ? $values[0] : null,
                '#disabled' => $disabled,
                '#empty_value' => '',
                '#display_required' => isset($fieldsRequired) && in_array($hierarchy_keys[0], $fieldsRequired),
                '#required' => false,
            ],
        ];
        $load_options_url = $this->_application->MainUrl(
            '/_drts/entity/' . $locationBundle->type . '/taxonomy_terms',
            ['bundle' => $locationBundle->name, Request::PARAM_CONTENT_TYPE => 'json', 'language' => $language, 'depth' => 1]
        );
        $hierarchy_depth = count($hierarchy_keys);
        for ($i = 1; $i < $hierarchy_depth; $i++) {
            $parent_dropdown_selector = sprintf('.drts-form-field-select-%d select', $i - 1);
            $ret[$hierarchy_keys[$i]] = [
                '#type' => 'select',
                '#title' => $hierarchy[$hierarchy_keys[$i]],
                '#horizontal' => true,
                '#multiple' => false,
                '#class' => 'drts-form-field-select-' . $i,
                '#hidden' => true,
                '#attributes' => [
                    'data-load-url' => $load_options_url,
                    'data-options-prefix' => '',
                    'class' => 'drts-location-term-select drts-location-find-address-component drts-form-selecthierarchical drts-location-address-' . $hierarchy_keys[$i],
                ],
                '#default_value' => isset($values[$i]) ? $values[$i] : null,
                '#states' => [
                    'load_options' => [
                        $parent_dropdown_selector => ['type' => 'selected', 'value' => true, 'container' => '.drts-location-address-container'],
                    ],
                ],
                '#options' => ['' => $default_text],
                '#states_selector' => '.drts-form-field-select-' . $i,
                '#skip_validate_option' => true,
                '#weight' => $i,
                '#disabled' => $disabled,
                '#display_required' => isset($fieldsRequired) && in_array($hierarchy_keys[$i], $fieldsRequired),
            ];
        }

        return $ret;
    }

    protected function _getLocationBundle(Field\IField $field)
    {
        if (!isset($field->Bundle->info['taxonomies']['location_location'])) return;

        return $this->_application->Entity_Bundle($field->Bundle->info['taxonomies']['location_location']);
    }

    protected function _getTopLevelLocations($bundle, $language = null)
    {
        $ret = [];
        $terms = $this->_application->Entity_TaxonomyTerms($bundle->name, null, 0, $language);
        if (!empty($terms[0])) {
            foreach (array_keys($terms[0]) as $term_id) {
                $ret[$term_id] = [
                    '#title' => $terms[0][$term_id]['title'],
                    '#attributes' => ['data-alt-value' => $terms[0][$term_id]['name']],
                ];
            }
        }
        return $ret;
    }

    protected function _hasTopLevelLocations($bundle)
    {
        $terms = $this->_application->Entity_TaxonomyTerms($bundle->name, null, 0);
        return !empty($terms[0]);
    }
}
