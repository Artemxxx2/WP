<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;

class InstalledComponentsByInterfaceHelper
{
    public function help(Application $application, $interface, $force = false)
    {
        $interfaces = $this->_getInterfaces($application, $force);
        
        if (!isset($interface)) return $interfaces;
        
        return isset($interfaces[$interface]) ? array_keys($interfaces[$interface]) : [];
    }
    
    protected function _getInterfaces(Application $application, $force)
    {
        $local = $application->LocalComponents($force);
        $components = $application->InstalledComponents($force);
        $data = [];
        foreach (array_keys($components) as $component_name) {
            if (!empty($local[$component_name]['interfaces'])) {
                foreach ($local[$component_name]['interfaces'] as $interface) {
                    $data[$interface][$component_name] = true;
                }
            }
        }

        return $data;
    }
}