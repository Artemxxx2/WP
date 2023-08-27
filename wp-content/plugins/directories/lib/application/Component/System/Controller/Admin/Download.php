<?php
namespace SabaiApps\Directories\Component\System\Controller\Admin;

use SabaiApps\Directories\Controller;
use SabaiApps\Directories\Context;

class Download extends Controller
{
    protected function _doExecute(Context $context)
    {
        if (!$file = $context->getRequest()->asStr('file')) {
            $context->setBadRequestError();
            return;
        }
        
        // Check request token
        if (!$this->_checkToken($context, 'system_admin_download', true)) {
            return;
        }
        
        $export_dir = $this->getComponent('System')->getTmpDir();
        if ((!$file_path = realpath($export_dir . '/' . $file))
            || strpos($file_path = $this->getPath($file_path), $export_dir) !== 0 // must be under the export directory
            || !file_exists($file_path)
        ) {
            $context->setError('Invalid file: ' . $file_path . ' (' . $export_dir . ')');
            return;
        }
        
        $this->System_Download($file_path);
        exit;
    }
}