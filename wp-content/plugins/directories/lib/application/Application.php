<?php
namespace SabaiApps\Directories;

use SabaiApps\Framework\Application\AbstractHttpApplication;
use SabaiApps\Framework\Application\Context as FrameworkContext;
use SabaiApps\Framework\Application\IController;
use SabaiApps\Framework\User\User;

class Application extends AbstractHttpApplication
{
    public static $p;
    private $_running = false,
        $_template, $_platform, $_user,
        $_components = [], $_componentsLoaded = [], $_componentsLoadedTimestamp,
        $_mobileDetect;

    // System version constants
    const VERSION = '1.3.108', PACKAGE = 'directories';

    const COMPONENT_NAME_REGEX = '/^[a-zA-Z0-9]{2,}$/';
    
    // Route types
    const ROUTE_NORMAL = 0, ROUTE_TAB = 1, ROUTE_MENU = 2, ROUTE_CALLBACK = 3,
        ROUTE_ACCESS_LINK = 0, ROUTE_ACCESS_CONTENT = 1,
        ROUTE_TITLE_NORMAL = 0, ROUTE_TITLE_TAB = 1, ROUTE_TITLE_MENU = 3, ROUTE_TITLE_INFO = 4;
    // Column types
    const COLUMN_INTEGER = 'integer', COLUMN_BOOLEAN = 'boolean', COLUMN_VARCHAR = 'text',
        COLUMN_TEXT = 'clob', COLUMN_DECIMAL = 'decimal';
    
    public function __construct(Platform\AbstractPlatform $platform)
    {
        parent::__construct($platform->getRouteParam());
        $this->_platform = $platform;
        spl_autoload_register([$this, 'autoload'], true, true);
        // Set helper directory and default helper functions
        $this->addHelperDir(__DIR__ . '/Helper', 'SabaiApps\\Directories\\Helper\\')
            ->setHelper('Platform', [$this, 'getPlatform'])
            ->setHelper('H', function(Application $application, $str, $quoteStyle = ENT_QUOTES, $doubleEncode = false) {
                return htmlspecialchars($str, $quoteStyle, 'UTF-8', $doubleEncode);
            })
            ->setHelper('Htmlize', function(Application $application, $str, $inlineTagsOnly = false, $forCaching = false) {
                return $application->getPlatform()->htmlize($str, $inlineTagsOnly, $forCaching);
            })
            ->setHelper('Attr', function (Application $application, array $attr, $exclude = null, $attrPrefix = '', $prefix = ' ', $quoteStyle = ENT_COMPAT) {
                if (empty($attr)) return '';
        
                if (isset($exclude)) {
                    foreach ((array)$exclude as $k) unset($attr[$k]);
                }
                if ($attrPrefix) $attrPrefix = $application->H($attrPrefix);
                foreach ($attr as $k => $v) $attr[$k] = sprintf('%s%s="%s"', $attrPrefix, $application->H($k), $application->H((string)$v, $quoteStyle));

                return $prefix . implode(' ', $attr); 
            });
    }
    
