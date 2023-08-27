<?php
namespace SabaiApps\Directories\Component\Directory\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Component\Directory\Model\Directory;

class TypesHelper
{
    public function help(Application $application, $useCache = true)
    {        
        if (!$useCache
            || (!$ret = $application->getPlatform()->getCache('directory_types'))
        ) {
            $ret = [];
            foreach ($application->InstalledComponentsByInterface('Directory\ITypes') as $component_name) {
                if (!$application->isComponentLoaded($component_name)) continue;
                
                foreach ($application->getComponent($component_name)->directoryGetTypeNames() as $type) {
                    if (!$application->getComponent($component_name)->directoryGetType($type)) continue;

                    $ret[$type] = $component_name;
                }
            }
            $application->getPlatform()->setCache($ret = $application->Filter('directory_types', $ret), 'directory_types', 0);
        }
        
        return $ret;
    }
    
    private $_impls = [];

    /**
     * Gets an implementation of SabaiApps\Directories\Component\Directory\Type\IType interface for a given type
     * @param Application $application
     * @param string $type
     */
    public function impl(Application $application, $type, $returnFalse = false, $useCache = true)
    {
        if (!isset($this->_impls[$type])) {            
            if ((!$types = $this->help($application, $useCache))
                || !isset($types[$type])
                || !$application->isComponentLoaded($types[$type])
            ) {                
                if ($returnFalse) return false;
                throw new Exception\UnexpectedValueException(sprintf('Invalid directory type: %s', $type));
            }
            $this->_impls[$type] = $application->getComponent($types[$type])->directoryGetType($type);
        }

        return $this->_impls[$type];
    }
    
    public function settingsForm(Application $application, $type, array $settings = [], array $parents = [], array $submitValues = null)
    {
        $form = [];
        $directory_type = $type instanceof Directory ? $type->type : $type;
        foreach ($this->impl($application, $directory_type)->directoryInfo('content_types') as $content_type) {
            $content_type_settings_form = $application->Filter(
                'directory_content_type_settings_form',
                [],
                array(
                    $type,
                    $content_type,
                    $this->impl($application, $directory_type)->directoryContentTypeInfo($content_type),
                    isset($settings[$content_type]) ? $settings[$content_type] : null,
                    array_merge($parents, array($content_type)),
                    isset($submitValues[$content_type]) ? $submitValues[$content_type] : null,
                )
            );
            if ($content_type_settings_form) {
                $form[$content_type] = $content_type_settings_form;
            }
        }
        return $form;
    }
    
    public function entityBundleTypeInfo(Application $application, $type, $contentTypeInfo)
    {
        $info = array(
            'admin_path' => '/directories/:directory_name/content_types/:bundle_name',
        );
        if (is_string($contentTypeInfo)) {
            $contentTypeInfo = $this->impl($application, $type)->directoryContentTypeInfo($contentTypeInfo);
        }
        $info += $contentTypeInfo;
        
        // Prefix taxonomy bundle type names with directory type name
        if (!empty($info['taxonomies'])) {
            foreach (array_keys($info['taxonomies']) as $content_type) {
                $info['taxonomies'][$type . '__' . $content_type] = $info['taxonomies'][$content_type];
                unset($info['taxonomies'][$content_type]);
            }
        }
        
        $r = new \ReflectionClass(get_class($this->impl($application, $type)));
        $directory_type_path = dirname($r->getFileName());
        // Convert fields/displays/views file paths to absolute paths
        foreach (array('fields', 'displays', 'views') as $key) {
            if (!empty($info[$key])
                && is_string($info[$key])
                && !strpos($info[$key], '/') !== 0
            ) {
                $info[$key] = $directory_type_path . '/' . $info[$key];
            }
        }
        
        return $application->Filter('directory_content_type_to_entity_bundle_type', $info, array($type));
    }
}