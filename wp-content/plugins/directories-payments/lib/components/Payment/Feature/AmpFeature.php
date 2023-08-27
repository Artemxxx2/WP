<?php
namespace SabaiApps\Directories\Component\Payment\Feature;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Payment\Model\Feature;

class AmpFeature extends AbstractFeature implements IAddonFeature
{    
    protected function _paymentFeatureInfo()
    {
        return array(
            'label' => __('AMP (Accelarated Mobile Pages) Settings', 'directories-payments'),
            'weight' => 99,
            'default_settings' => array(
                'enable' => false,
            ),
        );
    }
    
    public function paymentFeatureSupports(Entity\Model\Bundle $bundle, $planType = 'base')
    {
        return empty($bundle->info['is_taxonomy'])
            && !empty($bundle->info['public'])
            && $this->_application->getPlatform()->isAmpEnabled($bundle->name);
    }
    
    public function paymentFeatureSettingsForm(Entity\Model\Bundle $bundle, array $settings, $planType = 'base', array $parents = [])
    {
        return $this->_getSettingsForm($bundle, $settings, $parents);
    }
    
    protected function _getSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents, $horizontal = true)
    {
        return array(
            'enable' => array(
                '#title' => __('Enable AMP', 'directories-payments'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['enable']),
                '#horizontal' => $horizontal,
            ),
        );
    }
    
    public function paymentFeatureIsEnabled(Entity\Model\Bundle $bundle, array $settings)
    {
        return $this->_application->getPlatform()->isAmpEnabled($bundle->name) && !empty($settings['enable']);
    }
    
    public function paymentFeatureRender(Entity\Model\Bundle $bundle, array $settings, $planType = null)
    {
        return array(array('icon' => 'fas fa-bolt', 'html' => $this->_application->H(__('AMP enabled', 'directories-payments'))));
    }
    
    public function paymentFeatureApply(Entity\Type\IEntity $entity, Feature $feature, array &$values, $isAddon = false)
    {
        if (!$isAddon) return;

        return $this->_applyAddonFeature($entity, $feature, $values);
    }

    public function paymentFeatureUnapply(Entity\Type\IEntity $entity, Feature $feature, array &$values, $isAddon = false)
    {
        if (!$isAddon) return;

        return $this->_unapplyAddonFeature($entity, $feature, $values);
    }
    
    public function paymentAddonFeatureSupports(Entity\Model\Bundle $bundle)
    {
        return $this->paymentFeatureSupports($bundle);
    }
    
    public function paymentAddonFeatureSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = [])
    {
        return $this->_getSettingsForm($bundle, $settings, $parents);
    }
    
    public function paymentAddonFeatureExtraSettingsForm(Entity\Model\Bundle $bundle, array $planFeatures, array $currentExtraFeatures, array $parents = [])
    {
        return $this->_getSettingsForm($bundle, $currentExtraFeatures, $parents, false);
    }
        
    public function paymentAddonFeatureIsEnabled(Entity\Model\Bundle $bundle, array $settings)
    {
        return empty($settings['enable']) ? false: $settings;
    }

    public function paymentAddonFeatureIsOrderable(array $settings, array $currentFeatures)
    {
        // Can't order if already enabled
        return !isset($currentFeatures[$this->_name]);
    }
    
    public function paymentAddonFeatureExtraIsOrderable(array $planFeatures)
    {
        // Can't order if already enabled
        return !isset($planFeatures[$this->_name]);
    }
}