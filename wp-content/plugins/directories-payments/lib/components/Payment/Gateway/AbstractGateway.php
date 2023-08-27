<?php
namespace SabaiApps\Directories\Component\Payment\Gateway;

use SabaiApps\Directories\Application;

abstract class AbstractGateway implements IGateway
{
    protected $_application, $_name, $_info;

    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
    }

    public function paymentGatewayInfo($key = null)
    {
        if (!isset($this->_info)) $this->_info = (array)$this->_paymentGatewayInfo();

        return isset($key) ? (isset($this->_info[$key]) ? $this->_info[$key] : null) : $this->_info;
    }

    abstract protected function _paymentGatewayInfo();
}
