<?php
namespace SabaiApps\Directories\Component\Payment\Feature;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Payment\Model\Feature;

class TaxonomyTermsFeature extends AbstractFeature implements IAddonFeature
{    
    protected function _paymentFeatureInfo()
    {
        return array(
            'label' => __('Taxonomy Term Settings', 'directories-payments'),
            'weight' => 5,
        );
    }
    
    public function paymentFeatureSettings(Entity\Model\Bundle $bundle, $planType = 'base')
    {
        $settings = [];
        foreach (array_keys($this->_getTaxonomies($bundle, true)) as $taxonomy_bundle_type) {
            $settings[$taxonomy_bundle_type] = array(
                'unlimited' => false,
                'num' => $this->_application->Entity_BundleTypeInfo($taxonomy_bundle_type, 'is_hierarchical') ? 1 : 3
            );
        }
        
        return $settings;
    }
    
    public function paymentFeatureSupports(Entity\Model\Bundle $bundle, $planType = 'base')
    {
        return !empty($bundle->info['taxonomies']) && $this->_getTaxonomies($bundle, true);
    }
    
    public function paymentFeatureSettingsForm(Entity\Model\Bundle $bundle, array $settings, $planType = 'base', array $parents = [])
    {
        $form = [];
        foreach ($this->_getTaxonomies($bundle) as $taxonomy_bundle_type => $taxonomy_bundle) {
            $form[$taxonomy_bundle_type] = array(
                '#title' => $this->_maxNumAllowedLabel($taxonomy_bundle->getLabel()),
                '#horizontal' => true,
                '#horizontal_label_padding' => false,
                'unlimited' => array(
                    '#title' => __('Unlimited', 'directories-payments'),
                    '#type' => 'checkbox',
                    '#switch' => false,
                    '#default_value' => !empty($settings[$taxonomy_bundle_type]['unlimited']),
                ),
                'num' => array(
                    '#type' => 'slider',
                    '#default_value' => isset($settings[$taxonomy_bundle_type]['num']) ? $settings[$taxonomy_bundle_type]['num'] : 1,
                    '#integer' => true,
                    '#min_value' => 0,
                    '#max_value' => 50,
                    '#states' => array(
                        'invisible' => array(
                            sprintf('input[name="%s[unlimited][]"]', $this->_application->Form_FieldName(array_merge($parents, array($taxonomy_bundle_type)))) => array(
                                'type' => 'checked', 
                                'value' => true
                            ),
                        ),                       
                    ),
                ),
            );
        }
        
        return $form;
    }
    
    protected function _getTaxonomies(Entity\Model\Bundle $bundle, $nameOnly = false)
    {
        $ret = [];
        foreach ($bundle->info['taxonomies'] as $taxonomy_bundle_type => $taxonomy_bundle_name) {
            if ($this->_application->Entity_BundleTypeInfo($taxonomy_bundle_type, 'payment_feature_disable')
                || (!$taxonomy_bundle = $this->_application->Entity_Bundle($taxonomy_bundle_name))
            ) continue;
            
            $ret[$taxonomy_bundle_type] = $nameOnly ? $taxonomy_bundle_name : $taxonomy_bundle;
        }
        
        return $ret;
    }
    
    public function paymentFeatureApply(Entity\Type\IEntity $entity, Feature $feature, array &$values, $isAddon = false)
    {
        if (!$isAddon) return;

        return $this->_applyAddonFeature($entity, $feature, $values, true);
    }

    public function paymentFeatureUnapply(Entity\Type\IEntity $entity, Feature $feature, array &$values, $isAddon = false)
    {
        if (!$isAddon) return;

        return $this->_unapplyAddonFeature($entity, $feature, $values, true);
    }

