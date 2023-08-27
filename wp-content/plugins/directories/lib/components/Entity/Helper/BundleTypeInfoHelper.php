<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Component\Entity\Model\Bundle;

class BundleTypeInfoHelper
{    
    public function help(Application $application, $bundleType, $key = null, $cache = true)
    {
        if ($bundleType instanceof Bundle) {
            $bundleType = $bundleType->type;
        }
        $cache_id = 'entity_bundle_type_' . $bundleType;
        if (!$cache
            || (!$ret = $application->getPlatform()->getCache($cache_id))
        ) {
            try {
                $ret = $application->Entity_BundleTypes_impl($bundleType)->entityBundleTypeInfo();
                $ret['type'] = $bundleType;
            } catch (Exception\IException $e) {
                $application->logDebug($e);
                return;
            }
            if ($cache) {
                // Remove info that are most likely not needed to be cached
                unset($ret['fields'], $ret['displays'], $ret['views']);
                $application->getPlatform()->setCache($ret, $cache_id, 0);
            }
        }
        
        return isset($key) ? (isset($ret[$key]) ? $ret[$key] : null) : $ret;
    }
}