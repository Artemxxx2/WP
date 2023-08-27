<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

class TokenField extends AbstractField
{    
    public function formFieldInit($name, array &$data, Form $form)
    {
        if (empty($data['#token_id'])) {
            $data['#token_id'] = isset($form->settings['#name']) ? $form->settings['#name'] : $form->settings['#id'];
        }
    }

    public function formFieldSubmit(&$value, array &$data, Form $form)
    {
        if (!$this->_application->Form_Token_validate($value, $data['#token_id'], !empty($data['#token_reuseable']))) {
            $form->setError(__('Invalid token', 'directories'));
        }
    }

    public function formFieldRender(array &$data, Form $form)
    {
        if (empty($data['#token_lifetime'])) {
            $data['#token_lifetime'] = 1800;
        }
        $data['#default_value'] = $data['#value'] = $this->_application->Form_Token_create(
            $data['#token_id'], $data['#token_lifetime'], !empty($data['#token_reobtainable'])
        );
        $form->settings['#rendered_hiddens'][] = sprintf(
            '<input type="hidden" name="%s" value="%s"%s>',
            $this->_application->H($data['#name']),
            $this->_application->H($data['#default_value']),
            $this->_application->Attr($data['#attributes'])
        );
    }
}