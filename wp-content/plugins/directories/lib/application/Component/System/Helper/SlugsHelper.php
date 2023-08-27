<?php
namespace SabaiApps\Directories\Component\System\Helper;

use SabaiApps\Directories\Application;

class SlugsHelper
{
    /**
     * Returns all sluggable routes
     * @param Application $application
     */
    public function help(Application $application, $componentName = null, $moveToLast = null, $useCache = true)
    {
        if (!$useCache
            || (!$slugs = $application->getPlatform()->getCache('system_slugs'))
        ) {
            $slugs = [];
            foreach ($application->InstalledComponentsByInterface('System\ISlugs') as $component_name) {
                if (!$application->isComponentLoaded($component_name)) continue;
                
                $component = $application->getComponent($component_name);
                $component_slugs = [];
                foreach ($component->systemSlugs() as $slug_name => $slug_info) {
                    $component_slugs[$slug_name] = array(
                        'slug' => isset($slug_info['slug']) ? $slug_info['slug'] : $slug_name,
                        'component' => $component_name,
                    );
                    $component_slugs[$slug_name] += $slug_info;
                    $component_slugs[$slug_name] += array(
                        'title' => null,
                        'parent' => null,
                        'bundle_type' => null,
                        'is_taxonomy' => false,
                    );
                }
                $slugs[$component_name] = $component_slugs;
            }            
            $application->getPlatform()->setCache($slugs = $application->Filter('system_slugs', $slugs), 'system_slugs');
        }

        if (isset($componentName)) {
            return isset($slugs[$componentName]) ? $slugs[$componentName] : null;
        }
        
        if (isset($moveToLast)
            && isset($slugs[$moveToLast])
        ) {
            $slugs_to_move = $slugs[$moveToLast];
            unset($slugs[$moveToLast]);
            $slugs[$moveToLast] = $slugs_to_move;
        }

        return $slugs;
    }
}