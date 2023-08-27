<?php
namespace SabaiApps\Directories\Component\Payment\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Component\Payment;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

class PlansHelper
{
    protected $_plans = [];
    
    public function help(Application $application, $bundleName, $type = null, $purchasableOnly = false, $lang = null, $throwError = false)
    {        
        if (!isset($this->_plans[$bundleName])) {
            $this->_plans[$bundleName] = [];
            
            if ($payment_component = $application->getComponent('Payment')->getPaymentComponent($throwError)) {
                foreach ($payment_component->paymentGetPlanIds($bundleName, $lang) as $plan_id) {
                    if (!$plan = $payment_component->paymentGetPlan($plan_id)) continue;

                    if ($purchasableOnly
                        && !$plan->paymentPlanIsPurchasable()
                    ) continue;

                    $this->_plans[$bundleName][$plan_id] = $plan;
                }
            }
        }
        if (!isset($type)) return $this->_plans[$bundleName];
        
        $ret = [];
        settype($type, 'array');
        foreach (array_keys($this->_plans[$bundleName]) as $plan_id) {
            $plan = $this->_plans[$bundleName][$plan_id];
            if (in_array($plan->paymentPlanType(), $type)) {
                $ret[$plan_id] = $plan;
            }
        }
        return $ret;
    }
    
    public function orderableAddons(Application $application, $entityOrPlan)
    {   
        $current_features = [];
        if ($entityOrPlan instanceof IEntity) {
            if (!$plan = $application->Payment_Plan($entityOrPlan)) {
                throw new Exception\RuntimeException('Could not fetch payment plan from entity');
            }
            $current_features = (array)$entityOrPlan->getSingleFieldValue('payment_plan', 'addon_features');
        } elseif ($entityOrPlan instanceof Payment\IPlan) {
            $plan = $entityOrPlan;
        } else {
            throw new Exception\InvalidArgumentException('Invalid 2nd argument for ' . __CLASS__ . '::' . __METHOD__);
        }
        $current_features += $plan->paymentPlanFeatures();
        
        $ret = [];
        if ($plans = $this->help($application, $plan->paymentPlanBundleName(), 'addon')) {
            foreach ($plans as $plan_id => $addon_plan) {
                if (!$addon_plan->paymentPlanIsPurchasable()) continue;

                foreach ((array)$addon_plan->paymentPlanFeatures() as $feature_name => $feature_settings) {                
                    if (($feature = $application->Payment_Features_impl($feature_name, true))
                        && $feature instanceof Payment\Feature\IAddonFeature
                        && $feature->paymentAddonFeatureIsOrderable($feature_settings, $current_features)
                    ) {
                        $ret[$plan_id] = $addon_plan;
                        continue 2; // the plan is orderable, so skip other features
                    }
                }
            }
        }
        
        return $ret;
    }
    
