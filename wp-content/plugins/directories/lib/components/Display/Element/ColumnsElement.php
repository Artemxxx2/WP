<?php
namespace SabaiApps\Directories\Component\Display\Element;

use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Display\Model\Display;
use SabaiApps\Directories\Component\Display\Model\Element;

class ColumnsElement extends AbstractElement
{    
    protected function _displayElementInfo(Bundle $bundle)
    {
        return array(
            'type' => 'utility',
            'label' => _x('Columns', 'display element name', 'directories'),
            'description' => __('Group multiple display elements in columns', 'directories'),
            'default_settings' => array(
                'gutter_width' => 'none',
                'columns' => 3,
            ),
            'containable' => true,
            'child_element_name' => 'column',
            'child_element_create' => true,
            'add_child_label' => __('Add Column', 'directories'),
            'icon' => 'fas fa-columns',
            'designable' => ['margin'],
            'labellable' => true,
            'buttonable' => true,
        );
    }
    
    public function displayElementSettingsForm(Bundle $bundle, array $settings, Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        $form = array(
            'gutter_width' => array(
                '#title' => __('Gutter width', 'directories'),
                '#type' => 'select',
                '#options' => $this->_getGutterWidthOptions(),
                '#horizontal' => true,
                '#default_value' => $settings['gutter_width'],
            ),
        );
        if (!$isEdit) {
            $form['columns'] = array(
                '#type' => 'select',
                '#title' => __('Number of columns', 'directories'),
                '#default_value' => $settings['columns'],
                '#options' => array(1 => 1, 2 => 2, 3 => 3, 4 => 4, 6 => 6),
                '#horizontal' => true,
            );
        }
        
        return $form;
    }
    
    protected function _getGutterWidthOptions()
    {
        return [
            'none' => __('None', 'directories'),
            '' => __('Default', 'directories'),
            'md' => __('Medium', 'directories'),
            'lg' => __('Large', 'directories'),
        ];
    }
    
    public function displayElementCreateChildren(Display $display, array $settings, $parentId)
    {
        $ret = [];
        $width = 12 / $settings['columns'];
        for ($i = 0; $i < $settings['columns']; ++$i) {
            $ret[] = $this->_application->Display_AdminElement_create($display, 'column', $parentId, array('settings' => array('width' => $width)));
        }

        return $ret;
    }
    
    public function displayElementRender(Bundle $bundle, array $element, $var)
    {        
        if (!$columns = $this->_renderChildren($bundle, $element['children'], $var)) return;

        return sprintf(
            '<div class="drts-row%s">
%s
</div>',
            in_array($element['settings']['gutter_width'], array('none' ,'md', 'lg')) ? ' drts-gutter-' . $element['settings']['gutter_width'] : '',
            implode(PHP_EOL, $columns)
        );
    }

    protected function _displayElementReadableInfo(Bundle $bundle, Element $element)
    {
        $settings = $element->data['settings'];
        $options = $this->_getGutterWidthOptions();
        $ret = [
            'gutter_width' => [
                'label' => __('Gutter width', 'directories'),
                'value' => $options[$settings['gutter_width']],
            ],
        ];
        return ['settings' => ['value' => $ret]];
    }
    
    protected function _displayElementSupports(Bundle $bundle, Display $display)
    {
        return $display->type !== 'filters';
    }
}