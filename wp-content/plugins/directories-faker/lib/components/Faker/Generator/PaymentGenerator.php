<?php
namespace SabaiApps\Directories\Component\Faker\Generator;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;

class PaymentGenerator extends AbstractGenerator
{    
    protected function _fakerGeneratorInfo()
    {
        $info = parent::_fakerGeneratorInfo();
        switch ($this->_name) {
            case 'payment_plan':
                $info += array(
                    'default_settings' => array(
                        'probability' => 70,
                        'days' => array('min' => 0, 'max' => 365),
                    ),
                );
                break;
        }
        
        return $info;
    }
    
    public function fakerGeneratorSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        switch ($this->_name) {
            case 'payment_plan':
                $plans = $this->_application->Payment_Plans($field->Bundle->name, 'base', true);
                foreach (array_keys($plans) as $plan_id) {
                    $plans[$plan_id] = $plans[$plan_id]->paymentPlanTitle();
                }
                return array(
                    'probability' => $this->_getProbabilitySettingForm($settings['probability']),
                    'plans' => array(
                        '#type' => 'checkboxes',
                        '#title' => __('Payment Plans', 'directories-faker'),
                        '#options' => $plans,
                        '#default_value' => array_keys($plans),
                    ),
                    'days' => array(
                        '#type' => 'range',
                        '#title' => __('Duration', 'directories-faker'),
                        '#min_value' => 0,
                        '#max_value' => 365,
                        '#default_value' => $settings['days'],
                        '#description' => __('Select the range of possible durations for the field.', 'directories-faker'),
                    ),
                );
        }
    }
    
    public function fakerGeneratorGenerate(Field\IField $field, array $settings, array &$values, array &$formStorage)
    {
        switch ($this->_name) {
            case 'payment_plan':
                if (empty($settings['plans'])
                    || mt_rand(0, 100) > $settings['probability']
                ) return;
                
                if (empty($settings['days']['min']) && empty($settings['days']['max'])) {
                    $never_expires = true;
                }
                
                return array(array(
                    'plan_id' => $settings['plans'][array_rand($settings['plans'])],
                    'expires_at' => empty($never_expires) ? time() + (86400 * mt_rand($settings['days']['min'], $settings['days']['max'])) : 0,
                ));
        }
    }
    
    public function fakerGeneratorSupports(Entity\Model\Bundle $bundle, Field\IField $field)
    {
        switch ($this->_name) {
            case 'payment_plan':
                return !empty($bundle->info['payment_enable']);
        }
        return true;
    }
}