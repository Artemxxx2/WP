<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class ValidateDirectoryHelper
{
    public function help(Application $application, $dir, $ensureWriteable = false, $writeableMode = 0755, $recursive = true)
    {
        $dir = $application->getPath($dir);
        if (!is_dir($dir) && !@mkdir($dir, $writeableMode, $recursive)) {
            throw new Exception\RuntimeException(sprintf(__('Folder %s does not exist.', 'directories'), $dir));
        }
        if ($ensureWriteable && !is_writeable($dir) && !@chmod($dir, $writeableMode)) {
            throw new Exception\RuntimeException(sprintf(__('Folder %s is not writeable by the server.', 'directories'), $dir));
        }
        return $dir;
    }
}