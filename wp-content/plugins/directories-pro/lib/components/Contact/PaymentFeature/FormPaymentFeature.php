<?php
namespace SabaiApps\Directories\Component\Contact\PaymentFeature;

use SabaiApps\Directories\Component\Payment;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

class FormPaymentFeature extends Payment\Feature\AbstractFeature implements Payment\Feature\IAddonFeature
{    
    protected function _paymentFeatureInfo()
    {
        return [
            'label' => __('Contact Form', 'directories-pro'),
            'weight' => 99,
            'default_settings' => [
                'enable' => false,
                'recipients' => ['author'],
            ],
        ];
    }
    
    public function paymentFeatureSupports(Bundle $bundle, $planType = 'base')
    {
        return (bool)$this->_application->Entity_BundleTypeInfo($bundle, 'contact_enable');
    }
    
    public function paymentFeatureSettingsForm(Bundle $bundle, array $settings, $planType = 'base', array $parents = [])
    {
        return $this->_getSettingsForm($bundle, $settings, $parents);
    }
    
    protected function _getSettingsForm(Bundle $bundle, array $settings, array $parents, $horizontal = true)
    {
        $recipient_options = $this->_application->getComponent('Contact')->getRecipientOptions($bundle);
        return array(
            'enable' => array(
                '#title' => __('Enable contact form', 'directories-pro'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['enable']),
                '#horizontal' => $horizontal,
            ),
            'recipients' => array(
                '#title' => __('Contact form recipients', 'directories-pro'),
                '#type' => 'checkboxes',
                '#options' => $recipient_options[0],
                '#options_disabled' => $recipient_options[1],
                '#default_value' => isset($settings['recipients']) ? $settings['recipients'] : null,
                '#horizontal' => $horizontal,
                '#columns' => 1,
                '#states' => array(
                    'visible' => array(
                        sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, array('enable')))) => array('type' => 'checked', 'value' => true),
                    ),
                ),
            ),
        );
    }
    
    public function paymentFeatureIsEnabled(Bundle $bundle, array $settings)
    {
        return !empty($settings['enable']);
    }
    
    public function paymentFeatureRender(Bundle $bundle, array $settings, $planType = null)
    {
        return array(array('icon' => 'fas fa-envelope', 'html' => $this->_application->H(__('Contact Form', 'directories-pro'))));
    }
    
    public function paymentFeatureApply(IEntity $entity, Payment\Model\Feature $feature, array &$values, $isAddon = false)
    {
        if (!$isAddon) return;

        return $this->_applyAddonFeature($entity, $feature, $values);
    }

    public function paymentFeatureUnapply(IEntity $entity, Payment\Model\Feature $feature, array &$values, $isAddon = false)
    {
        if (!$isAddon) return;

        return $this->_unapplyAddonFeature($entity, $feature, $values);
    }
    
    public function paymentAddonFeatureSupports(Bundle $bundle)
    {
        return $this->paymentFeatureSupports($bundle);
    }
    
    public function paymentAddonFeatureSettingsForm(Bundle $bundle, array $settings, array $parents = [])
    {
        return $this->_getSettingsForm($bundle, $settings, $parents);
    }
    
    public function paymentAddonFeatureExtraSettingsForm(Bundle $bundle, array $planFeatures, array $currentExtraFeatures, array $parents = [])
    {
        return $this->_getSettingsForm($bundle, $currentExtraFeatures, $parents, false);
    }
        
    public function paymentAddonFeatureIsEnabled(Bundle $bundle, array $settings)
    {
        return empty($settings['enable']) ? false: $settings;
    }
    
    public function paymentAddonFeatureIsOrderable(array $settings, array $currentFeatures)
    {
        return $this->paymentAddonFeatureExtraIsOrderable($currentFeatures);
    }

    public function paymentAddonFeatureExtraIsOrderable(array $planFeatures)
    {
        // Can't order if already enabled
        return !isset($planFeatures[$this->_name]);
    }
}