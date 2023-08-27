<?php
namespace SabaiApps\Directories\Component\Payment\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class GatewaysHelper
{
    public function help(Application $application, $useCache = true)
    {
        if (!$useCache
            || (!$gateways = $application->getPlatform()->getCache('payment_gateways'))
        ) {
            $gateways = [];
            foreach ($application->InstalledComponentsByInterface('Payment\IGateways') as $component_name) {
                if (!$application->isComponentLoaded($component_name)) continue;
 
                foreach ($application->getComponent($component_name)->paymentGetGatewayNames() as $gateway_name) {
                    if (!$gateway = $application->getComponent($component_name)->paymentGetGateway($gateway_name)) continue;
                    
                    $gateways[$gateway_name] = $component_name;
                }
            }
            $application->getPlatform()->setCache($gateways, 'payment_gateways');
        }

        return $gateways;
    }
    
    private $_impls = [];

    /**
     * Gets an implementation of Payment\Gateway\IGateway interface for a given gateway name
     * @param Application $application
     * @param string $gateway
     */
    public function impl(Application $application, $gateway, $returnFalse = false)
    {
        if (!isset($this->_impls[$gateway])) {            
            if ((!$gateways = $application->Payment_Gateways())
                || !isset($gateways[$gateway])
                || !$application->isComponentLoaded($gateways[$gateway])
            ) {                
                if ($returnFalse) return false;
                throw new Exception\UnexpectedValueException(sprintf('Invalid payment gateway: %s', $gateway));
            }
            $this->_impls[$gateway] = $application->getComponent($gateways[$gateway])->paymentGetGateway($gateway);
        }

        return $this->_impls[$gateway];
    }
}