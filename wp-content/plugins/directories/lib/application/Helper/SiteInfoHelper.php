<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;

class SiteInfoHelper
{
    public function help(Application $application, $info = 'name')
    {
        switch ($info) {
            case 'name':
                return $application->getPlatform()->getSiteName();
            case 'url':
                return $application->getPlatform()->getSiteUrl();
            case 'email':
                return $application->getPlatform()->getSiteEmail();
            case 'admin_url':
                return $application->getPlatform()->getSiteAdminUrl();
        }
    }
}