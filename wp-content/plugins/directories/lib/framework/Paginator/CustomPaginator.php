<?php
namespace SabaiApps\Framework\Paginator;

class CustomPaginator extends AbstractPaginator
{
    protected $_getElementCountFunc, $_getElementsFunc, $_extraParams, $_extraParamsPrepend, $_emptyElements;

    public function __construct($getElementCountFunc, $getElementsFunc, $perpage, array $extraParams = [], array $extraParamsPrepend = [], $emptyElements = null, $limit = 0)
    {
        parent::__construct($perpage, $limit);
        $this->_getElementCountFunc = $getElementCountFunc;
        $this->_getElementsFunc = $getElementsFunc;
        $this->_extraParams = $extraParams;
        $this->_extraParamsPrepend = $extraParamsPrepend;
        $this->_emptyElements = $emptyElements;
    }
    
    public function setExtraParams(array $params, $prepend = false)
    {
        if ($prepend) {
            $this->_extraParamsPrepend = $params;
        } else {
            $this->_extraParams = $params;
        }
    }

    protected function _getElementCount()
    {
        return call_user_func_array(
            $this->_getElementCountFunc,
            array_merge($this->_extraParamsPrepend, $this->_extraParams)
        );
    }

    protected function _getElements($limit, $offset)
    {
        return call_user_func_array(
            $this->_getElementsFunc,
            array_merge($this->_extraParamsPrepend, [$limit, $offset], $this->_extraParams)
        );
    }

    protected function _getEmptyElements()
    {
        return isset($this->_emptyElements) ? $this->_emptyElements : parent::_getEmptyElements();
    }
}