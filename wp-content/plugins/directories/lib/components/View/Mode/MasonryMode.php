<?php
namespace SabaiApps\Directories\Component\View\Mode;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Entity\Model\Bundle;

class MasonryMode extends ListMode
{
    protected function _viewModeInfo()
    {
        return [
            'label' => _x('Masonry', 'view mode label', 'directories'),
            'default_settings' => [
                'template' => 'view_entities_masonry',
                'display' => 'summary',
                'no_js' => false,
                'no_js_grid' => [
                    'masonry_cols' => 'responsive',
                    'masonry_cols_responsive' => ['xs' => 2, 'lg' => 3, 'xl' => 4],
                ],
                'js_grid' => [
                    'masonry_cols' => 3,
                ],
            ],
        ] + parent::_viewModeInfo();
    }

    public function viewModeAssets(Bundle $bundle, array $settings)
    {
        if (!empty($settings['no_js'])
            || isset($settings['masonry_cols']) // compat with <1.3.12
        ) {
            return [
                'css_files' => [
                    'driveway' => ['driveway.min.css', null, null, null, true]
                ],
            ];
        }

        return [
            'js_files' => [
                'masonry' => ['masonry.pkgd.min.js', [], 'directories', true, true],
                'drts-view-masonry' => ['view-masonry.min.js', ['drts', 'masonry'], 'directories', true],
            ],
            'images_loaded_js' => true,
        ];
    }

    public function viewModeSettingsForm(Bundle $bundle, array $settings, array $parents = [])
    {
        $form = [
            'no_js' => [
                '#title' => __('Do not use JavaScript', 'directories'),
                '#default_value' => isset($settings['masonry_cols']) ? true : !empty($settings['no_js']),
                '#type' => 'checkbox',
                '#horizontal' => true,
            ],
            'no_js_grid' => [
                '#states' => [
                    'visible' => [
                        sprintf('[name="%s"]', $no_js_name = $this->_application->Form_FieldName(array_merge($parents, ['no_js']))) => ['type' => 'checked', 'value' => true]
                    ],
                ],
            ] + $this->_getGridColumnSettingsForm(isset($settings['masonry_cols']) ? $settings : $settings['no_js_grid'], array_merge($parents, ['no_js_grid']), null, 'masonry_cols'),
            'js_grid' => [
                '#states' => [
                    'visible' => [
                        sprintf('[name="%s"]', $no_js_name) => ['type' => 'checked', 'value' => false]
                    ],
                ],
            ] + $this->_getGridColumnSettingsForm($settings['js_grid'], array_merge($parents, ['js_grid']), null, 'masonry_cols', [2, 3, 4, 5, 6], false),
        ];

        return $form;
    }

    public function viewModeNav(Bundle $bundle, array $settings)
    {
        return [
            [
                [['filters'], []],
                [['filter', 'num'], ['sort', 'add']],
            ], // header
            [
                [[], ['perpages', 'pagination']],
            ], // footer
        ];
    }

    public function getNoJsGridClass(Bundle $bundle, array $settings)
    {
        $settings = isset($settings['no_js_grid']) ? $settings['no_js_grid'] : $settings; // compat with <1.3.12
        if (isset($settings['masonry_cols'])) {
            if ($settings['masonry_cols'] === 'responsive') {
                if (!empty($settings['masonry_cols_responsive'])) {
                    $cols = $settings['masonry_cols_responsive'];
                }
            } else {
                $cols = $settings['masonry_cols'];
            }
        }
        if (!isset($cols)
            && (!$cols = $this->_application->Entity_BundleTypeInfo($bundle, 'view_masonry_cols'))
        ) {
            $cols = empty($bundle->info['is_taxonomy']) ? ['sm' => 1, 'md' => 2, 'lg' => 3, 'xl' => 4] : ['xs' => 2, 'lg' => 3, 'xl' => 4];
        }
        return $this->getGridClass($cols, true);
    }
}
