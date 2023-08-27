<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

class TranslationsHelper
{
    public function help(Application $application, IEntity $entity, $loadEntityFields = true, $lang = null, $checkTranslatable = true)
    {
        $ret = [];
        if ($entity_ids = $this->ids($application, $entity, $lang, $checkTranslatable)) {
            if ($entities = $application->Entity_Entities($entity->getType(), $entity_ids, $loadEntityFields)) {
                foreach ($entity_ids as $lang => $entity_id) {
                    if (isset($entities[$entity_id])) {
                        $ret[$lang] = $entities[$entity_id];
                    }
                }
            }
        }
        
        return $ret;
    }
    
    public function ids(Application $application, IEntity $entity, $lang = null, $checkTranslatable = true)
    {
        if ((!$languages = $application->getPlatform()->getLanguages())
            || count($languages) <= 1
            || ($checkTranslatable && !$application->getPlatform()->isTranslatable($entity->getType(), $entity->getBundleName()))
        ) return [];

        if (isset($lang)) {
            if (!in_array($lang, $languages)) return [];

            $languages = [$lang];
        }
        
        $entity_ids = [];
        foreach ($languages as $lang) {
            if (($entity_id = $application->getPlatform()->getTranslatedId($entity->getType(), $entity->getBundleName(), $entity->getId(), $lang))
                && $entity_id != $entity->getId()
            ) {
                $entity_ids[$lang] = $entity_id;
            }
        }
        
        return $entity_ids;
    }
}