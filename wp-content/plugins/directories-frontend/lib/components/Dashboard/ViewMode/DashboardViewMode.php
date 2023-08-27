<?php
namespace SabaiApps\Directories\Component\Dashboard\ViewMode;

use SabaiApps\Directories\Component\View\Mode\AbstractMode;
use SabaiApps\Directories\Component\Entity\Model\Bundle;

class DashboardViewMode extends AbstractMode
{
    protected function _viewModeInfo()
    {
        return [
            'label' => 'Dashboard',
            'icon' => 'fas fa-tasks',
            'default_settings' => [
                'template' => 'view_entities_table',
                'display' => 'dashboard_row',
            ],
            'displays' => $this->_getDisplays(),
            'system' => true,
        ];
    }
    
    protected function _getDisplays()
    {
        return [
            'dashboard_row' => _x('Dashboard Row', 'display name', 'directories-frontend'),
        ];
    }
    
    public function viewModeSupports(Bundle $bundle)
    {
        return parent::viewModeSupports($bundle)
            && empty($bundle->info['internal'])
            && empty($bundle->info['is_taxonomy'])
            && empty($bundle->info['is_user']);
    }
    
    public function viewModeNav(Bundle $bundle, array $settings)
    {
        if (empty($settings['filter']['show'])) {
            return [
                [
                    [['num'], ['status', 'sort', 'add']],
                ], // header
                [
                    [[], ['perpages', 'pagination']],
                    [['load_more']],
                ], // footer
            ];
        }

        return [
            [
                [['filters'], []],
                [['filter'], ['status', 'sort', 'add']],
            ], // header
            [
                [['num'], ['perpages', 'pagination']],
                [['load_more']],
            ], // footer
        ];
    }
}