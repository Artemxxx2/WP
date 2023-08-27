<?php
namespace SabaiApps\Directories\Platform;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class Installer
{
    protected static $_instance;
    /**
     * @var AbstractPlatform
     */
    protected $_platform;

    public function __construct(AbstractPlatform $platform)
    {
        $this->_platform = $platform;
    }

    /**
     * @return Installer
     */
    public static function getInstance(AbstractPlatform $platform)
    {
        if (!isset(static::$_instance)) {
            static::$_instance = new static($platform);
        }
        return static::$_instance;
    }
    
    public function install(array $components = [])
    {
        if (intval(ini_get('max_execution_time')) < 600){
            @ini_set('max_execution_time', '600');
        }
        if (intval(ini_get('memory_limit')) < 128){
            @ini_set('memory_limit', '128M');
        }

        try {
            $this->_platform->getApplication(true, true, true);
            // If no exception, the package is already installed so do nothing
            return $this;
        } catch (Exception\NotInstalledException $e) {
            $app = $this->_platform->getApplication(false); // get application without loading components
        }

        $log = new \ArrayObject();

        try {
            $log[] = 'Clearing old cache data if any...';
            $this->_platform->clearCache();
            $log[] = 'done...';
        } catch (\Exception $e) {
            $log[] = $e->getMessage() . '...';
        }

        $log[] = 'Installing...';

        // Install the System component
        try {
            $system = $app->fetchComponent('System')->install();
            if (!$system_entity = $system->getModel('Component')->name_is('System')->fetchOne()) {
                throw new Exception\RuntimeException('Failed fetching the System component entity.');
            }
            $system_entity->config = $system->getDefaultConfig();
            $system_entity->events = $app->ComponentEvents($system);
            $system_entity->data = [];
            $system_entity->commit();
        } catch (\Exception $e) {
            throw new Exception\RuntimeException('Failed installing the System component. Error: ' . $e->getMessage());
        }

        $app->reloadComponents();

        $log[] = 'System component installed...';

        // Install core components first
        $components = [
            'System' => [],
            'Form' => [],
        ] + $components;
        $result = self::_installComponents($app, $components, $log, ['System' => $system_entity]);

        $log[] = 'done.';

        $this->_platform->clearCache();

        $install_log = implode('', (array)$log);
        $this->_platform->setOption('install_log', $install_log, false);

        if (!$result) {
            throw new Exception\RuntimeException(sprintf('Installation failed. Log: %s', $install_log));
        }

        return $this;
    }

    public function installPackage($package, $componentsPath = null)
    {
        if (intval(ini_get('max_execution_time')) < 600){
            @ini_set('max_execution_time', '600');
        }
        if (intval(ini_get('memory_limit')) < 128){
            @ini_set('memory_limit', '128M');
        }

        $log = new \ArrayObject();

        try {
            $log[] = 'Clearing old cache data if any';
            $this->_platform->clearCache();
            $log[] = 'done';
        } catch (\Exception $e) {
            $log[] = $e->getMessage();
        }

        $log[] = 'Installing ' . $package;

        $app = $this->_platform->getApplication(true, true, true);

        // Find components files
        if (!isset($componentsPath)) $componentsPath = $this->_platform->getPackagePath() . '/' . $package . '/lib/components';
        if (!$files = glob(rtrim($componentsPath, '/') . '/*', GLOB_ONLYDIR)) {
            throw new Exception\RuntimeException('No valid files found under ' . $componentsPath);
        }
        $components = [];
        foreach ($files as $file) {
            $component_name = basename($file);
            // Skip components without a valid name
            if (!preg_match(Application::COMPONENT_NAME_REGEX, $component_name)) continue;

            // Skip if no valid component file
            if (!file_exists($component_file = $componentsPath . '/' . $component_name . '/' . $component_name . 'Component.php')) {
                $log[] = 'Component file not found, skipping ' . $component_name;
                continue;
            }

            $components[$component_name] = [];
        }

        // Install components that are still not installed
        $installed_components = $app->InstalledComponents(true);
        $result = true;
        if ($components_to_install = array_diff_key($components, $installed_components)) {
            $result = self::_installComponents($app, $components_to_install, $log);
        }

        $log[] = 'done.';

        $this->_platform->clearCache();

        $install_log = implode('...', (array)$log);

        if (!$result) {
            throw new Exception\RuntimeException(sprintf('Failed installing %s. Log: %s', $package, $install_log));
        }

        $this->_platform->setOption('install_log_' . $package, $install_log, false);

        return $this;
    }

    protected static function _installComponents(Application $app, array $components, $log, array $componentsInstalled = [])
    {
        $failed = false;
        foreach ($components as $component => $component_settings) {
            if (isset($componentsInstalled[$component])) continue;

            $component_settings = array_merge(['priority' => 1], $component_settings);
            try {
                $entity = $app->System_Component_install($component, $component_settings['priority']);
            } catch (Exception\ComponentNotInstallableException $e) {
                $log[] = sprintf('skipping component %s since it may not be installed. Error: %s...', $component, $e->getMessage());
                continue;
            } catch (Exception\ComponentNotFoundException $e) {
                $log[] = sprintf('skipping component %s since its main file was not found. Error: %s...', $component, $e->getMessage());
                continue;
            } catch (Exception\ComponentNotInstalledException $e) {
                $log[] = sprintf('skipping component %s since its file and folders were not found. Error: %s...', $component, $e->getMessage());
                continue;
            } catch (\Exception $e) {
                $failed = true;
                $log[] = sprintf('failed installing component %s. Error: %s...', $component, $e->getMessage());
                break;
            }

            $componentsInstalled[$component] = $entity;

            $log[] = sprintf('%s component installed...', $component);
        }

        $app->reloadComponents();

        if (!$failed) {
            foreach ($componentsInstalled as $component => $component_entity) {
                $app->Action('system_component_installed', [$component_entity]);
            }
            // Reload components data
            $app->reloadComponents();
        } else {
            if (!empty($componentsInstalled)) {
                // Uninstall all components
                $log[] = 'Uninstalling installed components...';
                foreach (array_keys($componentsInstalled) as $component) {
                    try {
                        $app->getComponent($component)->uninstall(true);
                    } catch (\Exception $e) {
                        $log[] = sprintf('failed uninstalling the %s component! You must manually uninstall the component. Error: %s...', $component, $e->getMessage());
                        continue;
                    }
                    $log[] = sprintf('%s component uninstalled...', $component);
                }
            }
        }

        return !$failed;
    }
}