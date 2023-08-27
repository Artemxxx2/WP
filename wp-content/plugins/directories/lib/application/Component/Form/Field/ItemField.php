<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;
use SabaiApps\Directories\Exception;

class ItemField extends AbstractField
{   
    public function formFieldRender(array &$data, Form $form)
    {
        if (!isset($data['#markup'])) {
            $data['#markup'] = isset($data['#default_value']) ? $this->_application->H($data['#default_value']) : '';
        }
        $html = [];
        if (isset($data['#field_prefix'])) {
            $html[] = $this->_application->H($data['#field_prefix']);
        }
        $html[] = strtr($data['#markup'], array(
            '__FORM_FIELD_NAME__' => $data['#name'],
        ));
        if (isset($data['#field_suffix'])) {
            $html[] = $this->_application->H($data['#field_suffix']);
        }
        $this->_render(implode(PHP_EOL, $html), $data, $form);
    }
}