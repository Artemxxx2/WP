<?php
namespace SabaiApps\Directories\Component\Display\FormField;

use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Exception;

class ElementsFormField extends Form\Field\AbstractField
{
    protected $_fields = [], $_elementTypes;
    
    public function formFieldInit($name, array &$data, Form\Form $form)
    {
        if (empty($data['#display'])
            || empty($data['#display']['id'])
        ) throw new Exception\RuntimeException('Invalid display.');
        
        if (empty($data['#display']['bundle_name'])
            || (!$bundle = $this->_application->Entity_Bundle($data['#display']['bundle_name']))
        ) {
            throw new Exception\RuntimeException('Invalid bundle.');
        }
        $this->_elementTypes = $this->_application->Display_Elements_types($bundle, $data['#display']['type']);
        
        $data['#id'] = $form->getFieldId($name);

        $this->_fields[$data['#id']] = [
            'display_id' => $data['#display']['id'],
            'name' => $name
        ];
        
        if (!isset($form->settings['#pre_render'][__CLASS__])) {
            $form->settings['#pre_render'][__CLASS__] = [[$this, 'preRenderCallback'], [$bundle, !empty($data['#configure_inline'])]];
        }
    }
    
    public function formFieldRender(array &$data, Form\Form $form)
    {
        $html = [
            '<div class="drts-display-display" data-display-id="' . $this->_application->H($data['#display']['id']) . '" style="position:relative;">',
        ];
        if (!empty($data['#display']['elements'])) {
            $html[] = '<div class="drts-display-element-wrapper">';
            $name = $this->_application->H($data['#name']);
            foreach ($data['#display']['elements'] as $element) {
                $html[] = $this->_getElementHtml($name, $element, $data['#display']['bundle_name']);
            }
            $html[] = '</div>';
        } else {
            $html[] = '<div class="drts-display-element-wrapper"></div>';
        }
        $html[] = '<div id="drts-display-element-settings-inline-0" class="drts-display-element-settings-inline"></div>';
        $html[] = '<div class="drts-display-control">
            <button type="button" disabled class="drts-display-add-element drts-display-add-element-main drts-bs-btn drts-bs-btn-success" rel="sabaitooltip" title="' . $this->_application->H(__('Add Element', 'directories')) . '"><i class="fas fa-plus"></i></button>
        </div>';
        $html[] = '</div>';
        $this->_render(implode(PHP_EOL, $html), $data, $form);
    }
    
    protected function _getElementHtml($name, array $element, $bundleName)
    {
        ob_start();
        include __DIR__ . '/element.php';
        return ob_get_clean();
    }

    protected function _getElementDataArray($bundleName, array $element)
    {
        return $this->_application->Display_AdminElement_getDataArray(
            $bundleName,
            $element['_element_id'],
            $element['name'],
            $this->_elementTypes[$element['type']],
            $element['label'],
            $element['title'],
            (array)$element['info'],
            isset($element['css']) ? (array)$element['css'] : [],
            isset($element['cache']) ? (array)$element['cache'] : []
        );
    }
    
