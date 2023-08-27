<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Framework\User\AbstractIdentity;

class HasPermissionHelper
{    
    /**
     * Checks whether the user has a certain permission, e.g. hasPermission('A').
     *
     * @param Application $application
     * @param string $permission
     * @param AbstractIdentity|null
     * @return bool
     */
    public function help(Application $application, $permission, AbstractIdentity $identity = null)
    {
        if (!isset($identity)) {
            $user = $application->getUser();
            if ($user->isAdministrator()) return true;
                
            return $user->isAnonymous()
                ? $application->getPlatform()->guestHasPermission($permission)
                : $application->getPlatform()->hasPermission($user->id, $permission);
        }
        
        if ($application->IsAdministrator($identity)) return true;
                
        return $identity->isAnonymous()
            ? $application->getPlatform()->guestHasPermission($permission)
            : $application->getPlatform()->hasPermission($identity->id, $permission);
    }
}