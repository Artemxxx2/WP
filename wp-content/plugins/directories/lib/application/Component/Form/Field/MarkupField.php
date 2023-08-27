<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;
use SabaiApps\Directories\Exception;

class MarkupField extends AbstractField
{
    public function formFieldRender(array &$data, Form $form)
    {
        if (!isset($data['#markup'])) {
            if (!isset($data['#default_value'])) {
                return;
            }
            $data['#markup'] = $data['#default_value'];
        }
        
        if ($form->hasError($data['#name'])) {
            $error = $form->getError($data['#name']);
            $data['#html'][] = '<div class="'. $this->_application->H($data['#class']) . ' drts-form-has-error">';
            $data['#html'][] = $data['#markup'];
            if (isset($error) && strlen($error) > 0) {
                $data['#html'][] = '<div class="' . DRTS_BS_PREFIX . 'form-text drts-form-error ' . DRTS_BS_PREFIX . 'text-danger">'
                    . $this->_application->Htmlize($error, true)
                    . '</div>';
            }
            $data['#html'][] = '</div>';
        } else {        
            $data['#html'][] = $data['#markup'];
        }
    }
}