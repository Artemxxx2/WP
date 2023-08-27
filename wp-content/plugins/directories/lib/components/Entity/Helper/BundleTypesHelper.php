<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class BundleTypesHelper
{
    private $_impls = [], $_exists;
    
    public function help(Application $application, $bundleType = null)
    {
        if (!$bundle_types = $application->getPlatform()->getCache('entity_bundle_types')) {
            $bundle_types = [];
            foreach ($application->InstalledComponentsByInterface('Entity\IBundleTypes') as $component_name) {
                if (!$application->isComponentLoaded($component_name)) continue;

                foreach ($application->getComponent($component_name)->entityGetBundleTypeNames() as $bundle_type_name) {
                    $bundle_types[$bundle_type_name] = $component_name;
                }
            }
            $application->getPlatform()->setCache($application->Filter('entity_bundle_types', $bundle_types), 'entity_bundle_types', 0);
        }

        return isset($bundleType) ? (isset($bundle_types[$bundleType]) ? $bundle_types[$bundleType] : null) : $bundle_types;
    }
    
    /**
     * Checks if any valid bundle exists
     */
    public function exist(Application $application)
    {
        if (!isset($this->_exists)) {
            $this->_exists = false;
            foreach (array_unique($this->help($application)) as $component_name) {
                if ($application->isComponentLoaded($component_name)
                    && $application->Entity_Bundles_count($component_name)
                ) {
                    $this->_exists = true;
                    break;
                }
            }
        }
        return $this->_exists;
    }
    
    public function byFeatures(Application $application, array $features, $entityType = null)
    {
        $bundle_types = [];
        foreach (array_keys($this->help($application)) as $bundle_type) {
            $entity_type = $application->Entity_BundleTypeInfo($bundle_type, 'entity_type');
            foreach ($features as $feature) {
                if (!$application->Entity_BundleTypeInfo($bundle_type, $feature)) continue 2; // skip this bundle type
            }
            $bundle_types[$entity_type][] = $bundle_type;
        }
        
        return isset($entityType) ? (isset($bundle_types[$entityType]) ? $bundle_types[$entityType] : []) : $bundle_types;
    }
    
    /**
     * Gets an implementation of Entity\BundleType\IBundleType interface for a given bundle_type name
     * @param Application $application
     * @param string $bundleType
     */
    public function impl(Application $application, $bundleType, $returnFalse = false)
    {
        if (!isset($this->_impls[$bundleType])) {            
            if ((!$bundle_type_component = $this->help($application, $bundleType))
                || !$application->isComponentLoaded($bundle_type_component)
                || (!$bundle_type = $application->getComponent($bundle_type_component)->entityGetBundleType($bundleType))
            ) {                
                if ($returnFalse) return false;
                throw new Exception\UnexpectedValueException(sprintf('Invalid bundle type: %s', $bundleType));
            }
            $this->_impls[$bundleType] = $bundle_type;
        }

        return $this->_impls[$bundleType];
    }
    
    public function children(Application $application, $bundleType = null, $publicOnly = true, $useCache = true)
    {
        if (!$useCache
            || (!$child_bundle_types = $application->getPlatform()->getCache('entity_bundle_types_children'))
        ) {
            $child_bundle_types = [];
            $child_bundle_features = $application->Filter('entity_child_bundle_features', ['claiming_enable', 'review_enable']);
            foreach (array_keys($application->Entity_BundleTypes()) as $bundle_type) {
                $bundle_type_info = $application->Entity_BundleTypeInfo($bundle_type);
                if (empty($bundle_type_info['parent'])
                    || ($publicOnly && empty($bundle_type_info['public']))
                ) continue;
                
                if (in_array($bundle_type_info['parent'], $child_bundle_features)) {
                    $bundle_types_by_feature = $this->byFeatures($application, array($bundle_type_info['parent']));
                    foreach (array_keys($bundle_types_by_feature) as $entity_type) {
                        foreach ($bundle_types_by_feature[$entity_type] as $_parent) {
                            $child_bundle_types[$_parent][] = $bundle_type;
                        }
                    }
                } else {
                    $child_bundle_types[$bundle_type_info['parent']][] = $bundle_type;
                }
            }
            $application->getPlatform()->setCache($child_bundle_types, 'entity_bundle_types_children');
        }
        
        if (!isset($bundleType)) return $child_bundle_types;
        
        return isset($child_bundle_types[$bundleType]) ? $child_bundle_types[$bundleType] : [];
    }
}