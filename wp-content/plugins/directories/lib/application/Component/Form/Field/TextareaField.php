<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

class TextareaField extends AbstractField
{
    public function formFieldInit($name, array &$data, Form $form)
    {
        if (!isset($data['#attributes']['placeholder'])
            && isset($data['#placeholder'])
        ) {
            $data['#attributes']['placeholder'] = $data['#placeholder'];
        }
    }

    public function formFieldSubmit(&$value, array &$data, Form $form)
    {
        if (false !== $validated = $this->_application->Form_Validate_text($form, $value, $data, true, true)) {
            $value = $validated;
        }
    }

    public function formFieldRender(array &$data, Form $form)
    {        
        $html = sprintf(
            '<textarea name="%s" class="%sform-control" rows="%d"%s>%s</textarea>',
            $data['#name'],
            DRTS_BS_PREFIX,
            isset($data['#rows']) ? $data['#rows'] : 10,
            $this->_application->Attr($data['#attributes']),
            isset($data['#default_value']) ? $this->_application->H($data['#default_value']) : ''
        );
        $this->_render($html, $data, $form);
    }
}