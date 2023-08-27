<?php
namespace SabaiApps\Directories\Component\Map\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class ApiHelper
{
    public function help(Application $application, $load = false, array $options = null)
    {
        $config = $application->getComponent('Map')->getConfig();
        $map_api_name = isset($config['lib']['map']) ? $config['lib']['map'] : 'googlemaps';
        if (empty($map_api_name)
            || (!$map_api = $this->impl($application, $map_api_name, true))
        ) return;

        if ($load) {
            $map_api_settings = isset($config['lib']['api'][$map_api_name]) ? $config['lib']['api'][$map_api_name] : [];
            $platform = $application->getPlatform()
                ->addJsFile('map-api.min.js', 'drts-map-api', 'drts', 'directories');
            $map_api->mapApiLoad($map_api_settings, $application->Filter('map_display_settings', $config['map']));

            if (!empty($options['map_field'])) {
                $platform->addJsFile('map-field.min.js', 'drts-map-field', 'drts-map-api', 'directories')
                    ->addJsFile('jquery.fitmaps.min.js', 'jquery-fitmaps', 'jquery', 'directories', true, true);
            }
        }

        return $map_api;
    }

    public function components(Application $application, $useCache = true)
    {        
        if (!$useCache
            || (!$ret = $application->getPlatform()->getCache('map_apis'))
        ) {
            $ret = [];
            foreach ($application->InstalledComponentsByInterface('Map\IApis') as $component_name) {
                if (!$application->isComponentLoaded($component_name)) continue;
                
                foreach ($application->getComponent($component_name)->mapGetApiNames() as $name) {
                    if (!$application->getComponent($component_name)->mapGetApi($name)) continue;

                    $ret[$name] = $component_name;
                }
            }
            $application->getPlatform()->setCache($ret, 'map_apis', 0);
        }
        
        return $ret;
    }
    
    private $_impls = [];

    /**
     * Gets an implementation of SabaiApps\Directories\Component\Map\Api\IApi interface for a given api name
     * @param Application $application
     * @param string $api
     */
    public function impl(Application $application, $name, $returnFalse = false, $useCache = true)
    {
        if (!isset($this->_impls[$name])) {
            if ((!$apis = $this->components($application, $useCache))
                || !isset($apis[$name])
                || !$application->isComponentLoaded($apis[$name])
            ) {                
                if ($returnFalse) return false;
                throw new Exception\UnexpectedValueException(sprintf('Invalid map API: %s', $name));
            }
            $this->_impls[$name] = $application->getComponent($apis[$name])->mapGetApi($name);
        }

        return $this->_impls[$name];
    }

    public function options(Application $application)
    {
        $options = [];
        foreach ($this->components($application) as $name => $component) {
            if (!$application->isComponentLoaded($component)) continue;

            $options[$name] = $application->getComponent($component)->mapGetApi($name)->mapApiInfo('label');
        }
        return $options;
    }

    public function load(Application $application, array $options = [])
    {
        return $this->help($application, true, $options);
    }

    private static $_lang, $_languages = array(
        'ar', 'bg', 'bn', 'ca', 'cs', 'da', 'de', 'el', 'en', 'en-AU', 'en-GB', 'es', 'eu',
        'fa', 'fi', 'fil', 'fr', 'gl', 'gu', 'he', 'hi', 'hr', 'hu', 'id', 'it', 'iw', 'ja', 'kn',
        'ko', 'lt', 'lv', 'ml', 'mr', 'nl', 'nn', 'no', 'or', 'pl', 'pt', 'pt-BR', 'pt-PT',
        'rm', 'ro', 'ru', 'sk', 'sl', 'sr', 'sv', 'tl', 'ta', 'te', 'th', 'tr', 'uk', 'vi',
        'zh-CN', 'zh-TW'
    );

    public function language(Application $application, $reset = false)
    {
        $language = $application->getPlatform()->getLocale();
        if (!isset(self::$_lang) || $reset) {
            if (strpos($language, '_')) {
                $langs = array_reverse(explode('_', $language));
                $langs[0] = strtolower($langs[1]) . '-' . strtoupper($langs[0]);
            } elseif (strpos($language, '-')) {
                $langs = array_reverse(explode('-', $language));
                $langs[0] = strtolower($langs[1]) . '-' . strtoupper($langs[0]);
            } else {
                $langs = array(strtolower($language));
            }
            self::$_lang = 'en';
            foreach ($langs as $lang) {
                if (in_array($lang, self::$_languages)) {
                    self::$_lang = $lang;
                    break;
                }
            }
        }
        return self::$_lang;
    }
}