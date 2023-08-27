<?php
namespace SabaiApps\Directories\Component\Voting\DashboardPanel;

use SabaiApps\Directories\Component\Dashboard;
use SabaiApps\Framework\User\AbstractIdentity;

class VotesDashboardPanel extends Dashboard\Panel\AbstractPanel
{
    protected function _dashboardPanelInfo()
    {
        return [
            'label' => __('Votes', 'directories'),
            'weight' => 5,
            'wp_um_icon' => 'um-faicon-thumbs-up',
        ];
    }

    protected function _dashboardPanelLinks(array $settings, AbstractIdentity $identity = null)
    {
        if (isset($identity)) return; // Do not show if public dashboard

        $ret = [];
        $weight = 0;
        $types = isset($settings['types']['default']) ? $settings['types']['default'] : array_keys($this->_application->Voting_Types());
        foreach ($types as $type) {
            if ((!$type_impl = $this->_application->Voting_Types_impl($type))
                || (!$type_info = $type_impl->votingTypeInfo())
            ) continue;

            if (isset($settings['types']['options'][$type])) {
                $ret[$type] = [
                    'title' => $settings['types']['options'][$type],
                    'title_is_custom' => true,
                    'icon' => $type_info['icon'],
                ];
            } else {
                $ret[$type] = [
                    'title' => $type_info['label'],
                    'icon' => $type_info['icon'],
                ];
            }
            $ret[$type]['weight'] = ++$weight;
        }

        return $ret;
    }

    public function dashboardPanelSettingsForm(array $settings, array $parents)
    {
        foreach (array_keys($this->_application->Voting_Types()) as $type) {
            if ((!$type_impl = $this->_application->Voting_Types_impl($type))
                || (!$type_info = $type_impl->votingTypeInfo())
            ) continue;

            $types[$type] = $type_info['label'];
        }
        return [
            'types' => [
                '#title' => __('Vote types', 'directories'),
                '#type' => 'options',
                '#horizontal' => true,
                '#disable_add' => true,
                '#disable_icon' => true,
                '#disable_add_csv' => true,
                '#multiple' => true,
                '#options_value_disabled' => true,
                '#default_value' => array(
                    'options' => isset($settings['types']['options']) ? $settings['types']['options'] : $types,
                    'default' => isset($settings['types']['default']) ? $settings['types']['default'] : array_keys($types),
                ),
                '#options_placeholder' => $types,
            ],
        ];
    }

    public function dashboardPanelContent($link, array $settings, array $params, AbstractIdentity $identity = null)
    {
        return $this->_application->getPlatform()->render(
            $this->_application->getComponent('Dashboard')->getPanelUrl('voting_votes', $link, '/votes', [], true, $identity),
            [
                'is_dashboard' => false, // prevent rendering duplicate panel sections on reload panel
                'identity' => $identity,
            ]
        );
    }

    public function dashboardPanelOnLoad($isPublic = false)
    {
        if ($this->_application->getPlatform()->isAdmin()) return;

        $this->_application->getPlatform()->loadJqueryUiJs(array('effects-highlight'));
    }
}
