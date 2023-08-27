<?php
namespace SabaiApps\Directories\Component\Display\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display\Element\IElement;
use SabaiApps\Directories\Component\Display\Model\Display as DisplayModel;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Platform\WordPress\Platform;

class DisplayHelper
{
    protected $_displays = []; // runtime cache
    
    public function help(Application $application, $entityOrBundleName, $displayName = 'detailed', $type = 'entity', $useCache = true, $create = false)
    {
        $bundle = $application->Entity_Bundle($entityOrBundleName);
        if ($displayName instanceof DisplayModel) {
            $display = $displayName;
            $displayName = $displayName->name;
            $type = $displayName->type;
        } else {
            $display = null;
        }
        
        if (!$useCache
            || !isset($this->_displays[$bundle->name][$type][$displayName])
        ) {
            if ($display = $this->_getDisplay($application, $bundle, $displayName, $type, $useCache, $create, $display)) {
                $display = $application->Filter('display_display', $display, array($bundle, $type, $displayName));
            }
            $this->_displays[$bundle->name][$type][$displayName] = $display;
        }
        
        return $this->_displays[$bundle->name][$type][$displayName];
    }
    
    protected function _getDisplay(Application $application, Entity\Model\Bundle $bundle, $displayName, $type, $useCache, $create, $display = null)
    {        
        $cache_id = $this->_getCacheId($bundle->name, $type, $displayName);
        if (!$useCache ||
            (!$ret = $application->getPlatform()->getCache($cache_id))
        ) {
            if (!isset($display)) {
                if (!$display = $application->Display_Create_exists($bundle->name, $type, $displayName)) {
                    if (!$create) return;

                    try {
                        $display = $application->Display_Create($bundle, $type, $displayName, []);
                    } catch (Exception\IException $e) {
                        $application->logError($e);
                    }
                }
            }

            $ret = array(
                'id' => $display->id,
                'name' => $display->name,
                'elements' => [],
                'type' => $display->type,
                'pre_render' => false,
                'bundle_name' => $display->bundle_name,
                'class' => implode(' ', $css_classes = $display->getCssClasses()),
                'css' => isset($display->data['css']) && strlen($display->data['css']) ? str_replace('%class%', '.' . $css_classes[0], $display->data['css']) : null,
                'is_amp' => $display->isAmp(),
                'label' => isset($display->data['label']) && strlen($display->data['label']) ? $display->data['label'] : null,
            );
            
            $elements = [];
            foreach ($display->Elements as $element) {
                if ((!$element_impl = $application->Display_Elements_impl($bundle, $element->name, true))
                    || !$element_impl->displayElementSupports($bundle, $display)
                ) continue;

                // Create element ID if none
                if (!$element->element_id) {
                    try {
                        $element->element_id = $application->getModel(null, 'Display')
                            ->getGateway('Element')
                            ->getElementId($element->display_id, $element->name);
                        $element->commit();
                    } catch (Exception\IException $e) {
                        $application->logError($e);
                    }
                }

                try {
                    if (!$element_data = $this->_getElementData($application, $display, $bundle, $element, $element_impl)) continue;
                } catch (Exception\IException $e) {
                    $application->logError($e);
                    continue;
                }

                $element_data = $application->Filter('display_element_data', $element_data, array($bundle, $type, $displayName, $element->data));
                if (!$element_impl->displayElementIsEnabled($bundle, $element_data, $display)) continue;
                
                $elements[$element->parent_id][$element->id] = $element_data;
            }
            $this->_getElementTree($ret['elements'], $elements);
            
            // Cache which elements should be pre-rendered required for render
            foreach (array_keys($ret['elements']) as $id) {
                $element =& $ret['elements'][$id];
                $element_impl = $application->Display_Elements_impl($bundle, $element['name']);
                if ($element_impl->displayElementIsPreRenderable($bundle, $element)) {
                    $ret['pre_render'] = true; // pre render display
                    $element['pre_render'] = true; // pre render element
                }
            }

            $ret = $application->Filter('display_cache_display', $ret, array($bundle, $type, $displayName));
            $application->getPlatform()->setCache($ret, $cache_id);

            // Extract and cache path for each element for easy access using element ID
            $paths = [];
            $this->_extractPaths($paths, $ret['elements']);
            $application->getPlatform()->setCache($paths, $cache_id . '_paths');
        }
        
        return $ret;
    }

