<?php
namespace SabaiApps\Directories\Component\System;

use SabaiApps\Directories\Component\AbstractComponent;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Application;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class SystemComponent extends AbstractComponent implements IAdminRouter, ITools
{
    const VERSION = '1.3.108', PACKAGE = 'directories';

    public static function description()
    {
        return 'Provides API and UI for managing components.';
    }
    
    public function hasVarDir()
    {
        $ret = [];
        if (!$this->_application->getPlatform()->getLogDir()) {
            $ret[] = 'logs';
        }
        if (!$this->_application->getPlatform()->getTmpDir()) {
            $ret[] = 'tmp';
        }
        return $ret;
    }

    public function getLogDir()
    {
        if (!empty($this->_config['log_dir'])) return $this->_config['log_dir'];

        return ($dir = $this->_application->getPlatform()->getLogDir()) ? $dir : $this->getVarDir('logs');
    }

    public function getTmpDir()
    {
        return ($dir = $this->_application->getPlatform()->getTmpDir()) ? $dir : $this->getVarDir('tmp');
    }
    
    public function systemAdminRoutes()
    {
        return array(
            '/_drts/system/tool_with_progress' => array(
                'controller' => 'RunToolWithProgress',
            ),
            '/_drts/system/progress' => array(
                'controller' => 'Progress',
                'type' => Application::ROUTE_CALLBACK,
            ),
            '/_drts/system/log' => array(
                'controller' => 'ViewLog',
                'type' => Application::ROUTE_CALLBACK,
            ),
            '/_drts/system/download' => array(
                'controller' => 'Download',
                'type' => Application::ROUTE_CALLBACK,
            ),
        );
    }

    public function systemOnAccessAdminRoute(Context $context, $path, $accessType, array &$route){}

    public function systemAdminRouteTitle(Context $context, $path, $titleType, array $route){}

    public function onSystemIMainRouterInstalled(AbstractComponent $component)
    {
        $this->_onSystemIRouterInstalled($component, false);
    }

    public function onSystemIAdminRouterInstalled(AbstractComponent $component)
    {
        $this->_onSystemIRouterInstalled($component, true);
    }

    private function _onSystemIRouterInstalled(AbstractComponent $component, $admin = false)
    {
        if ($admin) {
            $this->_createRoutes($component, $component->systemAdminRoutes(), true);
        } else {
            $this->_createRoutes($component, $component->systemMainRoutes(false));
            foreach ($this->_application->getPlatform()->getLanguages() as $lang) {
                $this->_createRoutes($component, $component->systemMainRoutes($lang), false, $lang);
            }
        }
    }
    
    private function _createRoutes(AbstractComponent $component, array $routes, $admin = false, $lang = '')
    {
        if (empty($routes)) return;

        $model = $this->getModel();
        $root_paths = [];

        // Insert route data
        foreach ($routes as $route_path => $route_data) {
            $route_path = strtolower(rtrim($route_path, '/'));
            if ($lang !== '' && strpos($route_path, '/_drts') === 0) continue;
            
            $route = $model->create('Route');
            $route->markNew();
            $route->admin = $admin;
            $route->controller = (string)@$route_data['controller'];
            $route->forward = (string)@$route_data['forward'];
            $route->component = $component->getName();
            $route->controller_component = isset($route_data['controller_component']) ? $route_data['controller_component'] : $component->getName();
            $route->type = isset($route_data['type']) ? $route_data['type'] : Application::ROUTE_NORMAL;
            $route->path = $route_path;
            $route->format = (array)@$route_data['format'];
            $route->method = (string)@$route_data['method'];
            $route->access_callback = !empty($route_data['access_callback']) ? 1 : 0;
            $route->title_callback = !empty($route_data['title_callback']) ? 1 : 0;
            $route->callback_path = isset($route_data['callback_path']) ? $route_data['callback_path'] : $route_path;
            $route->callback_component = isset($route_data['callback_component']) ? $route_data['callback_component'] : $component->getName();
            $route->weight = isset($route_data['weight']) ? ($route_data['weight'] > 99 ? 99 : $route_data['weight']) : 9;
            $route->depth = substr_count($route_path, '/');
            $route->language = $lang;
            $route->data = (array)@$route_data['data'];
            if (!isset($route_data['priority'])) {
                // Set lower priority if it is a child route of another plugin
                if (0 !== strpos(str_replace('_', '', $route_path), '/' . strtolower($component->getName()))) {
                    $route->priority = 3; // default is 5
                }
            } else {
                $route->priority = intval($route_data['priority']);
            }

            if ($root_path = substr($route_path, 0, strpos($route_path, '/', 1))) {
                $root_paths[$root_path] = $root_path;
            }
        }

        $model->commit();

        // Clear cached route data
        if (!empty($root_paths)) {
            $lang = $this->_application->getPlatform()->getCurrentLanguage();
            foreach ($root_paths as $root_path) {
                $this->_application->getPlatform()->deleteCache('system_' . ($admin ? 'route_admin' : 'route') . str_replace('/', '_', $root_path) . '_' . $lang);
            }
        }
    }

    public function onSystemIMainRouterUninstalled(AbstractComponent $component)
    {
        $this->_onSystemIRouterUninstalled($component, false);
    }

    public function onSystemIAdminRouterUninstalled(AbstractComponent $component)
    {
        $this->_onSystemIRouterUninstalled($component, true);
    }

    private function _onSystemIRouterUninstalled(AbstractComponent $component, $admin = false)
    {
        $model = $this->getModel();
        $criteria = $model->createCriteria('Route')->admin_is($admin)->component_is($component->getName());
        $model->getGateway('Route')->deleteByCriteria($criteria);
    }

    public function reloadRoutes(AbstractComponent $component, $admin = false)
    {
        $this->_onSystemIRouterUninstalled($component, $admin);
        $this->_onSystemIRouterInstalled($component, $admin);
        return $this;
    }
    
    public function reloadAllRoutes($mainOnly = false)
    {
        $routes = $this->getModel('Route');
        if ($mainOnly) {
            $routes->admin_is(0);
            $interfaces = ['System\IMainRouter' => 0];
        } else {
            $interfaces = ['System\IMainRouter' => 0, 'System\IAdminRouter' => 1];
        }
        $routes->delete();
        foreach ($interfaces as $interface => $is_admin) {
            foreach ($this->_application->InstalledComponentsByInterface($interface) as $component_name) {
                if (!$this->_application->isComponentLoaded($component_name)) continue;
            
                $this->_onSystemIRouterInstalled($this->_application->getComponent($component_name), $is_admin);
            }
        }
        $this->_application->Action('system_all_routes_reloaded', [$mainOnly]);
        return $this;
    }

    public function onCoreResponseSendViewLayout(Context $context, &$content, &$vars)
    {
        $this->_application->getPlatform()->loadDefaultAssets()
            ->addJs('DRTS.init($("' . $context->getContainer() . '"));', true, -99);
    }

    public function onCoreResponseSendComplete(Context $context)
    {
        // Save response messages to session if flashing is enabled
        if ($context->isFlashEnabled() && ($flash = $context->getFlash())) {
            $this->_application->getPlatform()->setSessionVar('system_flash', $flash);
        }
    }

    public function getMainRoutes($rootPath = '/', $lang = null)
    {
        if (!isset($lang)) {
            if (!$lang = $this->_application->getPlatform()->getCurrentLanguage()) {
                $lang = '';
            }
        }
        return $this->_getRoutes($rootPath, false, $lang);
    }

    public function getAdminRoutes($rootPath = '/')
    {
        return $this->_getRoutes($rootPath, true);
    }

    private function _getRoutes($rootPath, $admin = false, $lang = '')
    {
        $root_path = rtrim($rootPath, '/');
        
        if ($lang !== '' && strpos($root_path, '/_drts') === 0) $lang = '';

        // Check if already cached
        $cache_id = 'system_' . ($admin ? 'route_admin' : 'route') . str_replace('/', '_', $root_path) . '_' . $lang;
        if ($cache = $this->_application->getPlatform()->getCache($cache_id)) {
            return $cache;
        }

        $ret = [];
        $routes = $this->getModel('Route')
            ->admin_is($admin)
            ->path_startsWith($root_path)
            ->language_is($lang)
            // fetch routes with lower priority first so that the ones with higher priority will overwrite them
            ->fetch(0, 0, 'priority', 'ASC');
        if ($routes->count()) {
            $root_path_dir = dirname($root_path);
            foreach ($routes as $route) {
                if (!$this->_application->isComponentLoaded($route->component)) continue;
                
                // Initialize route data
                // Any child route data already defined?
                $child_routes = !empty($ret[$route->path]['routes']) ? $ret[$route->path]['routes'] : [];
                $ret[$route->path] = $route->toArray();
                $ret[$route->path]['routes'] = $child_routes;

                $current_path = $route->path;
                while ($root_path_dir !== $parent_path = dirname($current_path)) {
                    $current_base = substr($current_path, strlen($parent_path) + 1); // remove the parent path part

                    if (!isset($ret[$current_path]['path'])) {
                        // Check whether format is defined if dynamic route
                        $format = [];
                        if (0 === strpos($current_base, ':') && isset($ret[$route->path]['format'][$current_base])) {
                            $format = $ret[$route->path]['format'][$current_base];
                            unset($ret[$route->path]['format'][$current_base]);
                        }
                        $ret[$current_path]['path'] = $current_path;
                        $ret[$current_path]['component'] = $route->component;
                        $ret[$current_path]['type'] = Application::ROUTE_NORMAL;
                        $ret[$current_path]['format'] = !empty($format) ? array($current_base => $format) : [];
                    }
                    if (!isset($ret[$parent_path]['component'])) $ret[$parent_path]['component'] = $route->component;
                    $ret[$parent_path]['routes'][$current_base] = $current_path;

                    $current_path = $parent_path;
                }
            }
        }

        // Allow components to modify routes
        $ret = $this->_application->Filter('system_routes', $ret, array($rootPath, $admin, $lang));
        // Cache routes
        $this->_application->getPlatform()->setCache($ret, $cache_id);

        return $ret;
    }

    public function onSystemComponentInstalled(Model\Component $componentEntity)
    {
        $component = $this->_application->getComponent($componentEntity->name);
        
        $this->_invokeComponentEvents($component, 'installed', 'install_success');
    }

    public function onSystemComponentUninstalled(Model\Component $componentEntity)
    {
        $component = $this->_application->getComponent($componentEntity->name);

        $this->_invokeComponentEvents($component, 'uninstalled', 'uninstall_success');
    }

    public function onSystemComponentUpgraded(Model\Component $componentEntity, $previousVersion)
    {
        $component = $this->_application->getComponent($componentEntity->name);
        
        $this->_invokeComponentEvents($component, 'upgraded', 'upgrade_success', array($previousVersion));
    }
    
    private function _invokeComponentEvents(AbstractComponent $component, $event, $event2, array $args = [])
    {
        $event_component_name = strtolower($component->getName());
        $args = array_merge(array($component), $args);
        $this->_application->Action($event_component_name . '_' . $event, $args);

        // Invoke first set of events for each interface implemented by the component
        if ($interfaces = class_implements($component, false)) { // get interfaces implemented by the plugin
            // Remove component namespace part
            $prefix = 'SabaiApps\\Directories\\Component\\';
            foreach (array_keys($interfaces) as $k) {
                if (strpos($interfaces[$k], $prefix) === 0) {
                    $_interface = trim(substr($interfaces[$k], strlen($prefix)), '\\');
                    $interfaces[$k] = $_interface;
                } else {
                    unset($interfaces[$k]);
                }
            }
            $interfaces = array_flip($interfaces);
        } else {
            $interfaces = [];
        }
        if (is_callable(array($component, 'interfaces'))
            && ($_interfaces = call_user_func(array($component, 'interfaces'))) // check for extra interfaces implemented
        ) {
            $interfaces += array_flip($_interfaces);
        }
        if (!empty($interfaces)) {
            // Dispatch event for each interface
            foreach (array_keys($interfaces) as $interface) {
                $action = str_replace('\\', '_', strtolower($interface)) . '_' . $event;
                $this->_application->Action($action, $args);
            }
        }
        
        $this->_application->Action($action = $event_component_name . $event2, $args);
        
        // Invoke second set of events for each interface implemented by the component
        if (!empty($interfaces)) {
            // Dispatch event for each interface
            foreach (array_keys($interfaces) as $interface) {
                $action = str_replace('\\', '_', strtolower($interface)) . '_' . $event2;
                $this->_application->Action($action, $args);
            }
        }
    }
    
    public function onSystemIWidgetsInstalled(AbstractComponent $component)
    {
        $this->_application->getPlatform()->deleteCache('system_widgets');
    }

    public function onSystemIWidgetsUninstalled(AbstractComponent $component)
    {
        $this->_application->getPlatform()->deleteCache('system_widgets');
    }

    public function onSystemIWidgetsUpgraded(AbstractComponent $component)
    {
        $this->_application->getPlatform()->deleteCache('system_widgets');
    }
    
    public function onCoreComponentsLoaded()
    {
        if ($logger = $this->_application->getLogger()) {
            if (empty($this->_config['disable_error_log'])) {
                // Add a log handler to write error logs
                $logger->pushHandler(new StreamHandler($this->getLogDir() . '/drts_error.log', Logger::ERROR));
            }
            if ($this->_application->getPlatform()->isDebugEnabled()) {
                // Add a log handler to write logs with debug level or up
                $logger->pushHandler(new StreamHandler($this->getLogDir() . '/drts_debug.log', Logger::DEBUG));
            }
        }

        // Show messages saved in session during the previous request as flash messages
        if ($flash = $this->_application->getPlatform()->getSessionVar('system_flash')) {
            $this->_application->getPlatform()->addFlash($flash)
                ->deleteSessionVar('system_flash');
        }
    }

    public function onCorePlatformWordPressAdminInit()
    {
        if (!$this->_application->getUser()->isAdministrator()) return;
        
        if (false === $updates = $this->_application->getPlatform()->getCache('system_component_updates')) {
            $updates = [];
            $installed_components = $this->_application->InstalledComponents();
            $local_components = $this->_application->LocalComponents();
            foreach ($installed_components as $component_name => $installed_component) {
                if (isset($local_components[$component_name])
                    && version_compare($installed_component['version'], $local_components[$component_name]['version'], '<')
                ) {
                    $updates[] = $component_name;
                }
            }
            $this->_application->getPlatform()->setCache($updates, 'system_component_updates');
        }
        if (!empty($updates)
            && $this->_application->getPlatform()->isAdmin()
        ) {
            $this->_application->getPlatform()->addFlash(array(
                array(
                    'msg' => sprintf(
                        __('There are %1$d upgradable component(s). Please go to System -> Tools and reload all components.', 'directories'),
                        count($updates)
                    ),
                    'level' => 'danger',
                ),
            ));
        }
    }

    
    public function onSystemAdminSystemLogsFilter(&$logs)
    {
        $logs['error'] = array(
            'label' => __('Error log', 'directories'),
            'file' => $this->getLogDir() . '/drts_error.log',
            'weight' => 1,
        );
        $logs['debug'] = array(
            'label' => __('Debug log', 'directories'),
            'file' => $this->getLogDir() . '/drts_debug.log',
            'weight' => 5,
            'days' => 30,
        );
    }

    public function onSystemCron(&$logs, &$lastRun, $force)
    {
        $clear_tmp = true;
        $clear_logs = [];
        $log_files = $this->_application->Filter('system_admin_system_logs', []);
        foreach ($log_files as $log_name => $log) {
            $clear_logs[$log_name] = empty($log['days']) ? 365 : $log['days'];
        }
        if (!$force) {
            if (!$this->_application->callHelper('System_Cron_canRunTask', [$this->_name . '_tmp', &$logs, &$lastRun, 604800])) {
                $clear_tmp = false;
            }
        }
        foreach (array_keys($clear_logs) as $log_name) {
            if (empty($lastRun[$this->_name . '_log_' . $log_name])) {
                // Never cleared, so set timestamp and do not clear this time
                $lastRun[$this->_name . '_log_' . $log_name] = time();
                unset($clear_logs[$log_name]);
            } elseif (time() - $lastRun[$this->_name . '_log_' . $log_name] < 86400 * $clear_logs[$log_name]) {
                // Do not clear since specified days have not yet passed since last run
                unset($clear_logs[$log_name]); // do not clear
            }
        }

        if ($clear_tmp) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->getTmpDir(), \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $file) {
                $func = ($file->isDir() ? 'rmdir' : 'unlink');
                $func($file->getRealPath());
            }
            $logs['success'][] = __('Cleared tmp directory', 'directories');
            $lastRun[$this->_name . '_tmp'] = time();
        }

        foreach (array_keys($clear_logs) as $log_name) {
            if (@unlink($log_files[$log_name]['file'])) {
                $logs['success'][] = sprintf(__('Cleared log file: %s', 'directories'), $log_files[$log_name]['label']);
                $lastRun[$this->_name . '_log_' . $log_name] = time();
            }
        }
    }

    public function systemGetToolNames()
    {
        return ['system_reload', 'system_run_cron', 'system_clear_cache', 'system_alter_collation', 'system_clear_logs'];
    }

    public function systemGetTool($name)
    {
        switch ($name) {
            case 'system_reload':
                return new Tool\ReloadComponentsTool($this->_application, $name);
            case 'system_run_cron':
                return new Tool\RunCronTool($this->_application, $name);
            case 'system_clear_cache':
                return new Tool\ClearCacheTool($this->_application, $name);
            case 'system_alter_collation':
                return new Tool\AlterCollationTool($this->_application, $name);
            case 'system_clear_logs':
                return new Tool\ClearLogsTool($this->_application, $name);
        }
    }

    public function onDirectoryAdminSettingsFormFilter(&$form)
    {

        $this->_application->callHelper('System_SettingsForm', [$this->_name, $this->_config, &$form]);
    }

    public function onCoreUninstallRemoveDataFilter(&$bool)
    {
        $bool = !empty($this->_config['uninstall_remove_data']);
    }
}
