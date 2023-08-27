<?php
namespace SabaiApps\Directories\Component\Directory\EntityBundleType;

use SabaiApps\Directories\Component\Entity\BundleType\AbstractBundleType;

class CategoryEntityBundleType extends AbstractBundleType
{    
    protected function _entityBundleTypeInfo()
    {
        return array(
            'type' => $this->_name,
            'entity_type' => 'term',
            'suffix' => 'dir_cat',
            'slug' => 'categories',
            'component' => 'Directory',
            'is_taxonomy' => true,
            'label' => __('Categories', 'directories'),
            'label_singular' => __('Category', 'directories'),
            'label_add' => __('Add Category', 'directories'),
            'label_all' => __('All Categories', 'directories'),
            'label_select' => __('Select Category', 'directories'),
            'label_count' => __('%s category', 'directories'),
            'label_count2' => __('%s categories', 'directories'),
            'label_page' => __('Category: %s', 'directories'),
            'icon' => 'fas fa-folder',
            'public' => true,
            'is_hierarchical' => true,
            'entity_icon' => 'directory_icon',
            'entity_color' => 'directory_color',
            'permalink' => array('slug' => 'category'),
            'properties' => array(
                'parent' => array(
                    'label' => __('Parent Category', 'directories'),
                ),
            ),
            'fields' => __DIR__ . '/category_fields.php',
            'displays' => __DIR__ . '/category_displays.php',
            'views' => __DIR__ . '/category_views.php',
        );
    }
}