    protected function _extractPaths(&$paths, $tree, array $elementIds = [])
    {
        foreach (array_keys($tree) as $id) {
            $element_ids = $elementIds;
            $element_ids[] = $id;
            $paths[$tree[$id]['element_id']] = implode('-', $element_ids);
            if (!empty($tree[$id]['children'])) {
                $this->_extractPaths($paths, $tree[$id]['children'], $element_ids);
            }
        }
    }
    
    protected function _getElementTree(&$tree, array $elements, $parentId = 0)
    {
        if (empty($elements[$parentId])) return;
        
        uasort($elements[$parentId], function ($a, $b) { return $a['weight'] < $b['weight'] ? -1 : 1; });
        foreach ($elements[$parentId] as $id => $element) {
            $tree[$id] = $element;
            $this->_getElementTree($tree[$id]['children'], $elements, $id);
        }
    }
    
    protected function _getElementData(Application $application, DisplayModel $display, Entity\Model\Bundle $bundle, $element, IElement $impl)
    {
        $info = $impl->displayElementInfo($bundle);
        $element_id = $element->name . '-' . $element->element_id;

        $data = $element->data;
        if (!isset($data['settings'])
            || !is_array($data['settings'])
        ) {
            $data['settings'] = [];
        }
        $data['settings'] += $info['default_settings'];

        // Set CSS class
        $classes = [
            'drts-display-element',
            'drts-display-element-' . $element_id,
        ];
        if ($inlineable = $impl->displayElementIsInlineable($bundle, $data['settings'])) {
            $classes[] = 'drts-display-element-inlineable';
        }
        if (isset($info['class'])) {
            $classes[] = $info['class'];
        }
        if (isset($data['advanced']['css_class'])) {
            $classes[] = $data['advanced']['css_class'];
        }
        foreach (['margin' => 'm', 'padding' => 'p'] as $css_prop => $css_prop_class) {
            if (!empty($data['advanced'][$css_prop . '_enable'])) {
                foreach (['top' => 't', 'right' => 'r', 'bottom' => 'b', 'left' => 'l'] as $css_pos => $css_pos_class) {
                    if (!empty($data['advanced'][$css_prop . '_' . $css_pos])) {
                        $size = $data['advanced'][$css_prop . '_' . $css_pos];
                        if ($size < 0) {
                            $size = 'n' . (-1 * $size);
                        }
                        $classes[] = '%bs_prefix%' . $css_prop_class . $css_pos_class . '-' . $size;
                    }
                }
            }
        }
        if (!empty($data['advanced']['text_align'])) {
            $classes[] = '%bs_prefix%text-' . $data['advanced']['text_align'];
        }
        if (!empty($data['advanced']['font_weight'])) {
            $classes[] = '%bs_prefix%font-weight-' . $data['advanced']['font_weight'];
        }
        if (!empty($data['advanced']['font_style'])
            && $data['advanced']['font_style'] === 'italic'
        ) {
            $classes[] = '%bs_prefix%font-italic';
        }

        // Set CSS style
        $styles = [];
        if (!empty($data['advanced']['font_size'])) {
            $font_size_type = $data['advanced']['font_size'];
            switch ($font_size_type) {
                case 'rel':
                    if (!empty($data['advanced']['font_size_rel'])) {
                        $styles[] = 'font-size:' . $data['advanced']['font_size_rel'] . $application->getPlatform()->getCssRelSize();
                    }
                    break;
                case 'em':
                case 'rem':
                    if (!empty($data['advanced']['font_size_' . $font_size_type])) {
                        $styles[] = 'font-size:' . $data['advanced']['font_size_' . $font_size_type] . $font_size_type;
                    }
                    break;
                case 'abs':
                case 'px':
                    if (!empty($data['advanced']['font_size_' . $font_size_type])) {
                        $styles[] = 'font-size:' . $data['advanced']['font_size_' . $font_size_type] . 'px';
                    }
                    break;
            }
        }

        return array(
            'id' => $element->id,
            'element_id' => $element_id,
            '_element_id' => $element->element_id,
            'display' => $display->name,
            'name' => $element->name,
            'label' => $info['label'],
            'settings' => $data['settings'],
            'title' => $impl->displayElementTitle($bundle, $data),
            'no_title' => $impl->displayElementIsNoTitle($bundle, $data),
            'admin_attr' => $impl->displayElementAdminAttr($bundle, $data['settings']),
            'dimmed' => $impl->displayElementIsDisabled($bundle, $data['settings'])
                || (!empty($data['visibility']['globalize']) && !empty($data['visibility']['globalize_remove'])),
            'type' => $info['type'],
            'class' => implode(' ', $classes),
            'css' => [
                'id' => isset($data['advanced']['css_id']) ? $data['advanced']['css_id'] : null,
                'class' => isset($data['advanced']['css_class']) ? $data['advanced']['css_class'] : null,
                'style' => empty($styles) ? '' : implode(';', $styles) . ';',
            ],
            'cache' => isset($data['advanced']['cache']) ? $data['advanced']['cache'] : null,
            'containable' => !empty($info['containable']),
            'weight' => $element->weight,
            'children' => [],
            'child_element_type' => isset($info['child_element_type']) ? $info['child_element_type'] : null,
            'child_element_name' => isset($info['child_element_name']) ? $info['child_element_name'] : null,
            'add_child_label' => isset($info['add_child_label']) ? $info['add_child_label'] : __('Add Element', 'directories'),
            'parent_element_name' => isset($info['parent_element_name']) ? $info['parent_element_name'] : null,
            'heading' => empty($data['heading']) ? [] : $data['heading'],
            'visibility' => empty($data['visibility']) ? [] : $data['visibility'],
            'system' => $element->system ? true : false,
            'inlineable' => $inlineable,
            'icon' => isset($info['icon']) ? $info['icon'] : null,
            'info' => $impl->displayElementReadableInfo($bundle, $element),
            'data' => array_diff_key($data, ['settings' => null, 'heading' => null, 'visibility' => null, 'advanced' => null]),
        );
    }
    
