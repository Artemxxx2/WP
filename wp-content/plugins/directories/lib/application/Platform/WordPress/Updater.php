<?php
namespace SabaiApps\Directories\Platform\WordPress;

use SabaiApps\Directories\Platform\AbstractUpdater;

class Updater extends AbstractUpdater
{
    public function addPlugin($pluginName, $licenceType, $licenceKey)
    {
        // Define the alternative API for updating checking
        add_filter('site_transient_update_plugins', function ($transient) use ($pluginName, $licenceType, $licenceKey) {
            // For some reason one of sites did not receive an object causing fatal error
            if (!is_object($transient)) return $transient;

            // Get the remote info
            try {
                if (!$info = $this->getInfo($pluginName, $licenceType, $licenceKey)) {
                    return $transient;
                }
            } catch (\Exception $e) {
                return $transient;
            }

            // If a newer version is available, add the update
            if (version_compare($this->_platform->getPackageVersion($pluginName), $info['version'], '<')) {
                $obj = new \stdClass(); // WordPress expects an object
                $obj->slug = $obj->plugin = $info['slug'];
                $obj->new_version = $info['version'];
                $obj->url = $info['homepage'];
                if (isset($info['download_link'])) { // download link is not available if no valid license key
                    $obj->package = $info['download_link'];
                }
                $transient->response[$pluginName . '/' . $pluginName . '.php'] = $obj;
            }

            return $transient;
        }, 99999);

        // Define the alternative response for information checking
        add_filter('plugins_api', function ($false, $action, $arg) use ($pluginName, $licenceType, $licenceKey) {
            if ($action !== 'plugin_information'
                || $arg->slug !== $pluginName
            ) return $false;

            try {
                $info = $this->getInfo($pluginName, $licenceType, $licenceKey);
                return (object)$info; // WordPress expects an object
            } catch (\Exception $e) {
                return $false;
            }
        }, 99999, 3);
    }

    public function clearOldVersionInfo()
    {
        if (!$info = $this->_getInfoCache()) return;

        $save = false;
        foreach (array_keys($info) as $package) {
            if (false === $info[$package]
                || !isset($info[$package]->version)
                || (!$current_version = $this->_platform->getPackageVersion($package))
                || version_compare($current_version, $info[$package]->version, '>')
            ) {
                unset($info[$package]);
                $save = true;
            }
        }
        if ($save) {
            $this->_setInfoCache($info);
        }
    }

    protected function _getOption($name)
    {
        return is_multisite() ? get_site_option('drts_' . strtolower($name)) : parent::_getOption($name);
    }

    protected function _setOption($name, $value, $autoload = true)
    {
        if (!is_multisite()) {
            parent::_setOption($name, $value, $autoload);
        } else {
            update_site_option('drts_' . strtolower($name), $value);
        }
    }
}
