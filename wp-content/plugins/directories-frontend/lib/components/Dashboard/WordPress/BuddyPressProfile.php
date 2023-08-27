<?php
namespace SabaiApps\Directories\Component\Dashboard\WordPress;

use SabaiApps\Directories\Context;
use SabaiApps\Framework\User\RegisteredIdentity;

class BuddyPressProfile extends AbstractProfile
{
    protected static $_name = 'BuddyPress';

    protected function _init()
    {
        add_action('bp_setup_nav', [$this, 'profile']);
    }

    protected function _getProfileUrl(RegisteredIdentity $identity)
    {
        return bp_core_get_userlink($identity->id, false, true);
    }

    public function profile()
    {
        if (!bp_is_user() // not on profile page
            || (!$user_id = (int)bp_displayed_user_id())
            || ($this->_isOwnProfileOnly() && $user_id !== (int)$this->_application->getUser()->id)
        ) return;

        $position = 21;
        $identity = $this->_getIdentity($user_id);
        $panels = $this->_application->getComponent('Dashboard')->getActivePanels($identity);
        foreach (array_keys($panels) as $panel_name) {
            if (isset($panels[$panel_name]['wp'])
                && false === $panels[$panel_name]['wp']
            ) continue;

            bp_core_new_nav_item([
                'name' => $panels[$panel_name]['title'],
                'slug' => $slug = 'drts_' . $panel_name,
                'position' => ++$position,
                'default_subnav_slug' => $slug,
                'screen_function' => [$this, 'profileNav'],
            ]);
        }
    }

    public function profileNav()
    {
        add_action('bp_template_content', function() {
            $action = $GLOBALS['bp']->current_action;
            if (strpos($action, 'drts_') !== 0) return;

            $this->_displayPanel(substr($action, strlen('drts_')), bp_displayed_user_id());
        });
        bp_core_load_template(apply_filters('bp_core_template_plugin', 'members/single/plugins'));
    }

    public function redirectDashboardAccess(Context $context, array $paths)
    {
        if ($url = bp_loggedin_user_domain()) {
            $this->_redirectDashboardAccess($context, $paths, $url);
        }
    }
}