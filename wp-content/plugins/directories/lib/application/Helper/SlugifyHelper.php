<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;

class SlugifyHelper
{
    public function help(Application $application, $text, $maxLength = 255, $separator = '-')
    {
        if (!$strlen = $application->System_MB_strlen($text)) return $text;
        
        // transliterate
        if (false === $slug = $this->_transliterate($text)) {
            // transliterate failed, return original but make sure the length does not exceed max limit
            return empty($maxLength) ? $text : (function_exists('mb_strcut') ? mb_strcut($text, 0 ,$maxLength) : substr($text, 0 ,$maxLength));
        }
        // replace non alnum chars with separator
        $slug = preg_replace('/\W+/', $separator, $slug);
        // return original if more than 20% of original text has been stripped
        if (strlen($slug) / $strlen < 0.8) {
            // make sure the length does not exceed max limit
            return empty($maxLength) ? $text : (function_exists('mb_strcut') ? mb_strcut($text, 0 ,$maxLength) : substr($text, 0 ,$maxLength));
        }
        // make sure the length does not exceed max limit
        if (!empty($maxLength)) {
            $slug = substr($slug, 0, $maxLength);
        }
        // trim
        $slug = trim($slug, $separator);
        
        return strtolower($slug);
    }
    
    protected function _transliterate($text)
    {
        if (false === $ret = iconv('utf-8', 'us-ascii//TRANSLIT', $text)) {
            return false;
        }
        
        // remove accents resulting from OSX iconv
        return str_replace(['\'', '`', '^'], '', $ret);
    }
}