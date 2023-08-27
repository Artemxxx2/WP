<?php
namespace SabaiApps\Directories\Component\Payment\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\WooCommerce\IProduct;

class CalculateFeesHelper
{
    public function help(Application $application, Entity\Type\IEntity $entity, IProduct $product, $action)
    {
        switch ($action) {
            case 'submit':
                if ($product->get_sabai_plan_type() !== 'base') return;

                $config = $this->_getPaymentConfig($application, 'renewal');
                if (empty($config['reactivation_fee'])
                    || (!$deactivated_at = $entity->getSingleFieldValue('payment_plan', 'deactivated_at'))
                ) return;
                
                return array(
                    'reactivation_fee' => array(
                        'amount' => $config['reactivation_fee'],
                        'label' => __('Re-activation fee', 'directories-payments'),
                    ),
                );
            case 'upgrade':
                if ($product->get_sabai_plan_type() !== 'base') return;

                $config = $this->_getPaymentConfig($application, 'upgrade');
                if ((!$new_plan_id = $product->get_id())
                    || (empty($config['switch_plan_fee']) && empty($config['prorated_discount']))
                    || (!$payment_plan_value = $entity->getSingleFieldValue('payment_plan'))
                    || empty($payment_plan_value['plan_id']) // current plan ID
                    || $payment_plan_value['plan_id'] == $new_plan_id // same plan
                ) return;
                
                $ret = [];
                if (!empty($config['switch_plan_fee'])) {
                    $ret['switch_plan_fee'] = array(
                        'amount' => $config['switch_plan_fee'],
                        'label' => __('Switch plan fee', 'directories-payments'),
                    );
                }
                if (!empty($config['prorated_discount'])) {
                    // Discount if previous plan price is set and has not yet expired
                    if ($discount = $this->_canDiscount($application, $payment_plan_value)) {
                        $ret['prorated_discount'] = array(
                            'amount' => $discount,
                            'label' => __('Prorated discount', 'directories-payments'),
                            'is_discount' => true,
                        );
                    }
                }
                
                return $ret;
        }
    }
    
    protected function _canDiscount(Application $application, array $currentPlan)
    {
        $ret = false;
        if (!empty($currentPlan['extra_data']['total']) // has previously paid amount
            && !empty($currentPlan['extra_data']['duration']) // has expiration date
            && (0 < $time_left = $currentPlan['expires_at'] - time()) // has valid time left
            && $time_left < ($duration_in_seconds = $currentPlan['extra_data']['duration'] * 86400)
        ) {
            $ret = $currentPlan['extra_data']['total'] * ($time_left / $duration_in_seconds);
        }
        
        // Let other apps filter
        return $application->Filter('payment_prorated_discount', $ret, array(
            $currentPlan['plan_id'], // current plan ID
            isset($currentPlan['extra_data']['total']) ? $currentPlan['extra_data']['total'] : null, // amount paid already for the current plan
            isset($currentPlan['extra_data']['duration']) ? $currentPlan['extra_data']['duration'] : null, // duration of current plan in seconds
            $currentPlan['expires_at'] // timestamp of current plan expiration date 
        ));
    }
    
    protected function _getPaymentConfig(Application $application, $key = null)
    {
        return $application->getComponent('Payment')->getConfig($key);
    }
}