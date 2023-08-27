<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;

class JsonEncodeHelper
{
    public function help(Application $application, $var, $option = null)
    {
        return json_encode($var, $option ?: JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }
}
