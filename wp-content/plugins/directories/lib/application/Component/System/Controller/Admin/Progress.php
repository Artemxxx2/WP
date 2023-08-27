<?php
namespace SabaiApps\Directories\Component\System\Controller\Admin;

use SabaiApps\Directories\Controller;
use SabaiApps\Directories\Context;

class Progress extends Controller
{
    protected function _doExecute(Context $context)
    {
        if ((!$name = $context->getRequest()->asStr('name'))
            || !$context->getRequest()->isAjax()
        ) {
            $context->setError();
            return;
        }
        
        $context->addTemplate('system_list')->setAttributes(array('list' => $this->System_Progress_get($name)));
    }
}