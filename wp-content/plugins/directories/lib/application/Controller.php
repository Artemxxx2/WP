<?php
namespace SabaiApps\Directories;

use SabaiApps\Framework\Application\IController;
use SabaiApps\Framework\Application\AbstractApplication;
use SabaiApps\Framework\Application\AbstractRoutingController as FrameworkAbstractRoutingController;
use SabaiApps\Framework\Application\Context as FrameworkContext;

/**
 * @method Platform\AbstractPlatform getPlatform()
 * @method \SabaiApps\Framework\DB\AbstractDB getDB()
 */
abstract class Controller implements IController
{
    protected $_application, $_parent, $_route;

    public function setRoute($route)
    {
        $this->_route = $route;

        return $this;
    }

    final public function setApplication(AbstractApplication $application)
    {
        $this->_application = $application;

        return $this;
    }

    final public function getApplication()
    {
        return $this->_application;
    }

    final public function setParent(FrameworkAbstractRoutingController $controller)
    {
        $this->_parent = $controller;

        return $this;
    }

    final public function execute(FrameworkContext $context)
    {
        $this->_doExecute($context);
    }

    final public function __call($method, $args)
    {
        return call_user_func_array([$this->_application, $method], $args);
    }
    
    final public function __get($name) {
        return $this->_application->getComponent($name);
    }

    protected function _checkToken(Context $context, $tokenId, $reuseable = false, $tokenName = Request::PARAM_TOKEN)
    {
        if (!$token = $context->getRequest()->asStr($tokenName, false)) {
            $context->setBadRequestError();
            return false;
        }
        if (!$this->_application->Form_Token_validate($token, $tokenId, $reuseable)) {
            $context->setForbiddenError(null, __('Token has expired. Please reload the page and try again.', 'directories'));
            return false;
        }
        return true;
    }

    abstract protected function _doExecute(Context $context);
}