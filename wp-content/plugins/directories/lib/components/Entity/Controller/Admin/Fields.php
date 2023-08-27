<?php
namespace SabaiApps\Directories\Component\Entity\Controller\Admin;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Display\Controller\Admin\AbstractDisplays;

class Fields extends AbstractDisplays
{
    protected $_displayType = 'form';
    
    protected function _getDisplays(Context $context)
    {
        return array('default' => null);
    }
    
    protected function _getDisplay(Context $context, $displayName)
    {
        return $this->Entity_Form_getDisplay($context->bundle, $displayName, true, true);
    }
}