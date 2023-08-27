<?php
namespace SabaiApps\Directories\Platform\WordPress;
    
class Loader
{
    private static $_path, $_version, $_priority;
        
    public static function pluginsDir()
    {
        return isset(self::$_path) ? dirname(self::$_path) :WP_CONTENT_DIR . '/plugins';
    }
        
    public static function plugin($file = false)
    {
        return $file ? self::pluginsDir() . '/directories/directories.php' : 'directories';
    }
        
    public static function register($path, $version, $priority = 0)
    {
        if (isset(self::$_path)) {
            if (self::$_version !== $version) {
                if (version_compare($version, self::$_version, '<')) return;
            } else {
                if ($priority <= self::$_priority) return;
            }
            self::$_path = $path;
        } else {
            self::$_path = $path;
            add_action('plugins_loaded', function () {
                self::getPlatform()->run();
            });
            if (is_admin()) {
                register_activation_hook(self::plugin(true), function () {
                    self::getPlatform()->activate();
                });
                register_uninstall_hook(self::plugin(true), [__CLASS__, '_uninstall']); // needs to be static
            }
        }
        self::$_version = $version;
        self::$_priority = $priority;
    }
    
    /**
     * @return Platform
     */    
    public static function getPlatform()
    {
        if (!class_exists('\SabaiApps\Directories\Platform\WordPress\Platform', false)) {
            if (!isset(self::$_path)) {
                throw new \RuntimeException('Path is not set!');
            }
            require self::$_path . '/vendor/autoload.php';
        }
        return Platform::getInstance();
    }
    
    public static function _uninstall()
    {
        try {
            self::getPlatform()->getApplication(true, true)->Uninstall();
        } catch (\SabaiApps\Directories\Exception\NotInstalledException $e) {}
    }
}      