<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;

class AdminUrlHelper
{
    public function help(Application $application, Entity\Type\IEntity $entity, $separator = '&amp;')
    {
        if (!$bundle = $application->Entity_Bundle($entity)) return '';
        
        return $application->AdminUrl($bundle->getAdminPath() . '/' . $entity->getId(), [], '', $separator);
    }
}