    public function autoload($class)
    {
        if (empty($this->_componentsLoaded)
            || 0 !== strpos($class = trim($class, '\\'), __NAMESPACE__ . '\\Component\\')
        ) return;

        $_class = substr($class, strlen(__NAMESPACE__ . '\\Component\\'));
        if (strpos($_class, '\\')) {
            $_parts = explode('\\', $_class);
            $component = array_shift($_parts);
            if (isset($this->_componentsLoaded[$component]['path'])) {
                include $this->getPackagePath() . $this->_componentsLoaded[$component]['path']
                    . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $_parts) . '.php';
            }
        } else {
            if (isset($this->_componentsLoaded[$_class]['path'])) {
                include $this->getPackagePath() . $this->_componentsLoaded[$_class]['path'] . '.php';
            }
        }
    }

    public function loadComponents()
    {
        if (!isset($this->_componentsLoadedTimestamp)) {
            $this->_loadComponents();
        }

        return $this;
    }

    public function reloadComponents($clearObjectCache = true)
    {
        if ($clearObjectCache) {
            $this->_components = [];
        }
        $this->_loadComponents(true);

        return $this;
    }

    public function run(IController $controller, FrameworkContext $context, $route = null)
    {
        $this->_running = true;

        $this->Action('core_run', [$context, $controller, $route]);

        $response = parent::run($controller, $context, $route);

        $this->Action('core_run_complete', [$context, $route]);

        $this->_running = false;

        return $response;
    }

    public function isRunning()
    {
        return $this->_running;
    } 
    
    public function backtrace($print = false, $limit = 10)
    {
        return $print ? debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $limit) : debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $limit);
    }

    /**
     * @return Platform\AbstractPlatform
     */
    public function getPlatform()
    {
        return $this->_platform;
    }

    /**
     * @return \SabaiApps\Framework\DB\AbstractDB 
     */
    public function getDB()
    {
        return $this->_platform->getDB();
    }
    
    public function getPath($path)
    {
        if (strpos($path, '\\') === false) {
            // Not a windows path
            // Make sure to start with single slash, some servers return // for some reason 
            return '/' . ltrim($path, '/');
        }
        $path = str_replace('\\', '/', $path);
        if (0 !== $first_slash_pos = strpos($path, '/')) {
            $path = substr($path, $first_slash_pos);  // remove c: part
        }
        return $path;
    }
    
    public function getPackagePath()
    {
        if (!isset($this->_packagePath)) {
            $this->_packagePath = $this->getPath($this->getPlatform()->getPackagePath());
        }
        return $this->_packagePath;
    }

    public function setUser(User $user)
    {
        $user_changed = $this->_user && $this->_user->id !== $user->id;
        // Notify that the current user object has been initialized
        $this->Action('core_user_initialized', [$user, $user_changed]);
        
        $this->_user = $user;
        
        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        if (!isset($this->_user)) {
            // Initialize the current user object if not any set
            if ($user = $this->_platform->getCurrentUser()) {
                $user->setAdministrator($this->_platform->isAdministrator($user->id));
            } else {
                $user = new User($this->_platform->getUserIdentityFetcher()->getAnonymous());
            }
            $this->setUser($user);
        }
        
        return $this->_user;
    }

    /**
     * A shortcut method for fetching the model object of an component
     * @param string $modelName
     * @param string $componentName
     * @return Component\Model
     */
    public function getModel($modelName = null, $componentName = null)
    {
        return $this->getComponent($componentName)->getModel($modelName);
    }
    
    /**
     * @return Template
     */
    public function getTemplate()
    {
        if (!isset($this->_template)) {
            $template_dirs = [];
            foreach ($this->_platform->getCustomAssetsDir() as $dir) {
                $template_dirs[] = $dir;
            }
            $template_dirs[] = $this->_platform->getAssetsDir() . '/templates';
            $this->_template = new Template($this, $template_dirs);
        }
        return $this->_template;
    }

    public function isComponentLoaded($componentName)
    {
        return isset($this->_componentsLoaded[$componentName]);
    }

    /**
     * A shortcut method for fetching a component object
     * @param string $componentName
     * @return Component\AbstractComponent
     */
    public function getComponent($componentName)
    {
        if (!isset($this->_components[$componentName])) {
            // Create component
            $component = $this->_createComponent($componentName);
            // Let the component have chance to initialize itself
            $component->init($this->_componentsLoaded[$componentName]['config']);
            // Add to memory cache
            $this->_components[$componentName] = $component;
        }

        return $this->_components[$componentName];
    }
    
    /**
     * Gets a component which is not yet installed
     *
     * @param string $componentName
     * @param array $config
     * @return Component\AbstractComponent
     */
    public function fetchComponent($componentName, array $config = [])
    {
        $component = $this->_createComponent($componentName);
        // Let the component have chance to initialize itself
        $component->init($config + $component->getDefaultConfig());
        
        return $component;
    }
    
    /**
     * @return Component\AbstractComponent
     */
    private function _createComponent($componentName)
    {
        // Instantiate component
        $class = '\SabaiApps\Directories\Component\\' . $componentName . '\\' . $componentName . 'Component';
        if (!class_exists($class, false)) {
            $component_file_path = $this->getComponentPath($componentName) . '/' . $componentName . 'Component.php';
            if (!@include $component_file_path) {
                // Clear component info cache to prevent loop caused by InstalledComponents helper trying to fetch System component when component info is reloaded
                if ($componentName === 'System') {
                    $this->clearComponentInfoCache();
                }
                // Reload component info and try again
                $this->_reloadComponentInfo($componentName);
                if (!@include $component_file_path) {
                    $this->clearComponentInfoCache();
                    throw new Exception\ComponentNotFoundException('Component file for component ' . $componentName . ' was not found at ' . $component_file_path);
                }
            }
        }
        $reflection = new \ReflectionClass($class);
        return $reflection->newInstanceArgs([$componentName, $this]);       
    }

    /**
     * Returns the full path to a component directory
     * @param string $componentName
     * @return string
     */
    public function getComponentPath($componentName)
    {
        if ($path = $this->_getComponentInfo($componentName, 'path')) {
            return $this->getPackagePath() . $path;
        }
        throw new Exception\ComponentNotFoundException('Component info for component ' . $componentName . ' was not found.');
     }
    
    protected function _getComponentInfo($componentName, $key)
    {
        if (!isset($this->_componentsLoaded[$componentName])) {
            // Fetch from local file
            $local_components = $this->LocalComponents();
            if (isset($local_components[$componentName])) {
                return isset($local_components[$componentName][$key]) ? $local_components[$componentName][$key] : null;
            }
            
            // No local file data, so force reload
            $this->_reloadComponentInfo($componentName);
        }
        return isset($this->_componentsLoaded[$componentName][$key]) ? $this->_componentsLoaded[$componentName][$key] : null;        
    }
    
    protected function _reloadComponentInfo($componentName)
    {
        $this->_loadComponents(true);
        if (isset($this->_componentsLoaded[$componentName])) return;

        $this->clearComponentInfoCache();
        throw new Exception\ComponentNotInstalledException('The following component is not installed or loaded: ' . $componentName);
    }
    
    public function clearComponentInfoCache()
    {
        $this->_platform->deleteCache('core_components_loaded' . md5(__FILE__))
            ->deleteCache('core_components_local')
            ->deleteCache('core_components_installed');
    }
    
    private function _loadComponents($force = false)
    {
        if ($force
            || (!$info = $this->_platform->getCache('core_components_loaded' . md5(__FILE__)))
            || empty($info['components'])
        ) {
            $info = ['components' => [], 'timestamp' => time(), 'events' => []];
            $this->clearEventListeners();
            $local = $this->LocalComponents(true);
            $installed_components = $this->InstalledComponents(true);
            foreach (array_keys($installed_components) as $component_name) {
                if (!isset($local[$component_name])) continue;
                
                $component_local = $local[$component_name];
                $info['components'][$component_name] = [
                    'path' => $component_local['path'],
                    'config' => $installed_components[$component_name]['config'],
                    'package' => $component_local['package'],
                ];
                if (!empty($installed_components[$component_name]['events'])) {
                    $info['events'][$component_name] = $installed_components[$component_name]['events'];
                }
            }
            $this->_platform->setCache($info, 'core_components_loaded' . md5(__FILE__), 0);
        }

        if (empty($info['components'])) {
            $this->clearComponentInfoCache();
            throw new Exception\NotInstalledException();
        }

        // Load components
        $this->_componentsLoaded = $info['components'];
        $this->_componentsLoadedTimestamp = $info['timestamp'];
        
        // Attach events
        if (!empty($info['events'])) {
            foreach (array_keys($info['events']) as $component_name) {
                foreach ($info['events'][$component_name] as $event) {
                    $this->addEventListener($event[0], $component_name, $event[1]);
                }
            }
        }
        
        $this->Action('core_components_loaded');
    }
    
    protected function _createResponse()
    {
        return new Response();
    }
    
    protected function _getEventListener($eventListener)
    {
        return $this->getComponent($eventListener);
    }
    
    protected function _getHelper($name)
    {
        if (strpos($name, '_', 1)) {
            // Search component's helper directory
            if (($name_arr = explode('_', $name))
                && $this->isComponentLoaded($name_arr[0])
            ) {
                if (isset($name_arr[2])) {
                    if (!isset($this->_helpers[$name])) {
                        $_name = $name_arr[0] . '_' . $name_arr[1];
                        if (!isset($this->_helpers[$_name])) {
                            $this->setHelper($_name, [$this->_createHelper($name_arr[0], $name_arr[1]), 'help']);
                        }
                        $this->setHelper($name, [$this->_helpers[$_name][0], $name_arr[2]]);
                    }
                } else {
                    if (!isset($this->_helpers[$name])) {
                        $this->setHelper($name, [$this->_createHelper($name_arr[0], $name_arr[1]), 'help']);
                    }
                }
                return $this->_helpers[$name]; 
            }
            throw new Exception\RuntimeException(sprintf('Call to undefined application helper %s.', $name));
        }
        
        return parent::_getHelper($name);
    }
    
    protected function _createHelper($component, $helper)
    {
        $class = '\SabaiApps\Directories\Component\\' . $component . '\Helper\\' . $helper . 'Helper';
        return new $class($this);
    }

    public function isMobile()
    {
        return ($md = $this->isMobileDetectable()) ? $md->isMobile() : false;
    }

    public function isMobileDetectable()
    {
        if (!isset($this->_mobileDetect)) {
            if (class_exists('\Mobile_Detect')
                && $this->Filter('core_use_mobile_detect', true)
            ) {
                $this->_mobileDetect = new \Mobile_Detect();
            } else {
                $this->_mobileDetect = false;
            }
        }
        return $this->_mobileDetect;
    }
}