    public function formFieldSubmit(&$value, array &$data, Form\Form $form)
    {
        if (!$display = $this->_application->getModel('Display', 'Display')->fetchById($data['#display']['id'])) {
            throw new Exception\RuntimeException('Invalid display.');
        }
        
        $elements = $value;
        $value = null;
        
        $current_elements = $updated_elements = [];
        foreach ($display->Elements as $element) {
            $current_elements[$element->id] = $element;
        }
        
        if (!empty($elements)) {
            $weight = $parent_id = 0;
            $prev_element_id = null;
            $parent_ids = [];
            foreach ($elements as $element_id) {                
                if ($element_id === '__CHILDREN_START__') {
                    $parent_ids[] = $parent_id;
                    $parent_id = $prev_element_id;
                    continue;
                }
                
                if ($element_id === '__CHILDREN_END__') {
                    $parent_id = array_pop($parent_ids);
                    continue;
                }
                
                if (!isset($current_elements[$element_id])) continue;

                $element = $updated_elements[$element_id] = $current_elements[$element_id];

                $element->weight = ++$weight;
                $element->parent_id = $parent_id;
                
                $prev_element_id = $element_id;
            }
        }
        
        // Remove elements
        $elements_removed = [];
        foreach (array_diff_key($current_elements, $updated_elements) as $current_element) {
            if ($current_element->system) continue;

            $elements_removed[$current_element->id] = $current_element;
        }
        $this->_application->getModel(null, 'Display')->commit();
        
        if (!empty($elements_removed)
            || !empty($updated_elements)
        ) {
            $bundle = $this->_application->Entity_Bundle($data['#display']['bundle_name']);
            foreach ($elements_removed as $element) {
                try {
                    $this->_application->Display_AdminElement_delete($bundle, $element->id);
                } catch (Exception\IException $e) {
                    $this->_application->logError($e);
                }
            }
            foreach ($updated_elements as $element) {
                try {
                    $this->_application->Display_Elements_impl($bundle, $element->name)->displayElementOnPositioned($bundle, (array)@$element->data['settings'], $element->weight);
                } catch (Exception\IException $e) {
                    $this->_application->logError($e);
                }
            }
            // Clear display elements cache
            $this->_application->getPlatform()->deleteCache('display_elements_' . $bundle->name);
            $data['#clear_display_cache'] = true;
        }
        
        if (!isset($data['#clear_display_cache'])
            || false !== $data['#clear_display_cache']
        ) {
            // Clear display and elements cache
            $this->_application->Display_Display_clearCache($display);
        }

        // Clear rendered display cache
        $this->_application->Display_Render_clearDisplayCache();
    }
    
    public function preRenderCallback(Form\Form $form, $bundle, $configureInline)
    {
        $admin_path = $this->_application->Entity_BundleTypeInfo($bundle, 'admin_path');
        $admin_path = strtr($admin_path, [
            ':bundle_name' => $bundle->name,
            ':directory_name' => $bundle->group,
            ':bundle_group' => $bundle->group,
        ]);
        $options = [
            'addElementTitle' => __('Add Element', 'directories'),
            'editElementTitle' => __('Edit Element', 'directories'),
            'deleteElementTitle' => __('Delete Element', 'directories'),
            'deleteConfirm' => __('Are you sure?', 'directories'),
            'elementTypes' => $this->_elementTypes,
            'addDisplayUrl' => (string)$this->_application->Url($admin_path . '/displays/add_display', [], '', '&'),
            'deleteDisplayUrl' => (string)$this->_application->Url($admin_path . '/displays/delete_display', [], '', '&'),
            'saveChangesAlert' => __('Please save changes first!', 'directories'),
        ];
        $js = [
            sprintf(
                'let adminDisplays = new DRTS.Display.adminDisplays("#%s", %s); ',
                $form->settings['#id'],
                $this->_application->JsonEncode($options)
            ),
        ];
        foreach ($this->_fields as $id => $data) {
            $_options = [
                'name' => $data['name'],
                'listElementsUrl' => (string)$this->_application->Url($admin_path . '/displays/list_elements', array('display_id' => $data['display_id']), '', '&'),
                'addElementUrl' => (string)$this->_application->Url($admin_path . '/displays/add_element', array('display_id' => $data['display_id']), '', '&'),
                'editElementUrl' => (string)$this->_application->Url($admin_path . '/displays/edit_element', array('display_id' => $data['display_id']), '', '&'),
                'configureInline' => $configureInline,
            ];
            $js[] = sprintf('adminDisplays.addDisplay("#%s", %s);', $id, $this->_application->JsonEncode($_options));
        }
        $form->settings['#js_ready'][] = implode(PHP_EOL, $js);

        $this->_application->getPlatform()->loadJqueryUiJs(array('sortable', 'draggable', 'effects-highlight'))
            ->addJsFile('display-admin-display.min.js', 'drts-display-admin-display', array('drts', 'jquery-ui-sortable'))
            ->addCssFile('display-admin-display.min.css', 'drts-display-admin-display', array('drts'));
        if ($this->_application->getPlatform()->isRtl()) {
            $this->_application->getPlatform()->addCssFile('display-admin-display-rtl.min.css', 'drts-display-admin-display-rtl', array('drts-display-admin-display'));
        }
        $this->_application->Form_Scripts();
    }
}
