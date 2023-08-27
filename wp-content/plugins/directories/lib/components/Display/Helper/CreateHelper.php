<?php
namespace SabaiApps\Directories\Component\Display\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Component\Entity;

class CreateHelper
{        
    public function help(Application $application, Entity\Model\Bundle $bundle, $type, $name, $data)
    {
        if ($display = $this->exists($application, $bundle->name, $type, $name)) return $display;
        
        if (is_string($data)) {
            if (strpos($data, '/') === 0) {
                if (!file_exists($data)
                    || (!$_data = include $data)
                ) {
                    throw new Exception\RuntimeException('Invalid file or data: ' . $data);
                }
                $data = $_data;
            } else {
                $data = json_decode($data, true);
                if (!is_array($data)) {
                    throw new Exception\RuntimeException(function_exists('json_last_error_msg') ? json_last_error_msg() : 'Invalid JSON data.');
                }
            }
        } elseif (!is_array($data)) {
            throw new Exception\RuntimeException('Invalid display data.');
        }
        
        // May be an array of displays, which happens when exporting multiple displays
        if (array_key_exists(0, $data)) {
            $data = $data[0];
        }
        
        $display = $application->getModel(null, 'Display')->create('Display')->markNew();
        $display->name = $name;
        $display->bundle_name = $bundle->name;
        $display->type = $type;
        $display->data = isset($data['data']) ? $data['data'] : null;
        $display->commit();
        
        if (!empty($data['elements'])) {
            $element_id_map = [];
            foreach ($data['elements'] as $j => $_element) {
                if (isset($_element['parent_id'])
                    && isset($element_id_map[$_element['parent_id']])
                ) {
                    $_element['parent_id'] = $element_id_map[$_element['parent_id']];
                } else {
                    $_element['parent_id'] = 0;
                }

                try {
                    if (!empty($_element['data'])) {
                        ElementFormHelper::filterSettings($_element['data']);
                    } else {
                        $_element['data'] = [];
                    }
                    $element = $this->element($application, $bundle, $display, $_element);
                } catch (Exception\IException $e) {
                    $application->logError($e);
                    continue;
                }

                if (isset($_element['id'])) {
                    $element_id_map[$_element['id']] = $element->id;
                }
            }
        }
        
        return $display;
    }
    
    public function exists(Application $application, $bundleName, $type, $name)
    {
        return $application->getModel('Display', 'Display')
            ->type_is($type)
            ->name_is($name)
            ->bundleName_is($bundleName)
            ->fetchOne();
    }
    
    public function element(Application $application, Entity\Model\Bundle $bundle, $display, array $data = [], $displayType = 'entity')
    {
        if (empty($data['name'])) {
            throw new Exception\RuntimeException('Display element name may not be empty');
        }
        
        // Init container as display object if string
        if (is_string($display)) {
            if (!$display = $this->exists($application, $bundle->name, $displayType, $display)) {
                throw new Exception\RuntimeException('Invalid bundle or display');
            }
        }

        // Allow element implementation class to modify settings before create
        if ((!$element_impl = $application->Display_Elements_impl($bundle, $data['name'], true))
            || !$element_impl->displayElementSupports($bundle, $display)
        ) return;
        if (!isset($data['data']['settings'])) {
            $data['data']['settings'] = [];
        }
        $data['data']['settings'] += (array)$element_impl->displayElementInfo($bundle, 'default_settings');
        $weight = isset($data['weight']) ? $data['weight'] : 1;
        $element_id = $application->getModel(null, 'Display')
            ->getGateway('Element')
            ->getElementId($display->id, $data['name']);
        $element_impl->displayElementOnCreate($bundle, $data['data'], $weight, $display, $data['name'], $element_id);
        $element = $display->createElement()->markNew();
        $element->name = $data['name'];
        $element->weight = $weight;
        $element->system = !empty($data['system']);
        $element->data = $data['data'];
        $element->parent_id = empty($data['parent_id']) ? 0 : $data['parent_id'];
        $element->element_id = $element_id;
        $element->commit();
        
        $element_impl->displayElementOnSaved($bundle, $element);
        
        return $element;
    }

    public function isCreatable(Application $application, $type, $name)
    {
        return $application->Filter(
            'display_is_creatable',
            ($type === 'entity' && $name === 'summary') || $type === 'filters',
            [$type, $name]
        );
    }
}
