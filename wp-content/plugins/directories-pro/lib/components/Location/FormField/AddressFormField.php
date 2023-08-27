<?php
namespace SabaiApps\Directories\Component\Location\FormField;

use SabaiApps\Directories\Component\Form;

class AddressFormField extends Form\Field\FieldsetField
{
    protected static $_count = 0;

    public function formFieldInit($name, array &$data, Form\Form $form)
    {
        $map = [
            'scrollwheel' => $this->_application->getComponent('Map')->getConfig('map', 'scrollwheel') ? 1 : 0,
        ];
        if (isset($data['#default_value']['_latlng'])) {
            $data['#default_value'] += $data['#default_value']['_latlng'];
        }
        if (isset($data['#default_value']['lat'])
            && isset($data['#default_value']['lng'])
            && is_numeric($data['#default_value']['lat'])
            && is_numeric($data['#default_value']['lng'])
            && !(empty($data['#default_value']['lat']) && empty($data['#default_value']['lng']))
        ) {
            $map['center-lat'] = $map['lat'] = str_replace(',', '.', floatval($data['#default_value']['lat']));
            $map['center-lng'] = $map['lng'] = str_replace(',', '.', floatval($data['#default_value']['lng']));
        } else {
            if (isset($data['#center_longitude'])) {
                $map['center-lng'] = str_replace(',', '.', floatval($data['#center_longitude']));
            } else {
                $map['center-lng'] = -73.95144;
            }
            if (isset($data['#center_latitude'])) {
                $map['center-lat'] = str_replace(',', '.', floatval($data['#center_latitude']));
            } else {
                $map['center-lat'] = 40.69847;
            }
            $map['lat'] = $map['lng'] = '';
        }
        if (!empty($data['#default_value']['zoom'])) {
            $map['zoom'] = $data['#default_value']['zoom'];
        } else {
            $map['zoom'] = isset($data['#zoom']) ? intval($data['#zoom']) : 10;
        }
        $map['map-type'] = isset($data['#map_type']) && in_array(strtolower($data['#map_type']), array('satellite', 'hybrid', 'osm'))
            ? $data['#map_type']
            : 'roadmap';
        $data = array(
            '#tree' => true,
            '#group' => true,
            '#children' => array(
                0 => [],
                1 => [],
            ),
            '#data' => $map,
        ) + $data;
        $data['#children'][1] += array(
            'buttons' => array(
                '#type' => 'markup',
            ),
            'map' => array(
                '#type' => 'item',
                '#markup' => sprintf(
                    '<div style="height:%dpx;" class="drts-map-map" data-form-field-name="__FORM_FIELD_NAME__"></div>',
                    empty($data['#map_height']) ? 300 : $data['#map_height']
                ),
            ),
            'manual' => array(
                '#type' => 'checkbox',
                '#title' => __('Enter address details manually', 'directories-pro'),
                '#attributes' => array('class' => 'drts-location-address-manual'),
                '#default_value' => false,
                '#switch' => false,
            ),
            '_address' => array(
                '#type' => 'address',
                '#disable_street2' => true,
                '#class' => 'drts-map-location ' . DRTS_BS_PREFIX . 'mb-0',
                '#class_street' => 'drts-location-address-street',
                '#class_city' => 'drts-location-address-city',
                '#class_province' => 'drts-location-address-province',
                '#class_zip' => 'drts-location-address-zip',
                '#class_country' => 'drts-location-address-country',
                '#attr_street' => isset($data['#street_format']) ? ['data-format' => $data['#street_format']] : null,
                '#default_value' => is_array(@$data['#default_value']['_address']) ? $data['#default_value']['_address'] : @$data['#default_value'],
                '#states' => $hidden_if_not_manual = array(
                    'visible' => array('.drts-location-address-manual' => array('type' => 'checked', 'value' => true, 'container' => '.drts-form-type-location-address')),
                ),
                '#states_selector' => '.drts-map-location',
                '#country_type' => 'System_Countries',
            ),
            'timezone' => [
                '#type' => 'select',
                '#class' => 'drts-location-address-timezone',
                '#title' => __('Timezone', 'directories-pro'),
                '#default_value' => isset($data['#default_value']['timezone']) ? $data['#default_value']['timezone'] : $this->_application->getPlatform()->getTimeZone(),
                '#options' => ['' => ''] + array_combine($identifiers = \DateTimeZone::listIdentifiers(), $identifiers),
                '#states' => $hidden_if_not_manual,
                '#states_selector' => '.drts-location-address-timezone',
            ],
            '_latlng' => array(
                '#type' => 'map_latlng',
                '#class' => 'drts-location-address-latlng',
                '#default_value' => ['lat' => $map['lat'], 'lng' => $map['lng']],
                '#states' => $hidden_if_not_manual,
                '#states_selector' => '.drts-location-address-latlng',
            ),
            'zoom' => array(
                '#type' => 'hidden',
                '#attributes' => array('class' => 'drts-map-field-zoom'),
                '#render_hidden_inline' => true,
                '#value' => isset($data['#default_value']['zoom']) ? $data['#default_value']['zoom'] : 10,
            ),
        );

        $find_addr_btn = $get_addr_btn = $clear_addr_btn = true;
        if (empty($data['#input_fields'])) {
            $data['#children'][0] += array(
                'address' => array(
                    '#type' => 'textfield',
                    '#attributes' => ['class' => 'drts-location-text-input drts-location-address-address drts-location-find-address-component ' . DRTS_BS_PREFIX . 'mb-2'],
                    '#default_value' => @$data['#default_value']['address'],
                    '#required' => !empty($data['#required']),
                ) + (array)@$data['#address'],
            );
        } else {
            foreach ($data['#input_fields'] as $key => $label) {
                if ($key === 'country') {
                    $data['#children'][0][$key] = array(
                        '#type' => 'select',
                        '#options' => ['' => __('— Select —', 'directories-pro')] + array_combine($countries = $this->_application->System_Countries(), $countries),
                        '#default_value' => isset($data['#default_value'][$key]) ? $data['#default_value'][$key] : (empty($data['#input_country']) ? null : $data['#input_country']),
                    );
                } else {
                    $data['#children'][0][$key] = array(
                        '#type' => 'textfield',
                    );
                    if ($key === 'street'
                        && isset($data['#street_format'])
                    ) {
                        $data['#children'][0][$key]['#attributes']['data-format'] = $data['#street_format'];
                    }
                }
                $data['#children'][0][$key] += [
                    '#title' => $label,
                    '#default_value' => @$data['#default_value'][$key],
                    '#attributes' => array('class' => 'drts-location-find-address-component drts-location-address-' . $key),
                    '#horizontal' => true,
                    '#required' => empty($data['#required']) ? false : (isset($data['#input_fields_required']) ? in_array($key, $data['#input_fields_required']) : $key !== 'street2'),
                ];
            }
            $data['#children'][1] += array(
                'address' => array(
                    '#type' => 'hidden',
                    '#class' => 'drts-location-address-address',
                    '#render_hidden_inline' => true,
                ),
            );
            foreach (array('street', 'zip', 'city', 'province', 'country') as $key) {
                if (!isset($data['#input_fields'][$key])) {
                    $data['#children'][1][$key] = array(
                        '#type' => 'hidden',
                        '#class' => 'drts-location-address-' . $key,
                        '#render_hidden_inline' => true,
                    );
                    if ($key === 'street') {
                        if (isset($data['#street_format'])) {
                            $data['#children'][1][$key]['#attributes']['data-format'] = $data['#street_format'];
                        }
                    } elseif ($key === 'country') {
                        if (!empty($data['#input_country'])) {
                            $data['#children'][1][$key]['#value'] = $data['#input_country'];
                            $data['#children'][1][$key]['#class'] .= ' drts-location-find-address-component';
                        }
                    }
                }
            }
            $get_addr_btn = false;
            unset($data['#children'][1]['_address']);
            $data['#children'][1]['manual']['#title'] = __('Enter geolocation info manually', 'directories-pro');
        }
        if (!isset($data['#latlng_required'])) {
            $data['#latlng_required'] = !empty($data['#required']);
        } else {
            if (empty($data['#required'])) {
                $data['#latlng_required'] = false;
            }
        }
        unset($data['#required']);

        // Remove map related fields and states if no map API configured
        if (!$this->_application->Map_Api()) {
            unset($data['#children'][1]['buttons'], $data['#children'][1]['map'], $data['#children'][1]['manual'],
                $data['#children'][1]['_address']['#states'], $data['#children'][1]['timezone']['#states'], $data['#children'][1]['_latlng']['#states'],
                $data['#children'][1]['_latlng']
            );
            $data['#latlng_required'] = false;
            if (!empty($data['#hide_timezone_if_no_map'])) {
                $data['#children'][1]['timezone']['#type'] = 'hidden';
                $data['#children'][1]['timezone']['#render_hidden_inline'] = true;
            } else {
                $data['#children'][1]['timezone']['#horizontal'] = true;
            }
        } else {
            if (!empty($data['#hide_timezone'])) {
                $data['#children'][1]['timezone']['#type'] = 'hidden';
                $data['#children'][1]['timezone']['#render_hidden_inline'] = true;
            }
        }

        // Remove map buttons and always show address detail fields
        if (!$this->_application->Map_Api()
            || !$this->_application->Location_Api('Geocoding')
        ) {
            unset($data['#children'][1]['buttons'], $data['#children'][1]['manual'],
                $data['#children'][1]['_address']['#states'], $data['#children'][1]['timezone']['#states'], $data['#children'][1]['_latlng']['#states']
            );
        } else {
            $data['#children'][1]['buttons']['#markup'] = $this->_getButtons($data, $find_addr_btn, $get_addr_btn, $clear_addr_btn);
        }

        $form->settings['#pre_render'][__CLASS__] = [[$this, 'preRenderCallback'], [empty($data['#input_fields'])]];

        parent::formFieldInit($name, $data, $form);
    }

