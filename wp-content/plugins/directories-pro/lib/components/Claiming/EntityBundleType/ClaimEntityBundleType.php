<?php
namespace SabaiApps\Directories\Component\Claiming\EntityBundleType;

use SabaiApps\Directories\Component\Entity\BundleType\AbstractBundleType;

class ClaimEntityBundleType extends AbstractBundleType
{    
    protected function _entityBundleTypeInfo()
    {
        return array(
            'type' => $this->_name,
            'entity_type' => 'post',
            'slug' => 'claims',
            'parent' => 'claiming_enable',
            'suffix' => 'clm_clm',
            'component' => 'Claiming',
            'label' => __('Claims', 'directories-pro'),
            'label_singular' => __('Claim', 'directories-pro'),
            'label_add' => __('Claim %s', 'directories-pro'),
            'label_all' => __('All Claims', 'directories-pro'),
            'label_count' => __('%s claim', 'directories-pro'),
            'label_count2' => __('%s claims', 'directories-pro'),
            'icon' => 'fas fa-check',
            'properties' => array(
                'content' => array(
                    'label' => __('Message', 'directories-pro'),
                    'widget_settings' => array('rows' => 10),
                    'required' => true, 
                ),
                'author' => array(
                    'required' => true,
                ),
            ),
            'fields' => __DIR__ . '/claim_fields.php',
            'displays' => __DIR__ . '/claim_displays.php',
            'public' => false,
            'no_title' => true,
        );
    }
}