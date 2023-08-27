<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class TypesHelper
{
    public function help(Application $application, $byComponent = false, $useCache = true)
    {
        if (!$useCache
            || (!$entity_types = $application->getPlatform()->getCache('entity_types'))
        ) {
            $entity_types = [];
            foreach ($application->InstalledComponentsByInterface('Entity\ITypes') as $component_name) {
                if (!$application->isComponentLoaded($component_name)) continue;
                
                foreach ($application->getComponent($component_name)->entityGetTypeNames() as $entity_type) {
                    if (!$application->getComponent($component_name)->entityGetType($entity_type)) {
                        continue;
                    }
                    $entity_types[$entity_type] = $component_name;
                }
            }
            $application->getPlatform()->setCache($entity_types, 'entity_types', 0);
        }
        
        if ($byComponent) {
            $entity_types_by_component = [];
            foreach ($entity_types as $entity_type => $component_name) {
                $entity_types_by_component[$component_name][] = $entity_type;
            }
            $entity_types = $entity_types_by_component;
        }

        return $entity_types;
    }
    
    private $_impls = [];

    /**
     * Gets an implementation of SabaiApps\Directories\Component\Entity\Type\IType interface for a given entity type
     * @param Application $application
     * @param string $entityType
     */
    public function impl(Application $application, $entityType, $useCache = true)
    {
        if (!isset($this->_impls[$entityType])) {
            $types = $this->help($application, false, $useCache);
            if (!isset($types[$entityType])
                || !$application->isComponentLoaded($types[$entityType])
            ) {
                throw new Exception\UnexpectedValueException(sprintf('Invalid entity type: %s', $entityType));
            }
            $this->_impls[$entityType] = $application->getComponent($types[$entityType])->entityGetType($entityType);
        }

        return $this->_impls[$entityType];
    }
}