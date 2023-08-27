<?php
namespace SabaiApps\Directories\Component\View\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Display\Controller\Admin\AddDisplay;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\View\Mode\IMode;

class ModesHelper
{
    public function help(Application $application, $excludeSystem = true, $useCache = true)
    {        
        if (!$useCache
            || (!$view_modes = $application->getPlatform()->getCache('view_modes'))
        ) {
            $view_modes = array(0 => [], 1 => []);
            foreach ($application->InstalledComponentsByInterface('View\IModes') as $component_name) {
                if (!$application->isComponentLoaded($component_name)
                    || (!$mode_names = $application->getComponent($component_name)->viewGetModeNames())
                ) continue;
   
                foreach ($mode_names as $mode_name) {
                    if (!$view_mode = $application->getComponent($component_name)->viewGetMode($mode_name)) continue;

                    $view_modes[(int)$view_mode->viewModeInfo('system')][$mode_name] = $component_name;
                }
            }
            $application->getPlatform()->setCache($view_modes, 'view_modes', 0);
        }
        
        return $excludeSystem ? $view_modes[0] : $view_modes[0] + $view_modes[1];
    }
    
    private $_impls = [];

    /**
     * Gets an implementation of SabaiApps\Directories\Component\View\IMode interface for a given view name
     * @param Application $application
     * @param string $mode
     */
    public function impl(Application $application, $mode, $returnFalse = false)
    {
        if (!isset($this->_impls[$mode])) {
            // Compat for <1.2.42
            if ($mode === 'photoslider') $mode = 'slider_photos';

            if ((!$view_modes = $this->help($application, false))
                || !isset($view_modes[$mode])
                || (!$application->isComponentLoaded($view_modes[$mode]))
            ) {                
                if ($returnFalse) return false;
                throw new Exception\UnexpectedValueException(sprintf('Invalid view mode: %s', $mode));
            }
            $this->_impls[$mode] = $application->getComponent($view_modes[$mode])->viewGetMode($mode);
        }

        return $this->_impls[$mode];
    }
    
    public function settingsForm(Application $application, IMode $mode, Entity\Model\Bundle $bundle, array $settings = [], array $parents = [], array $submitValues = null)
    {
        $settings += (array)$mode->viewModeInfo('default_settings');
        $form = (array)$mode->viewModeSettingsForm($bundle, $settings, $parents);
        if ($default_display = $mode->viewModeInfo('default_display')) {
            $displays = [];
            if (false === $mode->viewModeInfo('display_required')) { // allows empty display
                $displays[''] = __('— Select —', 'directories');
            }
            $displays += AddDisplay::existingDisplays($application, $bundle->name, $default_display);
            $form['display'] = [
                '#type' => 'select',
                '#title' => __('Select display', 'directories'),
                '#options' => $displays,
                '#horizontal' => true,
                '#default_value' => isset($settings['display']) && isset($displays[$settings['display']]) ? $settings['display'] : null,
            ];
            $form['display_cache'] = [
                '#type' => 'hidden',
                '#value' => false,
            ];
            /*
            $form['display_cache'] = $application->System_Util_cacheSettingsForm(
                isset($settings['display_cache']) ? $settings['display_cache'] : '',
                null,
                [60, 120],
                __('Cache content rendered by display', 'directories')
            );
            $form['display_cache_guest_only'] = [
                '#type' => 'checkbox',
                '#title' => __('Cache display for guest users only', 'directories'),
                '#default_value' => !isset($settings['display_cache_guest_only']) || $settings['display_cache_guest_only'],
                '#horizontal' => true,
                '#states' => [
                    'invisible' => [
                        sprintf('select[name="%s"]', $application->Form_FieldName(array_merge($parents, ['display_cache']))) => ['value' => ''],
                    ],
                ],
            ];
            */
        }
        
        return $application->Filter('view_mode_settings_form', $form, [$mode, $bundle, $settings, $parents, $submitValues]);
    }
}