<?php
namespace SabaiApps\Directories;

use SabaiApps\Framework\Application\AbstractApplication;
use SabaiApps\Framework\Application\AbstractRoutingController as FrameworkAbstractRoutingController;
use SabaiApps\Framework\Application\Context as FrameworkContext;

abstract class AbstractRoutingController extends FrameworkAbstractRoutingController
{
    private $_accessCallbackResults = [], $_tabsAdded = 0;
    protected $_application, $_parent;

    public function setApplication(AbstractApplication $application)
    {
        $this->_application = $application;

        return $this;
    }

    public function getApplication()
    {
        return $this->_application;
    }

    public function setParent(FrameworkAbstractRoutingController $controller)
    {
        $this->_parent = $controller;

        return $this;
    }

    final public function __call($method, $args)
    {
        return call_user_func_array([$this->_application, $method], $args);
    }

    protected function _isRoutable(FrameworkContext $context, $route)
    {
        $route = trim($route, '/');
        $allowed_content_types = ['html', 'xml', 'json'];
        if ($dot_pos = strpos($route, '.')) {
            $content_type = substr($route, $dot_pos + 1);
            if (in_array($content_type, $allowed_content_types)) {
                $route = substr($route, 0, $dot_pos);
            } else {
                unset($content_type);
            }
        } elseif ($context->getRequest()->has(Request::PARAM_CONTENT_TYPE)) {
            $content_type = $context->getRequest()->asStr(Request::PARAM_CONTENT_TYPE);
            if (!in_array($content_type, $allowed_content_types)) {
                unset($content_type);
            }
        }
        if (!$context->getContentType()) {
            $context->setContentType(isset($content_type) ? $content_type : 'html');
        }

        $paths_requested = explode('/', $route . '/');

        if (!$requested_root_route = array_shift($paths_requested)) return false;

        // URL encode multi-byte char route
        if (function_exists('mb_strlen')
            && strlen($requested_root_route) !== mb_strlen($requested_root_route)
        ) {
            $requested_root_route = strtolower(urlencode($requested_root_route));
        }

        $requested_root_path = '/' . $requested_root_route;

        if ((!$all_routes = $this->_getComponentRoutes($requested_root_path))
            || !isset($all_routes[$requested_root_path])
            || (!$route_matched = $all_routes[$requested_root_path])
            || !$this->isComponentLoaded($route_matched['component'])
        ) {
            $context->setNotFoundError();

            return false;
        }

        // Check if can access
        if (!$this->_canAccessRoute($context, $route_matched, Application::ROUTE_ACCESS_LINK)) {
            // Access denied. Set error if not already set and not a redirect
            if (!$context->isError() && !$context->isRedirect()) $context->setForbiddenError();

            return false;
        }

        // Set page info of the root route
        if ($route_matched_title = $this->_getTitle($context, $route_matched)) {
            $context->setInfo($route_matched_title, $this->Url($requested_root_path));
        }

        // Initialize some required variables
        $path_selected = $requested_root_route;
        $paths_matched = [$requested_root_route];
        $tabs = $menus = $dynamic_route_keys = [];
        $valid_route_matched = $route_matched;

        while (!empty($route_matched['routes'])
            && null !== ($path_requested = array_shift($paths_requested))
        ) {
            $routes = $route_matched['routes'];
            foreach ($routes as $route_key => $route_path) {
                if (!isset($all_routes[$route_path])) continue;

                $route_data = $all_routes[$route_path];

                if ($route_data['type'] == Application::ROUTE_TAB) {
                    // Dynamic routes may not become tabs
                    if (0 === strpos($route_key, ':')) continue;

                    $tabs[$route_data['weight'] + 1][$route_key] = $route_data;
                } elseif ($route_data['type'] == Application::ROUTE_MENU) {
                    // Dynamic routes may not become menu items
                    if (0 === strpos($route_key, ':')) continue;

                    $menus[$route_key] = $route_data;
                }
            }

            if (isset($route_matched['controller'])) {
                $tabs[0][''] = $route_matched;
            }

            // Some access callbacks set the response status as error, but the status should not be changed here since
            // we are now just checking whether or not the route is accessible, not really trying to access the route.
            $context->setView();

            // Default route
            if ($path_requested == '') {
                if ($tabs_added = $this->_addTabs($context, $tabs, $paths_matched)) {
                    $context->setCurrentTab('')->setInfo($tabs_added['']['title'], $tabs_added['']['url']);
                }
                $this->_addMenus($context, $menus, $paths_matched);

                break;
            }

            if (isset($routes[$path_requested])) {
                $path_selected = $path_requested;
                $route_matched = $all_routes[$routes[$path_requested]];
            } else {
                $matched = false;
                // Check if dynamic routes are defined and any matching route
                krsort($routes, SORT_STRING);
                foreach ($routes as $route_key => $route_path) {
                    if (0 !== strpos($route_key, ':')) continue;
                    if (!isset($all_routes[$route_path])) continue;

                    $route_data = $all_routes[$route_path];
                    
                    if (!empty($route_data['format'][$route_key])) {
                        $regex = '#^(' . str_replace('#', '\#', $route_data['format'][$route_key]) . ')$#i';
                    } else {
                        $regex = '#^([a-z0-9~\s\.:_\-\+@%]+)$#i';
                    }
                    if (!preg_match($regex, $path_requested, $matches)) continue;

                    $context->getRequest()->set(ltrim($route_key, ':'), $matches[1]);
                    $dynamic_route_keys[$route_key] = $matches[1];
                    $path_selected = $route_key;
                    $route_matched = $route_data;
                    $matched = true;
                    break;
                }

                if (!$matched) {
                    $context->setNotFoundError();
                    
                    return false;
                }
            }
            
            // Check if can access content of the route
            if (!$this->_canAccessRoute($context, $route_matched, Application::ROUTE_ACCESS_LINK)) {
                // Access denied

                // Forward to another route if format key is set in route
                if (!empty($route_matched['forward'])) {
                    return new Route(null, $route_matched);
                }

                // Set error if not already set
                if (!$context->isError() && !$context->isRedirect()) $context->setForbiddenError();
                
                return false;
            }

            if ($route_matched['type'] != Application::ROUTE_CALLBACK) {
                // Add breadcrumbs
                if ($tabs_added = $this->_addTabs($context, $tabs, $paths_matched)) {
                    // Resolve current tab if requested route is not a tab
                    if ($route_matched['type'] != Application::ROUTE_TAB) {
                        // Set the default tab as the current tab
                        $context->setCurrentTab('');

                        // Add breadcrumb for the default tab
                        $context->setInfo($tabs_added['']['title'], $this->Url($tabs_added['']['url']));
                    } else {
                        $context->setCurrentTab($path_selected);
                    }
                }
                if ($title = $this->_getTitle($context, $route_matched, Application::ROUTE_TITLE_INFO)) {
                    $context->setInfo(
                        $title,
                        $this->Url(isset($route_matched['permalink_url']) ? $route_matched['permalink_url'] :  '/' . implode('/', $paths_matched) . '/' . $path_requested)
                    );
                }
            }

            $paths_matched[] = $path_requested;

            if (!empty($route_matched['controller']) || !empty($route_matched['forward'])) {
                $valid_route_matched = $route_matched;
            }

            // Clear menus/tabs
            $tabs = $menus = [];
        }

        // Any valid route has matched?
        if (empty($valid_route_matched)
            || (empty($route_matched['controller']) && empty($route_matched['forward'])) // make sure controller/forward is set by the last route matched
            || (!empty($route_matched['data']['embed_only']) && !$context->isEmbed())
        ) {
            $context->setNotFoundError();

            return false;
        }
        
        // Check if can access content of the route
        if (!$this->Filter('core_access_route', $this->_canAccessRoute($context, $valid_route_matched), [$context, $valid_route_matched, $paths_matched])) {
            // Access denied. Set error if not already set
            if (!$context->isError() && !$context->isRedirect()) $context->setForbiddenError();

            return false;
        }

        // We don't need routes data anymore, save memory
        unset($valid_route_matched['routes']);

        if (!empty($valid_route_matched['method'])
            && strcasecmp(Request::method(), $valid_route_matched['method']) !== 0
        ) {
            // The requested method is not allowed for this route
            $context->setMethodNotAllowedError();

            return false;
        }

        if (!empty($valid_route_matched['forward'])) {
            // Convert dynamic route parts to actual values
            $valid_route_matched['forward'] = strtr($valid_route_matched['forward'], $dynamic_route_keys);
        } else {            
            if (empty($valid_route_matched['controller'])) {
                $context->setRedirect($this->_application->getPlatform()->getMainUrl());
                
                return false;
            }

            $this->_processRoute($valid_route_matched);
        }

        return new Route('/' . implode('/', $paths_matched) . '/', $valid_route_matched);
    }

