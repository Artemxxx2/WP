<?php
namespace SabaiApps\Directories\Component\View\Controller\Admin;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Display\Controller\Admin\AbstractDisplays;

class Filters extends AbstractDisplays
{
    protected $_displayType = 'filters';
    
    protected function _getDisplays(Context $context)
    {
        return array('default' => null);
    }
    
    protected function _getDisplay(Context $context, $displayName)
    {
        return $this->View_FilterForm_getDisplay($context->bundle, $displayName, true, $displayName === 'default');
    }

    protected function _getAddDisplayTitle()
    {
        return __('Add Filter Group', 'directories');
    }
}