    protected function _getCacheId($bundleName, $type, $displayName)
    {
        return 'display_display_' . $bundleName . '_' . $type . '_' . $displayName;
    }
    
    public function clearCache(Application $application, $bundleName, $type = null, $displayName = null)
    {
        if ($bundleName instanceof DisplayModel) {
            $type = $bundleName->type;
            $displayName = $bundleName->name;
            $bundleName = $bundleName->bundle_name;
        }
        $application->getPlatform()->deleteCache($cache_id = $this->_getCacheId($bundleName, $type, $displayName))
            ->deleteCache($cache_id . '_paths');
    }

    public function element(Application $application, array $display, $elementId)
    {
        if (empty($display['elements'])
            || (!$paths = $application->getPlatform()->getCache($this->_getCacheId($display['bundle_name'], $display['type'], $display['name']) . '_paths'))
            || !isset($paths[$elementId])
            || (!$path = explode('-', $paths[$elementId]))
            || (!$id = array_shift($path))
            || !isset($display['elements'][$id])
        ) return;

        $element = $display['elements'][$id];
        while ($id = array_shift($path)) {
            if (!isset($element['children'][$id])) return;

            $element = $element['children'][$id];
        }

        return $element;
    }

    public function export(Application $application, DisplayModel $display, \Closure $elementCallback = null)
    {
        if (!$bundle = $application->Entity_Bundle($display->bundle_name)) return;

        $display_arr = [
            'elements' => [],
        ];
        $_elements = [];
        foreach ($display->Elements as $element) {
            if (!$element_impl = $application->Display_Elements_impl($bundle, $element->name, true)) continue;

            try {
                // Let element implementation modify data which will also be used when importing
                $data = $element->data;
                $element_impl->displayElementOnExport($bundle, $data);
            } catch (Exception\IException $e) {
                $application->logError($e);
                continue;
            }

            $element_arr = array(
                'id' => (int)$element->id,
                'name' => $element->name,
                'data' => $data,
                'parent_id' => (int)$element->parent_id,
                'weight' => (int)$element->weight,
                'system' => (bool)$element->system,
            );
            if (isset($elementCallback)) {
                $element_arr = $elementCallback($element_arr);
            }
            if (!empty($element_arr['parent_id'])
                && !isset($display_arr['elements'][$element_arr['parent_id']]) // parent element not yet added
            ) {
                // wait until parent element is added
                $_elements[$element_arr['parent_id']][$element_arr['id']] = $element_arr;
            } else {
                $display_arr['elements'][$element_arr['id']] = $element_arr;
                if (isset($_elements[$element_arr['id']])) {
                    $this->_addChildElements($display_arr['elements'], $element_arr['id'], $_elements);
                }
            }
        }
        if (empty($display_arr['elements'])) return;

        return [
            'name' => $display->name,
            'type' => $display->type,
            'data' => $display->data,
        ] + $display_arr;
    }

    protected function _addChildElements(array &$elements, $parentId, array &$children)
    {
        foreach (array_keys($children[$parentId]) as $element_id) {
            $elements[$element_id] = $children[$parentId][$element_id];
            unset($children[$parentId][$element_id]);
            if (isset($children[$element_id])) {
                $this->_addChildElements($elements, $element_id, $children);
            }
        }
    }
}
