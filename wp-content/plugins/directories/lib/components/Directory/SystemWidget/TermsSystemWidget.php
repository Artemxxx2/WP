<?php
namespace SabaiApps\Directories\Component\Directory\SystemWidget;

use SabaiApps\Directories\Component\System\Widget\AbstractWidget;
use SabaiApps\Directories\Application;

class TermsSystemWidget extends AbstractWidget
{    
    protected $_bundleType, $_directoryType, $_contentType;
    
    public function __construct(Application $application, $name, $bundleType)
    {
        parent::__construct($application, $name);
        list($this->_bundleType, $content_bundle_type) = explode('___', $bundleType);
        list($this->_directoryType, $this->_contentType) = explode('__', $content_bundle_type);
    }
    
    protected function _systemWidgetInfo()
    {
        $directory_type_label = $this->_application->Directory_Types_impl($this->_directoryType)->directoryInfo('label');
        $bundle_type_label = $this->_application->Entity_BundleTypeInfo($this->_bundleType, 'label');
        return array(
            'title' => $directory_type_label . ' - ' . $bundle_type_label,
            'summary' => sprintf(__("A list of your site's %s", 'directories'), $bundle_type_label),
        );
    }
    
    protected function _getWidgetSettings(array $settings)
    {
        $directory_options = [];
        foreach ($this->_application->getModel('Directory', 'Directory')->type_is($this->_directoryType)->fetch() as $directory) {
            if (!$this->_application->Directory_Types_impl($directory->type)) continue;
            
            $directory_options[$directory->name] = $directory->getLabel();
        }
        if (empty($directory_options)) return;
        
        $directory_option_keys = array_keys($directory_options);
        $form = array(
            'directory' => array(
                '#title' => __('Select directory', 'directories'),
                '#options' => $directory_options,
                '#type' => count($directory_options) <= 1 ? 'hidden' : 'select',
                '#default_value' => array_shift($directory_option_keys),
            ),
            'depth' => array(
                '#type' => 'textfield',
                '#title' => __('Depth (0 for unlimited)', 'directories'),
                '#integer' => true,
                '#default_value' => 0, 
                '#size' => 3,
            ),
        );
        if ($this->_application->Entity_BundleTypeInfo($this->_bundleType, 'entity_image')
            || $this->_application->Entity_BundleTypeInfo($this->_bundleType, 'entity_icon')
        ) {
            $form['icon'] = array(
                '#type' => 'checkbox',
                '#title' => __('Show icon', 'directories'),
                '#default_value' => false, 
            );
            $form['icon_size'] = array(
                '#type' => 'select',
                '#title' => __('Icon size', 'directories'),
                '#default_value' => 'sm',
                '#options' => $this->_application->System_Util_iconSizeOptions(),
            );
        }
        $form += array(
            'hide_count' => array(
                '#type' => 'checkbox',
                '#title' => __('Hide post count', 'directories'),
                '#default_value' => true, 
            ),
            'hide_empty' => array(
                '#type' => 'checkbox',
                '#title' => __('Hide empty terms', 'directories'),
                '#default_value' => false, 
            ),
        );
        
        return $form;
    }
    
    protected function _getWidgetContent(array $settings)
    {
        if (!$bundle = $this->_application->Entity_Bundle($this->_bundleType, 'Directory', $settings['directory'])) return;
        
        $list = $this->_application->Entity_TaxonomyTerms_html(
            $bundle->name,
            array(
                'content_bundle' => $this->_directoryType . '__' . $this->_contentType,
                'hide_count' => !empty($settings['hide_count']),
                'hide_empty' => !empty($settings['hide_empty']),
                'depth' => (int)@$settings['depth'],
                'icon' => !empty($settings['icon']),
                'icon_size' => isset($settings['icon_size']) ? $settings['icon_size'] : 'sm',
                'link' => true,
                'prefix' => '— ',
            )
        );
        
        return $list ? '<div class="drts-entity-taxonomy-terms"><div>' . implode('</div><div>', $list) . '</div></div>' : null;
    }
}