<?php
namespace SabaiApps\Directories\Platform\WordPress;

use SabaiApps\Directories\Application;

class ActionHelper extends \SabaiApps\Directories\Helper\ActionHelper
{
    public function help(Application $application, $name, array $args = [], $return = false)
    {
        parent::help($application, $name, $args, $return);
        do_action_ref_array('drts_' . $name, $args);
    }
}