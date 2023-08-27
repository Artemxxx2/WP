<?php
namespace SabaiApps\Framework\User;

abstract class AbstractIdentity implements \Serializable
{
    protected $_data;

    /**
     * Constructor
     *
     * @param array $data Data associated with the identity
     */
    public function __construct(array $data = [])
    {
        $this->_data = $data;
    }

    public function serialize()
    {
        return serialize($this->_data);
    }

    public function unserialize($serialized)
    {
        $this->_data = unserialize($serialized);
    }

    public function __serialize()
    {
        return $this->_data;
    }

    public function __unserialize($serialized)
    {
        $this->_data = $serialized;
    }

    /**
     * Returns the data associated with the identity
     * @return array
     */
    public function getData()
    {
        return $this->_data;
    }
    
    public function get($key)
    {
        return array_key_exists($key, $this->_data) ? $this->_data[$key] : null;
    }

    /**
     * Checks if the identity is anonymous
     * @return bool
     */
    abstract public function isAnonymous();

    /**
     * Magic method
     *
     * @param string $key
     */
    public function __get($key)
    {
        return $this->get($key);
    }
    
    public function __set($name, $value)
    {
        if (!array_key_exists($name, $this->_data)) return;
        
        $this->_data[$name] = $value;
    }

    /**
     * Magic method
     *
     * @param string $key
     */
    public function __isset($key)
    {
        return array_key_exists($key, $this->_data) && isset($this->_data[$key]);
    }
}