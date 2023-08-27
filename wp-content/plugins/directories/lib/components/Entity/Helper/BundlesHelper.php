<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Framework\User\AbstractIdentity;

class BundlesHelper
{   
    public function help(Application $application, array $bundleNames = null, $componentName = null, $group = null)
    {
        $ret = [];
        if (isset($bundleNames)) {
            if (isset($componentName)) {
                // $bundleNames is bundle types
                foreach ($bundleNames as $k => $bundle_type) {
                    if (isset(BundleHelper::$bundlesByType[$componentName][$group][$bundle_type])) {
                        $bundle = BundleHelper::$bundlesByType[$componentName][$group][$bundle_type];
                        $ret[$bundle->name] = $bundle;
                        unset($bundleNames[$k]);
                    }
                }
                if (!empty($bundleNames)) {
                    $ret += $this->byType($application, $bundleNames, $componentName, $group);
                }
            } else {
                foreach ($bundleNames as $k => $bundle_name) {
                    if (isset(BundleHelper::$bundles[$bundle_name])) {
                        $ret[$bundle_name] = BundleHelper::$bundles[$bundle_name];
                        unset($bundleNames[$k]);
                    }
                }
                if (!empty($bundleNames)) {
                    foreach ($application->getModel('Bundle', 'Entity')->name_in($bundleNames)->fetch() as $bundle) {
                        if ($application->Entity_Bundle_isValid($bundle)) {
                            $ret[$bundle->name] = BundleHelper::add($bundle);
                        }
                    }
                }
            }
        } else {
            $model = $application->getModel('Bundle', 'Entity');
            if (isset($componentName)) {
                $model->component_is($componentName);
                if (isset($group)) {
                    $model->group_is($group);
                }
            }
            foreach ($model->fetch() as $bundle) {    
                if ($application->Entity_Bundle_isValid($bundle)) {
                    $ret[$bundle->name] = BundleHelper::add($bundle);
                }
            }
        }
        
        return $ret;
    }
    
    public function count(Application $application, $componentName = null, $group = null)
    {
        $count = $application->getModel('Bundle', 'Entity');
        if (isset($componentName)) {
            $count->component_is($componentName)->group_is($group);
        }
        return $count->count();
    }
    
    public function byType(Application $application, $bundleType, $componentName = null, $group = null)
    {
        $ret = [];
        $bundles = $application->getModel('Bundle', 'Entity')->type_in((array)$bundleType);
        if (isset($componentName)) {
            $bundles->component_is($componentName);
            if (isset($group)) {
                $bundles->group_is($group);
            }
        }
        foreach ($bundles->fetch() as $bundle) {
            if ($application->Entity_Bundle_isValid($bundle)) {
                $ret[$bundle->name] = BundleHelper::add($bundle);
            }
        }
        
        return $ret;
    }
    
    public function collection(Application $application, $bundleNames)
    {
        $ret = [];
        foreach ($this->help($application, $bundleNames) as $bundle) {                
            $ret[$bundle->name] = $bundle;
        }
        return $application->getModel('Bundle', 'Entity')->createCollection($ret);
    }
    
    protected $_addableBundles = [];
    
    public function addable(Application $application, $bundleType, AbstractIdentity $identity = null)
    {
        $user_id = isset($identity) ? $identity->id : $application->getUser()->id;
        if (!isset($this->_addableBundles[$user_id])) {
            $this->_addableBundles[$user_id] = [];
            foreach ($this->byType($application, $bundleType) as $bundle_name=> $bundle) {
                if (!$application->isComponentLoaded($bundle->component)
                    || !empty($bundle->info['parent'])
                    || empty($bundle->info['public'])
                    || !$application->HasPermission('entity_create_' . $bundle_name)
                ) continue;

                $this->_addableBundles[$user_id][$bundle_name] = $bundle->getGroupLabel() . ' - ' . $bundle->getLabel('singular');
            }
        }
        return $this->_addableBundles[$user_id];
    }
    
    public function sort(Application $application, array $bundles = null, $componentName = null, $group = null)
    {
        if (!isset($bundles)) $bundles = $this->help($application, null, $componentName, $group);
        uasort($bundles, function ($a, $b) { strnatcmp($a->name, $b->name); });
        $_bundles = $ret = [];
        foreach ($bundles as $bundle) {       
            if (!$application->isComponentLoaded($bundle->component)) continue;
            
            $_bundles[$bundle->component][empty($bundle->info['is_taxonomy']) ? 0 : 1][empty($bundle->info['parent']) ? 0 : 1][$bundle->name] = $bundle;
        }
        foreach (array_keys($_bundles) as $component_name) {
            $ret[$component_name] = [];
            ksort($_bundles[$component_name]);
            foreach (array_keys($_bundles[$component_name]) as $i) {
                ksort($_bundles[$component_name][$i]);
                foreach (array_keys($_bundles[$component_name][$i]) as $j) {
                    $ret[$component_name] += $_bundles[$component_name][$i][$j];
                }   
            }
        }

        if (isset($componentName)) return isset($ret[$componentName]) ? $ret[$componentName] : [];
        
        return $ret;
    }
    
    public function referencing(Application $application, $bundleName)
    {
        $bundles = [];
        foreach ($this->help($application) as $bundle) {            
            foreach ($application->Entity_Field($bundle) as $field) {
                if ($field->getFieldType() !== 'entity_reference') continue;

                $field_settings = $field->getFieldSettings();
                if (empty($field_settings['bundle'])) continue;
            
                $bundles[$field_settings['bundle']][$field->getFieldName()][] = $field->bundle_name;
            }
        }
        
        return isset($bundles[$bundleName]) ? $bundles[$bundleName] : [];
    }
}