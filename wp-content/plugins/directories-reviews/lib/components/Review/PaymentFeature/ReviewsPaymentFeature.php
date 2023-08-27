<?php
namespace SabaiApps\Directories\Component\Review\PaymentFeature;

use SabaiApps\Directories\Component\Payment;
use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Entity\Model\Bundle;

class ReviewsPaymentFeature extends Payment\Feature\AbstractFeature implements Payment\Feature\IAddonFeature
{    
    protected function _paymentFeatureInfo()
    {
        return [
            'label' => _x('Reviews', 'payment feature', 'directories-reviews'),
            'weight' => 50,
            'default_settings' => [
                'enable' => false,
            ],
        ];
    }
    
    public function paymentFeatureSupports(Bundle $bundle, $planType = 'base')
    {
        return !empty($bundle->info['review_enable']);
    }
    
    public function paymentFeatureSettingsForm(Bundle $bundle, array $settings, $planType = 'base', array $parents = [])
    {
        return $this->_getSettingsForm($settings);
    }

    protected function _getSettingsForm(array $settings, $horizontal = true)
    {
        return [
            'enable' => [
                '#title' => __('Enable reviews', 'directories-reviews'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['enable']),
                '#horizontal' => $horizontal,
            ],
        ];
    }

    public function paymentFeatureIsEnabled(Bundle $bundle, array $settings)
    {
        return !empty($settings['enable']);
    }

    public function paymentFeatureRender(Bundle $bundle, array $settings, $planType = null)
    {
        return array(array('icon' => 'fas fa-star', 'html' => $this->_application->H(_x('Reviews', 'payment feature', 'directories-reviews'))));
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
        return $this->_getSettingsForm($settings);
    }

    public function paymentAddonFeatureExtraSettingsForm(Bundle $bundle, array $planFeatures, array $currentExtraFeatures, array $parents = [])
    {
        return $this->_getSettingsForm($currentExtraFeatures, false);
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