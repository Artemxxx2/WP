<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

class SortableCheckboxesField extends CheckboxesField
{
    protected static $_elements = [];
    
    public function formFieldInit($name, array &$data, Form $form)
    {
        parent::formFieldInit($name, $data, $form);
        $data['#inline'] = $data['#columns'] = false;
        if (!isset($data['#id'])) {
            $data['#id'] = $form->getFieldId($name);
        }

        if (!isset(self::$_elements[$form->settings['#id']])) {
            self::$_elements[$form->settings['#id']] = [];
        }
        self::$_elements[$form->settings['#id']][$data['#id']] = $data['#id'];
        
        $form->settings['#pre_render'][__CLASS__] = array($this, 'preRenderCallback');
        
        // Sort options as was sorted previously
        if (!empty($data['#default_value'])) {
            $sorted_options = [];
            foreach ($data['#default_value'] as $value) {
                if (isset($data['#options'][$value])) {
                    $sorted_options[$value] = $data['#options'][$value];
                    unset($data['#options'][$value]);
                }
            }
            $data['#options'] = $sorted_options + $data['#options'];
        }
        
        $data['#options_scroll'] = true;
    }
    
    protected function _getOptionPrefix($prefix, $depth)
    {
        return ' style="padding-' . ($this->_application->getPlatform()->isRtl() ? 'right' : 'left') . ':' . $depth . '0px"';
    }

    protected function _getOptionFormat(array $data, $checked = false)
    {
        return '<div%10$s>'
            . '<div class="%9$scustom-control %9$scustom-%1$s%8$s" data-depth="%7$d">'
            . '<input class="%9$scustom-control-input" type="%1$s" id="%11$s-%3$s" name="%2$s" value="%3$s"%4$s />'
            . '<label class="%9$scustom-control-label" for="%11$s-%3$s">%5$s</label>'
            . '</div>'
            . '</div>';
    }
    
    public function preRenderCallback(Form $form)
    {        
        $this->_application->getPlatform()->loadJqueryUiJs(array('sortable'));
        if (!empty(self::$_elements[$form->settings['#id']])) {
            $form->settings['#js_ready'][] = '$("#' . implode(', #', self::$_elements[$form->settings['#id']]) . '")
    .find(".drts-form-field-radio-options")
    .sortable({handle:"label", containment:"parent", axis:"y", cursor:"move"});';
        }
    }
}