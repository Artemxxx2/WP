<?php
namespace SabaiApps\Directories\Platform\WordPress;

use SabaiApps\Directories\Application;

class UrlHelper extends \SabaiApps\Directories\Helper\UrlHelper
{
    protected $_pageIds;
    
    public function __construct(array $pageIds)
    {
        $this->_pageIds = $pageIds;
    }
    
    public function help(Application $application, $route = '', array $params = [], $fragment = '', $separator = '&amp;', $script = null)
    {
        if (is_string($route)
            || $route instanceof \SabaiApps\Directories\Route
        ) {
            $route = (string)$route;
            if (strpos($route, '/') === 0) {
                $_route = trim($route, '/');
                $params['drts_route'] = $_route;
                $params['page_id'] = $this->_getPageId($_route);
                $route = '';
            }
        } elseif (is_array($route)) {
            if (isset($route['route'])
                && strpos($route['route'], '/') === 0
            ) {
                $_route = trim($route['route'], '/');
                $route['params']['drts_route'] = $_route;
                $route['params']['page_id'] = $this->_getPageId($_route);
                $route['route'] = '';
            }
        }
        
        return parent::help($application, $route, $params, $fragment, $separator, $script);
    }
    
    protected function _getPageId($route)
    {
        if ($pos = strpos($route, '/')) {
            $route = substr($route, 0, $pos);
        }
        $page_name = $route;
        if (isset($this->_pageIds[$page_name])) {
            return $this->_pageIds[$page_name];
        }
        // Probably route starting with "drts", use whatever page available since the URL
        // should be used for JSON request and it does not matter what page is used
        $page_ids = array_values($this->_pageIds);
        return $page_ids[0];
    }
}