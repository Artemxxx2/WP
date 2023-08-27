<?php
namespace SabaiApps\Directories\Component\Location\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Context;

class DrawMapOptionsHelper
{
    public function help(Application $application, array $settings = [], Context $context = null)
    {        
        // Searching/Filtering by location?
        $location_value = null;
        if (!empty($context->filter['filters_applied']['location_address'])) {
            $filters_applied = $context->filter['filters_applied']['location_address'];
            if (($filter_name = array_shift($filters_applied))
                && isset($context->filter['filter_values'][$filter_name])
            ) { // location filter
                $location_value = $context->filter['filter_values'][$filter_name];
            }
        } elseif (isset($context->search_values['search_location_address'])) { // location search
            $location_value = $context->search_values['search_location_address'];
        }
        if (isset($location_value)) {
            if (!empty($location_value['radius'])
                && !empty($location_value['center'])
                && ($center = explode(',', $location_value['center']))
                && count($center) === 2
            ) {
                // Radius search
                $settings['center'] = array($center[0], $center[1]);
                // Show radius circle?
                if (!isset($settings['circle']) || $settings['circle'] !== false) {
                    $settings['circle'] = array(
                        'stroke_color' => '#9999ff',
                        'fill_color' => '#9999ff',
                        'radius' => $location_value['radius'] * ($application->getComponent('Map')->getConfig('map', 'distance_unit') === 'mi' ? 1609.344 : 1000),
                    );
                }
            } else {
                if (!empty($location_value['viewport'])) {
                    // Current map view search or location search with auto radius
                    $settings['center'] = array(
                        ($location_value['viewport'][0] + $location_value['viewport'][2]) / 2,
                        ($location_value['viewport'][1] + $location_value['viewport'][3]) / 2,
                    );
                    if (!empty($location_value['zoom'])) {
                        $settings['zoom'] = intval($location_value['zoom']);
                    }
                    $settings['fit_bounds'] = isset($location_value['radius']); // location search with auto radius if radius is set
                }
            }
        }
        
        return $settings;
    }
}