    public function form(Application $application, $entityOrBundleName, $type = 'base', $excludeCurrent = false, $pricingTable = false, $addFreePlan = true)
    {
        $plan_descriptions = $plans_disabled = [];
        $current_plan_id = null;
        if (!is_array($type)
            && $type === 'addon'
        ) {
            try {
                if (!$entityOrBundleName instanceof IEntity) {
                    throw new Exception\InvalidArgumentException('Invalid 1st argument for ' . __CLASS__ . '::' . __METHOD__);
                }
                $bundle_name = $entityOrBundleName->getBundleName();
                $plans = $this->orderableAddons($application, $entityOrBundleName);
            } catch (Exception\IException $e) {
                $plans = [];
                $application->logError($e);
            }
            foreach (array_keys($plans) as $plan_id) {
                $plan = $plans[$plan_id];
                $plan_descriptions[$plan_id] = $plan->paymentPlanDescription();
                $plans[$plan_id] = $application->H($plan->paymentPlanTitle()) . ' - ' . $plan->paymentPlanPrice(true);
            }
        } else {
            if ($entityOrBundleName instanceof IEntity) {
                $bundle_name = $entityOrBundleName->getBundleName();
                $current_plan_id = $entityOrBundleName->getSingleFieldValue('payment_plan', 'plan_id');
            } else {
                $bundle_name = $entityOrBundleName;
            }
            $plans = $this->help($application, $bundle_name, $type);
            foreach (array_keys($plans) as $plan_id) {                
                $plan_descriptions[$plan_id] = $plans[$plan_id]->paymentPlanDescription();
                $title = $application->H($plans[$plan_id]->paymentPlanTitle());
                if ($current_plan_id
                    && $current_plan_id === $plan_id
                ) {
                    if ($excludeCurrent) {
                        $plans_disabled[] = $plan_id;
                    }
                    $title .= '<strong> — <span class="drts-payment-select-plan-state">'
                        . $application->H(__('Current plan', 'directories-payments'))
                        . '</span></strong>';
                } else {
                    if (!$plans[$plan_id]->paymentPlanIsPurchasable()
                        || (!$addFreePlan && $plans[$plan_id]->paymentPlanIsFree())
                    ) {
                        unset($plans[$plan_id]);
                        continue;
                    }

                    $title .= ' - ' . $plans[$plan_id]->paymentPlanPrice(true);
                }
                if ($plans[$plan_id]->paymentPlanIsFeatured()) {
                    $title = '<span class="drts-payment-select-plan-featured">' . $title . '</span>';
                }
                $plans[$plan_id] = $title;
            }

            if ($addFreePlan
                && $this->noPaymentPlanEnabled($application, $bundle_name)
            ) {
                $no_payment_plan_label = $this->noPaymentPlanLabel($application);
                $plans[0] = $no_payment_plan_label[0];
                if (strlen($no_payment_plan_label[1])) {
                    $plan_descriptions[0] = $no_payment_plan_label[1];
                }
            }
        }

        if (!empty($plans)) {
            $plans = $application->Filter('payment_plans_form', $plans, [$bundle_name, $type]);
        }
        
        if (empty($plans)) {
            return array(
                '#header' => array(
                    '<div class="' . DRTS_BS_PREFIX . 'alert ' . DRTS_BS_PREFIX . 'alert-warning">'
                        . $application->H(__('There are currently no payment plans available.', 'directories-payments'))
                        . '</div>'
                ),
            );
        }

        if ($pricingTable) {
            $bundle = $application->Entity_Bundle($entityOrBundleName);
            $pricing_table_settings = [
                'plans' => array_keys($plans),
                'add_no_payment_plan' => isset($plans[0]),
                'no_payment_plan_title' => isset($plans[0]) ? $plans[0] : null,
                'no_payment_plan_desc' => isset($plan_descriptions[0]) ? $plan_descriptions[0] : null,
            ];
            if (is_array($pricingTable)) {
                $pricing_table_settings += $pricingTable;
            }
            return [
                '#class' => 'drts-payment-pricing-table-form',
                'pricing_table' => [
                    '#type' => 'markup',
                    '#markup' => $application->getPlatform()->render($bundle->getPath() . '/pricing', ['settings' => $pricing_table_settings]),
                    '#js_ready' => '$("#__FORM_ID__").on("click", ".drts-payment-plan-footer a", function(){
    $("#__FORM_ID__").append($("<input>", {
        type: "hidden",
        name: "plan",
        value: $(this).closest(".drts-payment-plan").data("plan-id")
    })).append($("<input>", {
        type: "hidden",
        name: "bundle",
        value: "' . $bundle->name . '"
    })).find("button[value=select_plan]").click();
    return false;
});',
                ],
            ];
        }
        
        return [
            'plan' => [
                '#type' => 'radios',
                '#columns' => 1,
                '#title' => __('Select Plan', 'directories-payments'),
                '#required' => true,
                '#options' => $plans,
                '#options_description' => $plan_descriptions,
                '#options_disabled' => $plans_disabled,
                '#option_no_escape' => true,
                '#default_value' => $current_plan_id,
                '#default_value_auto' => empty($current_plan_id),
                '#class' => 'drts-payment-select-plan',
                '#horizontal' => true,
            ],
        ];
    }

    public function noPaymentPlanLabel(Application $application)
    {
        $none_label = $application->getComponent('Payment')->getConfig('selection', 'none_label');
        $none_label  = $application->System_TranslateString($none_label, 'no_payment_plan_label', 'payment');
        $none_desc = $application->getComponent('Payment')->getConfig('selection', 'none_desc');
        $none_desc  = $application->System_TranslateString($none_desc, 'no_payment_plan_desc', 'payment');

        return [$none_label, $none_desc];
    }

    public function noPaymentPlanEnabled(Application $application, $bundleName)
    {
        return $application->Filter(
            'payment_plans_no_payment_plan_enabled',
            (bool)$application->getComponent('Payment')->getConfig('selection', 'allow_none'),
            [$bundleName]
        );
    }
}