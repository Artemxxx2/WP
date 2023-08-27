<?php
namespace SabaiApps\Directories\Component\Dashboard\Panel;

use SabaiApps\Framework\User\AbstractIdentity;

class AccountPanel extends AbstractPanel
{
    protected function _dashboardPanelInfo()
    {
        return [
            'label' => __('Account', 'directories-frontend'),
            'weight' => 10,
            'wp' => false,
        ];
    }

    protected function _dashboardPanelLinks(array $settings, AbstractIdentity $identity = null)
    {
        if (isset($identity)) return; // Do not show if public dashboard

        $ret = [];
        $weight = 0;
        $all_pages = $this->_accountPanelPages();
        $pages = isset($settings['pages']['default']) ? $settings['pages']['default'] : array_keys($all_pages);
        foreach ($pages as $page) {
            if (!isset($all_pages[$page])) continue;

            $ret[$page] = $all_pages[$page];
            if (isset($settings['pages']['options'][$page])) {
                $ret[$page]['title'] = $settings['pages']['options'][$page];
                $ret[$page]['title_is_custom'] = true;
            }
            $ret[$page]['weight'] = ++$weight;
        }
        return $ret;
    }

    protected function _accountPanelPages()
    {
        $pages = [
            'change_password' => [
                'title' => _x('Change password', 'directories-frontend'),
                'icon' => 'fas fa-key',
                'weight' => 10,
            ],
            'delete_account' => [
                'title' => _x('Delete account', 'directories-frontend'),
                'icon' => 'fas fa-user-times',
                'weight' => 20,
            ],
        ];

        return $this->_application->Filter('dashboard_account_panel_pages', $pages);
    }

    public function dashboardPanelSettingsForm(array $settings, array $parents)
    {
        $options = [];
        foreach ($this->_accountPanelPages() as $link_name => $link) {
            $options[$link_name] = $link['title'];
        }
        return [
            'pages' => [
                '#title' => __('Select pages', 'directories-frontend'),
                '#type' => 'options',
                '#horizontal' => true,
                '#disable_add' => true,
                '#disable_icon' => true,
                '#disable_add_csv' => true,
                '#multiple' => true,
                '#options_value_disabled' => true,
                '#default_value' => [
                    'options' => isset($settings['pages']['options']) ? $settings['pages']['options'] : $options,
                    'default' => isset($settings['pages']['default']) ? $settings['pages']['default'] : array_keys($options),
                ],
                '#options_placeholder' => $options,
            ],
        ];
    }

    public function dashboardPanelContent($link, array $settings, array $params, AbstractIdentity $identity = null)
    {
        if (isset($identity)) return;

        return $this->_application->getPlatform()->render(
            $this->_application->getComponent('Dashboard')->getPanelUrl('account', $link, '/' . $link, [], true, $identity),
            ['is_dashboard' => false] // prevent rendering duplicate panel sections on reload panel
        );
    }
}