<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

class ColorPickerField extends TextField
{
    public function formFieldInit($name, array &$data, Form $form)
    {
        $data['#add_clear'] = empty($data['#hide_input']) && (!isset($data['#add_clear']) || $data['#add_clear']);
        $data['#placeholder'] = __('Select a color', 'directories');
        $form->settings['#pre_render'][__CLASS__] = [$this, 'preRenderCallback'];
        parent::formFieldInit($name, $data, $form);
    }
    
    public function formFieldRender(array &$data, Form $form)
    {
        if (!empty($data['#hide_input'])) {
            $data['#attributes']['data-static-open'] = 1;
            $type = 'hidden';
        } else {
            $data['#attributes']['style'] = 'max-width:200px;';
            $type = 'text';
        }
        $this->_render($this->_renderInput($data, $form, $type), $data, $form);
    }
    
    public function preRenderCallback(Form $form)
    {
        $this->_application->Form_Scripts(['colorpicker']);
        $form->settings['#js_ready'][] = sprintf(
            '(function() {
    $("#%s").find(".drts-form-type-colorpicker").each(function(){
        DRTS.Form.field.colorpicker($(this)); 
    });
})();',
            $form->settings['#id']
        );
    }
}