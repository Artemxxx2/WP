<?php
namespace SabaiApps\Directories\Component\DirectoryPro\EntityBundleType;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity\BundleType\AbstractBundleType;

class CustomTaxonomyEntityBundleType extends AbstractBundleType
{
    protected $_slug, $_customInfo;

    public function __construct(Application $application, $name, $slug, array $info)
    {
        parent::__construct($application, $name);
        $this->_slug = $slug;
        $this->_customInfo = $info;
    }

    protected function _entityBundleTypeInfo()
    {
        $info = [
            'type' => $this->_name,
            'entity_type' => 'term',
            'suffix' => 'dct_' . $this->_slug,
            'slug' => $this->_slug,
            'component' => 'DirectoryPro',
            'is_taxonomy' => true,
            'public' => true,
            'entity_icon' => '',
            'entity_color' => '',
            'views' => __DIR__ . '/custom_taxonomy_views.php',
        ] + $this->_customInfo;

        // Set default labels if not set
        if (!isset($info['label_add'])) $info['label_add'] = sprintf(_x('Add %s', 'add term', 'directories-pro'), $info['label_singular']);
        if (!isset($info['label_all'])) $info['label_all'] = sprintf(_x('All %s', 'all terms', 'directories-pro'), $info['label']);
        if (!isset($info['label_select'])) $info['label_select'] = sprintf(_x('Select %s' , 'select term', 'directories-pro'), $info['label_singular']);
        if (!isset($info['label_count'])) $info['label_count'] = '%s ' . strtolower($info['label_singular']);
        if (!isset($info['label_count2'])) $info['label_count2'] = '%s ' . strtolower($info['label']);
        if (!isset($info['label_page'])) $info['label_page'] = $info['label_singular'] . ': %s';

        // Set permalink
        $info['permalink'] = [
            'slug' => strtr(strtolower($info['label_singular']), [' ' => '_']),
        ];

        // Hierarchical?
        if (!empty($info['hierarchical'])) {
            if (!isset($info['is_hierarchical'])) {
                $info['is_hierarchical'] = true;
            }
            unset($info['hierarchical']);
        }
        if (!empty($info['is_hierarchical'])) {
            $info['properties'] = [
                'parent' => [
                    'label' => sprintf(__('Parent %s', 'directories-pro'), $info['label_singular']),
                ],
            ];
            if (!isset($info['icon'])) $info['icon'] = 'fas fa-folder';
            $info['displays'] = __DIR__ . '/custom_taxonomy_displays_hierarchical.php';
        } else {
            if (!isset($info['icon'])) $info['icon'] = 'fas fa-tag';
            $info['displays'] = __DIR__ . '/custom_taxonomy_displays.php';
        }

        return $info;
    }
}