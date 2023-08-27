<?php
namespace SabaiApps\Directories\Component\Dashboard\Panel;

use SabaiApps\Directories\Application;
use SabaiApps\Framework\User\AbstractIdentity;

abstract class AbstractPanel implements IPanel
{
    protected $_application, $_name, $_info, $_links = [];

    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
    }
    
    public function dashboardPanelInfo($key = null)
    {
        if (!isset($this->_info)) $this->_info = (array)$this->_dashboardPanelInfo();

        return isset($key) ? (isset($this->_info[$key]) ? $this->_info[$key] : null) : $this->_info;
    }
    
    public function dashboardPanelOnLoad($isPublic = false){}
    
    public function dashboardPanelContent($link, array $settings, array $params, AbstractIdentity $identity = null)
    {
        return print_r($link, true);
    }
    
    public function dashboardPanelLinks(array $settings, AbstractIdentity $identity = null)
    {
        $user_id = isset($identity) ? $identity->id : $this->_application->getUser()->id;
        if (!isset($this->_links[$user_id])) {
            $links = $this->_application->Filter('dashboard_panel_links', (array)$this->_dashboardPanelLinks($settings, $identity), [$this->_name, $user_id]);
            if (!empty($links)) {
                uasort($links, function ($a, $b) {
                    if ($a['weight'] === $b['weight']) return strnatcmp($a['title'], $b['title']);

                    return $a['weight'] < $b['weight'] ? -1 : 1;
                });
            }
            $this->_links[$user_id] = $links;
        }
        return $this->_links[$user_id];
    }

    public function dashboardPanelSettingsForm(array $settings, array $parents){}

    public function dashboardPanelLabel()
    {
        return $this->dashboardPanelInfo('label');
    }

    abstract protected function _dashboardPanelInfo();
    abstract protected function _dashboardPanelLinks(array $settings, AbstractIdentity $identity = null);
    
    public function panelHtmlLinks(array $settings, $firstActive = true, $badge = false, AbstractIdentity $identity = null, $language = null)
    {
        $links = $this->dashboardPanelLinks($settings, $identity);
        if (!isset($language)) {
            $language = $this->_application->getPlatform()->getCurrentLanguage();
        }
        foreach (array_keys($links) as $link_name) {
            $link =& $links[$link_name];

            // Maybe translate link title
            if (!empty($link['title_is_custom'])
                && $language
            ) {
                $link['title'] = $this->_application->System_TranslateString($link['title'], $this->_name . '_panel_' . $link_name . '_label', 'dashboard_panel', $language);
            }

            $link['title'] = $this->_application->H($link['title']);
            if (isset($link['icon'])) {
                $link['title'] = '<span><i class="fa-fw ' . $link['icon'] . '"></i> ' . $link['title'] . '</span>';
            }
            if (isset($link['count'])) {
                if ($badge) {
                    $link['title'] .= sprintf(' <span class="%1$sbadge %1$sbadge-pill %1$sbadge-secondary">%2$d</span>', DRTS_BS_PREFIX, $link['count']);
                } else {
                    $link['title'] .= ' (' . $link['count'] . ')';
                }
            }
            $link['attr'] = [
                'class' => 'drts-dashboard-panel-link',
                empty($link['no_ajax']) ? 'data-ajax-url' : 'data-url' => $this->_application->getComponent('Dashboard')->getPanelUrl($this->_name, $link_name, '', [], true, $identity),
                'data-panel-name' => $this->_name,
                'data-link-name' => $link_name,
            ];
            if ($firstActive) {
                if (is_string($firstActive)) {
                    if ($link_name === $firstActive) {
                        $link['attr']['class'] .= ' ' . DRTS_BS_PREFIX . 'active';
                    }
                } else {
                    if (!isset($is_first)) {
                        $link['attr']['class'] .= ' ' . DRTS_BS_PREFIX . 'active';
                        $is_first = false;
                    }
                }
            }
        }
        
        return $links;
    }
}