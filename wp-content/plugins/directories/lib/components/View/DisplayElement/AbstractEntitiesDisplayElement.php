<?php
namespace SabaiApps\Directories\Component\View\DisplayElement;

use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

abstract class AbstractEntitiesDisplayElement extends Display\Element\AbstractElement
{    
    protected function _displayElementInfo(Bundle $bundle)
    {
        return [
            'type' => 'content',
            'default_settings' => [
                'view' => null,
                'hide_empty' => true,
            ],
            'icon' => 'far fa-list-alt',
            'cacheable' => true,
            'designable' => ['margin', 'padding'],
        ];
    }
    
    protected function _getEntitiesBundleType($entityOrBundle)
    {
        return $entityOrBundle instanceof IEntity ? $entityOrBundle->getBundleType() : $entityOrBundle->type;
    }
    
    protected function _getEntitiesComponent($entityOrBundle)
    {
        return $this->_application->Entity_Bundle($entityOrBundle)->component;
    }
    
    protected function _getEntitiesBundleGroup($entityOrBundle)
    {
        return $this->_application->Entity_Bundle($entityOrBundle)->group;
    }
    
    protected function _getEntitiesBundle($entityOrBundle, array $settings)
    {
        return $this->_application->Entity_Bundle(
            $this->_getEntitiesBundleType($entityOrBundle),
            $this->_getEntitiesComponent($entityOrBundle),
            $this->_getEntitiesBundleGroup($entityOrBundle)
        );
    }
    
    protected function _displayElementSupports(Bundle $bundle, Display\Model\Display $display)
    {
        return $display->type === 'entity' && $display->name === 'detailed';
    }
    
    public function displayElementSettingsForm(Bundle $bundle, array $settings, Display\Model\Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        return [
            'view' => [
                '#title' => __('Select view', 'directories'),
                '#type' => 'select',
                '#horizontal' => true,
                '#options' => $this->_getViewOptions($bundle, $settings),
                '#default_value' => $settings['view'],
            ],
            'hide_empty' => [
                '#title' => __('Hide if no content to show', 'directories'),
                '#type' => 'checkbox',
                '#horizontal' => true,
                '#default_value' => !empty($settings['hide_empty']),
            ],
        ];
    }

    protected function _getViewOptions(Bundle $bundle, array $settings)
    {
        $views = [];
        foreach ($this->_application->getModel('View', 'View')->bundleName_is($this->_getEntitiesBundle($bundle, $settings)->name)->fetch() as $view) {
            $views[$view->name] = $view->getLabel();
        }
        return $views;
    }
    
    protected function _getListEntitiesSettings(Bundle $bundle, array $element, IEntity $entity)
    {
        return array(
            'load_view' => $element['settings']['view'],
            'hide_empty' => !isset($element['settings']['hide_empty']) // compat with <=1.2.84
                || !empty($element['settings']['hide_empty']),
        );
    }
    
    protected function _getListEntitiesPath(Bundle $bundle, array $element, IEntity $entity)
    {
        return $bundle->getPath();
    }
    
    public function displayElementRender(Bundle $bundle, array $element, $var)
    {
        // Get bundle of entities to list
        if (!$_bundle = $this->_getEntitiesBundle($bundle, $element['settings'])) return;

        if (!$this->_application->Filter('view_display_element_entities_render', true, [$this->_name, $_bundle, $var])) return;
        
        if ((!$list_entities_settings = $this->_getListEntitiesSettings($_bundle, $element, $var))
            || empty($list_entities_settings['load_view'])
        ) return;
        
        if (empty($_bundle->info['parent'])
            && empty($_bundle->info['is_taxonomy'])
        ) {
            // @todo: See why filter button does not work with top level bundles when shown in a display. Until then, disable it.
            // Seems to work? Commented out on 7/5/2023
            //$list_entities_settings['settings']['filter']['show'] = false;
        }
        
        return $this->_application->getPlatform()->render(
            $this->_getListEntitiesPath($_bundle, $element, $var),
            ['settings' => ['hide_empty' => true] + $list_entities_settings],
            [
                'cache' => false,
                'title' => false,
                'container' => null,
                'render_assets' => false,
            ]
        );
    }

    protected function _displayElementReadableInfo(Bundle $bundle, Display\Model\Element $element)
    {
        $settings = $element->data['settings'];
        $ret = [];
        if (!empty($settings['view'])
            && ($views = $this->_getViewOptions($bundle, $settings))
            && isset($views[$settings['view']])
        ) {
            $ret['view'] = [
                'label' => __('Select view', 'directories'),
                'value' => $views[$settings['view']],
            ];
        }

        return ['settings' => ['value' => $ret]];
    }
}
