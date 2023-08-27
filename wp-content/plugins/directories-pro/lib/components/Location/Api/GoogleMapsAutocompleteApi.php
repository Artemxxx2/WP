<?php
namespace SabaiApps\Directories\Component\Location\Api;

class GoogleMapsAutocompleteApi extends AbstractGoogleMapsApi implements IAutocompleteApi
{
    protected function _doGetInfo()
    {
        return [
            'label' => __('Google Maps Place Autocomplete', 'directories-pro'),
            'default_settings' => [
                'autocomplete' => [
                    'type' => '(regions)',
                    'country' => null,
                ],
            ],
        ];
    }

    public function locationApiLoad(array $settings)
    {
        $handle = $this->_load('autocomplete', true);
        if (isset($settings['autocomplete'])) {
            if (empty($settings['autocomplete']['country'])) {
                unset($settings['autocomplete']['country']);
            }
            if (isset($settings['autocomplete']['type'])
                && empty($settings['autocomplete']['type'])
            ) {
                unset($settings['autocomplete']['type']);
            }
        } else {
            $settings['autocomplete'] = [];
        }
        $this->_application->getPlatform()->addJsInline(
            $handle,
            sprintf(
                'var DRTS_Location_googlemapsAutocomplete = %s;',
                $this->_application->JsonEncode($settings['autocomplete'])
            )
        );
    }

    public function locationApiSettingsForm(array $settings, array $parents)
    {
        return [
            'autocomplete' => [
                '#title' => __('Google Maps Place Autocomplete', 'directories-pro'),
                '#class' => 'drts-form-label-lg',
                '#states' => [
                    'visible' => [
                        '[name="Map[lib][location_autocomplete]"]' => ['type' => 'value' , 'value' => 'location_googlemaps'],
                    ],
                ],
                '#weight' => 10,
                'type' => [
                    '#type' => 'select',
                    '#default_value' => $settings['autocomplete']['type'],
                    '#title' => __('Autocomplete place type', 'directories-pro'),
                    '#options' => [
                        '' => __('All', 'directories-pro'),
                        'geocode' => __('Default', 'directories-pro'),
                        'address' => __('Show results with a precise address', 'directories-pro'),
                        'establishment' => __('Show business results', 'directories-pro'),
                        '(regions)' => __('Show regions', 'directories-pro'),
                        '(cities)' => __('Show cities', 'directories-pro'),
                    ],
                    '#horizontal' => true,
                ],
                'country' => [
                    '#title' => isset($title) ? $title : __('Country code', 'directories-pro'),
                    '#description' => __('Enter two-letter ISO 3166-1 Alpha-2 compatible country codes separated by commas to restrict address suggestions to specific countries.', 'directories-pro'),
                    '#type' => 'textfield',
                    '#default_value' => $settings['autocomplete']['country'],
                    '#min_length' => 2,
                    '#max_length' => 2,
                    '#horizontal' => true,
                    '#placeholder' => 'US,JP',
                    '#separator' => ',',
                    '#alpha' => true,
                ],
            ],
        ];
    }
}