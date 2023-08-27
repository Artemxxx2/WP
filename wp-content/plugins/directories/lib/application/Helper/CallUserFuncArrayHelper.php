<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;

class CallUserFuncArrayHelper
{
    public function help(Application $application, $callback, array $params = [])
    {
        if (is_array($callback) && isset($callback[1]) && is_array($callback[1])) {
            $params = empty($params) ? $callback[1] : array_merge($params, $callback[1]);
            $callback = $callback[0];
        }

        return call_user_func_array($callback, $params);
    }
}