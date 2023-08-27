<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;

class EntitiesHelper
{
    public function help(Application $application, $entityType, $entityIds, $loadEntityFields = true, $preserveOrder = false, $bundleName = null, $lang = null)
    {
        $entities = [];
        if (empty($entityIds)) return $entities;

        $entities = $application->Entity_Types_impl($entityType)->entityTypeEntitiesByIds($entityIds, $bundleName, $lang);

        // Load fields?
        if ($loadEntityFields) {
            $application->Entity_Field_load(
                $entityType,
                $entities,
                false, // force
                true, // cache
                is_array($loadEntityFields) && !empty($loadEntityFields) ? $loadEntityFields : null // load specific fields?
            );
        }
        // Preserve order
        if (!$preserveOrder) return $entities;
        
        // Re-order entities as requested
        $ret = [];
        foreach ($entityIds as $entity_id) {
            if (!isset($entities[$entity_id])) {
                continue;
            }
            $ret[$entity_id] = $entities[$entity_id];
        }

        return $ret;
    }
}