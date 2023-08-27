<?php
namespace SabaiApps\Directories\Component\System\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class WidgetsHelper
{
    protected static $_widgetCount = 0, $_cssLoaded = false;
    
    /**
     * Returns all available widgets
     * @param Application $application
     */
    public function help(Application $application, $useCache = true)
    {
        if (!$useCache
            || (!$widgets = $application->getPlatform()->getCache('system_widgets'))
        ) {
            $widgets = [];
            foreach ($application->InstalledComponentsByInterface('System\IWidgets') as $component_name) {
                if (!$application->isComponentLoaded($component_name)) continue;
                
                foreach ($application->getComponent($component_name)->systemGetWidgetNames() as $widget_name) {
                    if (!$widget = $application->getComponent($component_name)->systemGetWidget($widget_name)) {
                        continue;
                    }
                    try {
                        $info = $widget->systemWidgetInfo();
                    } catch (Exception\IException $e) {
                        continue;
                    }
                    $widgets[$widget_name] = array(
                        'component' => $component_name,
                        'title' => isset($info['title']) ? (string)$widget->systemWidgetInfo('title') : '',
                        'summary' => isset($info['summary']) ? (string)$widget->systemWidgetInfo('summary') : '',
                        'load_js' => !empty($info['load_js']),
                    );
                }
            }
            $application->getPlatform()->setCache($widgets, 'system_widgets', 0);
        }

        return $widgets;
    }
    
    public function widget(Application $application, $widgetName, $loadModel = false)
    {
        $widgets = $this->help($application);
        if (!isset($widgets[$widgetName])
            || !$application->isComponentLoaded($widgets[$widgetName]['component'])
        ) return;
        
        return $application->getComponent($widgets[$widgetName]['component'])->systemGetWidget($widgetName);
    }
    
    public function renderWidget(Application $application, $widgetName, array $settings)
    {
        IF (!$widget = $this->widget($application, $widgetName, true)) return;
        
        if (!$content = $widget->systemWidgetContent($settings)) return;
        
        $class = 'drts drts-system-widget drts-system-widget-'. str_replace('_', '-', $widgetName) .' ' . DRTS_BS_PREFIX . 'clearfix';
        $id = 'drts-system-widget-' . ++self::$_widgetCount;
        $div = '<div class="' . $class . '" id="' . $id . '">';
        $ret = array($div);
        if (is_array($content['content'])) {
            $ret[] = $application->getTemplate()->render(['system_widget_' . $widgetName, 'system_widget_list'], $content);
        } else {
            $ret[] = $content['content'];
        }
        $ret[] = '</div>';
        
        if ($widget->systemWidgetInfo('load_js')) {
            $application->getPlatform()->loadDefaultAssets(true, false) // CSS will be loaded
                ->addJs('DRTS.init($("#' . $id . '"));', true, -99);
        }
        
        if (!self::$_cssLoaded) {
            $application->getPlatform()->loadDefaultAssets(false); // load CSS
            self::$_cssLoaded = true;
        }
        
        return implode(PHP_EOL, $ret);
    }
}