    protected function _getButtons(array $data, $findBtn, $getBtn, $clearBtn)
    {
        $ret = ['<div class="drts-location-address-field-buttons ' . DRTS_BS_PREFIX . 'mb-2">'];
        if ($findBtn) {
            $overwrite = empty($data['#find_btn_overwrite']) ? '' : 1;
            $ret[] = '<button type="button" class="' . DRTS_BS_PREFIX . 'btn ' . DRTS_BS_PREFIX . 'btn-outline-secondary ' . DRTS_BS_PREFIX . 'btn-sm drts-location-find-address" data-overwrite-fields="' . $overwrite . '"><i class="fas fa-search fa-fw"></i> ' . __('Find on map', 'directories-pro') . '</button>';
        }
        if ($getBtn) {
            $ret[] = '<button type="button" class="' . DRTS_BS_PREFIX . 'btn ' . DRTS_BS_PREFIX . 'btn-outline-secondary ' . DRTS_BS_PREFIX . 'btn-sm drts-location-get-address"><i class="fas fa-arrow-up fa-fw"></i> ' . __('Get from map', 'directories-pro') . '</button>';
        }
        $ret[] = '<button type="button" style="display:none;" class="' . DRTS_BS_PREFIX . 'btn ' . DRTS_BS_PREFIX . 'btn-outline-secondary ' . DRTS_BS_PREFIX . 'btn-sm drts-location-geolocate"><i class="fas fa-location-arrow fa-fw"></i> ' . __('Current location', 'directories-pro') . '</button>';
        if ($clearBtn) {
            $ret[] = '<button type="button" class="' . DRTS_BS_PREFIX . 'btn ' . DRTS_BS_PREFIX . 'btn-outline-danger ' . DRTS_BS_PREFIX . 'btn-sm drts-location-clear-address"><i class="fas fa-times fa-fw"></i> ' . __('Clear map', 'directories-pro') . '</button>';
        }
        $ret[] = '</div>';
        return implode(PHP_EOL, $ret);
    }

