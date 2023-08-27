<?php
namespace SabaiApps\Directories\Component\System\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\System\Model\Component;
use SabaiApps\Directories\Exception;

class ComponentHelper
{
    public function help(Application $application, $componentName)
    {
        if (!$component = $application->getModel('Component', 'System')->fetchById($componentName)) {
            throw new Exception\RuntimeException('Failed fetching component from database: ' . $componentName);
        }
        return $component;
    }
    
    public function install(Application $application, $componentName, $priority = 0)
    {   
        // Check if plugin name is not a reserved name
        $reserved_component_names = array('Sabai', 'Main', 'Admin', 'Component', 'Application', 'Model', 'Helper', 'Core', 'Platform', 'Components');
        if (in_array(strtolower($componentName), array_map('strtolower', $reserved_component_names))) {
            throw new Exception\UnexpectedValueException(sprintf('Component name %s is reserved by the system.', $componentName));
        }
        if (!preg_match(Application::COMPONENT_NAME_REGEX, $componentName)) {
            throw new Exception\UnexpectedValueException(sprintf('Invalid component name %s.', $componentName));
        }
        
        $_component = $application->fetchComponent($componentName);
        
        if (!$_component->isInstallable()) {
            throw new Exception\ComponentNotInstallableException('Component is not installable.');
        }
        
        $component = $application->getModel(null, 'System')->create('Component');
        $component->name = $_component->getName();
        $component->version = $_component->getVersion();
        $component->priority = $priority >= 99 ? 98 : $priority;
        $component->config = $_component->getDefaultConfig();
        $component->events = $application->ComponentEvents($_component);
        
        $component->markNew()->commit();

        $_component->install();
        
        return $component;
    }
    
    public function installAll(Application $application, array $components)
    {
        if (empty($components)) return;
        
        $component_entities = [];
        foreach ($components as $component_name) {
            $component_entities[$component_name] = $this->install($application, $component_name, 0);
        }
        $application->reloadComponents();
        foreach ($component_entities as $component_name => $component_entity) {
            $application->Action('system_component_installed', array($component_entity));
        }
        $application->getPlatform()->clearCache();

        return array_keys($component_entities);
    }
    
    public function upgrade(Application $application, $component, $force = false)
    {        
        if (!$component instanceof Component) {
            $component = $this->help($application, $component);
        }
        if (!$application->isComponentLoaded($component->name)) return;
        
        $_component = $application->getComponent($component->name);
        $new_version = $_component->getVersion();
        if ($force
            || $_component->isUpgradeable($component->version, $new_version)
        ) {
            $_component->upgrade($component, $new_version);
            $component->version = $new_version;
            $component->config = array_replace_recursive($_component->getDefaultConfig(), $component->config);
            $component->events = $application->ComponentEvents($_component);
            $component->commit();
        }
        
        return $component;
    }
    
    public function upgradeAll(Application $application, array $components = null, $force = false)
    {
        $model = $application->getModel('Component', 'System');
        if (!empty($components)) {
            $model->name_in($components);
        }
        $component_entities = $component_versions = [];
        foreach ($model->fetch() as $component) {
            $component_versions[$component->name] = $component->version;
            if ($component_entity = $this->upgrade($application, $component, $force)) {
                $component_entities[$component->name] = $component_entity;
            }
        }
        $application->reloadComponents();
        foreach ($component_entities as $component_name => $component_entity) {
            $application->Action('system_component_upgraded', array($component_entity, $component_versions[$component_name]));
        }
        $application->getPlatform()->clearCache();

        return array_keys($component_entities);
    }
    
    public function saveConfig(Application $application, $componentName, array $config, $merge = true)
    {
        if (!$application->isComponentLoaded($componentName)) return;
        
        $component = $this->help($application, $componentName);
        
        // Merge current config?
        if ($merge && $component->config) {
            $this->_merge($config, (array)$component->config, $merge);
        }
        
        $component->config = $config;
        $component->commit();
    }
    
    protected function _merge(array &$new, array $old, $deeper = true)
    {
        // Do not merge if values contains integer keys, since the value is coming from
        // checkboxes which we do not want to have it merged with old values.
        if (array_key_exists(0, $new)
            || array_key_exists(0, $old)
        ) return;
        
        $new += $old;
        if ($deeper !== true) {
            --$deeper;
            if ($deeper <= 0) return;
        }
        
        foreach (array_keys($new) as $key) {
            if (is_array($new[$key])
                && array_key_exists($key, $old)
                && is_array($old[$key])
            ) {
                $this->_merge($new[$key], $old[$key]);
            }
        }
    }
}
