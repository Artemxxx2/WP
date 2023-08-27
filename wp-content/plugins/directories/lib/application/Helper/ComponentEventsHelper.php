<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\AbstractComponent;

class ComponentEventsHelper
{
    public function help(Application $app, AbstractComponent $component)
    {
        // Fetch method names that start with 'on'
        $events = array_map(
            function ($method) {
                return strtolower(substr($method, 2));
            },
            array_filter(get_class_methods($component), function ($method) {
                return strpos(strtolower($method), 'on') === 0;
            })
        );
        if (is_callable([$component_class = get_class($component), 'events'])
            && ($_events = call_user_func([$component_class, 'events'])) // check for extra events
        ) {      
            foreach ($_events as $event_name => $event_priority) {
                $event_name = strtolower($event_name);
                if (false !== $index = array_search($event_name, $events)) {
                    unset($events[$index]);
                }
                $events[] = [$event_name, $event_priority];
            }
        }
        
        return $events;
    }
}