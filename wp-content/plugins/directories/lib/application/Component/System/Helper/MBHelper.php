<?php
namespace SabaiApps\Directories\Component\System\Helper;

use SabaiApps\Directories\Application;

class MBHelper
{   
    public function strimwidth(Application $application, $str, $start, $width, $trimmarker, $encoding = 'UTF-8')
    {
        if (function_exists('mb_strimwidth')) {
            return mb_strimwidth($str, $start, $width, $trimmarker, $encoding);
        }
        return substr($str, $start, $width - strlen($trimmarker)) . $trimmarker;
    }

    public function strlen(Application $application, $str, $encoding = 'UTF-8')
    {
        // Fix for mb_strlen returning 2 for \r\n
        $str = str_replace("\r\n", "\n", $str);
        return function_exists('mb_strlen') ? mb_strlen($str, $encoding) : strlen($str);
    }
    
    public function strcut(Application $application, $str, $start, $length, $encoding = 'UTF-8')
    {
        return function_exists('mb_strcut') ? mb_strcut($str, $start, $length, $encoding) : substr($str, $start, $length);
    }
}