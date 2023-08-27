<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

class PasswordField extends TextField
{
    public function formFieldInit($name, array &$data, Form $form)
    {
        unset($data['#separator'], $data['#mask']);
        parent::formFieldInit($name, $data, $form);
        
        $data['#attributes']['autocomplete'] = 'off';
        if (empty($data['#redisplay'])) {
            // Do not display value in HTML to prevent password theft
            $data['#value'] = '';
        }
    }
}