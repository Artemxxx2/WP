<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Request;
use SabaiApps\Framework\Application\Url;

class UrlHelper
{
    public function help(Application $application, $route = '', array $params = [], $fragment = '', $separator = '&amp;', $script = null)
    {
        if ($route instanceof Url) return $route;

        if (is_array($route)) return $application->createUrl($route);

        if (is_string($route) && strlen($route) && strpos($route, '/') !== 0) {
            if (filter_var($route, FILTER_VALIDATE_URL) === false) {
                $application->logWarning('Rejecting invalid URL in link: ' . $route);
                return $application->createUrl();
            }
            if (strpos($route, $application->getScriptUrl($application->getCurrentScriptName())) !== 0) {
                // External URL
                return $application->createUrl(['script_url' => $route]);
            }
            if (!$parsed = parse_url(str_replace('&amp;', '&', $route))) {
                $application->logWarning('Failed parsing URL in link: ' . $route);
                return $application->createUrl();
            }

            $fragment = !isset($fragment) && isset($parsed['fragment']) ? $parsed['fragment'] : (string)$fragment;
            if (!empty($parsed['query']))  {
                $query = [];
                parse_str($parsed['query'], $query);
                unset($query[Request::PARAM_AJAX]);
                $params += $query;
                if (isset($params[$application->getRouteParam()])) {
                    $route = $params[$application->getRouteParam()];
                    unset($params[$application->getRouteParam()]);

                    return $this->_getUrl($application, $route, $params, $fragment, $separator, $script);
                }
            }

            $url = $parsed['scheme'] . '://' . $parsed['host'];
            if (isset($parsed['port'])) {
                $url .= ':' . $parsed['port'];
            }
            if (isset($parsed['path'])) {
                $url .= $parsed['path']; 
            }
            return $application->createUrl([
                'script_url' => $url,
                'params' => $params,
                'fragment' => $fragment,
                'separator' => $separator,
            ]);
        }

        return $this->_getUrl($application, $route, $params, $fragment, $separator, $script);
    }

    protected function _getUrl(Application $application, $route, $params, $fragment, $separator, $script)
    {
        return $application->createUrl([
            'route' => $route,
            'params' => $params,
            'fragment' => $fragment,
            'separator' => $separator,
            'script' => $script,
        ]);
    }
}