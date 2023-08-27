<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;

class FilterHelper
{
    public function help(Application $application, $name, $value = null, array $args = [])
    {
        $event_type = str_replace('_', '', $name) . 'filter';
        if ($application->hasEventListner($event_type)) {        
            if (is_object($value)) {
                array_unshift($args, $value);
            } else {
                // Pass in the value as reference so it can be altered
                $value_in_array = [&$value];
                $args = array_merge($value_in_array, $args);
            }
            // Dispatch filter event
            $application->dispatchEvent($event_type, $args);
        }

        return $value; // return the altered value
    }
}