<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class LocalComponentsHelper
{
    protected static $_componentPaths = [];

    public function help(Application $application, $force = false)
    {
        if ($force || (false === $components = $application->getPlatform()->getCache('core_components_local'))) {
            $components = $this->_getLocalComponents($application);
            $application->getPlatform()->setCache($components, 'core_components_local');
        }
        return $components;
    }

    protected function _getLocalComponents(Application $application)
    {
        $logs[] = 'Loading local component files';
        $components = [];
        // Get paths to available addons
        $directories = $application->Filter('core_component_paths', []);
        // Add the core addon path
        array_unshift($directories, [dirname(__DIR__) . '/Component', Application::VERSION]);

        // Check files that can be loaded
        $files_to_load = $component_versions = [];
        foreach ($directories as $directory) {
            if (!is_array($directory)
                || empty($directory[0])
                || empty($directory[1])
            ) continue;

            $version = $directory[1];
            $directory = $directory[0];
            $logs[] = 'Searching component files from ' . $directory;
            $directory = $application->getPath($directory);
            if (!$files = glob($directory . '/*', GLOB_ONLYDIR)) {
                $logs[] = 'No valid files found under ' . $directory;
                continue;
            }

            $logs[] = 'Checking files';
            foreach ($files as $file) {
                $component_name = basename($file);
                $logs[] = 'Checking ' . $component_name;

                // Skip addons without a valid name
                if (!preg_match(Application::COMPONENT_NAME_REGEX, $component_name)) continue;

                // Skip if addon already loaded unless newer version
                if (isset($component_versions[$component_name])
                    && version_compare($component_versions[$component_name], $version, '>=') // use plugin version to assume component version
                ) {
                    $logs[] = 'The component is already loaded';
                    continue;
                }

                // Skip if no valid component file
                if (!file_exists($component_file = $directory . '/' . $component_name . '/' . $component_name . 'Component.php')) {
                    $logs[] = 'Component file not found, skipping';
                    continue;
                }
                $logs[] = 'Component file found';

                $component_versions[$component_name] = $version;

                // Register addon path so that addon files can be autoloaded
                self::addComponentPath($component_name, $directory . '/' . $component_name);

                $files_to_load[$directory][$component_name] = $component_file;
            }
        }

        // Load files
        spl_autoload_register([__CLASS__, 'autoload'], true, true);
        $package_root = $application->getPackagePath();
        $component_namespace = 'SabaiApps\Directories\Component';
        foreach ($files_to_load as $directory => $files) {
            foreach ($files as $component_name => $file) {
                $logs[] = 'File for component ' . $component_name . ' found';

                require_once $file;
                $component_class = $component_namespace . '\\' .  $component_name . '\\' . $component_name . 'Component';
                if (!class_exists('\\' . $component_class, false)
                    || !is_subclass_of($component_class, $component_namespace . '\AbstractComponent')
                ) continue;

                $logs[] = 'Class for component ' . $component_name . ' found';

                if ($interfaces = class_implements($component_class, false)) {
                    foreach (array_keys($interfaces) as $k) {
                        if (strpos($interfaces[$k], $component_namespace) === 0) {
                            $_interface = trim(substr($interfaces[$k], strlen($component_namespace)), '\\');
                            $interfaces[$_interface] = $_interface;
                        }
                        unset($interfaces[$k]);
                    }
                } else {
                    $interfaces = [];
                }
                if (is_callable([$component_class, 'interfaces'])
                    && ($_interfaces = call_user_func(array($component_class, 'interfaces'))) // check for extra component interfaces
                ) {
                    $interfaces += array_flip($_interfaces);
                }
                $path = $directory . '/' . $component_name;
                $components[$component_name] = [
                    'version' => $version = $component_class::VERSION,
                    'package' => $component_class::PACKAGE,
                    'interfaces' => array_keys($interfaces),
                    'path' => isset($path) ? substr($path, strlen($package_root)) : null,
                    'description' => is_callable([$component_class, 'description']) ? call_user_func([$component_class, 'description']) : '',
                ];

                $logs[] = 'Component ' . $component_name . ' (' . $version . ') loaded';
            }
        }
        spl_autoload_unregister([__CLASS__, 'autoload']);
        $logs[] = 'done.';
        $application->getPlatform()->setOption('addons_local_log', implode('...', $logs), false);
        ksort($components);

        return $components;
    }

    public static function addComponentPath($component, $path)
    {
        self::$_componentPaths[$component] = $path;
    }

    public static function autoload($class)
    {
        if (0 !== strpos($class = trim($class, '\\'), 'SabaiApps\Directories\Component\\')) return;

        $_class = substr($class, strlen('SabaiApps\Directories\Component\\'));

        if (false === strpos($_class, '\\')) return;

        $_parts = explode('\\', $_class);
        $component = array_shift($_parts);
        if (isset(self::$_componentPaths[$component])
            && include self::$_componentPaths[$component] . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $_parts) . '.php'
        ) return;

        throw new Exception\RuntimeException('Failed loading addon class ' . $class);
    }
}
