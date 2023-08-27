<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;

class FileIconHelper
{    
    public function help(Application $application, $extension)
    {
        switch ($extension) {
            case 'gif':
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'webp':
            case 'svg':
                return 'far fa-file-image';
            case 'xls':
                return 'far fa-file-excel';
            case 'php':
            case 'js':
            case 'html':
            case 'htm':
            case 'xml':
                return 'far fa-file-code';
            case 'zip':
            case 'tgz':
                return 'far fa-file-archive';
            case 'txt':
                return 'far fa-file-alt';
            case 'wmv':
            case 'mpg':
            case 'mpeg':
                return 'far fa-file-video';
            case 'mp3':
                return 'far fa-file-audio';
            case 'pdf':
                return 'far fa-file-pdf';
            case 'doc':
                return 'far fa-file-word';
            case 'ppt':
                return 'far fa-file-powerpoint';
            default:
                return 'far fa-file';
        }
    }
}