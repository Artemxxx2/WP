<?php
namespace SabaiApps\Directories\Component\Directory\EntityBundleType;

use SabaiApps\Directories\Component\Entity\BundleType\AbstractBundleType;

class TagEntityBundleType extends AbstractBundleType
{    
    protected function _entityBundleTypeInfo()
    {
        return array(
            'type' => $this->_name,
            'entity_type' => 'term',
            'suffix' => 'dir_tag',
            'component' => 'Directory',
            'slug' => 'tags',
            'is_taxonomy' => true,
            'label' => __('Tags', 'directories'),
            'label_singular' => __('Tag', 'directories'),
            'label_add' => __('Add Tag', 'directories'),
            'label_all' => __('All Tags', 'directories'),
            'label_select' => __('Select Tag', 'directories'),
            'label_count' => __('%s tag', 'directories'),
            'label_count2' => __('%s tags', 'directories'),
            'label_page' => __('Tag: %s', 'directories'),
            'icon' => 'fas fa-tag',
            'public' => true,
            'is_hierarchical' => false,
            'entity_icon' => '',
            'entity_color' => '',
            'permalink' => array('slug' => 'tag'),
            'displays' => __DIR__ . '/tag_displays.php',
            'views' => __DIR__ . '/tag_views.php',
        );
    }
}