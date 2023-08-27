<?php
namespace SabaiApps\Directories\Component\WordPressContent\Controller\Admin;

use SabaiApps\Directories\Component\System;
use SabaiApps\Directories\Context;

class DirectoryPermissions extends System\Controller\Admin\AbstractSettings
{        
    protected function _getSettingsForm(Context $context, array &$formStorage)
    {
        return $this->WordPressContent_PermissionSettingsForm('Directory', $context->directory->name);
    }
}