<?php
namespace SabaiApps\Framework\Application;

interface IController
{
    /**
     * Sets a string representation of the route to which requests should be routed
     *
     * @param $route string
     * @return IController Returns itself for chaining.
     */
    public function setRoute($route);
    /**
     * Sets an application instance
     *
     * @param AbstractApplication $application
     * @return IController Returns itself for chaining.
     */
    public function setApplication(AbstractApplication $application);
    /**
     * Gets an application instance
     * @return AbstractApplication
     */
    public function getApplication();
    /**
     * Sets a parent controller
     *
     * @param AbstractRoutingController $controller
     * @return IController Returns itself for chaining.
     */
    public function setParent(AbstractRoutingController $controller);
    /**
     * Executes the controller
     *
     * @param Context $context
     */
    public function execute(Context $context);
}