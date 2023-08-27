<?php
namespace SabaiApps\Directories\Component\Map;

use SabaiApps\Directories\Component\AbstractComponent;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\System;
use SabaiApps\Directories\Component\View;

class MapComponent extends AbstractComponent implements
    IApis,
    Form\IFields,
    Field\ITypes,
    Field\IWidgets,
    Field\IRenderers,
    View\IModes
{
    const VERSION = '1.3.108', PACKAGE = 'directories',
        LAT_REGEX = '/^-?([1-8]?[1-9]|[1-9]?0)\.{1}\d{4,}/',
        LNG_REGEX = '/^-?((([1]?[0-7][0-9]|[1-9]?[0-9])\.{1}\d{5,}$)|[1]?[1-8][0]\.{1}0{4,}$)/';
    
    public static function description()
    {
        return 'Adds geographic metadata and features to content.';
    }
    
    public static function interfaces()
    {
        return array(
            'Faker\IGenerators',
        );
    }

    public function mapGetApiNames()
    {
        return ['googlemaps'];
    }

    public function mapGetApi($name)
    {
        return new Api\GoogleMapsApi($this->_application, $name);
    }

    public function formGetFieldTypes()
    {
        return ['map_map', 'map_latlng'];
    }

    public function formGetField($name)
    {
        switch ($name) {
            case 'map_map':
                return new FormField\MapFormField($this->_application, $name);
            case 'map_latlng':
                return new FormField\LatLngFormField($this->_application, $name);
        }
    }

    public function fieldGetTypeNames()
    {
        return ['map_map'];
    }

    public function fieldGetType($name)
    {
        switch ($name) {
            case 'map_map':
                return new FieldType\MapFieldType($this->_application, $name);
        }
    }
    
    public function fieldGetRendererNames()
    {
        return ['map_map', 'map_street_view', 'map_static_map'];
    }

    public function fieldGetRenderer($name)
    {
        switch ($name) {
            case 'map_map':
                return new FieldRenderer\MapFieldRenderer($this->_application, $name);
            case 'map_street_view':
                return new FieldRenderer\StreetViewFieldRenderer($this->_application, $name);
            case 'map_static_map':
                return new FieldRenderer\StaticMapFieldRenderer($this->_application, $name);
        }
    }

    public function fieldGetWidgetNames()
    {
        return ['map_map'];
    }

    public function fieldGetWidget($name)
    {
        switch ($name) {
            case 'map_map':
                return new FieldWidget\MapFieldWidget($this->_application, $name);
        }
    }
    
    public function getDefaultConfig()
    {
        return [
            'map' => [
                'distance_unit' => 'km',
                'default_zoom' => 9,
                'default_location' => ['lat' => 40.69847, 'lng' => -73.95144],
                'scrollwheel' => false,
                'marker_custom' => true,
                'marker_color' => null,
                'marker_size' => 38,
                'marker_icon' => 'fas fa-dot-circle',
                'marker_icon_color' => null,
                'marker_clusters' => false,
                'fit_bounds' => true,
                'fit_bounds_padding' => 0,
            ],
            'lib' => [
                'map' => '',
            ],
        ];
    }
    
    public function onFormScripts($options)
    {
        if (empty($options) || in_array('map_field', $options)) {
            $this->_application->Map_Api_load(array('map_field' => true));
        }
    }
    
    public function fakerGetGeneratorNames()
    {
        return ['map_map'];
    }
    
    public function fakerGetGenerator($name)
    {
        return new FakerGenerator\MapFakerGenerator($this->_application, $name);
    }
    
    public function onDirectoryAdminSettingsFormFilter(&$form)
    {
        $map = $this->_config['map'];
        $marker_custom_selector = 'input[name="' . $this->_name . '[map][marker_custom]"]';
        $form['tabs'][$this->_name] = [
            '#title' => __('Map', 'directories'),
            '#weight' => 5,
        ];
        $form['fields'][$this->_name] = [
            '#component' => $this->_name,
            '#tab' => $this->_name,
            'lib' => [
                '#title' => __('Map API Settings', 'directories'),
                '#description' => sprintf(
                    $this->_application->H(__('If you are going to use any of the map features, you must configure map libraries. See %s for more details.', 'directories')),
                    '<a target="_blank" href="https://directoriespro.com/documentation/getting-started/installation.html#configure-map-libraries">Configure Map Libraries</a>'
                ),
                '#description_no_escape' => true,
                'map' => [
                    '#type' => 'select',
                    '#options' => ['' => __('— None —', 'directories')] + $this->_application->Map_Api_options(),
                    '#horizontal' => true,
                    '#title' => __('Map provider', 'directories'),
                    '#default_value' => isset($this->_config['lib']['map']) ? $this->_config['lib']['map'] : 'googlemaps',
                    '#weight' => 1,
                ],
                'api' => [
                    '#weight' => 99,
                ],
            ],
            'map' => [
                '#title' => __('Display Settings', 'directories'),
                '#states' => [
                    'invisible' => [
                        '[name="' . $this->_name . '[lib][map]"]' => ['value' => ''],
                    ]
                ],
                'distance_unit' => array(
                    '#type' => 'select',
                    '#title' => __('Distance unit', 'directories'),
                    '#options' => array('km' => __('Kilometers', 'directories'), 'mi' => __('Miles', 'directories')),
                    '#default_value' => isset($map['distance_unit']) ? $map['distance_unit'] : null,
                    '#horizontal' => true,
                    '#weight' => 1,
                ),
                'default_zoom' => array(
                    '#type' => 'slider',
                    '#min_value' => 1,
                    '#max_value' => 19,
                    '#integer' => true,
                    '#title' => __('Default zoom level', 'directories'),
                    '#default_value' => isset($map['default_zoom']) ? $map['default_zoom'] : null,
                    '#horizontal' => true,
                    '#weight' => 5,
                ),
                'default_location' => array(
                    '#title' => __('Default location', 'directories'),
                    //'#type' => 'map_map',
                    //'#map_height' => 200,
                    //'#default_value' => isset($map['default_location']) ? $map['default_location'] : null,
                    '#horizontal' => true,
                    '#weight' => 10,
                    'lat' => [
                        '#type' => 'textfield',
                        '#maxlength' => 20,
                        '#title' => $this->_application->H(__('Latitude', 'directories')),
                        '#default_value' => isset($map['default_location']['lat']) ? $map['default_location']['lat'] : 40.69847,
                        '#regex' => self::LAT_REGEX,
                        '#numeric' => true,
                        '#horizontal' => true,
                    ],
                    'lng' => [
                        '#type' => 'textfield',
                        '#maxlength' => 20,
                        '#title' => $this->_application->H(__('Longitude', 'directories')),
                        '#default_value' => isset($map['default_location']['lng']) ? $map['default_location']['lng'] : -73.95144,
                        '#regex' => self::LNG_REGEX,
                        '#numeric' => true,
                        '#horizontal' => true,
                    ],
                ),
                'scrollwheel' => array(
                    '#type' => 'checkbox',
                    '#default_value' => !empty($map['scrollwheel']),
                    '#title' => __('Enable scrollwheel zooming', 'directories'),
                    '#horizontal' => true,
                    '#weight' => 15,
                ),
                'marker_custom' => array(
                    '#type' => 'checkbox',
                    '#default_value' => !empty($map['marker_custom']),
                    '#title' => __('Enable custom markers', 'directories'),
                    '#horizontal' => true,
                    '#weight' => 20,
                ),
                'marker_color' => array(
                    '#type' => 'colorpicker',
                    '#title' => __('Custom marker color', 'directories'),
                    '#default_value' => isset($map['marker_color']) ? $map['marker_color'] : null,
                    '#horizontal' => true,
                    '#states' => array(
                        'visible' => array(
                            $marker_custom_selector  => array('type' => 'checked', 'value' => true),
                        ),
                    ),
                    '#weight' => 25,
                ),
                'marker_size' => array(
                    '#type' => 'slider',
                    '#title' => __('Custom marker size', 'directories'),
                    '#default_value' => isset($map['marker_size']) ? $map['marker_size'] : 38,
                    '#min_value' => 24,
                    '#max_value' => 80,
                    '#integer' => true,
                    '#field_suffix' => 'px',
                    '#horizontal' => true,
                    '#states' => array(
                        'visible' => array(
                            $marker_custom_selector => array('type' => 'checked', 'value' => true),
                        ),
                    ),
                    '#weight' => 30,
                ),
                'marker_icon' => array(
                    '#type' => 'iconpicker',
                    '#title' => __('Custom marker icon', 'directories'),
                    '#default_value' => empty($map['marker_icon']) ? null : $map['marker_icon'],
                    '#horizontal' => true,
                    '#states' => array(
                        'visible' => array(
                            $marker_custom_selector => array('type' => 'checked', 'value' => true),
                        ),
                    ),
                    '#weight' => 35,
                ),
                'marker_icon_color' => array(
                    '#type' => 'colorpicker',
                    '#title' => __('Custom marker icon color', 'directories'),
                    '#default_value' => isset($map['marker_icon_color']) ? $map['marker_icon_color'] : null,
                    '#horizontal' => true,
                    '#states' => array(
                        'visible' => array(
                            $marker_custom_selector => array('type' => 'checked', 'value' => true),
                        ),
                    ),
                    '#weight' => 40,
                ),
                'marker_clusters' => array(
                    '#type' => 'checkbox',
                    '#default_value' => !empty($map['marker_clusters']),
                    '#title' => __('Enable marker clusters', 'directories'),
                    '#horizontal' => true,
                    '#weight' => 45,
                ),
                'fit_bounds' => array(
                    '#type' => 'checkbox',
                    '#title' => __('Fit all markers inside map', 'directories'),
                    '#default_value' => !empty($map['fit_bounds']),
                    '#horizontal' => true,
                    '#weight' => 50,
                ),
                'center_default' => [
                    '#type' => 'checkbox',
                    '#title' => __('Center map to default location', 'directories'),
                    '#default_value' => !empty($map['center_default']),
                    '#horizontal' => true,
                    '#states' => [
                        'visible' => [
                            'input[name="' . $this->_name . '[map][fit_bounds]"]' => ['type' => 'checked', 'value' => false],
                        ],
                    ],
                    '#weight' => 50,
                    '#element_validate' => [
                        function (Form\Form $form, &$value) {
                            if ($form->getValue([$this->_name, 'map', 'fit_bounds'])) {
                                $value = 0;
                            }
                        }
                    ],
                ],
                'require_consent' => [
                    '#type' => 'checkbox',
                    '#title' => __('Require user consent before loading map', 'directories'),
                    '#default_value' => !empty($map['require_consent']),
                    '#horizontal' => true,
                    '#states' => [
                        'invisible' => [
                            '[name="' . $this->_name . '[lib][map]"]' => ['value' => ''],
                        ]
                    ],
                    '#weight' => 90,
                ],
            ],
        ];
        $lib_name_selector = 'select[name="' . $this->_name . '[lib][map]"]';
        foreach ($this->_application->Map_Api_components() as $name => $component) {
            if (!$api = $this->_application->Map_Api_impl($name, true)) continue;

            // Add settings for the map API library
            $api_settings = [];
            if (!isset($this->_config['lib']['api'][$name])) {
                // BC for < v1.2.0
                if ($name === 'googlemaps'
                    && isset($this->_config['api'])
                ) {
                    $api_settings = $this->_config['api'];
                }
            } else {
                $api_settings = $this->_config['lib']['api'][$name];
            }
            $api_settings += (array)$api->mapApiInfo('default_settings');
            $form['fields'][$this->_name]['lib']['api'][$name] = $api->mapApiSettingsForm($api_settings, [$this->_name, 'lib', 'api', $name]);
            $form['fields'][$this->_name]['lib']['api'][$name]['#states']['visible_or'][$lib_name_selector] = ['type' => 'value', 'value' => $name];

            // Add custom map display settings if any
            $map_settings = $map + (array)$api->mapApiInfo('default_map_settings');
            if ($map_settings_form = $api->mapApiMapSettingsForm($map_settings, [$this->_name, 'map'])) {
                foreach (array_keys($map_settings_form) as $key) {
                    if (strpos($key, '#') === 0) continue;

                    $map_settings_form[$key]['#states']['visible'][$lib_name_selector] = ['type' => 'value', 'value' => $name];
                }
                $form['fields'][$this->_name]['map'] += $map_settings_form;
            }
        }

        // Let other components filter library settings easier
        $form['fields'][$this->_name]['lib'] = $this->_application->Filter(
            'map_library_settings_form',
            $form['fields'][$this->_name]['lib'],
            [$this->_config['lib'], [$this->_name, 'lib']]
        );
    }

    public function upgrade($current, $newVersion, System\Progress $progress = null)
    {
        parent::upgrade($current, $newVersion, $progress);
        if (version_compare($current->version, '1.2.0-dev.0', '<')
            && version_compare(self::VERSION, '1.2.0-dev.20', '>=')
        ) {
            $config = $current->config;
            $config['lib']['map'] = 'googlemaps';
            $config['lib']['location_geocoding']
                = $config['lib']['location_timezone']
                = $config['lib']['location_autocomplete']
                = 'location_googlemaps';
            $config['lib']['api']['googlemaps']['key']
                = $config['lib']['api']['location_googlemaps']['api']['key']
                = $config['api']['key'];
            $current->config = $config;
        }
        return $this;
    }

    public function viewGetModeNames()
    {
        return ['map'];
    }

    public function viewGetMode($name)
    {
        return new ViewMode\MapViewMode($this->_application, $name);
    }

    public function onViewEntitiesSettingsFilter(&$settings, $bundle, $view)
    {
        if ((string)$view !== 'map') return;

        $settings['map'] += $this->_config['map'];
    }

    public function onViewEntitiesQuerySettingsFilter(&$query, $bundle, $context)
    {
        if ((string)$context->view !== 'map'
            || empty($context->settings['map']['coordinates_field'])
            || isset($query[$context->settings['map']['coordinates_field']])
        ) return;

        $query['fields'][$context->settings['map']['coordinates_field']] = 1;
    }
}