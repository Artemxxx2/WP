<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

class ParentEntityHelper
{    
    public function help(Application $application, IEntity $entity, $loadEntityFieldValues = true)
    {        
        if (!$entity->isFieldsLoaded()) {
            $application->Entity_Field_load($entity);
        }
        if (!$parent = $entity->getParent()) {
            if ((!$parent_id = $entity->getParentId())
                || (!$parent = $application->Entity_Entity($entity->getType(), $parent_id, false))
            ) return;
            
            $entity->setParent($parent);
        }
        if ($loadEntityFieldValues) {
            $application->Entity_Field_load($parent);
        }
        
        return $parent;
    }
}