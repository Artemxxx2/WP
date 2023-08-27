<?php
namespace SabaiApps\Directories\Component\DirectoryPro\PaymentFeature;

use SabaiApps\Directories\Component\Payment;
use SabaiApps\Directories\Component\Entity;

class PhotosPaymentFeature extends Payment\Feature\AbstractFeature
    implements Payment\Feature\IAddonFeature
{    
    protected function _paymentFeatureInfo()
    {
        return array(
            'label' => __('Photo Settings', 'directories-pro'),
            'weight' => 9,
            'default_settings' => array(
                'unlimited' => false,
                'num' => 5,
            ),
        );
    }
    
    public function paymentFeatureSupports(Entity\Model\Bundle $bundle, $planType = 'base')
    {
        return $bundle->type === 'directory__listing';
    }
    
    public function paymentFeatureSettingsForm(Entity\Model\Bundle $bundle, array $settings, $planType = 'base', array $parents = [])
    {
        return array(
            'unlimited' => array(
                '#title' => $this->_maxNumAllowedLabel(__('Photos', 'directories-pro')),
                '#on_label' => __('Unlimited', 'directories-pro'),
                '#type' => 'checkbox',
                '#switch' => false,
                '#default_value' => !empty($settings['unlimited']),
                '#horizontal' => true,
            ),
            'num' => array(
                '#type' => 'slider',
                '#default_value' => $settings['num'],
                '#integer' => true,
                '#min_value' => 0,
                '#max_value' => 50,
                '#states' => array(
                    'invisible' => array(
                        sprintf('input[name="%s[unlimited][]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'checked', 'value' => true),
                    ),                       
                ),
                '#horizontal' => true,
            ),
        );
    }
    
    public function paymentFeatureApply(Entity\Type\IEntity $entity, Payment\Model\Feature $feature, array &$values, $isAddon = false)
    {
        if (!$isAddon) return;

        return $this->_applyAddonFeature($entity, $feature, $values);
    }

    public function paymentFeatureUnapply(Entity\Type\IEntity $entity, Payment\Model\Feature $feature, array &$values, $isAddon = false)
    {
        if (!$isAddon) return;

        return $this->_unapplyAddonFeature($entity, $feature, $values);
    }

    public function paymentFeatureOnEntityForm(Entity\Model\Bundle $bundle, array $settings, array &$form, Entity\Type\IEntity $entity = null, $isAdmin = false)
    {
        if ($isAdmin && $this->_application->IsAdministrator()) return; // do not restrict for administrators
        
        if (empty($settings[0]['unlimited'])) {
            $limit = $settings[0]['num'];
            if (!empty($settings[1]['num'])) {
                $limit += $settings[1]['num'];
            }
            
            if (empty($limit)) {
                // No photos allowed
                unset($form['directory_photos']);
                return;
            }
            
            if (empty($form['directory_photos']['#max_num_files'])
                || $limit < $form['directory_photos']['#max_num_files']
            ) {
                $form['directory_photos']['#max_num_files'] = $limit;
            }
        } else {
            $form['directory_photos']['#max_num_files'] = 0;
        }
    }
    
    public function paymentFeatureRender(Entity\Model\Bundle $bundle, array $settings, $planType = null)
    {
        if (empty($settings['unlimited'])) {
            $label = sprintf($this->_application->H(_n('%s Photo', '%s Photos', $settings['num'], 'directories-pro')), '<em>' . $settings['num'] . '</em>');
        } else {
            $label = sprintf($this->_application->H(_n('%s Photo', '%s Photos', 10, 'directories-pro')), '<em>' . __('Unlimited', 'directories-pro') . '</em>');
        }
        return array(array(
            'icon' => 'fas fa-camera',
            'html' => $label,
            'settings' => $settings,
        ));
    }
    
    public function paymentAddonFeatureSupports(Entity\Model\Bundle $bundle)
    {
        return $bundle->type === 'directory__listing';
    }
    
    protected function _getAddonSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = [], $horizontal = true)
    {
        return array(
            'num' => array(
                '#title' => $this->_additionalNumAllowedLabel(__('Photos', 'directories-pro')),
                '#type' => 'slider',
                '#default_value' => isset($settings['num']) ? $settings['num'] : 0,
                '#integer' => true,
                '#min_value' => 0,
                '#max_value' => 30,
                '#horizontal' => $horizontal,
            ),
        );
    }
    
    public function paymentAddonFeatureSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = [])
    {
        return $this->_getAddonSettingsForm($bundle, $settings, $parents);
    }
    
    public function paymentAddonFeatureExtraSettingsForm(Entity\Model\Bundle $bundle, array $planFeatures, array $currentExtraFeatures, array $parents = [])
    {
        return $this->_getAddonSettingsForm($bundle, $currentExtraFeatures, $parents, false);
    }
        
    public function paymentAddonFeatureIsEnabled(Entity\Model\Bundle $bundle, array $settings)
    {
        return !empty($settings['num']) && intval($settings['num']) > 0;
    }

    public function paymentAddonFeatureIsOrderable(array $settings, array $currentFeatures)
    {
        return $this->paymentAddonFeatureExtraIsOrderable($currentFeatures);
    }
    
    public function paymentAddonFeatureExtraIsOrderable(array $planFeatures)
    {
        return empty($planFeatures[$this->_name]['unlimited']);
    }
}