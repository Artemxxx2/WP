<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;

class UserIdentityHelper
{
    public function help(Application $application, $userId = null)
    {
        if (!isset($userId)) {
            if (!$application->getUser()->isAnonymous()) {
                return $application->getUser()->getIdentity();
            }
            $userId = 0;
        }

        return is_array($userId)
            ? $application->getPlatform()->getUserIdentityFetcher()->fetchByIds($userId)
            : $application->getPlatform()->getUserIdentityFetcher()->fetchById($userId);
    }
}