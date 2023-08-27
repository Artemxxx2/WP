<?php
namespace SabaiApps\Directories\Platform\WordPress;

use SabaiApps\Directories\Application;

class FilterHelper extends \SabaiApps\Directories\Helper\FilterHelper
{
    public function help(Application $application, $name, $value = null, array $args = [])
    {
        $value = parent::help($application, $name, $value, $args);
        array_unshift($args, 'drts_' . $name, $value);
        return call_user_func_array('apply_filters', $args);
    }
}