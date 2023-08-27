<?php
namespace SabaiApps\Directories\Platform;

use SabaiApps\Directories\Exception;

class AbstractUpdater
{
    const URL = 'https://directoriespro.com/updates';
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
     * @return AbstractUpdater
     */
    public static function getInstance(AbstractPlatform $platform)
    {
        if (!isset(static::$_instance)) {
            static::$_instance = new static($platform);
        }
        return static::$_instance;
    }
    
    public function getInfo($package, $licenseType, $licenseKey, $force = false)
    {
        $info = $this->_getInfoCache();
        if ($force
            || !isset($info[$package])
        ) {
            $params = [
                'action' => 'info',
                'package' => $package,
                'license_type' => $licenseType,
                'license_key' => $licenseKey,
                'site_version' => $this->_platform->getSiteVersion(),
                'site_url' => $this->_platform->getSiteUrl(),
                'package_version' => $this->_platform->getPackageVersion($package),
                'version_pref' => defined('DRTS_UPDATER_VERSION_PREF') ? DRTS_UPDATER_VERSION_PREF : '',
            ];
            if (!is_array($info)) $info = [];
            try {
                $response = $this->_platform->remotePost(self::URL, $params);
                if (!$decoded = json_decode($response, true)) {
                    throw new Exception\RuntimeException('Failed decoding package info.');
                }
                $info[$package] = $decoded;
                $this->_setInfoCache($info);
            } catch (\Exception $e) {
                $info[$package] = false;
                $this->_setInfoCache($info);
                throw $e;
            }
        }
        
        return $info[$package];
    }
    
    protected function _getInfoCache()
    {
        if ((!$timeout = $this->_getOption('core_updater_package_info_timeout'))
            || $timeout < time()
        ) return;
        
        return $this->_getOption('core_updater_package_info');
    }
    
    protected function _setInfoCache($info)
    {
        // Use option instead of cache so that the cache may not be cleared by the user
        $this->_setOption('core_updater_package_info', $info, false);
        $this->_setOption('core_updater_package_info_timeout', time() + 18000, false); // cache 5 hours
    }

    protected function _getOption($name)
    {
        return $this->_platform->getOption($name);
    }

    protected function _setOption($name, $value, $autoload = true)
    {
        $this->_platform->setOption($name, $value, $autoload);
    }
}
