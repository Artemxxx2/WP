<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;

class KeywordsHelper
{
    public function help(Application $application, $input, $minLength = null, $includeFull = false, $split = true)
    {
        if (!isset($minLength)) $minLength = 3;
        $keywords = [];
        if ($includeFull) {
            $keywords[] = trim($input, '"');
        }
        if ($split) {
            $inputs = $this->_splitString($input, 15);
            foreach ($inputs as $keyword) {
                if ($quote_count = substr_count($keyword, '"')) { // check if any quotes
                    $_keyword = explode('"', $keyword);
                    if (isset($fragment)) { // has a phrase open but not closed?
                        $keywords[] = $fragment . ' ' . array_shift($_keyword);
                        unset($fragment);
                        if (!$quote_count % 2) {
                            // the last quote is not closed
                            $fragment = array_pop($_keyword);
                        }
                    } else {
                        if ($quote_count % 2) {
                            // the last quote is not closed
                            $fragment = array_pop($_keyword);
                        }
                    }
                    if (!empty($_keyword)) $keywords = array_merge($keywords, $_keyword);
                } else {
                    if (isset($fragment)) { // has a phrase open but not closed?
                        $fragment .= ' ' . $keyword;
                    } else {
                        $keywords[] = $keyword;
                    }
                }
            }
            // Add the last unclosed fragment if any, to the list of keywords
            if (isset($fragment)) $keywords[] = $fragment;
        }

        // Extract unique keywords that are not empty
        $keywords_passed = $keywords_failed = [];
        foreach ($keywords as $keyword) {
            if (($keyword = trim($keyword))
                && !isset($keywords_passed[$keyword])
                && !isset($keywords_failed[$keyword])
            ) {
                if ($application->System_MB_strlen($keyword) >= $minLength) {
                    $keywords_passed[$keyword] = $keyword;
                } else {
                    $keywords_failed[$keyword] = $keyword;
                }
            }
        }

        return [$keywords_passed, $keywords_failed, $input];
    }
    
    protected function _splitString($str, $limit = -1)
    {
        if (function_exists('mb_split')) {
            return mb_split('\s+', trim(function_exists('mb_convert_kana') ? mb_convert_kana($str, 's', 'UTF-8') : $str), $limit);
        }
        return preg_split('/\s+/', $str, $limit);
    }
}