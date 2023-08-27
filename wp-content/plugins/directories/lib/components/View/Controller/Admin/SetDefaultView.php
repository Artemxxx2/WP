<?php
namespace SabaiApps\Directories\Component\View\Controller\Admin;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Controller;

class SetDefaultView extends Controller
{
    protected function _doExecute(Context $context)
    {
        // Must be an Ajax request
        if (!$context->getRequest()->isAjax()) {
            $context->setBadRequestError();
            return;
        }

        // Check request token
        if (!$this->_checkToken($context, 'view_admin_views', true)) {
            return;
        }
        
        // Fetch view
        if ((!$name = $context->getRequest()->asStr('name'))
            || (!$view = $this->getModel('View', 'View')->bundleName_is($context->bundle->name)->name_is($name)->fetchOne())
        ) {
            $context->setError('Invalid view: ' . $name);
            return;
        }
        
        // Set as default view
        $this->View_AdminView_setDefault($context->bundle, $view, true);

        // Send success response
        $context->setSuccess($this->_application->Entity_BundleTypeInfo($context->bundle->type, 'admin_path') . '/views', ['name' => $view->name]);
    }
}