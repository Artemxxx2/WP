<?php
namespace SabaiApps\Directories\Component\Field\Helper;

use SabaiApps\Directories\Application;

class TypesHelper
{
    private $_features = [];

    /**
     * Returns all available field types
     * @param Application $application
     */
    public function help(Application $application, $useCache = true)
    {
        if (!$useCache
            || (!$field_types = $application->getPlatform()->getCache('field_types'))
        ) {
            $field_types = [];
            foreach ($application->InstalledComponentsByInterface('Field\ITypes') as $component_name) {
                if (!$application->isComponentLoaded($component_name)) continue;
                
                foreach ($application->getComponent($component_name)->fieldGetTypeNames() as $type) {
                    $field_type = $application->getComponent($component_name)->fieldGetType($type);
                    if (!is_object($field_type)
                        || null === ($info = $field_type->fieldTypeInfo())
                    ) continue;
                    
                    $creatable = isset($info['creatable']) && !$info['creatable'] ? false : true;
                    $widgets = $this->_getFeaturesByFieldType($application, $type, 'Widget', 'label', __('Default', 'directories'));
                    $renderers = $this->_getFeaturesByFieldType($application, $type, 'Renderer', 'label', __('Default', 'directories'));
                    $filters = $this->_getFeaturesByFieldType($application, $type, 'Filter', 'label', __('Default', 'directories'));
                    $default_widget = isset($info['default_widget']) ? $info['default_widget'] : $type;
                    $default_renderer = isset($info['default_renderer']) ? $info['default_renderer'] : $type;
                    $field_types[$type] = array(
                        'component' => $component_name,
                        'type' => $type,
                        'default_widget' => isset($widgets[$default_widget]) ? $default_widget : current(array_keys($widgets)),
                        'default_renderer' => isset($renderers[$default_renderer]) ? $default_renderer : current(array_keys($renderers)),
                        'widgets' => $widgets,
                        'renderers' => $renderers,
                        'filters' => $filters,
                        'label' => (string)@$info['label'],
                        'description' => (string)@$info['description'],
                        'creatable' => $creatable,
                        'creatable_filters' => $this->_getFeaturesByFieldType($application, $type, 'Filter', 'creatable', true),
                        'icon' => @$info['icon'],
                        'admin_only' => !empty($info['admin_only']),
                    );
                    $field_types[$type] += $info;
                }
            }
            $field_types = $application->Filter('field_types', $field_types);
            uasort($field_types, function ($a, $b) { return strcmp($a['label'], $b['label']); });
            $application->getPlatform()->setCache($field_types, 'field_types');
        }

        return $field_types;
    }
    
    private function _getFeaturesByFieldType(Application $application, $fieldType, $featureName, $key = 'label', $default = null)
    {
        if (!isset($this->_features[$featureName][$key])) {
            if (!isset($this->_features[$featureName])) {
                $this->_features[$featureName] = [];
            }
            $this->_features[$featureName][$key] = [];
            $helper = 'Field_' . $featureName . 's';
            $method1 = 'fieldGet' . $featureName;
            $method2 = 'field' . $featureName . 'Info';
            foreach ($application->$helper() as $name => $component) {
                if (!$application->isComponentLoaded($component)) {
                    continue;
                }
                if (!$feature = $application->getComponent($component)->$method1($name)) {
                    $application->logError('Feature ' . $featureName . ' (' . $name . ') not found for ' . $component);
                    continue;
                }
                $info = $feature->$method2();
                foreach ((array)@$info['field_types'] as $field_type) {
                    if (isset($info[$key])) {
                        if ($info[$key] === false) continue;
                    } else {
                        if (!isset($default)) continue;
                        $info[$key] = $default;
                    }
                    
                    $this->_features[$featureName][$key][$field_type][$name] = @$info[$key];
                }
            }
        }
        return isset($this->_features[$featureName][$key][$fieldType]) ? $this->_features[$featureName][$key][$fieldType] : [];        
    }
    
    public function clearCache(Application $application)
    {
        $application->getPlatform()->deleteCache('field_types');
    }
}