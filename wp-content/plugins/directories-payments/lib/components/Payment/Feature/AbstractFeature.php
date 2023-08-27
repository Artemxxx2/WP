<?php
namespace SabaiApps\Directories\Component\Payment\Feature;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Payment\Model\Feature;
use SabaiApps\Directories\Component\Payment\IPlan;

abstract class AbstractFeature implements IFeature
{
    protected $_application, $_name;
    
    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
    }
    
    abstract protected function _paymentFeatureInfo();
    
    public function paymentFeatureInfo($key = null)
    {
        $info = $this->_paymentFeatureInfo();
        return isset($key) ? (isset($info[$key]) ? $info[$key] : null) : $info;
    }
    
    public function paymentFeatureSettings(Entity\Model\Bundle $bundle, $planType = 'base')
    {
        return false; // use default_settings in info array
    }
    
    public function paymentFeatureSupports(Entity\Model\Bundle $bundle, $planType = 'base')
    {
        return true;
    }
    
    public function paymentFeatureSettingsForm(Entity\Model\Bundle $bundle, array $settings, $planType = 'base', array $parents = []){}
        
    public function paymentFeatureIsEnabled(Entity\Model\Bundle $bundle, array $settings)
    {
        return true;
    }
    
    public function paymentFeatureOnEntityForm(Entity\Model\Bundle $bundle, array $settings, array &$form, Entity\Type\IEntity $entity = null, $isAdmin = false){}
    
    public function paymentFeatureOnClaimEntityForm(Entity\Model\Bundle $bundle, array $settings, array &$form, Entity\Type\IEntity $entity){}
    
    public function paymentFeatureOnAdded(Entity\Type\IEntity $entity, Feature $feature, array $settings, IPlan $plan, array &$values)
    {
        $feature->addMetas($settings);
    }
        
    public function paymentFeatureApply(Entity\Type\IEntity $entity, Feature $feature, array &$values, $isAddon = false)
    {
        return true;
    }
    
    public function paymentFeatureUnapply(Entity\Type\IEntity $entity, Feature $feature, array &$values, $isAddon = false)
    {
        return true;
    }
    
    public function paymentFeatureRender(Entity\Model\Bundle $bundle, array $settings, $planType = null){}
        
    public function isFieldRequired($form, $parents, $dependee = 'enable')
    {
        $values = $form->getValue($parents);
        return !empty($values[$dependee]);
    }
    
    protected function _applyAddonFeature(Entity\Type\IEntity $entity, Feature $feature, array &$values, $isMultiple = false)
    {
        if (!$entity->getSingleFieldValue('payment_plan', 'plan_id')) return; // needs a plan associated in order to apply add-ons

        $metas = $feature->getMetas();
        if (isset($values['payment_plan']['addon_features'][$this->_name])) {
            if ($isMultiple) {
                foreach (array_keys($metas) as $key) {
                    if (!isset($values['payment_plan']['addon_features'][$this->_name][$key])) continue;

                    $this->_doApplyAddonFeature($metas[$key], $values['payment_plan']['addon_features'][$this->_name][$key], $feature->id);
                }
            } else {
                $this->_doApplyAddonFeature($metas, $values['payment_plan']['addon_features'][$this->_name], $feature->id);
            }
        }
        $values['payment_plan']['addon_features'][$this->_name] = $metas;
        
        return true;
    }

    protected function _doApplyAddonFeature(&$metas, array $value, $featureId)
    {
        if (!empty($value['num'])) {
            if (empty($metas['num'])) {
                $metas['num'] = 0;
            }
            $metas['num'] += $value['num'];
        }
        if (!empty($value['unlimited'])) {
            $metas['unlimited'] = true;
        } else {
            if (!empty($metas['unlimited'])) {
                $metas['unlimited_by'] = $featureId;
            }
        }
    }

    protected function _unapplyAddonFeature(Entity\Type\IEntity $entity, Feature $feature, array &$values, $isMultiple = false)
    {
        if (!$entity->getSingleFieldValue('payment_plan', 'plan_id')) return; // needs a plan associated in order to apply add-ons

        if (isset($values['payment_plan']['addon_features'][$this->_name])) {
            $metas = $feature->getMetas();
            if ($isMultiple) {
                foreach (array_keys($metas) as $key) {
                    if (!isset($values['payment_plan']['addon_features'][$this->_name][$key])) continue;

                    $this->_doUnApplyAddonFeature($metas[$key], $values['payment_plan']['addon_features'][$this->_name][$key], $feature->id);
                }
            } else {
                $this->_doUnApplyAddonFeature($metas, $values['payment_plan']['addon_features'][$this->_name], $feature->id);
            }
        }

        return true;
    }

    protected function _doUnApplyAddonFeature(&$metas, array $value, $featureId)
    {
        if (!empty($value['num'])
            && !empty($metas['num'])
        ) {
            $value['num'] -= $metas['num'];
            if ($value['num'] < 0) {
                unset($value['num']);
            }
        }
        if (!empty($value['unlimited'])
            && !empty($metas['unlimited'])
        ) {
            if (!empty($value['unlimited_by'])) {
                if ($value['unlimited_by'] == $featureId) {
                    unset($value['unlimited']);
                }
            } else {
                unset($value['unlimited']);
            }
        }
    }
    
    protected function _maxNumAllowedLabel($label)
    {
        return sprintf(__('Max number of %s allowed', 'directories-payments'), strtolower($label), $label);
    }
    
    protected function _additionalNumAllowedLabel($label)
    {
        return sprintf(__('Additional number of %s allowed', 'directories-payments'), strtolower($label), $label);
    }
}