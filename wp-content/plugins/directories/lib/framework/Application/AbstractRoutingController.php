<?php
namespace SabaiApps\Framework\Application;

use SabaiApps\Framework\Exception;

abstract class AbstractRoutingController implements IController
{
    protected $_route;

    public function setRoute($route)
    {
        $this->_route = $route;

        return $this;
    }

    public function execute(Context $context)
    {
        if (isset($this->_route)
            && ($route = $this->_isRoutable($context, $this->_route))
        ) {
            if ($forward = $route->isForward()) { // forward to another route?
                // Re-route to the forwarded route
                $this->forward($forward, $context);
            } else {
                $this->_doExecute($context, $route);
            }
        } else {
            if (!$context->isView()) return;

            $this->_doExecute($context, $this->_getDefaultRoute());
        }
    }

    /**
     * Forwards request to another route
     *
     * @param string $forward
     * @param Context $context
     */
    public function forward($forward, Context $context)
    {
        if ($route = $this->_isRoutable($context, $forward)) {
            // Is this route being forwarded to another route?
            if ($_forward = $route->isForward()) {
                // Recursive forwarding is not allowed
                throw new Exception(
                    sprintf('Recursive request forwarding detected. The request forwarded to route %s may not be forwarded to another route %s.', $forward, $_forward)
                );
            } else {
                $this->_doExecute($context, $route);
            }
        } else {
            if (!$context->isView()) return;

            if (isset($this->_parent)) {
                $this->_parent->forward($forward, $context);

                return;
            }

            $this->_doExecute($context, $this->_getDefaultRoute());
        }
    }

    /**
     * Runs the controller if any
     *
     * @param Context $context
     * @param IRoute $route
     */
    protected function _doExecute(Context $context, IRoute $route)
    {
        // Set current route
        $context->setRoute($route);

        // Fetch controller instance
        if (!$controller = $route->getController()) return; // no controller defined

        if (is_string($controller)
            && (!$controller = $this->_getController($controller, $route->getControllerArgs())) // controller does not exist
        ) {
            return;
        }

        $this->_executeController($context, $controller);
    }

    protected function _executeController(Context $context, IController $controller)
    {
        $controller->setApplication($this->getApplication())->setParent($this)->setRoute($this->_route)->execute($context);
    }

    /**
     * Gets a controller instance
     *
     * @param string $controllerClass
     * @param array $controllerArgs
     * @return IController
     */
    protected function _getController($controllerClass, array $controllerArgs)
    {
        if (!class_exists($controllerClass, true)) {
            return false;
        }

        if (empty($controllerArgs)) {
            return new $controllerClass();
        }

        $reflection = new \ReflectionClass($controllerClass);

        return $reflection->newInstanceArgs($controllerArgs);
    }

    /**
     * Returns a route instance
     *
     * @return mixed IRoute or false
     * @param Context $context
     * @param string $route
     */
    abstract protected function _isRoutable(Context $context, $route);
    /**
     * Returns the default route
     *
     * @return IRoute
     */
    abstract protected function _getDefaultRoute();
}