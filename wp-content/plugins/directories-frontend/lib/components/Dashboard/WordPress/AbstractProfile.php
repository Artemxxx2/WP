<?php
namespace SabaiApps\Directories\Component\Dashboard\WordPress;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Context;
use SabaiApps\Framework\User\RegisteredIdentity;

abstract class AbstractProfile
{
    protected static $_instance = [], $_name;
    protected $_application, $_label, $_settings;

    final public function __construct(Application $application, $label, array $settings = [])
    {
        $this->_application = $application;
        $this->_label = $label;
        $this->_settings = $settings;
        add_filter('drts_core_user_link_attr', function ($attr, $identity) {
            if (!$identity->isAnonymous()
                && ($url = $this->_getProfileUrl($identity))
            ) {
                $attr['href'] = $url;
                $attr['target'] = '_self';
                unset($attr['rel']);
            }
            return $attr;
        }, 10, 2);
        $this->_init();
    }

    static public function init(Application $application, array $settings = [])
    {
        if (!isset(static::$_instance[static::$_name])) {
            static::$_instance[static::$_name] = new static($application, static::$_name, $settings);
        }
        return static::$_instance[static::$_name];
    }

    abstract protected function _init();

    abstract protected function _getProfileUrl(RegisteredIdentity $identity);

    protected function _isOwnProfileOnly()
    {
        return !empty($this->_settings['_own_profile_only']);
    }

    protected function _getIdentity($userId)
    {
        // Fetch identity if viewing other user's profile
        if (($userId = intval($userId))
            && $userId !== $this->_application->getUser()->id
            && ($identity = $this->_application->UserIdentity($userId))
            && !$identity->isAnonymous()
        ) {
            return $identity;
        }
    }

    protected function _displayPanel($panelName, $userId)
    {
        if (!$panel = $this->_application->Dashboard_Panels_impl($panelName, true)) return;

        $identity = $userId instanceof \SabaiApps\Framework\User\AbstractIdentity ? $userId : $this->_getIdentity($userId);
        $panel->dashboardPanelOnLoad($identity ? true : false);
        $username = $identity ? $identity->username : $this->_application->getUser()->username;
        $path = '/' . $this->_application->getComponent('Dashboard')->getSlug('dashboard')
            . '/'. urlencode($username) . '/'. $panelName;
        if (isset($_GET['drts_path'])) {
            $path .= $_GET['drts_path'];
        }

        // Render links as tabs
        $dashboard_config = $this->_application->getComponent('Dashboard')->getConfig();
        $panel_settings = isset($dashboard_config['panel_settings'][$panelName]) ? $dashboard_config['panel_settings'][$panelName] : [];
        $panel_settings += (array)$panel->dashboardPanelInfo('default_settings');
        if (($links = $panel->panelHtmlLinks($panel_settings, isset($link_name) ? $link_name : true, false, $identity))
            && count($links) > 1
        ) {
            echo '<div class="drts">';
            echo '<nav class="drts-dashboard-links ' . DRTS_BS_PREFIX . 'nav ' . DRTS_BS_PREFIX . 'nav-tabs ' . DRTS_BS_PREFIX . 'nav-justified ' . DRTS_BS_PREFIX . 'mb-4">';
            foreach ($links as $link) {
                echo '<a href="#" class="' . DRTS_BS_PREFIX . 'nav-item ' . DRTS_BS_PREFIX . 'nav-link '
                    . $this->_application->H($link['attr']['class']) . '"' . $this->_application->Attr($link['attr'], 'class') . '>'
                    . $link['title'] . '</a>';
            }
            echo '</nav></div>';
        }
        $container = 'drts-dashboard-main';
        echo $this->_application->getPlatform()->render(
            $path,
            ['is_dashboard' => false], // attributes
            [
                'cache' => false,
                'title' => false,
                'container' => $container,
                'class' => 'drts-dashboard-main',
            ]
        );
        echo $this->_application->Dashboard_Panels_js('#' . $container, false, false);
        $this->_application->Form_Scripts();
    }

    public function getSettingsForm(array $parents)
    {
        return [
            '#title' => sprintf(__('%s Profile Page Integration', 'directories-frontend'), $this->_label),
            '#class' => 'drts-form-label-lg',
            'account_show' => [
                '#title' => __('Show dashboard panels', 'directories-frontend'),
                '#type' => 'checkbox',
                '#default_value' => !empty($this->_settings['account_show']),
                '#horizontal' => true,
                '#description' => sprintf(
                    __('Check this option to show dashboard panels on the %s profile page.', 'directories-frontend'),
                    $this->_label
                ),
            ],
            'account_redirect' => [
                '#title' => __('Redirect dashboard access', 'directories-frontend'),
                '#type' => 'checkbox',
                '#default_value' => !empty($this->_settings['account_redirect']),
                '#horizontal' => true,
                '#description' => sprintf(
                    __('Check this option to redirect dashboard access to the %s profile page.', 'directories-frontend'),
                    $this->_label
                ),
                '#states' => [
                    'visible' => [
                        sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['account_show']))) => [
                            'type' => 'checked',
                            'value' => true
                        ],
                    ]
                ],
            ],
        ];
    }

    protected function _redirectDashboardAccess(Context $context, array $paths, $url, $panelParam = null)
    {
        unset($paths[0], $paths[1]);
        $params = $context->getRequest()->getParams();
        if (isset($paths[2])) {
            if (isset($panelParam)) {
                $params[$panelParam] = 'drts_' . $paths[2];
            } else {
                $url = rtrim($url, '/') . '/drts_' . $paths[2];
            }
            unset($paths[2]);
        }

        if (!empty($paths)) $params['drts_path'] = '/' . implode('/', $paths);
        // Remove params already in the path
        unset($params['panel_name'], $params['entity_id'], $params['user_name']);

        if (!empty($params)) {
            $separator = strpos($url, '?') === false ? '?' : '&';
            $url .= $separator . http_build_query($params, '', '&');
        }
        $context->setRedirect($url);
    }

    abstract public function redirectDashboardAccess(Context $context, array $paths);
}