<?php
namespace SabaiApps\Directories\Component\DirectoryPro\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;

class ExportBundleHelper
{
    public function help(Application $application, Entity\Model\Bundle $bundle, $makeTranslatable = false, $domain = null)
    {  
        $ret = array(
            'info' => ['title' => 'Info', 'data' => $bundle->info, 'placeholders' => []],
            'fields' => ['title' => 'Fields', 'data' => [], 'placeholders' => []],
            'displays' => ['title' => 'Displays', 'data' => [], 'placeholders' => []],
            'views' => ['title' => 'Views', 'data' => [], 'placeholders' => []],
        );
        
        if ($makeTranslatable
            && !isset($domain)
        ) {
            if (($component = $application->Entity_BundleTypeInfo($bundle, 'component'))
                && $application->isComponentLoaded($component)
            ) {        
                $domain = $application->getComponent($component)->getPackage();
            } else {
                $domain = 'directories-pro';
            }
        }
        
        // Info
        if ($makeTranslatable) {
            foreach (['label', 'label_singular', 'label_add', 'label_all', 'label_select',
                'label_count', 'label_count2', 'label_page', 'label_search'
            ] as $data_key) {
                if (isset($ret['info']['data'][$data_key])
                    && strlen($ret['info']['data'][$data_key])
                ) {
                    $ret['info']['data'][$data_key] = $this->_makeTranslatable($ret['info']['data'][$data_key], $domain, $ret['info']);
                }
            }
        }
            
        // Fields
        $fields = $application->Entity_Field($bundle);
        // List of fields to exclude that should be created by the system
        $exclude_field_types = [
            'entity_terms',
            'entity_featured',
            'voting_vote',
            'payment_plan',
            'payment_orders',
            'frontendsubmit_guest',
            'entity_term_content_count',
            'entity_child_count',
        ];
        $exclude_field_names = [
            'location_address',
        ];
        foreach (array_keys($fields) as $field_name) {
            $field = $fields[$field_name];
            if (!$field instanceof Entity\Model\Field
                || $field->isPropertyField()
                || in_array($field->getFieldType(), $exclude_field_types)
                || in_array($field->getFieldName(), $exclude_field_names)
            ) continue;
            
            $field_data = $field->getFieldData();
            
            // Make label translatable?
            if ($makeTranslatable
               && isset($field_data['label'])
               && strlen($field_data['label'])
            ) {
                $field_data['label'] = $this->_makeTranslatable($field_data['label'], $domain, $ret['fields']);
            }
                
            $ret['fields']['data'][$field_name] = array(
                'type' => $field->getFieldType(),
                'settings' => $field->getFieldSettings(),
                'realm' => strpos($field_name, 'field_') === 0 ? Entity\EntityComponent::FIELD_REALM_ALL : Entity\EntityComponent::FIELD_REALM_BUNDLE_DEFAULT,
                'data' => $field_data,
            );
        }
            
        // Displays
        $displays = $application->getModel('Display', 'Display')
            ->bundleName_is($bundle->name)
            ->fetch();
        $element_callback = null;
        if ($makeTranslatable) {
            $container =& $ret['displays'];
            $element_callback = function($element) use ($domain, &$container) {
                foreach (['settings', 'heading'] as $data_key) {
                    if (isset($element['data'][$data_key]['label_custom'])
                        && strlen($element['data'][$data_key]['label_custom'])
                    ) {
                        $element['data'][$data_key]['label_custom'] = $this->_makeTranslatable(
                            $element['data'][$data_key]['label_custom'],
                            $domain,
                            $container
                        );
                    }
                }
                return $element;
            };
        }
        foreach ($displays as $display) {
            if (!$display_arr = $application->Display_Display_export($display, $element_callback)) continue;

            $ret['displays']['data'][$display->type][$display->name] = $display_arr;
        }
            
        // Views
        $views = $application->getModel('View', 'View')
            ->bundleName_is($bundle->name)
            ->fetch();
        foreach ($views as $view) {
            $ret['views']['data'][$view->name] = array(
                'mode' => $view->mode,
                'label' => $makeTranslatable ? $this->_makeTranslatable($view->data['label'], $domain, $ret['views']) : $view->data['label'],
                'settings' => $view->data['settings'],
                'default' => $view->default,
            );
        }
        
        return $ret;
    }
    
    protected function _makeTranslatable($string, $domain, &$container)
    {
        $placeholder = '%%%' . $string . '%%%';
        $container['placeholders']["'" . $placeholder . "'"] = sprintf(
            '__(\'%s\', \'%s\')',
            str_replace("'", "\\'", $string),
            $domain
        );
        return $placeholder;
    }
}