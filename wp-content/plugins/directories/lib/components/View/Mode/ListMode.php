<?php
namespace SabaiApps\Directories\Component\View\Mode;

use SabaiApps\Directories\Component\Entity\Model\Bundle;

class ListMode extends AbstractMode
{
    protected function _viewModeInfo()
    {
        return [
            'label' => _x('List', 'view mode label', 'directories'),
            'default_settings' => [
                'template' => 'view_entities_list',
                'display' => 'summary',
                'list_grid' => true,
                'list_no_row' => false,
                'list_grid_cols' => ['num' => 'responsive', 'num_responsive' => ['xs' => 2, 'lg' => 3, 'xl' => 4]],
                'list_grid_gutter_width' => 'sm',
                'list_layout_switch_cookie' => 'drts-entity-view-list-layout',
                'list_grid_default' => false,
            ],
            'default_display' => 'summary',
            'displays' => [
                'summary' => [
                    'label' => _x('Summary', 'display name', 'directories'),
                    'weight' => 1,
                ],
            ],
            'mapable' => true,
        ];
    }

    public function viewModeSupports(Bundle $bundle)
    {
        return parent::viewModeSupports($bundle)
            && !empty($bundle->info['public'])
            && empty($bundle->info['internal']);
    }

    public function viewModeSettingsForm(Bundle $bundle, array $settings, array $parents = [])
    {
        $list_grid_selector = sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['list_grid'])));
        return [
            'list_grid' => [
                '#title' => __('Enable grid layout', 'directories'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['list_grid']),
                '#horizontal' => true,
            ],
            'list_no_row' => [
                '#title' => __('Disable row layout', 'directories'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['list_no_row']),
                '#horizontal' => true,
                '#states' => [
                    'visible' => [
                        $list_grid_selector => ['type' => 'checked', 'value' => true],
                    ],
                ],
            ],
            'list_grid_default' => [
                '#title' => __('Set grid layout as default', 'directories'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['list_grid_default']),
                '#horizontal' => true,
                '#states' => [
                    'visible' => [
                        $list_grid_selector => ['type' => 'checked', 'value' => true],
                        sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['list_no_row']))) => ['type' => 'checked', 'value' => false],

                    ],
                ],
            ],
            'list_grid_cols' => $this->_getGridColumnSettingsForm($settings['list_grid_cols'], array_merge($parents, ['list_grid_cols']), __('Grid layout columns', 'directories'), 'num') + [
                '#states' => [
                    'visible' => [
                        $list_grid_selector => ['type' => 'checked', 'value' => true],
                    ],
                ],
            ],
            'list_grid_gutter_width' => [
                '#title' => __('Grid layout gutter width', 'directories'),
                '#type' => 'select',
                '#default_value' => empty($settings['list_grid_gutter_width']) ? 'sm' : $settings['list_grid_gutter_width'],
                '#options' => [
                    'none' => __('None', 'directories'),
                    'xs' => __('Small', 'directories'),
                    'sm' => __('Default', 'directories'),
                    'md' => __('Medium', 'directories'),
                    'lg' => __('Large', 'directories'),
                ],
                '#horizontal' => true,
                '#states' => [
                    'visible' => [
                        $list_grid_selector => ['type' => 'checked', 'value' => true],
                    ],
                ],
            ],
        ];
    }

    public function viewModeNav(Bundle $bundle, array $settings)
    {
        return [
            [
                [['filters'], []],
                [['filter', 'num'], ['layout_switch', 'sort', 'add']],
            ], // header
            [
                [[], ['perpages', 'pagination']],
                [['load_more']],
            ], // footer
        ];
    }

    protected function _getGridColumnSettingsForm(array $settings, array $parents = [], $label = null, $name = null, array $columns = null, $responsive = true)
    {
        if (!isset($name)) $name = $this->_name . '_cols';
        if (!isset($columns)) $columns = [2, 3, 4, 6];
        return $this->_application->GridColumnSettingsForm($name, $settings, $parents, $label, $columns, $responsive);
    }

    public function getGridClass($cols, $dw = false)
    {
        $prefix = $dw ? 'drts-dw-' : 'drts-col-';
        if (!is_array($cols)) return $prefix . intval(12 / $cols);

        $classes = [];
        if (isset($cols['xs'])) {
            $_size = 12 / $cols['xs'];
            unset($cols['xs']);
        } else {
            $_size = 12;
        }
        $classes[] = $prefix . $_size;
        foreach (array_keys($cols) as $_width) {
            if (!is_numeric($cols[$_width])) continue;

            $classes[] = $prefix . $_width . '-' . intval(12 / $cols[$_width]);
        }
        return implode(' ', $classes);
    }
}
