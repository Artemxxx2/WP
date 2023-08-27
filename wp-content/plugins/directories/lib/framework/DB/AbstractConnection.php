<?php
namespace SabaiApps\Framework\DB;

abstract class AbstractConnection
{
    /**
     * @var string
     */
    protected $_scheme;
    /**
     * @var bool
     */
    protected $_connected;

    /**
     * Constructor
     *
     * @param string $scheme
     */
    protected function __construct($scheme)
    {
        $this->_scheme = $scheme;
    }
    
    /**
     * Establishes connection with the data source
     */
    public function connect()
    {
        if (!$this->_connected) {
            $this->_doConnect();
            $this->_connected = true;
        }

        return $this;
    }

    /**
     * Gets the name of database scheme
     *
     * @return string
     */
    public function getScheme()
    {
        return $this->_scheme;
    }

    abstract protected function _doConnect();
}