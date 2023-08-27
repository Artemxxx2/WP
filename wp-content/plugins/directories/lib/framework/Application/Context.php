<?php
namespace SabaiApps\Framework\Application;

class Context
{
    const STATUS_ERROR = 1, STATUS_SUCCESS = 2, STATUS_VIEW = 3;

    protected $_status = self::STATUS_VIEW;
    private $_request, $_attributes = [], $_route;

    public function setRequest(AbstractRequest $request)
    {
        $this->_request = $request;

        return $this;
    }

    /**
     *
     * @return AbstractRequest 
     */
    public function getRequest()
    {
        return $this->_request;
    }

    public function getAttributes()
    {
        return $this->_attributes;
    }

    public function setAttributes(array $attributes, $merge = true)
    {
        $this->_attributes = $merge ? array_merge($this->_attributes, $attributes) : $attributes;

        return $this;
    }

    public function setRoute(IRoute $route)
    {
        $this->_route = $route;

        return $this;
    }

    /**
     *
     * @return IRoute 
     */
    public function getRoute()
    {
        return $this->_route;
    }

    public function setSuccess()
    {
        $this->_status = self::STATUS_SUCCESS;

        return $this;
    }

    public function isSuccess()
    {
        return $this->_status === self::STATUS_SUCCESS;
    }

    public function setError()
    {
        $this->_status = self::STATUS_ERROR;

        return $this;
    }

    public function isError()
    {
        return $this->_status === self::STATUS_ERROR;
    }

    public function setView()
    {
        $this->_status = self::STATUS_VIEW;

        return $this;
    }

    public function isView()
    {
        return $this->_status === self::STATUS_VIEW;
    }

    public function getStatus()
    {
        return $this->_status;
    }

    public function &__get($name)
    {
        return $this->_attributes[$name];
    }

    public function __set($name, $value)
    {
        $this->_attributes[$name] = $value;
    }

    public function __isset($name)
    {
        return isset($this->_attributes[$name]);
    }

    public function __unset($name)
    {
        unset($this->_attributes[$name]);
    }
}