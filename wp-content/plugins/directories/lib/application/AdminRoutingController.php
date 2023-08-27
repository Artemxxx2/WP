<?php
namespace SabaiApps\Directories;

use SabaiApps\Framework\Application\Context as FrameworkContext;

class AdminRoutingController extends AbstractRoutingController
{   
    protected function _getDefaultRoute()
    {
        return new Route('/', ['controller_class' => '\SabaiApps\Directories\AdminIndexController']);
    }

    protected function _getComponentRoutes($rootPath)
    {
        return $this->_application->getComponent('System')->getAdminRoutes($rootPath);
    }

    protected function _processAccessCallback(Context $context, array &$route, $accessType)
    {
        return $this->_application->getComponent($route['callback_component'])->systemOnAccessAdminRoute(
            $context,
            $route['callback_path'],
            $accessType,
            $route
        );
    }

    protected function _processTitleCallback(Context $context, array $route, $titleType)
    {
        return $this->_application->getComponent($route['callback_component'])->systemAdminRouteTitle(
            $context,
            $route['callback_path'],
            $titleType,
            $route
        );
    }
    
    protected function _processRoute(array &$route)
    {
        $route['controller_class'] = '\SabaiApps\Directories\Component\\' . $route['controller_component'] . '\Controller\Admin\\' . $route['controller'];
    }

    public function execute(FrameworkContext $context)
    {
        if (!$this->getPlatform()->isAdmin()) {
            $context->setForbiddenError();
            return;
        }
        if ($this->getUser()->isAnonymous()) {
            $context->setUnauthorizedError($this->AdminUrl());
            return;
        }

        parent::execute($context);
    }
}