<?php
namespace SabaiApps\Directories\Component\Display\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\Form\FormComponent;

class AdminElementHelper
{
    public function create(Application $application, Display\Model\Display $display, $name, $parentId = 0, array $values = null)
    {
        if (!$bundle = $application->Entity_Bundle($display->bundle_name)) {
            throw new Exception\RuntimeException('Invalid bundle: ' . $display->bundle_name);
        }

        $element = $application->Display_Create_element($bundle, $display, [
            'name' => $name,
            'parent_id' => $parentId,
            'data' => !isset($values) ? [] : $this->_getElementDataForSave($application, $bundle, $name, $values),
        ]);
        $element_impl = $application->Display_Elements_impl($bundle, $name);
        $info = $element_impl->displayElementInfo($bundle);
        $element_types = $application->Display_Elements_types($bundle);
        $ret = [
            'id' => $element->id,
            'type' => $info['type'],
            'name' => $element->name,
            'parent_id' => $element->parent_id,
            'display_id' => $element->display_id,
            'title' => $title = $element_impl->displayElementTitle($bundle, $element->data),
            'no_title' => $element_impl->displayElementIsNoTitle($bundle, $element->data),
            'label' => $info['label'],
            'attr' => $element_impl->displayElementAdminAttr($bundle, $element->data['settings']),
            'system' => $element->system,
            'icon' => isset($info['icon']) ? $info['icon'] : null,
            'dimmed' => $element_impl->displayElementIsDisabled($bundle, $element->data['settings'])
                || (!empty($element->data['visibility']['globalize']) && !empty($element->data['visibility']['globalize_remove'])),
            'data' => $this->getDataArray(
                $application,
                $bundle->name,
                $element->element_id,
                $element->name,
                $element_types[$info['type']],
                $info['label'],
                $title,
                (array)$element_impl->displayElementReadableInfo($bundle, $element),
                empty($element->data['css']) ? [] : (array)$element->data['css'],
                empty($element->data['cache']) ? [] : (array)$element->data['cache']
            ),
            'containable' => !empty($info['containable']),
            'sortable_connect' => true,
        ];

        // Auto-generate child elements
        if ($ret['containable']) {
            $ret['children'] = [];
            if (!empty($info['child_element_name'])) {
                if (!empty($info['child_element_create'])) {
                    if (is_callable(array($element_impl, 'displayElementCreateChildren'))) {
                        if ($children = $element_impl->displayElementCreateChildren($display, $element->data['settings'], $element->id)) {
                            $ret['children'] = $children;
                        }
                    } else {
                        for ($i = 0; $i < $info['child_element_create']; $i++) {
                            if ($child = $application->Display_AdminElement_create($display, $info['child_element_name'], $element->id)) {
                                $ret['children'][] = $child;
                            }
                        }
                    }
                }
                $ret += array(
                    'child_element_name' => @$info['child_element_name'],
                );
                $ret['sortable_connect'] = false;
            } elseif (!empty($info['child_element_type'])) {
                $ret += array(
                    'child_element_type' => @$info['child_element_type'],
                );
            }
            $ret['add_child_label'] = isset($info['add_child_label']) ? $info['add_child_label'] : __('Add Element', 'directories');
        }

        // Clear rendered display cache
        $application->Display_Render_clearDisplayCache();

        return $ret;
    }

    protected function _getElementDataForSave(Application $application, $bundle, $elementName, array $values)
    {
        $settings = isset($values['general']['settings']) ? $values['general']['settings'] : [];
        if (isset($values['settings'])) $settings += $values['settings'];
        $settings += (array)$application->Display_Elements_impl($bundle, $elementName)->displayElementInfo($bundle, 'default_settings');
        unset($values['display_id'], $values['element_id'], $values['directory_name'], $values['bundle_name'],
            $values['general'], $values[FormComponent::FORM_SUBMIT_BUTTON_NAME]);
        return [
            'settings' => $settings,
            'heading' => isset($values['heading']) ? $values['heading'] : null,
            'advanced' => isset($values['advanced']) ? $values['advanced'] : null,
            'visibility' => isset($values['visibility']) ? $values['visibility'] : null,
        ] + $values;
    }
    