    public function formFieldSubmit(&$value, array &$data, Form\Form $form)
    {
        parent::formFieldSubmit($value, $data, $form);

        if (empty($value['_latlng']['lat'])
            || empty($value['_latlng']['lng'])
        ) {
            if (!empty($data['#latlng_required'])) {
                $form->setError(__('Please click on the map or fill out the following fields.', 'directories-pro'), $data['#name'] . '[_latlng]');
                $form->setError(__('Please select a valid location on map.', 'directories-pro'), $data['#name'] . '[map]');
            }
        }

        if ($form->hasError()) return;

        if (!empty($value['_latlng'])) {
            foreach ($value['_latlng'] as $key => $_value) {
                $value[$key] = $_value;
            }
        } else {
            unset($value['_latlng']); // prevent from saving array as string
        }

        if (!empty($value['_address'])) {
            foreach ($value['_address'] as $key => $_value) {
                $value[$key] = $_value;
            }
        } else {
            unset($value['_address']); // prevent from saving array as string
        }
    }

    public function preRenderCallback($form, $autocomplete = false)
    {
        // Do not load location JS if no map provider
        if (!$this->_application->Map_Api()) return;

        $this->_application->Location_Api_load(['location_field' => true, 'location_autocomplete' => $autocomplete]);

        $form->settings['#js_ready'][] = '(function() {
    $("#' . $form->settings['#id'] . ' .drts-form-type-location-address").each(function(index, field) {
        var $field = $(field);
        if ($field.is(":visible")) {
            new DRTS.Location.field($field);
        } else {
            var tab = $field.closest(".' . DRTS_BS_PREFIX . 'tab-pane, .' . DRTS_BS_PREFIX . 'collapse");
            if (tab.length) {
                $("#" + (tab.hasClass("' . DRTS_BS_PREFIX . 'tab-pane") ? tab.attr("id") + "-trigger" : tab.attr("id"))).on("shown.bs.tab shown.bs.collapse", function(e, data){
                    new DRTS.Location.field($field);
                });
            }
        }
    });
    $(DRTS).on("clonefield.sabai", function(e, data) {
        if (data.clone.hasClass("drts-location-address-container")) {
            let field = data.clone.find(".drts-form-type-location-address");
            if (field.length) {
                new DRTS.Location.field(field.data("lat", null).data("lng", null));
            }
        }
    });
})();';
    }
}
