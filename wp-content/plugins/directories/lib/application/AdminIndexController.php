<?php
namespace SabaiApps\Directories;

class AdminIndexController extends Controller
{
    private static $_done = false;

    protected function _doExecute(Context $context)
    {
        if (!$this->IsAdministrator()) {
            $context->setForbiddenError();
            return;
        }
        
        // Prevent recursive routing
        if (!self::$_done) {
            $this->_parent->forward('/directories/settings', $context);
            self::$_done = true;
        }
    }
}