    public function paymentFeatureOnEntityForm(Entity\Model\Bundle $bundle, array $settings, array &$form, Entity\Type\IEntity $entity = null, $isAdmin = false)
    {
        if ($isAdmin && $this->_application->IsAdministrator()) return; // do not restrict for administrators
        
        foreach ($settings[0] as $bundle_type => $_settings) {
            if (!isset($form[$bundle_type])) continue;
            
            if (empty($_settings['unlimited'])) {
                $limit = $_settings['num'];
                if (!empty($settings[1][$bundle_type]['num'])) {
                    $limit += $settings[1][$bundle_type]['num'];
                }

                if (empty($limit)) {
                    // No terms allowed
                    unset($form[$bundle_type]);
                    continue;
                }

                if (isset($form['#entity_field_max_num_items'][$bundle_type])) {
                    if (empty($form['#entity_field_max_num_items'][$bundle_type])
                        || $form['#entity_field_max_num_items'][$bundle_type] > $limit
                    ) {
                        $form['#entity_field_max_num_items'][$bundle_type] = $limit;
                    } else {
                        return;
                    }
                } else {
                    $form['#entity_field_max_num_items'][$bundle_type] = $limit;
                }
            } else {
                if (!isset($form['#entity_field_max_num_items'][$bundle_type])) {
                    $form['#entity_field_max_num_items'][$bundle_type] = 0;
                } else {
                    if ($form['#entity_field_max_num_items'][$bundle_type] !== 0) return;
                }
            }

            if (isset($form[$bundle_type][0])) {
                // Select dropdown fields
                
                if (empty($_settings['unlimited'])) {                
                    // Remove fields over limit
                    $current_num = 0;
                    foreach (array_keys($form[$bundle_type]) as $key) {
                        if (is_numeric($key)) {
                            ++$current_num;
                            if ($current_num > $limit) {
                                // over limit
                                unset($form[$bundle_type][$key]);
                            }
                        }
                    }
                    // Add add more button
                    $form[$bundle_type]['_add'] = array(
                        '#type' => 'addmore',
                        '#next_index' => $current_num,
                        '#max_num' => $limit,
                        '#hidden' => $current_num >= $limit,
                    );
                } else {
                    $current_num = 0;
                    foreach (array_keys($form[$bundle_type]) as $key) {
                        if (is_numeric($key)) {
                            ++$current_num;
                        }
                    }
                    // Add add more button
                    $form[$bundle_type]['_add'] = array(
                        '#type' => 'addmore',
                        '#next_index' => $current_num,
                    );
                }
            } else {
                // Checkboxes or Multi-select
                
                if (empty($_settings['unlimited'])) {
                    $form[$bundle_type]['#max_selection'] = $limit;
                }
            }
        }
    }
    
    public function paymentFeatureRender(Entity\Model\Bundle $bundle, array $settings, $planType = null)
    {
        $ret = [];
        foreach ($settings as $bundle_type => $_settings) {
            if (!$taxonomy_bundle = $this->_application->Entity_Bundle($bundle_type, $bundle->component, $bundle->group)) {
                continue;
            }
            if (empty($_settings['unlimited'])) {
                if (empty($_settings['num'])) continue;

                $label = sprintf($this->_application->H($taxonomy_bundle->getLabel($_settings['num'] > 1 ? 'count2' : 'count')), '<em>' . $_settings['num'] . '</em>');
            } else {
                $label = sprintf($this->_application->H($taxonomy_bundle->getLabel('count2')), '<em>' . __('Unlimited', 'directories-payments') . '</em>');
            }
            $ret[$bundle_type] = array(
                'icon' => $this->_application->Entity_BundleTypeInfo($taxonomy_bundle, 'icon'),
                'html' => $label,
                'settings' => $_settings,
            );
        }
        
        return $ret;
    }
    
    public function paymentAddonFeatureSupports(Entity\Model\Bundle $bundle)
    {
        return !empty($bundle->info['taxonomies']) && $this->_getTaxonomies($bundle, true);
    }
    
    public function paymentAddonFeatureSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = [])
    {
        return $this->_getAddonSettingsForm($bundle, $settings, $parents);
    }
    
    protected function _getAddonSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = [], $horizontal = true)
    {
        $form = [];
        foreach ($this->_getTaxonomies($bundle) as $taxonomy_bundle_type => $taxonomy_bundle) {
            $form[$taxonomy_bundle_type] = array(
                '#title' => $this->_additionalNumAllowedLabel($taxonomy_bundle->getLabel()),
                '#weight' => 5,
                'num' => array(
                    '#type' => 'slider',
                    '#default_value' => isset($settings[$taxonomy_bundle_type]['num']) ? $settings[$taxonomy_bundle_type]['num'] : 0,
                    '#integer' => true,
                    '#min_value' => 0,
                    '#max_value' => 20,
                ),
                '#horizontal' => $horizontal,
            );
        }
        
        return $form;
    }
    
    public function paymentAddonFeatureExtraSettingsForm(Entity\Model\Bundle $bundle, array $planFeatures, array $currentExtraFeatures, array $parents = [])
    {
        return $this->_getAddonSettingsForm($bundle, $currentExtraFeatures, $parents, false);
    }
        
    public function paymentAddonFeatureIsEnabled(Entity\Model\Bundle $bundle, array $settings)
    {
        // Copy original so original settings can be returned even when one or more taxonomies have num 0.
        $_settings = $settings;
        foreach (array_keys($this->_getTaxonomies($bundle, true)) as $taxonomy_bundle_type) {
            if (empty($_settings[$taxonomy_bundle_type]['num']) || intval($_settings[$taxonomy_bundle_type]['num']) <= 0) {
                unset($_settings[$taxonomy_bundle_type]);
            }
        }
        
        return empty($_settings) ? false : $settings;
    }
    
    public function paymentAddonFeatureIsOrderable(array $settings, array $currentFeatures)
    {
        return $this->paymentAddonFeatureExtraIsOrderable($currentFeatures);
    }

    public function paymentAddonFeatureExtraIsOrderable(array $planFeatures)
    {
        if (empty($planFeatures[$this->_name])) return true;

        foreach (array_keys($planFeatures[$this->_name]) as $taoxnomy_bundle_type) {
            if (empty($planFeatures[$this->_name][$taoxnomy_bundle_type]['unlimited'])) return true;
        }
        return false;
    }
}