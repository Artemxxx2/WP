<?php
namespace SabaiApps\Directories\Component\Field\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class RenderersHelper
{
    /**
     * Returns all available field renderers
     * @param Application $application
     */
    public function help(Application $application, $useCache = true)
    {
        if (!$useCache
            || (!$renderers = $application->getPlatform()->getCache('field_renderers'))
        ) {
            $renderers = [];
            foreach ($application->InstalledComponentsByInterface('Field\IRenderers') as $component_name) {
                if (!$application->isComponentLoaded($component_name)) continue;
                
                foreach ($application->getComponent($component_name)->fieldGetRendererNames() as $renderer_name) {
                    if (!$application->getComponent($component_name)->fieldGetRenderer($renderer_name)) {
                        continue;
                    }
                    $renderers[$renderer_name] = $component_name;
                }
            }
            $renderers = $application->Filter('field_renderers', $renderers);
            $application->getPlatform()->setCache($renderers, 'field_renderers', 0);
        }

        return $renderers;
    }
    
    private $_impls = [];

    /**
     * Gets an implementation of Field\Renderer\IRenderer interface for a given renderer type
     * @param Application $application
     * @param string $renderer
     */
    public function impl(Application $application, $renderer, $returnFalse = false)
    {
        if (!isset($this->_impls[$renderer])) {
            $renderers = $this->help($application);

            // Compat for <1.2.42
            if ($renderer === 'photoslider') $renderer = 'slider_photos';

            // Valid renderer type?
            if (!isset($renderers[$renderer])
                || (!$application->isComponentLoaded($renderers[$renderer]))
                || (!$_renderer = $application->getComponent($renderers[$renderer])->fieldGetRenderer($renderer))
            ) {                
                if ($returnFalse) return false;
                throw new Exception\UnexpectedValueException(sprintf('Invalid renderer type: %s', $renderer));
            }
            $this->_impls[$renderer] = $_renderer;
        }

        return $this->_impls[$renderer];
    }
}