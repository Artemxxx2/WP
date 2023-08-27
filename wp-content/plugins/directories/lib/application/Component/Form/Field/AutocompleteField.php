<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

class AutocompleteField extends SelectField
{
    public function formFieldInit($name, array &$data, Form $form)
    {
        if (isset($data['#select2_ajax_url'])) {
            $data += array(
                '#select2' => true,
                '#select2_ajax' => true,
            );
        } else {
            $data['#select2'] = false;
        }
        
        return parent::formFieldInit($name, $data, $form);        
    }
}