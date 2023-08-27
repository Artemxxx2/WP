<?php
namespace SabaiApps\Directories\Component\Payment\Feature;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Payment\Model\Feature;
use SabaiApps\Directories\Component\Payment\IPlan;

class PlanFeature extends AbstractFeature
{    
    protected function _paymentFeatureInfo()
    {
        return [
            'label' => __('Plan Duration Settings', 'directories-payments'),
            'weight' => -1,
            'default_settings' => [
                'duration_unlimited' => false,
                'duration' => 365,
            ],
        ];
    }
    
    public function paymentFeatureSettingsForm(Entity\Model\Bundle $bundle, array $settings, $planType = 'base', array $parents = [])
    {
        if ($planType !== 'base') return;

        $max_duration = $this->_application->Filter('payment_feature_plan_max_duration', 365, [$bundle, $planType]);
        return [
            'duration_unlimited' => [
                '#title' => __('Duration in days', 'directories-payments'),
                '#on_label' => __('Unlimited', 'directories-payments'),
                '#type' => 'checkbox',
                '#switch' => false,
                '#default_value' => !empty($settings['duration_unlimited']),
                '#horizontal' => true,
            ],
            'duration' => [
                '#type' => 'slider',
                '#default_value' => isset($settings['duration']) ? $settings['duration'] : $max_duration,
                '#integer' => true,
                '#field_suffix' => __('day(s)', 'directories-payments'),
                '#min_value' => 1,
                '#max_value' => $max_duration,
                '#step' => $max_duration > 365 ? 5 : 1,
                '#states' => [
                    'invisible' => [
                        sprintf('input[name="%s[duration_unlimited][]"]', $this->_application->Form_FieldName($parents)) => ['type' => 'checked', 'value' => true],
                    ],
                ],  
                '#horizontal' => true,
            ],
        ];
    }
    
    public function paymentFeatureOnAdded(Entity\Type\IEntity $entity, Feature $feature, array $settings, IPlan $plan, array &$values)
    {
        $meta = [
            'plan_id' => $plan->paymentPlanId(),
            'duration' => $duration = (empty($settings['duration_unlimited']) && !empty($settings['duration']) ? (int)$settings['duration'] : 0),
            'extra_data' => [
                'duration' => $duration,
                'total' => round($plan->paymentPlanTotal(), 2),
            ],
        ];
        if ($current = $entity->getSingleFieldValue('payment_plan')) {
            $meta['prev_value'] = $current;
        }
        
        $feature->addMetas($meta);
    }

    public function paymentFeatureApply(Entity\Type\IEntity $entity, Feature $feature, array &$values, $isAddon = false)
    {        
        if (!$plan_id = $feature->getMeta('plan_id')) return false;

        if ($duration = $feature->getMeta('duration')) {
            if (($prev_value = $feature->getMeta('prev_value'))
                && isset($prev_value['expires_at'])
                && $prev_value['expires_at'] > time()
            ) {
                $expires_at = $prev_value['expires_at'];
            } else {
                $expires_at = time();
            }
            $expires_at += 86400 * $duration;
        } else {
            $expires_at = 0;
        }

        $values['payment_plan'] = $this->_application->Filter(
            'payment_plan_value',
            [
                'plan_id' => $plan_id,
                'expires_at' => $expires_at,
                'extra_data' => ['featuregroup_id' => $feature->featuregroup_id] + $feature->getMeta('extra_data'),
                'deactivated_at' => 0,
            ],
            [$entity, $feature]
        );
                
        return true;
    }
    
    public function paymentFeatureUnapply(Entity\Type\IEntity $entity, Feature $feature, array &$values, $isAddon = false)
    {
        // Revert back to previously configured payment plan values if any
        if ($prev_value = $feature->getMeta('prev_value')) {
            $values['payment_plan'] = $prev_value;
        } else {
            $values['payment_plan'] = false;
        }
        
        return true;
    }

    public function paymentFeatureRender(Entity\Model\Bundle $bundle, array $settings, $planType = null)
    {
        if ($planType !== 'base') return;

        if (empty($settings['duration_unlimited']) && !empty($settings['duration'])) {
            $label = sprintf(
                $this->_application->H(__('Duration: %s', 'directories-payments')),
                '<em>' . sprintf($this->_application->H(_n('%s Day', '%s Days', $settings['duration'], 'directories-payments')), $settings['duration']) . '</em>'
            );
        } else {
            $label = sprintf(
                $this->_application->H(__('Duration: %s', 'directories-payments')),
                '<em>' . __('Unlimited', 'directories-payments') . '</em>'
            );
        }
        return [[
            'icon' => 'fas fa-calendar-alt',
            'html' => $label,
            'settings' => $settings,
        ]];
    }
}