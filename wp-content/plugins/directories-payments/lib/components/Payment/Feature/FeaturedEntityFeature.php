<?php
namespace SabaiApps\Directories\Component\Payment\Feature;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Payment\Model\Feature;

class FeaturedEntityFeature extends AbstractFeature implements IAddonFeature
{    
    protected function _paymentFeatureInfo()
    {
        return array(
            'label' => __('Featured Content Settings', 'directories-payments'),
            'weight' => 2,
            'default_settings' => array(
                'enable' => false,
                'priority' => 5,
            ),
        );
    }
    
    public function paymentFeatureSupports(Entity\Model\Bundle $bundle, $planType = 'base')
    {
        // Not supporting base level since currently there is no way to update entity_featured field on level update
        return false;
    }
    
    public function paymentFeatureSettingsForm(Entity\Model\Bundle $bundle, array $settings, $planType = 'base', array $parents = [])
    {
        return $this->_getSettingsForm($bundle, $settings, $parents);
    }
    
    protected function _getSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents, $horizontal = true, $switch = true)
    {
        return array(
            'enable' => array(
                '#title' => __('Display as featured', 'directories-payments'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['enable']),
                '#horizontal' => $horizontal,
                '#switch' => $switch,
                '#on_label' => $switch ? null : '',
            ),
            'priority' => array(
                '#type' => 'select',
                '#title' => __('Priority', 'directories-payments'),
                '#options' => Entity\FieldType\FeaturedFieldType::priorities(),
                '#default_value' => $settings['priority'],
                '#states' => array(
                    'visible' => array(
                        sprintf($switch ? 'input[name="%s[enable]"]' : 'input[name="%s[enable][]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'checked', 'value' => true),
                    ),                       
                ),
                '#horizontal' => $horizontal,
            ),      
        );
    }
    
    public function paymentFeatureIsEnabled(Entity\Model\Bundle $bundle, array $settings)
    {
        return !empty($settings['enable']);
    }
    
    public function paymentFeatureApply(Entity\Type\IEntity $entity, Feature $feature, array &$values, $isAddon = false)
    {
        if (null !== $duration = $feature->getMeta('duration')) {
            $expires_at = 0; // init
            if ($entity_featured = $entity->getSingleFieldValue('entity_featured')) {
                // Already featured
                $featured_at = $entity_featured['featured_at'];
                if (!empty($entity_featured['expires_at'])
                    && $entity_featured['expires_at'] > time()
                    && $duration
                ) {
                    // Extend expiration date
                    $expires_at = $entity_featured['expires_at'] + $duration * 86400;
                }
            } else {
                // Newly featured
                $featured_at = time();
                if ($duration) $expires_at = time() + $duration * 86400;
            }
        } else {
            // Base plan feature, so there is no duration
            $expires_at = 0;
            if ($entity_featured = $entity->getSingleFieldValue('entity_featured')) {
                // Already featured
                $featured_at = $entity_featured['featured_at'];
            } else {
                $featured_at = time();
            }
        }
        $values['entity_featured'] = array(
            'enable' => true,
            'value' => $feature->getMeta('priority'),
            'featured_at' => $featured_at,
            'expires_at' => $expires_at
        );

        return true;
    }
    
    public function paymentFeatureUnapply(Entity\Type\IEntity $entity, Feature $feature, array &$values, $isAddon = false)
    {
        if (!$entity_featured = $entity->getSingleFieldValue('entity_featured')) return;
        
        // Subtract featured duration time from the current expiration date if any set, otherwise unfeature
        $value = false;
        if ($entity_featured['expires_at'] > time()
            && ($duration = $feature->getMeta('duration'))
        ) {
            $expires_at = $entity_featured['expires_at'] - $duration * 86400; // undo expiration time
            if ($expires_at > time()) {
                $value = array(
                    'value' => $entity_featured['value'],
                    'featured_at' => $entity_featured['featured_at'],
                    'expires_at' => $expires_at
                );
            }
        }
        $values['entity_featured'] = $value;
        
        return true;
    }
    
    public function paymentFeatureRender(Entity\Model\Bundle $bundle, array $settings, $planType = null)
    {
        $label = !empty($settings['duration'])
            ? sprintf(_n('Featured %d day', 'Featured %d days', $settings['duration'], 'directories-payments'), $settings['duration'])
            : _x('Featured', 'pricing table', 'directories-payments');
        return [[
            'icon' => 'fas fa-certificate',
            'html' => $this->_application->H($label),
            'settings' => $settings,
        ]];
    }
    
    public function paymentAddonFeatureSupports(Entity\Model\Bundle $bundle)
    {
        return $this->_application->Entity_BundleTypeInfo($bundle, 'featurable');
    }
    
    public function paymentAddonFeatureSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = [])
    {
        return $this->_getSettingsForm($bundle, $settings, $parents, true, false) + array(
            'duration' => array(
                '#title' => __('Duration in days', 'directories-payments'),
                '#description' => __('Enter the number of days content will be marked as featured.', 'directories-payments'),
                '#type' => 'slider',
                '#default_value' => isset($settings['duration']) ? $settings['duration'] : 7,
                '#integer' => true,
                '#field_suffix' => __('day(s)', 'directories-payments'),
                '#min_value' => 0,
                '#max_value' => 365,
                '#states' => array(
                    'visible' => array(
                        sprintf('input[name="%s[enable][]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'checked', 'value' => true),
                    ),                       
                ),
                '#horizontal' => true,
            ),
        );
    }
    
    public function paymentAddonFeatureExtraSettingsForm(Entity\Model\Bundle $bundle, array $planFeatures, array $currentExtraFeatures, array $parents = []){}
        
    public function paymentAddonFeatureIsEnabled(Entity\Model\Bundle $bundle, array $settings)
    {
        return empty($settings['enable']) ? false: $settings;
    }
    
    public function paymentAddonFeatureIsOrderable(array $settings, array $currentFeatures)
    {
        return empty($currentFeatures[$this->_name]['enable']);
    }

    public function paymentAddonFeatureExtraIsOrderable(array $planFeatures)
    {
        return false;
    }
}