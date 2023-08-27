<?php
namespace SabaiApps\Directories\Component;

use SabaiApps\Framework\Application\IEventListener;
use SabaiApps\Directories\Application;

abstract class AbstractComponent implements IEventListener
{
    protected $_name, $_config, $_system = false;
    
    /** @var Application $_application */
    protected $_application;

    private static $_models = [];

    public function __construct($name, Application $application)
    {
        $this->_name = $name;
        $this->_application = $application;
    }
    
    final public function init(array $config)
    {
        $this->_config = $config;
        $this->_init();
    }
    
    protected function _init() {}
    
    public function handleEvent($eventType, array $eventArgs)
    {
        if (!is_callable([$this, 'on' . $eventType])) return;

        return @call_user_func_array([$this, 'on' . $eventType], $eventArgs);
    }

    final public function __toString()
    {
        return $this->_name;
    }
    
    final public function getApplication()
    {
        return $this->_application;
    }

    final public function getName()
    {
        return $this->_name;
    }

    final public function getVersion()
    {
        $class = get_class($this);
        return $class::VERSION;
    }
    
    final public function getPackage()
    {
        $class = get_class($this);
        return $class::PACKAGE;
    }

    final public function getModel($modelName = null)
    {
        if (!isset(self::$_models[$this->_name])) {
            self::$_models[$this->_name] = new Model($this);
        }

        return isset($modelName) ? self::$_models[$this->_name]->getRepository($modelName) : self::$_models[$this->_name];
    }

    final public function getConfig($name = null)
    {        
        if (0 === $num_args = func_num_args()) return $this->_config;
        if ($num_args === 1) return isset($this->_config[$name]) ? $this->_config[$name] : null;

        $args = func_get_args();
        $config = $this->_config;
        foreach ($args as $arg) {
            if (!isset($config[$arg])) return null;

            $config = $config[$arg];
        }

        return $config;
    }

    public function getDefaultConfig()
    {
        // Override this to provide default configuration parameters
        return [];
    }

    /**
     * Installs the plugin
     */
    public function install()
    {
        if ($schema = $this->_hasSchema()) {
            $this->_application->getPlatform()->updateDatabase($schema);
        }
        $this->_createVarDirs();

        return $this;
    }

    /**
     * Uninstalls the plugin
     * @throws RuntimeException
     */
    public function uninstall($removeData = false)
    {
        if ($removeData
            && ($latest_schema = $this->_hasSchema())
        ) {
            if (false === $schema_old = $this->_getOlderSchemaList($this->getVersion())) {
                throw new RuntimeException('Failed fetching schema files.');
            }

            if (!empty($schema_old)) {
                // get the last schema file
                $previous_schema = array_pop($schema_old);
            } else {
                $previous_schema = $latest_schema;
            }
            $this->_application->getPlatform()->updateDatabase(null, $previous_schema);
        }

        return $this;
    }

    /**
     * Upgrades the plugin
     * @throws \RuntimeException
     */
    public function upgrade($current, $newVersion, System\Progress $progress = null)
    {
        if ($this->_hasSchema()) {
            if ((false === $schema_old = $this->_getOlderSchemaList($current->version))
                || (false === $schema_new = $this->_getNewerSchemaList($current->version, $newVersion))
            ) {
                throw new \RuntimeException('Failed fetching schema files.');
            }
            
            if (!empty($schema_new)) {
                $sorter = function ($a, $b) {return version_compare($a, $b, '<') ? -1 : 1;};
                usort($schema_new, $sorter); // sort from old to new
                $new_schema = array_pop($schema_new);
                $previous_schema = null;
                if (!empty($schema_old)) {
                    usort($schema_old, $sorter); // sort from old to new
                    // get the last schema file
                    $previous_schema = array_pop($schema_old);
                }
                $this->_application->getPlatform()->updateDatabase($new_schema, $previous_schema);
                if (isset($progress)) {
                    $progress->set(sprintf(
                        'Updated database schema of ' . $this->_name . ' from %s to %s.',
                        isset($previous_schema) ? basename($previous_schema) : 'none',
                        basename($new_schema)
                    ));
                }
            }
        }

        $this->_createVarDirs();

        return $this;
    }
    
    protected function _createVarDirs()
    {
        // Create data directory
        if ($dir = $this->hasVarDir()) {
            if (!is_dir($path = $this->getVarDir())) {
                @mkdir($path, 0755, true);
            }
            if (is_array($dir)) {
                foreach ($dir as $_dir) {
                    if (!is_dir($path = $this->getVarDir($_dir))) {
                        @mkdir($path, 0755);
                    }
                }
            }
        }
    }

    private function _getSchemaList()
    {
        $list = [];
        $schema_dir = $this->_application->getComponentPath($this->_name) . '/schema';
        if (false === $files = glob($schema_dir . '/*.php', GLOB_NOSORT)) return false; // return false on error
        foreach ($files as $file) {
            if (preg_match('/^\d+(?:\.\d+)*(?:\-[a-zA-Z]+\.*\d*)?\.php$/', basename($file))) {
                $file_version = basename($file, '.php');
                $list[$file_version] = $file;
            }
        }

        return $list;
    }

    private function _getOlderSchemaList($version)
    {
        if (!$list = $this->_getSchemaList()) return $list;

        return array_intersect_key($list, array_flip(array_filter(
            array_flip($list),
            function ($v) use ($version) { return version_compare($v, $version, "<="); }
        )));
    }

    private function _getNewerSchemaList($version, $maxVersion)
    {
        if (!$list = $this->_getSchemaList()) return $list;

        return array_intersect_key($list, array_flip(array_filter(
            array_flip($list),
            function ($v) use ($version, $maxVersion) { return version_compare($v, $version, ">") && version_compare($v, $maxVersion, "<="); }
        )));
    }

    final protected function _hasSchema($version = 'latest')
    {
        $schema_path = $this->_application->getComponentPath($this->_name) . '/schema/' . $version . '.php';

        return file_exists($schema_path) ? $schema_path : false;
    }

    final public function getVarDir($subdir = null)
    {
        $var_dir = $this->_application->getPath($this->_application->getPlatform()->getVarDir()) . '/' . $this->_name;
        
        return isset($subdir) ? $var_dir . '/' . $subdir : $var_dir;
    }

    public function hasVarDir()
    {
        return false;
    }
    
    public function isInstallable()
    {
        return true;
    }
    
    public function isUpgradeable($currentVersion, $newVersion)
    {
        return version_compare($currentVersion, $newVersion, '<');
    }
    
    public function hasSlug($name, $lang = null)
    {
        return $this->_application->getPlatform()->hasSlug($this->_name, strtolower($name), $lang);
    }
    
    public function getSlug($name, $lang = null)
    {
        return $this->_application->getPlatform()->getSlug($this->_name, strtolower($name), $lang);
    }
    
    public function getTitle($name, $lang = null)
    {
        return $this->_application->getPlatform()->getTitle($this->_name, strtolower($name), $lang);
    }
    
    
}