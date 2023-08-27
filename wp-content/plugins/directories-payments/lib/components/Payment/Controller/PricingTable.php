<?php
namespace SabaiApps\Directories\Component\Payment\Controller;

use SabaiApps\Directories\Controller;
use SabaiApps\Directories\Context;

class PricingTable extends Controller
{
    protected function _doExecute(Context $context)
    {
        $settings = $this->_getSettings($context);
        $plans = $this->Payment_Plans($context->bundle->name, $this->Filter('payment_base_plan_types', ['base'], [$context->bundle->name]), true);
        // Specific plans only?
        if (isset($settings['plans'])) {
            if (is_string($settings['plans'])) {
                $settings['plans'] = explode(',', trim($settings['plans']));
            }
            if (!empty($settings['plans'])) {
                $_plans = [];
                // Filter and sort as specified
                foreach ($settings['plans'] as $plan_id) {
                    if (isset($plans[$plan_id])) {
                        $_plans[$plan_id] = $plans[$plan_id];
                    }
                }
                $plans = $_plans;
            }
        }
        
        $max_feature_count = 0;
        $has_featured = false;
        foreach (array_keys($plans) as $plan_id) {
            $plan = $plans[$plan_id];
            $features = $this->Payment_Features_render($plan->paymentPlanFeatures(), $context->bundle, $plan->paymentPlanType());
            $feature_count = array_sum(array_map('count', $features));
            if ($feature_count > $max_feature_count) {
                $max_feature_count = $feature_count;
            }
            if (!$has_featured) {
                $has_featured = $plan->paymentPlanIsFeatured();
            }
            $plans[$plan_id] = array(
                'title' => $plan->paymentPlanTitle(),
                'description' => $plan->paymentPlanDescription(),
                'price' => $plan->paymentPlanPrice(true),
                'featured' => $plan->paymentPlanIsFeatured(),
                'features' => $features,
                'order_url' => $this->Url(
                    '/' . $this->FrontendSubmit_AddEntitySlug($context->bundle),
                    array('bundle' => $context->bundle->name, 'plan' => $plan_id)
                ),
            );
        }

        // Add no payment plan option?
        if ($settings['add_no_payment_plan'] !== false) {
            $default_features = [];
            if (!empty($context->bundle->info['payment_default_features']['enabled'])) {
                $default_features = $context->bundle->info['payment_default_features']['enabled'];
            }
            $features = $this->Payment_Features_render($default_features, $context->bundle);
            $feature_count = array_sum(array_map('count', $features));
            if ($feature_count > $max_feature_count) {
                $max_feature_count = $feature_count;
            }
            $no_payment_plan_label = $this->Payment_Plans_noPaymentPlanLabel();
            $no_payment_plan = [
                'title' => isset($settings['no_payment_plan_title']) ? $settings['no_payment_plan_title'] : $no_payment_plan_label[0],
                'description' => isset($settings['no_payment_plan_desc']) ? $settings['no_payment_plan_desc'] : $no_payment_plan_label[1],
                'price' => _x('Free', 'payment plan price', 'directories-payments'),
                'featured' => false,
                'features' => $features,
                'order_url' => $this->Url(
                    '/' . $this->FrontendSubmit_AddEntitySlug($context->bundle),
                    array('bundle' => $context->bundle->name, 'plan' => 0)
                ),
            ];
            if ($settings['add_no_payment_plan'] === 0) {
                $plans = [0 => $no_payment_plan] + $plans; // prepend
            } else {
                $plans[0] = $no_payment_plan; // append
            }
        }

        $context->addTemplate($this->getPlatform()->getAssetsDir('directories-payments') . '/templates/payment_pricing_table')
            ->setAttributes(array(
                'plans' => $this->Filter('payment_pricing_table_plans', $plans, [$context->bundle]),
                'max_feature_count' => $max_feature_count,
                'has_featured' => $has_featured,
            ) + $settings);
    }
    
    protected function _getSettings(Context $context)
    {   
        $settings = $context->settings ?: [];
        $settings += array(
            'plans' => null,
            'layout' => 'group',
            'color' => 'primary',
            'btn_text' => null,
        );
        $settings += array(
            'btn_color' => in_array($settings['color'], array('dark', 'light')) ? 'outline-secondary' : 'outline-' . $settings['color'],
            'featured_text_color' => in_array($settings['color'], array('dark', 'light')) ? 'secondary' : 'white',
            'featured_border_color' => $settings['color'],
            'featured_bg_color' => $settings['color'],
            'featured_btn_color' => in_array($settings['color'], array('dark', 'light')) ? 'outline-secondary' : 'outline-light',
        );
        if (!strlen($settings['btn_text'])) {
            $settings['btn_text'] = __('Choose Plan', 'directories-payments');
        }
        if (!in_array($settings['layout'], array('group', 'deck'))) {
            $settings['layout'] = 'group';
        }
        // Add no payment plan option?
        if ($this->Payment_Plans_noPaymentPlanEnabled($context->bundle->name)
            && isset($settings['add_no_payment_plan'])
        ) {
            $settings['add_no_payment_plan'] = (int)$settings['add_no_payment_plan'];
        } else {
            $settings['add_no_payment_plan'] = false;
        }
        
        return $settings;
    }
}