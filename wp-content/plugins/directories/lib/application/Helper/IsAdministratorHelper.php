<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Framework\User\AbstractIdentity;

class IsAdministratorHelper
{
    public function help(Application $application, AbstractIdentity $identity = null)
    {
        if (!isset($identity)) {
            return $application->getUser()->isAdministrator();
        }
        return !$identity->isAnonymous() && $application->getPlatform()->isAdministrator($identity->id);
    }
}