<?php
namespace SabaiApps\Directories\Component\View\Controller\Admin;

use SabaiApps\Directories\Context;

class CloneView extends AddView
{    
    protected function _getSettings(Context $context)
    {
        return ['mode' => $context->view->mode, 'settings' => $context->view->data['settings']];
    }
    
    protected function _getSuccessUrl(Context $context)
    {
        return dirname(dirname($context->getRoute()));
    }
}