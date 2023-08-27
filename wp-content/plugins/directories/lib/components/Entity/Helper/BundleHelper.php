<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Component\Entity;

class BundleHelper
{
    public static $bundles = [], $bundlesByType = [];
    
    public function help(Application $application, $entityOrBundle, $component = null, $group = '', $throwException = false)
    {
        if ($entityOrBundle instanceof Entity\Type\IEntity) {
            $ret = $this->_getBundleByName($application, $entityOrBundle->getBundleName());
        } elseif (is_string($entityOrBundle)) {
            $ret = isset($component) ? $this->_getBundleByType($application, $entityOrBundle, $component, $group) : $this->_getBundleByName($application, $entityOrBundle);
        } elseif ($entityOrBundle instanceof Entity\Model\Bundle) {
            $ret = $this->isValid($application, $entityOrBundle) ? self::add($entityOrBundle) : false;
        } else {
            if ($throwException) throw new Exception\RuntimeException('Invalid bundle: ' . (string)$entityOrBundle);
            
            $ret = false;
        }
        
        return $ret;
    }
    
    public function isValid(Application $application, Entity\Model\Bundle $bundle)
    {
        if (!$application->isComponentLoaded($bundle->component)
            || (!$bundle_type_component = $application->Entity_BundleTypes($bundle->type))
            || !$application->isComponentLoaded($bundle_type_component)
        ) return false;
        
        if (empty($bundle->info['parent'])) return true;
        
        // Make sure the parent bundle is also valid
        return $application->Entity_Bundle($bundle->info['parent']) ? true : false;
    }
    
    protected function _getBundleByName(Application $application, $bundleName)
    {
        if (isset(self::$bundles[$bundleName])) return self::$bundles[$bundleName];
        
        if (($bundle = $application->getModel('Bundle', 'Entity')->name_is($bundleName)->fetchOne())
            && $this->isValid($application, $bundle)
        ) {
            return self::add($bundle);
        }
    }
    
    protected function _getBundleByType(Application $application, $bundleType, $component, $group)
    {
        if (!isset(self::$bundlesByType[$component][$group])) {
            // Fetch all first and validate by top level bundles first to prevent additional queries by isValid() method.
            $bundles = [];
            foreach ($application->getModel('Bundle', 'Entity')->component_is($component)->group_is($group)->fetch() as $bundle) {
                $bundles[empty($bundle->info['parent']) ? 0 : 1][] = $bundle;
            }
            ksort($bundles);
            foreach (array_keys($bundles) as $k) {
                foreach (array_keys($bundles[$k]) as $j) {
                    if ($this->isValid($application, $bundles[$k][$j])) {
                        self::add($bundles[$k][$j]);
                    }
                }
            }
        } elseif (!isset(self::$bundlesByType[$component][$group][$bundleType])) {
            if ($bundle = $application->getModel('Bundle', 'Entity')->component_is($component)->group_is($group)->type_is($bundleType)->fetchOne()) {
                if ($this->isValid($application, $bundle)) {
                    self::add($bundle);
                }
            }
        }
        if (isset(self::$bundlesByType[$component][$group][$bundleType])) {
            return self::$bundlesByType[$component][$group][$bundleType];
        }
    }
    
    public static function add(Entity\Model\Bundle $bundle)
    {
        self::$bundles[$bundle->name] = self::$bundlesByType[$bundle->component][$bundle->group][$bundle->type] = $bundle;
        
        return $bundle;
    }
    
    public static function remove($bundleName)
    {
        if (isset(self::$bundles[$bundleName])) {
            $bundle = self::$bundles[$bundleName];
            unset(self::$bundlesByType[$bundle->component][$bundle->group][$bundle->type], self::$bundles[$bundleName]);
        }
    }
    
    public function adminLinks(Application $application, Entity\Model\Bundle $bundle, $route)
    {
        $_links = array(
            'edit' => array(
                'weight' => 0,
                'link' => array(
                    'path' => '',
                    'label' => __('Edit', 'directories'),
                ),
            ),
            'fields' => array(
                'weight' => 1,
                'link' => array(
                    'path' => '/fields',
                    'label' => __('Manage Fields', 'directories'),
                ),
            ),
        );
        if ($application->Entity_Displays($bundle)) { // make sure displays exist
            $_links['displays'] = [
                'weight' => 10,
                'link' => array(
                    'path' => '/displays',
                    'label' => __('Manage Displays', 'directories'),
                ),
            ];
        }
        $_links = $application->Filter('entity_bundle_admin_links', $_links, array($bundle));
        // Sort
        uasort($_links, function($a, $b) { return $a['weight'] < $b['weight'] ? -1 : 1; });
        // Build links
        $links = [];
        $route = '/' . trim($route, '/');
        foreach (array_keys($_links) as $key) {
            $link = $_links[$key]['link'];
            if (isset($link[0])) { // array of links
                $links[$key] = [];
                foreach ($link as $_link) {
                    if (is_array($_link)) {
                        $links[$key][] = $application->LinkTo($_link['label'], $route . $_link['path'], isset($_link['options']) ? $_link['options'] : []);
                    } else {
                        $links[$key][] = $_link;
                    }
                }
            } else {
                $links[$key] = $application->LinkTo($link['label'], $route . $link['path'], isset($link['options']) ? $link['options'] : []);
            }
        }
        
        return $links;
    }

    public function path(Application $application, Entity\Model\Bundle $bundle, $permalink = false, $lang = null)
    {
        if (empty($bundle->info['parent'])
            && empty($bundle->info['internal'])
        ) {
            $path = '/' . $application->getComponent($bundle->component)->getSlug($bundle->group, $lang);
            if ($permalink
                || empty($bundle->info['is_primary'])
            ) {
                $path .= '/' . $bundle->info['slug'];
            }
        } elseif (!empty($bundle->info['parent'])) {
            if (!$parent_bundle = $this->help($application, $bundle->info['parent'])) {
                // probably during installation of component, so fetch by bundle type,
                $parent_bundle = $this->help($application, $bundle->info['parent'], $bundle->component, $bundle->group, true);
            }
            if ($permalink) {
                $path = $this->path($application, $parent_bundle, true, $lang) . '/:slug/' . $bundle->info['slug'];
            } else {
                $path = $this->path($application, $parent_bundle, false, $lang) . '/' . $application->Entity_BundleTypeInfo($bundle, 'slug');
            }
        } elseif (!empty($bundle->info['internal'])) {
            $path = '/_drts/' . $application->getComponent($bundle->component)->getSlug($bundle->group, $lang) . '/' . $bundle->info['slug'];
        }

        return $path;
    }
}