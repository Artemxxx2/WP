<?php
namespace SabaiApps\Directories\Component\WooCommerce\Helper;

use SabaiApps\Directories\Application;

class ProductTypesHelper
{
    public function help(Application $application, $type = null, $force = false)
    {
        if ($force
            || (!$product_types = $application->getPlatform()->getCache('woocommerce_product_types'))
        ) {
            $product_types = ['base' => ['name' => []], 'addon' => ['name' => []]];
            $base_labels = [];
            foreach ($application->Entity_BundleTypes_byFeatures(array('payment_enable')) as $bundle_types) {
                foreach ($application->Entity_Bundles_byType($bundle_types) as $bundle) {
                    if (!$application->isComponentLoaded($bundle->component)
                        || empty($bundle->info['payment_enable'])
                    ) continue;

                    $label = get_post_type_object($bundle->name)->labels->singular_name;
                    $product_types['base']['name']['drts_' . $bundle->name] = $label . ' ' . __('(Base plan)', 'directories-payments');
                    $product_types['addon']['name']['drts_' . $bundle->name . '__addon'] = $label . ' ' . __('(Add-on plan)', 'directories-payments');
                    $base_labels['drts_' . $bundle->name] = $label;
                }
            }
            $product_types = $application->Filter('woocommerce_product_types', $product_types, [$base_labels]);
            asort($product_types['base']['name']);
            asort($product_types['addon']['name']);
            $application->getPlatform()->setCache($product_types, 'woocommerce_product_types', 0);
        }
        
        return isset($type) ? (array)@$product_types[$type] : $product_types;
    }
}