    public function update(Application $application, Display\Model\Display $display, Display\Model\Element $element, array $values)
    {
        if (!$bundle = $application->Entity_Bundle($display->bundle_name)) {
            throw new Exception\RuntimeException('Invalid bundle: ' . $display->bundle_name);
        }

        // Allow element implementation class to modify settings before update
        $data = $this->_getElementDataForSave($application, $bundle, $element->name, $values);
        $element_impl = $application->Display_Elements_impl($bundle, $element->name);
        $element_impl->displayElementOnUpdate($bundle, $data, $element);
        $element->data = $data;
        $element->commit();
        $element_impl->displayElementOnSaved($bundle, $element);

        if ($element_impl->displayElementInfo($bundle, 'cacheable')) {
            // Clear rendered element cache
            $application->Display_Render_clearElementCache();
        }
        // Clear rendered display cache
        $application->Display_Render_clearDisplayCache();
        
        $element_types = $application->Display_Elements_types($bundle);
        
        return array(
            'id' => $element->id,
            'type' => $type = $element_impl->displayElementInfo($bundle, 'type'),
            'name' => $element->name,
            'parent_id' => $element->parent_id,
            'display_id' => $element->display_id,
            'title' => $title = $element_impl->displayElementTitle($bundle, $element->data),
            'no_title' => $element_impl->displayElementIsNoTitle($bundle, $element->data),
            'label' => $label = $element_impl->displayElementInfo($bundle, 'label'),
            'attr' => $element_impl->displayElementAdminAttr($bundle, $element->data['settings']),
            'system' => $element->system,
            'icon' => $element_impl->displayElementInfo($bundle, 'icon'),
            'dimmed' => $element_impl->displayElementIsDisabled($bundle, $element->data['settings'])
                || (!empty($element->data['visibility']['globalize']) && !empty($element->data['visibility']['globalize_remove'])),
            'data' => $this->getDataArray(
                $application,
                $bundle->name,
                $element->element_id,
                $element->name,
                $element_types[$type],
                $label,
                $title,
                (array)$element_impl->displayElementReadableInfo($bundle, $element),
                empty($element->data['css']) ? [] : (array)$element->data['css'],
                empty($element->data['cache']) ? [] : (array)$element->data['cache']
            ),
        );
    }
    
    public function delete(Application $application, $bundle, $elementId, $notify = true)
    {
        if (!$bundle = $application->Entity_Bundle($bundle)) {
            throw new Exception\RuntimeException('Invalid bundle');
        }
        
        if (!$element = $application->getModel('Element', 'Display')->fetchById($elementId)) {
            $application->logWarning('Trying to delete a non-existent display element. Element ID: ' . $elementId);
            return;
        }
        
        if ($notify
            && ($element_impl = $application->Display_Elements_impl($bundle, $element->name, true)) // element may already not exist
        ) {
            // Fetch settings to be passed when notifying
            $settings = (array)@$element->data['settings'];
            $element_name = $element->name;
            $element_id = $element->element_id;
            // Delete element
            $element->markRemoved()->commit();
            // Notify
            $element_impl->displayElementOnRemoved($bundle, $settings, $element_name, $element_id);
        } else {
            // Delete element
            $element->markRemoved()->commit();
        }
    }
    
    public function getDataArray(Application $application, $bundleName, $id, $name, $type, $label, $title, array $info = [], array $css = [], array $cache = [])
    {
        $data = [
            'general' => [
                'label' => __('General', 'directories'),
                'value' => [
                    'id' => [
                        'label' => __('Element ID', 'directories'),
                        'value' => $name . '-' . (empty($id) ? 1 : $id),
                    ],
                    'type' => [
                        'label' => __('Element type', 'directories'),
                        'value' => $label . ' (' . $type . ')',
                    ],
                ],
            ],
            'settings' => [
                'label' => __('Settings', 'directories'),
                'value' => [],
            ],
            'css' => [
                'label' => __('CSS', 'directories'),
                'value' => [
                    'class' => [
                        'label' => __('CSS class', 'directories'),
                        'value' => '<code>.drts-display-element-' . $name . '-' .  $id . '</code>',
                        'is_html' => true,
                    ],
                ],
            ],
        ];
        if (strlen($title)) {
            $data['general']['value']['title'] = [
                'label' => __('Label', 'directories'),
                'value' => $title,
                'is_html' => true,
            ];
        }
        if (isset($css['class']) && strlen($css['class'])) {
            $data['css']['value']['class_custom'] = [
                'label' => __('CSS class', 'directories'),
                'value' => '<code>.' . $application->H($css['class']) . '</code>',
                'is_html' => true,
            ];
        }
        if (isset($css['id']) && strlen($css['id'])) {
            $data['css']['value']['id'] = [
                'label' => __('CSS ID', 'directories'),
                'value' => '<code>.' . $application->H($css['id']) . '</code>',
                'is_html' => true,
            ];
        }
        if (!empty($cache['cache'])) {
            if ($cache['cache'] >= 86400) {
                $value = sprintf(_n('%d day', '%d days', $day = $cache['cache'] / 86400, 'directories'), $day);
            } elseif ($cache['cache'] >= 3600) {
                $value = sprintf(_n('%d hour', '%d hours', $hour = $cache['cache'] / 3600, 'directories'), $hour);
            } else {
                $value = sprintf(_n('%d minute', '%d minutes', $min = $cache['cache'] / 60, 'directories'), $min);
            }
            $data['settings']['value'] += [
                'cache' => [
                    'label' => __('Cache output', 'directories'),
                    'value' => $value,
                ],
            ];
        }
        if (!empty($info)) {
            $data = array_replace_recursive($data, $info);
        }
        return $application->Filter('display_admin_element_data', $data, [$bundleName, $id, $name]);
    }
}
