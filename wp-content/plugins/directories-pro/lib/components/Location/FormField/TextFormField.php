<?php
namespace SabaiApps\Directories\Component\Location\FormField;

use SabaiApps\Directories\Component\Form;

class TextFormField extends Form\Field\FieldsetField
{
    public function formFieldInit($name, array &$data, Form\Form $form)
    {        
        $data = array(
            '#tree' => true,
            '#group' => true,
            '#children' => array(
                0 => [],
            ),
        ) + $data;
        if (empty($data['#disable_input'])) {
            // Disable suggest place option if no autocomplete API library is configured.
            $data['#suggest_place'] = $this->_application->Location_Api('Autocomplete') ? true : false;

            foreach (array(
                'suggest_place',
                'suggest_place_header',
                'suggest_place_icon',
                'suggest_place_country',
                'suggest_location',
                'suggest_location_header',
                'suggest_location_icon',
                'suggest_location_url',
                'suggest_location_count',
                'suggest_location_parents',
                'geolocation',
                'suggest_place_geocode',
            ) as $key) {
                $_key = '#' . $key;
                if (isset($data[$_key])) {
                    if (is_bool($data[$_key])) {
                        $data[$_key] = (int)$data[$_key];
                    } elseif (is_string($data[$_key])) {
                        if (!strlen($data[$_key])) continue;
                    } elseif (is_array($data[$_key])) {
                        if (empty($data[$_key])) continue;
                    
                        $data[$_key] = $this->_application->JsonEncode($data[$_key]);
                    }
                    $data['#attributes']['data-' . str_replace('_', '-', $key)] = $data[$_key];
                }
            }
            if ($this->_application->Location_Api_name('Autocomplete') === 'location_googlemaps') {
                $data['#attributes']['data-suggest-place-footer'] = '<img src="' . $this->_application->ImageUrl('powered_by_google.png', 'directories-pro') . '" />';
            }
            if (!empty($data['#geolocation'])) {
                $data['#attributes']['data-geolocation-text'] = __('Current location', 'directories-pro');
            }

            $text_field_attr = isset($data['#text_attributes']) ? $data['#text_attributes'] : [];
            if (isset($data['#text_id'])) $text_field_attr['id'] = $data['#text_id'];
            $data['#children'][0] += array(
                'text' => array(
                    '#type' => 'textfield',
                    '#attributes' => ['class' => 'drts-location-text-input', 'autocomplete' => 'off'] + $text_field_attr,
                    '#default_value' => isset($data['#default_value']['text']) ? $data['#default_value']['text'] : null,
                    '#required' => !empty($data['#required']),
                    '#error_no_output' => !empty($data['#error_no_output']),
                    '#placeholder' => isset($data['#placeholder']) ? $data['#placeholder'] : null,
                    '#add_clear' => true,
                    '#field_prefix' => isset($data['#text_field_prefix']) ? $data['#text_field_prefix'] : null,
                    '#field_prefix_no_addon' => true,
                ),
            );
            if (empty($data['#disable_radius'])) {
                $data['#children'][0]['radius'] = [
                    '#type' => 'slider',
                    '#min_value' => $min_radius = isset($data['#min_radius']) ? (int)$data['#min_radius'] : 0,
                    '#max_value' => $max_radius = isset($data['#max_radius']) ? (int)$data['#max_radius'] : $this->_application->Filter('location_address_search_max_radius', 100),
                    '#min_text' => $min_radius === 0 ? __('Auto', 'directories-pro') : null,
                    '#default_value' => isset($data['#default_value']['radius'])
                        ? $data['#default_value']['radius']
                        : (isset($data['#radius']) ? (int)$data['#radius'] : null),
                    '#field_suffix' => $this->_application->getComponent('Map')->getConfig('map', 'distance_unit') === 'mi'
                        ? __('mi', 'directories-pro')
                        : __('km', 'directories-pro'),
                    '#field_prefix' => $this->_application->H(__('Radius: ', 'directories-pro')),
                    '#step' => isset($data['#radius_step']) ? $data['#radius_step'] : ($max_radius <= 20 ? 1 : 5),
                    '#attributes' => [
                        'class' => 'drts-location-text-radius drts-location-text-radius-slider',
                        'data-default-radius' => isset($data['#radius']) ? (int)$data['#radius'] : ''
                    ],
                    '#states' => [
                        'visible' => [
                            'input[name="' . $name . '[text]"]' => ['type' => 'filled', 'value' => true],
                            'input[name="' . $name . '[term_id]"]' => ['value' => ''],
                            'input[name="' . $name . '[taxonomy]"]' => ['value' => ''],
                        ],
                    ],
                    '#hidden' => true,
                ];

            }
            $data['#children'][0] += array(    
                'term_id' => array(
                    '#type' => 'hidden',
                    '#class' => 'drts-location-text-term-id',
                    '#default_value' => isset($data['#default_value']['term_id']) ? $data['#default_value']['term_id'] : null,
                    '#render_hidden_inline' => true,
                ),
                'taxonomy' => array(
                    '#type' => 'hidden',
                    '#class' => 'drts-location-text-taxonomy',
                    '#default_value' => isset($data['#default_value']['taxonomy']) ? $data['#default_value']['taxonomy'] : null,
                    '#render_hidden_inline' => true,
                ),
            );
        } else {
            $data['#hidden'] = true;
            $data['#attributes'] = array('data-suggest-location' => 0, 'data-suggest-place' => 0, 'data-geolocation' => 0);
            $data['#children'][0] += array(
                'text' => array(
                    '#type' => 'hidden',
                    '#class' => 'drts-location-text-input',
                    '#default_value' => isset($data['#default_value']['text']) ? $data['#default_value']['text'] : null,
                    '#render_hidden_inline' => false,
                ),
            );
        }
        $data['#children'][0] += array(
            'center' => array(
                '#type' => 'hidden',
                '#class' => 'drts-location-text-center',
                '#default_value' => isset($data['#default_value']['center']) ? $data['#default_value']['center'] : null,
                '#render_hidden_inline' => empty($data['#disable_input']),
            ),
            'viewport' => array(
                '#type' => 'hidden',
                '#class' => 'drts-location-text-viewport',
                '#default_value' => isset($data['#default_value']['viewport']) ?
                    (is_array($data['#default_value']['viewport']) ? implode(',', $data['#default_value']['viewport']) : $data['#default_value']['viewport']) :
                    null,
                '#render_hidden_inline' => empty($data['#disable_input']),
            ),
            'zoom' => array(
                '#type' => 'hidden',
                '#class' => 'drts-location-text-zoom',
                '#default_value' => isset($data['#default_value']['zoom']) ? $data['#default_value']['zoom'] : null,
                '#render_hidden_inline' => empty($data['#disable_input']),
            ),
            'radius' => array(
                '#type' => 'hidden',
                '#class' => 'drts-location-text-radius',
                '#default_value' => isset($data['#default_value']['radius']) ? $data['#default_value']['radius'] : (isset($data['#radius']) ? (int)$data['#radius'] : null),
                '#render_hidden_inline' => empty($data['#disable_input']),
                '#data' => ['default-radius' => isset($data['#radius']) ? (int)$data['#radius'] : ''],
            ),
        );
        
        if (empty($data['#disable_input'])) {
            $data['#id'] = $form->getFieldId($name);
            if (isset($data['#js_ready'])) {
                settype($data['#js_ready'], 'array');
            } else {
                $data['#js_ready'] = [];
            }
            $data['#js_ready'][] = 'DRTS.Location.textfield("#' . $data['#id'] . '");';
            $form->settings['#pre_render'][__CLASS__] = array(array($this, 'preRenderCallback'), array(empty($data['#disable_input'])));
        }
        
        parent::formFieldInit($name, $data, $form);
    }
    
    public function formFieldRender(array &$data, Form\Form $form)
    {
        parent::formFieldRender($data, $form);
        if (!empty($data['#disable_input'])
            && !empty($data['#html'])
        ) {
            $form->settings['#rendered_hiddens'][] = implode(PHP_EOL, $data['#html']);
            $data['#html'] = null;
        }
    }

    public function preRenderCallback(Form\Form $form, $textfieldEnabled = true)
    {
        $this->_application->Location_Api_load(array('location_textfield' => true));
        if ($textfieldEnabled) {
            $this->_application->Location_Api_load(array('location_autocomplete' => true));
            $this->_application->Form_Scripts_typeahead();
        }
    }
}
