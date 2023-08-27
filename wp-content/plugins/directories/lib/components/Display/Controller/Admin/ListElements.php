<?php
namespace SabaiApps\Directories\Component\Display\Controller\Admin;

use SabaiApps\Directories\Controller;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Exception;

class ListElements extends Controller
{
    protected $_defaultType = 'field';
    
    protected function _doExecute(Context $context)
    {
        if ((!$display = $this->_getDisplay($context))
            || (!$bundle = $this->Entity_Bundle($display->bundle_name))
        ) {
            $context->setError();
            return;
        }
        $requested_types = $context->getRequest()->asArray('type');
        $element_types = $this->Display_Elements_types($bundle, $display->type);
        $elements = [];
        foreach (array_keys($this->Display_Elements($bundle, false)) as $element_name) {
            try {
                if ((!$element = $this->Display_Elements_impl($bundle, $element_name))
                    || !$element->displayElementSupports($bundle, $display)
                    || (!$element_info = $element->displayElementInfo($bundle))
                    || (isset($element_info['listable']) && !$element_info['listable'])
                    || ($requested_types && !in_array($element_info['type'], $requested_types))
                    || (!empty($element_info['displays']) && !in_array($display->name, (array)$element_info['displays']))
                ) continue;
            } catch (Exception\IException $e) {
                $this->logError($e);
                continue;
            }
            
            $info = $element->displayElementInfo($bundle);
            if (!empty($info['parent_element_name'])) continue;
           
            $elements[(string)@$info['type']][$element_name] = $info;
        }
        $sorter = function ($a, $b) {
            return strnatcmp($a['label'], $b['label']);
        };
        foreach (array_keys($elements) as $element_type) {
            uasort($elements[$element_type], $sorter);
        }
        foreach (array_keys($element_types) as $element_type) {
            if (empty($elements[$element_type])) {
                unset($element_types[$element_type]);
            }
        }
        $context->addTemplate(count($element_types) > 1 ? 'display_admin_elements_tabbed' : 'display_admin_elements')
            ->setAttributes(array(
                'element_types' => $element_types,
                'elements' => $elements,
                'default_type' => $this->_defaultType,
            ));
    }
    
    protected function _getDisplay(Context $context)
    {
        if ((!$display_id = $context->getRequest()->asInt('display_id'))
            || (!$display = $this->getModel('Display', 'Display')->fetchById($display_id))
        ) return false;

        return $display;
    }
}
