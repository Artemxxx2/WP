<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;

class DisplayHelper
{
    protected $_templatePaths = [];
    
    public function help(Application $application, Entity\Type\IEntity $entity, $display, array $vars, array $options = [], $extension = '.html')
    {
        $bundle_name = $entity->getBundleName();
        $display_name = is_array($display) ? $display['name'] : $display;
        if (!isset($this->_templatePaths[$bundle_name][$display_name])) {
            if ($template_path = $this->hasCustomTemplate($application, $bundle_name, $display_name, $extension)) {
                if ($extension === '.html') {
                    $template_name = basename($template_path, '.html.php');
                    // Load custom CSS for this display if any
                    $css_path = dirname($template_path) . '/' . $template_name . '.css';
                    if (file_exists($css_path)) {
                        $application->getPlatform()->addCssFile(
                            $application->FileUrl($css_path),
                            'drts-entity-display-template-name-' . $template_name,
                            ['drts'],
                            false
                        );
                    }
                }
            } else {
                $template_path = false;
            }
            $this->_templatePaths[$bundle_name][$display_name] = $template_path;
        }
        if ($this->_templatePaths[$bundle_name][$display_name]) {
            $application->getTemplate()->includeFile($this->_templatePaths[$bundle_name][$display_name], [
                'entity' => $entity,
                'display' => $display, // this may be either string or array
                'display_name' => $display_name,
                'options' => $options,
            ] + $vars);
        } else {
            if (isset($vars['CONTEXT'])) {
                $options['attr']['id'] = $entity->getUniqueId(substr($vars['CONTEXT']->getContainer(), 1));
            }
            if ($rendered = $application->Display_Render(
                $entity->getBundleName(),
                $display,
                $entity,
                $options
            )) {
                if (!empty($options['return'])) return $rendered;
                echo $rendered;
            }
        }
    }
    
    public function hasCustomTemplate(Application $application, $entityOrBundle, $displayName, $extension = '.html')
    {
        $bundle = $application->Entity_Bundle($entityOrBundle);
        $template_name = $bundle->type . '-display_' . $displayName;
        $templates = $bundle->group ? [$template_name . '-' . $bundle->group] : [];
        $templates[] = $template_name;
        foreach ($templates as $_template_name) {
            if ($template_path = $application->getTemplate()->exists($_template_name, $extension)) {
                return $template_path;
            }
        }
        return false;
    }
    
    public function preRender(Application $application, array $entities, $displayName, $displayType = 'entity')
    {
        $entities_by_bundle = $html = [];
        foreach ($entities as $entity_id => $entity) {
            $entities_by_bundle[$entity->getBundleName()][$entity_id] = $entity;
        }
        foreach (array_keys($entities_by_bundle) as $bundle_name) {
            $pre_rendered = $this->preRenderbyBundle($application, $bundle_name, $entities_by_bundle[$bundle_name], $displayName, $displayType);
            if (!empty($pre_rendered['html'])) {
                $html = $pre_rendered['html'];
            }
        }
        
        return ['entities' => $entities, 'html' => $html];
    }
    
    public function preRenderbyBundle(Application $application, $bundleName, array $entities, $displayName, $displayType = 'entity')
    {
        $ret = array(
            'entities' => $entities,
            'html' => [],
        );
        if (($display = $application->Display_Display($bundleName, $displayName, $displayType))
            && $display['type'] === 'entity'
            && $display['pre_render']
        ) {
            $application->callHelper(
                'Display_Render_preRender',
                array($display, $application->Entity_Bundle($bundleName), &$ret)
            );
        }
        
        return $ret;
    }
}