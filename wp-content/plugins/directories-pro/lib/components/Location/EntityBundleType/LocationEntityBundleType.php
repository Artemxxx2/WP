<?php
namespace SabaiApps\Directories\Component\Location\EntityBundleType;

use SabaiApps\Directories\Component\Entity\BundleType\AbstractBundleType;

class LocationEntityBundleType extends AbstractBundleType
{    
    protected function _entityBundleTypeInfo()
    {
        return array(
            'type' => $this->_name,
            'entity_type' => 'term',
            'suffix' => 'loc_loc',
            'component' => 'Location',
            'slug' => 'locations',
            'is_taxonomy' => true,
            'label' => __('Locations', 'directories-pro'),
            'label_singular' => __('Location', 'directories-pro'),
            'label_add' => __('Add Location', 'directories-pro'),
            'label_all' => __('All Locations', 'directories-pro'),
            'label_select' => __('Select Location', 'directories-pro'),
            'label_count' => __('%s location', 'directories-pro'),
            'label_count2' => __('%s locations', 'directories-pro'),
            'label_page' => __('Location: %s', 'directories-pro'),
            'icon' => 'fas fa-map-marker-alt',
            'public' => true,
            'is_hierarchical' => true,
            'entity_image' => 'location_photo',
            'properties' => array(
                'parent' => array(
                    'label' => __('Parent Location', 'directories-pro'),
                ),
            ),
            'fields' => __DIR__ . '/location_fields.php',
            'displays' => __DIR__ . '/location_displays.php',
            'views' => __DIR__ . '/location_views.php',
            'taxonomy_assignable' => false,
            'faker_disable_entity_terms' => true,
            'permalink' => array('slug' => 'location'),
            'payment_feature_disable' => true,
            'map_location_taxonomy' => true,
        );
    }
    
    public function entityBundleTypeSettingsForm(array $settings, array $parents = [])
    {
        $default_hierarchy = $options = $this->_application->Location_Hierarchy();
        if (!empty($settings['location_hierarchy_custom'])
            && isset($settings['location_hierarchy'])
        ) {
            $options = $settings['location_hierarchy'];
        }
        $location_hierarchy_custom_selector = sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['location_hierarchy_custom'])));
        return [
            '#title' => __('Location Hierarchy Settings', 'directories-pro'),
            '#id' => 'drts-location-hierarchy-settings',
            'location_hierarchy_current' => array(
                '#type' => 'item',
                '#title' => __('Default location hierarchy', 'directories-pro'),
                '#markup' => '<em>' . implode('</em> &raquo; <em>', $default_hierarchy) . '</em>',
                '#horizontal' => true,
                '#horizontal_label_padding' => false,
                '#states' => [
                    'visible' => [
                        $location_hierarchy_custom_selector => ['type' => 'checked', 'value' => false],
                    ],
                ],
            ),
            'location_hierarchy_custom' => array(
                '#type' => 'checkbox',
                '#title' => __('Enable custom location hierarchy', 'directories-pro'),
                '#default_value' => !empty($settings['location_hierarchy_custom']),
                '#horizontal' => true,
                '#horizontal_label_padding' => false,
            ),
            'location_hierarchy' => [
                '#type' => 'options',
                '#horizontal' => true,
                '#disable_icon' => true,
                '#disable_add_csv' => true,
                '#multiple' => true,
                '#options_value_disabled' => array_keys($default_hierarchy),
                '#default_value' => array(
                    'options' => $options + $default_hierarchy,
                    'default' => array_keys($options),
                ),
                '#value_title' => __('slug', 'directories-pro'),
                '#slugify_value' => true,
                '#default_options_only' => true,
                '#states' => [
                    'visible' => [
                        $location_hierarchy_custom_selector => ['type' => 'checked', 'value' => true],
                    ],
                ],
            ],
        ];
    }
}