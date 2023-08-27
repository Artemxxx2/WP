<?php
namespace SabaiApps\Framework\User;

class User
{
    /**
     * @var AbstractIdentity
     */
    protected $_identity;
    /**
     * @var bool
     */
    protected $_administrator = false;

    /**
     * Constructor
     *
     * @param AbstractIdentity $identity
     */
    public function __construct(AbstractIdentity $identity)
    {
        $this->_identity = $identity;
    }

    /**
     * Magic method
     *
     * @param string $key
     */
    public function __get($key)
    {
        return $this->_identity->$key;
    }

    /**
     * Returns an identy object for the user
     *
     * @return AbstractIdentity
     */
    public function getIdentity()
    {
        return $this->_identity;
    }

    /**
     * Checks if the user has an anonymous identity
     *
     * @return bool
     */
    public function isAnonymous()
    {
        return $this->_identity->isAnonymous();
    }

    /**
     * Sets the user identity as a super user
     *
     * @param bool $flag
     */
    public function setAdministrator($flag = true)
    {
        $this->_administrator = $flag;

        return $this;
    }

    /**
     * Checks whether this user is a super user or not
     *
     * @return bool
     */
    public function isAdministrator()
    {
        return $this->_administrator;
    }
}