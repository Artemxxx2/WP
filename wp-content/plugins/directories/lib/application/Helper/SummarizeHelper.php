<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;

class SummarizeHelper
{
    public function help(Application $application, $text, $length = 0, $trimmarker = '...')
    {
        if (!strlen($text)) return '';
        
        $text = strip_tags(strtr($text, ["\r" => '', "\n" => ' ']));
        
        return empty($length) ? $text : $application->System_MB_strimwidth($text, 0, $length, $trimmarker);
    }
}