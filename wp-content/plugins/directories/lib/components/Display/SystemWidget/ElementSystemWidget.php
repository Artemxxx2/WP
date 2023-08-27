<?php
namespace SabaiApps\Directories\Component\Display\SystemWidget;

use SabaiApps\Directories\Component\System\Widget\AbstractWidget;

class ElementSystemWidget extends AbstractWidget
{
    protected $_cacheable = false;
    
    protected function _systemWidgetInfo()
    {
        return array(
            'title' => __('Display Element', 'directories'),
            'summary' => __('Display content rendered by a display element.', 'directories'),
        );
    }
    
    protected function _getElementOptions($component, $bundle, array &$options, array $elements)
    {
        foreach (array_keys($elements) as $element_id) {
            $element =& $elements[$element_id];
            if (!empty($element['visibility']['globalize'])) {
                $options[$bundle->name . ',' . $element_id] = $component . ' - '
                    . $this->_application->Entity_BundleTypeInfo($bundle->type, 'label_singular') . ' - '
                    . (strlen($element['title']) ? strip_tags($element['title']) : $element['label'])
                    . ' (ID: ' . $element['id'] . ')';
            } else {
                if (!empty($element['children'])) {
                    $this->_getElementOptions($component, $bundle, $options, $element['children']);
                }
            }
        }
    }
    
    protected function _getWidgetSettings(array $settings)
    {
        $options = [];
        foreach ($this->_application->Entity_Bundles_sort() as $component_name => $bundles) {
            foreach ($bundles as $bundle_name => $bundle) {
                if ($display = $this->_application->Display_Display($bundle_name, 'detailed')) {
                    $this->_getElementOptions($component_name, $bundle, $options, $display['elements']);
                }
            }
        }
        
        return array(
            'element' => array(
                '#title' => __('Select display element', 'directories'),
                '#options' => $options,
                '#type' => 'select',
                '#default_value' => null,
            ),
        );
    }
    
    protected function _getWidgetContent(array $settings)
    {
        if (empty($settings['element'])
            || !isset($GLOBALS['drts_entity'])
            || (!$element = explode(',', $settings['element']))
            || $GLOBALS['drts_entity']->getBundleName() !== $element[0]
            || !isset($GLOBALS['drts_display_elements'][$element[0]][$element[1]])
            || !strlen($GLOBALS['drts_display_elements'][$element[0]][$element[1]])
        ) return;
        
        return $GLOBALS['drts_display_elements'][$element[0]][$element[1]];
    }
}