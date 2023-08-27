<?php
namespace SabaiApps\Directories\Component\System\Controller\Admin;

use SabaiApps\Directories\Controller;
use SabaiApps\Directories\Context;

class ViewLog extends Controller
{
    protected function _doExecute(Context $context)
    {
        // Must be an Ajax request
        if (!$log = $context->getRequest()->asStr('log')) {
            $context->setBadRequestError();
            return;
        }
        
        // Check if valid log
        $logs = $this->Filter('system_admin_system_logs', []);
        if (!isset($logs[$log])
            || empty($logs[$log]['file'])
            || !file_exists($logs[$log]['file'])
        ) {
            return;
        }
        
        header('Content-type: text/plain');
        readfile($logs[$log]['file']);
        exit;
    }
}