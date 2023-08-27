<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;

class TaxonomyContentBundleTypesHelper
{
    public function help(Application $application, $bundleType)
    {
        if (!$bundle_types = $application->getPlatform()->getCache('entity_taxonomy_content_bundle_types')) {
            $bundle_types = [];
            foreach ($application->Entity_Bundles() as $bundle) {
                if (empty($bundle->info['taxonomies'])) continue;
                
                foreach (array_keys($bundle->info['taxonomies']) as $taxonomy_bundle_type) {
                    $bundle_types[$taxonomy_bundle_type][$bundle->type] = $bundle->type;
                }
            }
            $application->getPlatform()->setCache($bundle_types, 'entity_taxonomy_content_bundle_types');
        }
        
        return isset($bundle_types[$bundleType]) ? $bundle_types[$bundleType] : [];
    }
    
    public function clear(Application $application)
    {
        $application->getPlatform()->deleteCache('entity_taxonomy_content_bundle_types');
    }
}