    abstract protected function _getComponentRoutes($rootPath);
    abstract protected function _processAccessCallback(Context $context, array &$route, $accessType);
    abstract protected function _processTitleCallback(Context $context, array $route, $titleType);
    abstract protected function _processRoute(array &$route);

    private function _canAccessRoute(Context $context, &$route, $accessType = Application::ROUTE_ACCESS_CONTENT)
    {
        if (isset($route['callback_component']) && !$this->isComponentLoaded($route['callback_component'])) return false;
        
        if (empty($route['access_callback'])) return true;

        // Make sure the callback is called only once for each path
        $path = $route['path'];
        if (!isset($this->_accessCallbackResults[$path][$accessType])) {
            $this->_accessCallbackResults[$path][$accessType] = $this->_processAccessCallback($context, $route, $accessType);
            // Also deny access if any error is set in context
            if ($context->isError()) {
                $this->_accessCallbackResults[$path][$accessType] = false;
            }
        }

        return $this->_accessCallbackResults[$path][$accessType];
    }

    private function _getTitle(Context $context, array $route, $titleType = Application::ROUTE_TITLE_NORMAL)
    {
        if (empty($route['title_callback'])) return isset($route['title']) ? $route['title'] : null;

        return $this->_processTitleCallback($context, $route, $titleType);
    }

