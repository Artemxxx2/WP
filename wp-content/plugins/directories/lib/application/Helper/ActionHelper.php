<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;

class ActionHelper
{
    public function help(Application $application, $name, array $args = [], $return = false)
    {
        if ($return) {
            ob_start();
            $this->_doAction($application, $name, $args);
            return ob_get_clean();
        }
        $this->_doAction($application, $name, $args);
    }

    protected function _doAction(Application $application, $name, array $args)
    {
        $event_type = str_replace('_', '', $name);
        if ($application->hasEventListner($event_type)) {
            $application->dispatchEvent($event_type, $args);
        }
    }
}