<?php
namespace SabaiApps\Directories;

class Link extends \SabaiApps\Framework\Link
{
    protected function _getAttributes()
    {
        $attr = parent::_getAttributes();
        if (!isset($attr['class'])) {
            $attr['class'] = '';
        }
        if ($this->isActive()) {
            $attr['class'] .= ' ' . DRTS_BS_PREFIX . 'active';
        } elseif ($this->isDisabled()) {
            $attr['class'] .= ' ' . DRTS_BS_PREFIX . 'disabled';
        }
        return $attr;
    }
    
    public function setActive($flag = true)
    {
        $this->_options['active'] = (bool)$flag;
        return $this;
    }
    
    public function isActive()
    {
        return !empty($this->_options['active']);
    }
    
    public function setDisabled($flag = true)
    {
        $this->_options['disabled'] = (bool)$flag;
        return $this;
    }
    
    public function isDisabled()
    {
        return !empty($this->_options['disabled']);
    }
}