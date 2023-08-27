<?php
namespace SabaiApps\Framework\Application;

abstract class AbstractResponse
{
    protected $_application;
    
    final public function setApplication(AbstractApplication $application)
    {
        $this->_application = $application;
        
        return $this;
    }
    
    public function send(Context $context)
    {
        switch ($context->getStatus()) {
            case Context::STATUS_SUCCESS:
                return $this->_sendSuccess($context);
            case Context::STATUS_ERROR:
                return $this->_sendError($context);
            default:
                return $this->_sendView($context);
        } 
    }
    
    abstract protected function _sendSuccess(Context $context);
    abstract protected function _sendError(Context $context);
    abstract protected function _sendView(Context $context);
}