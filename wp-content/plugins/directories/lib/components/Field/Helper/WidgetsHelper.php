<?php
namespace SabaiApps\Directories\Component\Field\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class WidgetsHelper
{
    /**
     * Returns all available field widgets
     * @param Application $application
     */
    public function help(Application $application, $useCache = true)
    {
        if (!$useCache
            || (!$widgets = $application->getPlatform()->getCache('field_widgets'))
        ) {
            $widgets = [];
            foreach ($application->InstalledComponentsByInterface('Field\IWidgets') as $component_name) {
                if (!$application->isComponentLoaded($component_name)) continue;
                
                foreach ($application->getComponent($component_name)->fieldGetWidgetNames() as $widget_name) {
                    if (!$application->getComponent($component_name)->fieldGetWidget($widget_name)) {
                        continue;
                    }
                    $widgets[$widget_name] = $component_name;
                }
            }
            $widgets = $application->Filter('field_widgets', $widgets);
            $application->getPlatform()->setCache($widgets, 'field_widgets', 0);
        }

        return $widgets;
    }
    
    public function clearCache(Application $application)
    {
        $application->getPlatform()->deleteCache('field_widgets');
    }
    
    private $_impls = [];

    /**
     * Gets an implementation of Field\Widget\IWidget interface for a given widget type
     * @param Application $application
     * @param string $widget
     */
    public function impl(Application $application, $widget, $returnFalse = false)
    {
        if (!isset($this->_impls[$widget])) {
            $widgets = $this->help($application);
            // Valid widget type?
            if (!isset($widgets[$widget])
                || (!$application->isComponentLoaded($widgets[$widget]))
            ) {
                if ($returnFalse) return false;
                throw new Exception\UnexpectedValueException(sprintf('Invalid widget type: %s', $widget));
            }
            $this->_impls[$widget] = $application->getComponent($widgets[$widget])->fieldGetWidget($widget);
        }

        return $this->_impls[$widget];
    }
}