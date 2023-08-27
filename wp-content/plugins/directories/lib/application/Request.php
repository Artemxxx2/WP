<?php
namespace SabaiApps\Directories;

use SabaiApps\Framework\Application\HttpRequest;

class Request extends HttpRequest
{
    protected static $_isAjax;
    
    // Pre-defined request parameter constants
    const PARAM_AJAX = '_ajax_', PARAM_TOKEN = '_t_', PARAM_CONTENT_TYPE = '_type_';
    
    public static function isAjax()
    {
        if (!isset(self::$_isAjax)) {
            if (empty($_REQUEST[self::PARAM_AJAX])
                || (!self::$_isAjax = preg_replace('/[^A-Za-z0-9_\-\.# ]/', ' ', str_replace(['%23', '%20'], ['#', ' '], $_REQUEST[self::PARAM_AJAX])))
            ) {
                self::$_isAjax = self::isXhr();
            }
        }

        return self::$_isAjax;
    }
    
    public static function isModal()
    {
        return self::isAjax() && strpos(self::$_isAjax, '#drts-modal') === 0;
    }
    
    public static function url($withQueryStr = true)
    {
        $url = parent::url($withQueryStr);

        if (!$withQueryStr) return $url;

        // Attempt to parse URL and remove ajax/token system paramters
        if ($parsed = parse_url($url)) {
            if (!empty($parsed['query'])) {
                $params = [];
                parse_str(rawurldecode($parsed['query']), $params);
                unset($params[self::PARAM_AJAX], $params[self::PARAM_TOKEN]);
                $query_str = '?' . strtr(http_build_query($params), ['%7E' => '~', '+' => '%20']); // http_build_query does urlencode, so need a little adjustment for RFC1738 compat
            } else {
                $query_str = '';
            }

            $url = sprintf(
                '%s://%s%s%s%s',
                $parsed['scheme'],
                !empty($parsed['port']) ? $parsed['host'] . ':' . $parsed['port'] : $parsed['host'],
                $parsed['path'],
                $query_str,
                !empty($parsed['fragment']) ? '#' . $parsed['fragment'] : ''
            );
        }

        return $url;
    }
}