    private function _addTabs(Context $context, array $tabs, array $pathsMatched)
    {
        ksort($tabs);
        $_tabs = [];
        $path = implode('/', $pathsMatched);
        foreach (array_keys($tabs) as $weight) {
            foreach ($tabs[$weight] as $route_key => $route_data) {
                // Is the current user allowed to access the link to this route?
                if (!$this->_canAccessRoute($context, $route_data, Application::ROUTE_ACCESS_LINK)) continue;
                
                if (!$title = $this->_getTitle($context, $route_data, Application::ROUTE_TITLE_TAB)) {
                    if ($route_key !== '') continue;
                    
                    $title = __('Top', 'directories');
                }
                
                $_tabs[$route_key] = [
                    'title' => $title,
                    'url' => '/' . $path . '/' . $route_key,
                    'data' => $route_data['data'],
                ];
            }
        }
        
        if (count($_tabs) <= 1) return false;
        
        // Add tabs
        $context->pushTabs($_tabs);
        ++$this->_tabsAdded;

        return $_tabs;
    }

    private function _addMenus(Context $context, array $menus, $pathsMatched)
    {
        $_menus = [];
        $path = implode('/', $pathsMatched);
        foreach ($menus as $route_key => $route) {
            // Is the current user allowed to access the link to this route?
            if (!$this->_canAccessRoute($context, $route, Application::ROUTE_ACCESS_LINK)) continue;

            if (!$title = $this->_getTitle($context, $route, Application::ROUTE_TITLE_MENU)) continue;
            
            $_url = '/' . $path . '/' . $route_key;
            if (is_array($title)) {
                $_title = array_shift($title);
                $url = [];
                foreach ($title as $key => $__title) {
                    $url[$_url . '/' . $key] = $__title;
                }
                $title = $_title;
            } else {
                $url = $_url;
            }

            // Add menu
            $_menus[$route['weight']][$route_key] = [
                'title' => $title,
                'url' => $url,
                'data' => $route['data'],
            ];
        }
        if (empty($_menus)) return;

        ksort($_menus);
        $menus = [];
        foreach (array_keys($_menus) as $weight) {
            foreach ($_menus[$weight] as $data) {
                $menus[] = $data;
            }
        }
        $context->setMenus($menus);
    }
}
