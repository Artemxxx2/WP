<?php
namespace SabaiApps\Directories;

class MainRoutingController extends AbstractRoutingController
{    
    protected function _getDefaultRoute()
    {
        return new Route('/', ['controller_class' => '\SabaiApps\Directories\MainIndexController']);
    }

    protected function _getComponentRoutes($rootPath)
    {
        return $this->_application->getComponent('System')->getMainRoutes($rootPath);
    }

    protected function _processAccessCallback(Context $context, array &$route, $accessType)
    {
        return $this->_application->getComponent($route['callback_component'])->systemOnAccessMainRoute(
            $context,
            $route['callback_path'],
            $accessType,
            $route
        );
    }

    protected function _processTitleCallback(Context $context, array $route, $titleType)
    {
        return $this->_application->getComponent($route['callback_component'])->systemMainRouteTitle(
            $context,
            $route['callback_path'],
            $titleType,
            $route
        );
    }
    
    protected function _processRoute(array &$route)
    {
        $route['controller_class'] = '\SabaiApps\Directories\Component\\' . $route['controller_component'] . '\Controller\\' . $route['controller'];
    }
}