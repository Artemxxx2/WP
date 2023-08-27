<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class FileTypeHelper
{
    public function help(Application $application, $file, $isImage = false)
    {
        if ($isImage) {
            if ($size = @getimagesize($file)) {
                return $size['mime'];
            }
        }
        if (function_exists('finfo_file')) {
            if ($finfo = @finfo_open(FILEINFO_MIME)) {
                $mime = finfo_file($finfo, $file);
                @finfo_close($finfo);
                if ($mime) return $mime;
            }
        }
        if (!function_exists('mime_content_type')) {
            throw new Exception\RuntimeException('Could not find finfo_file or mime_content_type function');
        }
        
        return mime_content_type($file);
    }
}