<?php
namespace SabaiApps\Directories\Component\Display\Element;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Display\Model\Display;
use SabaiApps\Directories\Component\Display\Model\Element;
use SabaiApps\Directories\Component\Entity\Model\Bundle;

abstract class AbstractElement implements IElement
{
    /** @var Application */
    protected $_application;
    /** @var string */
    protected $_name;
    /** @var array */
    protected $_info;

    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
    }

    public function __toString()
    {
        return $this->_name;
    }

    public function displayElementInfo(Bundle $bundle, $key = null)
    {
        if (!isset($this->_info)) {
            $this->_info = (array)$this->_displayElementInfo($bundle);
        }

        return isset($key) ? (isset($this->_info[$key]) ? $this->_info[$key] : null) : $this->_info;
    }
        
    public function displayElementSupports(Bundle $bundle, Display $display)
    {
        if ($display->isAmp()
            && !$this->_displayElementSupportsAmp($bundle, $display)
        ) return false;
        
        return $this->_displayElementSupports($bundle, $display);
    }
        
    protected function _displayElementSupports(Bundle $bundle, Display $display)
    {
        return true;
    }
    
    protected function _displayElementSupportsAmp(Bundle $bundle, Display $display)
    {
        return false;
    }
    
    public function displayElementSettingsForm(Bundle $bundle, array $settings, Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = []){}
    
    public function displayElementTitle(Bundle $bundle, array $element)
    {
        return isset($element['heading']['label']) ? $this->_application->Display_ElementLabelSettingsForm_label($element['heading']) : '';
    }
    
    public function displayElementAdminAttr(Bundle $bundle, array $settings)
    {
        return [];
    }

    public function displayElementIsNoTitle(Bundle $bundle, array $element)
    {
        return false;
    }
        
    public function displayElementIsEnabled(Bundle $bundle, array $element, Display $display)
    {
        return true;
    }
    
    public function displayElementIsDisabled(Bundle $bundle, array $settings)
    {
        return false;
    }
    
    public function displayElementIsInlineable(Bundle $bundle, array $settings)
    {
        return (bool)$this->displayElementInfo($bundle, 'inlineable');
    }
    
    public function displayElementIsPreRenderable(Bundle $bundle, array &$element)
    {
        $ret = false;
        if (!empty($element['children'])) {
            foreach (array_keys($element['children']) as $child_id) {
                if ($element_impl = $this->_application->Display_Elements_impl($bundle, $element['children'][$child_id]['name'], true)) {
                    $element['children'][$child_id]['parent_visibility'] = empty($element['parent_visibility']) ? [] : $element['parent_visibility'];
                    if (!empty($element['visibility'])) {
                        $element['children'][$child_id]['parent_visibility'][] = $element['visibility'];
                    }
                    if ($element_impl->displayElementIsPreRenderable($bundle, $element['children'][$child_id])) {
                        $element['children'][$child_id]['pre_render'] = true;
                        $ret = true;
                    }
                }
            }
        }
        if (!$ret
            && !empty($element['settings']['_buttons']['enable'])
            && $this->displayElementInfo($bundle, 'buttonable')
        ) {
            foreach ($element['settings']['_buttons']['arrangement'] as $btn_name) {
                if ($btn = $this->_application->Display_Buttons_impl($bundle, $btn_name, true)) {
                    $btn_settings = isset($element['settings']['buttons'][$btn_name]['settings']) ? (array)$element['settings']['buttons'][$btn_name]['settings'] : [];
                    $ret = $btn->displayButtonIsPreRenderable($bundle, $btn_settings);
                }
            }
        }

        return $ret;
    }
    
    public function displayElementPreRender(Bundle $bundle, array $element, &$var)
    {
        if (!empty($element['children'])) {
            foreach ($element['children'] as $child) {
                if (!empty($child['pre_render'])
                    && ($element_impl = $this->_application->Display_Elements_impl($bundle, $child['name'], true))
                ) {
                    $element_impl->displayElementPreRender($bundle, $child, $var);
                }
            }
        }
        if (!empty($element['settings']['_buttons']['enable'])
            && $this->displayElementInfo($bundle, 'buttonable')
        ) {
            foreach ($element['settings']['_buttons']['arrangement'] as $btn_name) {
                if ($btn = $this->_application->Display_Buttons_impl($bundle, $btn_name, true)) {
                    $btn_settings = isset($element['settings']['buttons'][$btn_name]['settings']) ? (array)$element['settings']['buttons'][$btn_name]['settings'] : [];
                    $btn->displayButtonPreRender($bundle, $btn_settings, $var['entities']);
                }
            }
        }
    }
    
    public function displayElementOnCreate(Bundle $bundle, array &$data, $weight, Display $display, $elementName, $elementId){}
    public function displayElementOnUpdate(Bundle $bundle, array &$data, Element $element){}
    public function displayElementOnExport(Bundle $bundle, array &$data){}
    public function displayElementOnRemoved(Bundle $bundle, array $settings, $elementName, $elementId){}
    public function displayElementOnPositioned(Bundle $bundle, array $settings, $weight){}
    
    public function displayElementOnSaved(Bundle $bundle, Element $element)
    {
        if (isset($element->data['heading']['label'])) {
            $this->_application->Display_ElementLabelSettingsForm_registerLabel(
                $element->data['heading'],
                $this->displayElementStringId('heading', $element->element_id)
            );
        }
    }

    public function displayElementReadableInfo(Bundle $bundle, Element $element)
    {
        $info = $this->_application->Filter(
            'display_element_readable_info',
            (array)$this->_displayElementReadableInfo($bundle, $element),
            [$bundle, $element]
        );
        $settings = $element->data['settings'];
        if (!empty($settings['_labels']['enable'])
            && $this->displayElementInfo($bundle, 'labellable')
            && !empty($settings['_labels']['arrangement'])
        ) {
            $info['settings']['value']['_labels'] = [
                'label' => __('Labels', 'directories'),
                'value' => implode(', ', $this->_application->Display_Labels_labelLabels($bundle, $settings['_labels']['arrangement'])),
            ];
        }
        if (!empty($settings['_buttons']['enable'])
            && $this->displayElementInfo($bundle, 'buttonable')
            && !empty($settings['_buttons']['arrangement'])
        ) {
            $info['settings']['value']['_buttons'] = [
                'label' => __('Buttons', 'directories'),
                'value' => implode(', ', $this->_application->Display_Buttons_buttonLabels($bundle, $settings['_buttons']['arrangement'])),
            ];
        }
        return $info;
    }

    protected function _displayElementReadableInfo(Bundle $bundle, Element $element)
    {
        return [];
    }
    
    protected function _registerString($str, $name, $id, $elementName = null)
    {
        $this->_application->getPlatform()->registerString($str, $this->displayElementStringId($name, $id, $elementName), 'display_element');
    }
    
    protected function _unregisterString($name, $id, $elementName = null)
    {
        $this->_application->getPlatform()->unregisterString($this->displayElementStringId($name, $id, $elementName), 'display_element');
    }
    
    protected function _translateString($str, $name, $id, $elementName = null)
    {
        return $this->_application->System_TranslateString($str, $this->displayElementStringId($name, $id, $elementName), 'display_element');
    }
    
    public function displayElementStringId($name, $id, $elementName = null)
    {
        return self::stringId(isset($elementName) ? $elementName : $this->_name, $name, $id);
    }
    
    public static function stringId($elementName, $name, $id)
    {
        return $elementName . '_' . $name . '_' . $id;
    }
    
    protected function _renderChildren(Bundle $bundle, array $children, $var)
    {
        if (empty($children)) return;
        
        $ret = [];
        foreach ($children as $child) {
            $child_content = call_user_func_array(
                array($this->_application, 'Display_Render_element'),
                array($bundle, $child, $var)
            );
            if ($child_content) {
                $ret[] = $child_content;
            }
        }
        return $ret;
    }

    abstract protected function _displayElementInfo(Bundle $bundle);
}
