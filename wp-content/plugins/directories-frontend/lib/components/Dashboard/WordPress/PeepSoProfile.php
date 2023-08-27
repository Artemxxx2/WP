<?php
namespace SabaiApps\Directories\Component\Dashboard\WordPress;

use SabaiApps\Directories\Context;
use SabaiApps\Framework\User\RegisteredIdentity;

class PeepSoProfile extends AbstractProfile
{
    protected static $_name = 'PeeoSo';

    protected function _init()
    {
        add_filter('peepso_navigation_profile', function($links) {
            if (!isset($links['_user_id'])
                || (!$user_id = (int)$links['_user_id'])
                || ($this->_isOwnProfileOnly() && $user_id !== (int)$this->_application->getUser()->id)
            ) return $links;

            $identity = $this->_getIdentity($user_id);
            $panels = $this->_application->getComponent('Dashboard')->getActivePanels($identity);
            foreach (array_keys($panels) as $panel_name) {
                if (isset($panels[$panel_name]['wp'])
                    && false === $panels[$panel_name]['wp']
                ) continue;

                $link_name = 'drts_' . $panel_name;
                $links[$link_name] = [
                    'href' => $link_name,
                    'label' => $panels[$panel_name]['title'],
                    'icon' => null,
                ];
            }
            return $links;
        }, 1000);

        $panels = $this->_application->getComponent('Dashboard')->getActivePanels();
        foreach (array_keys($panels) as $panel_name) {
            if (isset($panels[$panel_name]['wp'])
                && false === $panels[$panel_name]['wp']
            ) continue;

            $link_name = 'drts_' . $panel_name;
            add_action('peepso_profile_segment_' . $link_name, function() use ($link_name, $panel_name) {
                $user_id = \PeepSoUrlSegments::get_view_id(\PeepSoProfileShortcode::get_instance()->get_view_user_id());
                $data = [
                    'link_name' => $link_name,
                    'user_id' => $user_id,
                ];
                ob_start();
                $this->_displayPanel($panel_name, $user_id);
                $data['content'] = ob_get_clean();
                \PeepSoTemplate::add_template_directory(__DIR__ . '/peepso');
                echo \PeepSoTemplate::exec_template('drts', 'profile', $data, true);
            });
        }
    }

    protected function _getProfileUrl(RegisteredIdentity $identity)
    {
        return ($user = \PeepSoUser::get_instance($identity->id)) ? $user->get_profileurl() : null;
    }

    public function redirectDashboardAccess(Context $context, array $paths)
    {
        if ($url = \PeepSoUser::get_instance()->get_profileurl()) {
            $this->_redirectDashboardAccess($context, $paths, $url);
        }
    }
}