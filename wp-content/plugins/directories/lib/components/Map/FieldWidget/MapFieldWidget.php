<?php
namespace SabaiApps\Directories\Component\Map\FieldWidget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Map\MapComponent;

class MapFieldWidget extends Field\Widget\AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('Map', 'directories'),
            'field_types' => array($this->_name),
            'default_settings' => array(
                'map_type' => 'roadmap',
                'map_height' => 300,
                'center_latitude' => null,
                'center_longitude' => null,
                'zoom' => 10,
            ),
            'repeatable' => true,
        );
    }

    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        $ret = [];
        return $ret + array(
            'map_height' => array(
                '#type' => 'textfield',
                '#size' => 4,
                '#maxlength' => 3,
                '#field_suffix' => 'px',
                '#title' => __('Map height', 'directories'),
                '#description' => __('Enter the height of map in pixels.', 'directories'),
                '#default_value' => $settings['map_height'],
                '#numeric' => true,
            ),
            'center_latitude' => array(
                '#type' => 'textfield',
                '#maxlength' => 20,
                '#title' => __('Default latitude', 'directories'),
                '#description' => __('Enter the latitude of the default map location in decimals.', 'directories'),
                '#default_value' => $settings['center_latitude'],
                '#regex' => MapComponent::LAT_REGEX,
                '#numeric' => true,
            ),
            'center_longitude' => array(
                '#type' => 'textfield',
                '#maxlength' => 20,
                '#title' => __('Default longitude', 'directories'),
                '#description' => __('Enter the longitude of the default map location in decimals.', 'directories'),
                '#default_value' => $settings['center_longitude'],
                '#regex' => MapComponent::LNG_REGEX,
                '#numeric' => true,
            ),
            'zoom' => array(
                '#type' => 'slider',
                '#min_value' => 0,
                '#max_value' => 19,
                '#title' => __('Default zoom level', 'directories'),
                '#default_value' => $settings['zoom'],
                '#integer' => true,
            ),
        );
    }

    public function fieldWidgetForm(Field\IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        if (!$this->_application->Map_Api()) {
            return [
                '#type' => 'item',
                '#markup' => '<div class="' . DRTS_BS_PREFIX . 'alert ' . DRTS_BS_PREFIX . 'alert-danger ">' . __('Invalid map provider.', 'directories') . '</div>',
            ];
        }

        $map_config = $this->_application->getComponent('Map')->getConfig('map');
        return array(
            '#type' => 'map_map',
            '#map_type' => $map_config['type'],
            '#map_height' => $settings['map_height'],
            '#center_latitude' => empty($settings['center_latitude']) ? $map_config['default_location']['lat'] : $settings['center_latitude'],
            '#center_longitude' => empty($settings['center_longitude']) ? $map_config['default_location']['lng'] : $settings['center_longitude'],
            '#zoom' => $settings['zoom'],
            '#default_value' => $value,
        );
    }
}
