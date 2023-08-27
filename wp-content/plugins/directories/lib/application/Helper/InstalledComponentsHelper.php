<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;

class InstalledComponentsHelper
{
    public function help(Application $application, $force = false)
    {
        if ($force || (false === $ret = $application->getPlatform()->getCache('core_components_installed'))) {
            $ret = [];
            try {
                $components = $application->fetchComponent('System')->getModel('Component')->fetch(0, 0, 'priority', 'DESC');
                foreach ($components as $component) {
                    $events = [];
                    if ($component->events) {
                        $default_event_priority = $component->name === 'System' ? 99 : 10;
                        foreach ((array)$component->events as $event) {
                            if (!is_array($event)) {
                                $event = [$event];
                            }
                            $events[] = [strtolower($event[0]), isset($event[1]) ? $event[1] : $default_event_priority];
                        }
                    }
                    $ret[$component->name] = [
                        'version' => $component->version,
                        'config' => $component->config ?: [],
                        'events' => $events,
                    ];
                }
            } catch (\Exception $e) {
                // Probably application has not been installed yet
                $application->logError($e);
            }
            $application->getPlatform()->setCache($ret, 'core_components_installed');
        }

        return $ret;
    }
}
