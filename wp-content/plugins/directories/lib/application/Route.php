<?php
namespace SabaiApps\Directories;

use SabaiApps\Framework\Application\IRoute;

class Route implements IRoute
{
    /**
     * @var string
     */
    private $_route;
    /**
     * @var array
     */
    private $_data;

    /**
     * Constructor
     *
     * @param string $route
     * @param array $data
     */
    public function __construct($route, array $data)
    {
        $this->_route = $route;
        $this->_data = $data;
    }

    public function getData()
    {
        return $this->_data;
    }
    
    public function __get($name)
    {
        return isset($this->_data[$name]) ? $this->_data[$name] : null;
    }

    public function __toString()
    {
        return $this->_route;
    }

    /**
     * Gets the class name of requested controller found in route
     *
     * @return string
     */
    public function getController()
    {
        return isset($this->_data['controller_class']) ? $this->_data['controller_class'] : false;
    }

    /**
     * Returns controller file path
     *
     * @return nyll
     */
    public function getControllerFile(){}

    /**
     * Returns controller constructor paramters
     *
     * @return array
     */
    public function getControllerArgs()
    {
        return isset($this->_data['controller_args']) ? $this->_data['controller_args'] : [];
    }
    
    /**
     * Gets the name of requested controller
     *
     * @return string
     */
    public function getControllerName()
    {
        return isset($this->_data['controller']) ? $this->_data['controller'] : false;
    }

    /**
     * Returns another route to which request should be fowarded
     *
     * @return mixed string or false
     */
    public function isForward()
    {
        return isset($this->_data['forward']) ? $this->_data['forward'] : false;
    }
    
    /**
     * Gets the name of plugin with which this route is associated
     *
     * @return array
     */
    public function getComponent()
    {
        return isset($this->_data['component']) ? $this->_data['component'] : false;
    }
}