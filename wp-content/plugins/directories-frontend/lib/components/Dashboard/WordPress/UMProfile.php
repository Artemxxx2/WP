<?php
namespace SabaiApps\Directories\Component\Dashboard\WordPress;

use SabaiApps\Directories\Context;
use SabaiApps\Framework\User\RegisteredIdentity;

class UMProfile extends AbstractProfile
{
    protected static $_name = 'Ultimate Member';
    protected $_actionAdded = [];

    protected function _init()
    {
        add_filter('um_profile_tabs', [$this, 'profile'], 1000);
    }

    protected function _getProfileUrl(RegisteredIdentity $identity)
    {
        um_fetch_user($identity->id);
        $url = um_user_profile_url();
        um_reset_user();
        return $url;
    }

    public function redirectDashboardAccess(Context $context, array $paths)
    {
        if ($url = um_user_profile_url()) {
            $this->_redirectDashboardAccess($context, $paths, $url, 'profiletab');
        }
    }
    
    public function profile($tabs)
    {
        if (is_admin()) {
            $panels = $this->_application->getComponent('Dashboard')->getActivePanels();
            foreach (array_keys($panels) as $panel_name) {
                if (isset($panels[$panel_name]['wp'])
                    && false === $panels[$panel_name]['wp']
                ) continue;

                $tab_name = 'drts_' . $panel_name;
                $tabs[$tab_name] = $this->_getTab($panels[$panel_name]);
            }
        } else {
            if (!um_get_requested_user() // Not rendering profile tabs yet
                || (!$user_id = (int)um_profile_id())
                || ($this->_isOwnProfileOnly() && $user_id !== (int)$this->_application->getUser()->id)
            ) return $tabs;

            $identity = $this->_getIdentity($user_id);
            $panels = $this->_application->getComponent('Dashboard')->getActivePanels($identity);
            foreach (array_keys($panels) as $panel_name) {
                if (isset($panels[$panel_name]['wp'])
                    && false === $panels[$panel_name]['wp']
                ) continue;

                $tab_name = 'drts_' . $panel_name;
                $tabs[$tab_name] = $this->_getTab($panels[$panel_name]);
                // Add action hook show panel content
                if (empty($this->_actionAdded[$panel_name])) {
                    add_action('um_profile_content_' . $tab_name . '_default', function($args) use ($identity, $panel_name) {
                        $this->_displayPanel($panel_name, $identity);
                    });
                    $this->_actionAdded[$panel_name] = true;
                }
            }
        }

        return $tabs;
    }

    protected function _getTab(array $panel)
    {
        return [
            'name' => $panel['title'],
            'icon' => isset($panel['wp_um_icon']) ? $panel['wp_um_icon'] : 'um-faicon-pencil',
            'custom' => true
        ];
    }
}
