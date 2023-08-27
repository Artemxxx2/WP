<?php
namespace SabaiApps\Directories\Component\Field\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class TypeHelper
{
    private $_components, $_impls = [];

    /**
     * Gets an implementation of SabaiApps\Directories\Component\Field\Type\IType interface for a given field type
     * @param SabaiApps\Directories\Application $application
     * @param string $type
     * @param bool $useCache
     */
    public function help(Application $application, $type, $returnFalse = false, $useCache = true)
    {
        if (!isset($this->_impls[$type])) {
            // Field handlers initialized?
            if (!isset($this->_components) || !$useCache) {
                $this->_init($application, $useCache);
            }
            // Valid field type?
            if (!isset($this->_components[$type])
                || (!$component = $application->getComponent($this->_components[$type]))
            ) {                
                if ($returnFalse) return false;
                
                throw new Exception\UnexpectedValueException(sprintf('Invalid field type: %s', $type));
            }
            $this->_impls[$type] = $component->fieldGetType($type);
        }

        return $this->_impls[$type];
    }

    private function _init(Application $application, $useCache = true)
    {
        if (!$useCache
            || (!$types = $application->getPlatform()->getCache('field_types_'))
        ) {
            $types = [];
            foreach ($application->InstalledComponentsByInterface('Field\ITypes') as $component_name) {
                if (!$application->isComponentLoaded($component_name)) continue;
                
                foreach ($application->getComponent($component_name)->fieldGetTypeNames() as $type_name) {
                    if (!$application->getComponent($component_name)->fieldGetType($type_name)) {
                        continue;
                    }
                    $types[$type_name] = $component_name;
                }
            }
            $application->getPlatform()->setCache($types, 'field_types_', 0);
        }
        
        $this->_components = $types;
    }
    
    public function reset(Application $application)
    {
        $this->_components = null;
    }
    
    public function clearCache(Application $application)
    {
        $application->getPlatform()->deleteCache('field_types_');
    }
}