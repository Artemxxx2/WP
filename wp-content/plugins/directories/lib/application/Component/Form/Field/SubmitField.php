<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;
use SabaiApps\Directories\Exception;

class SubmitField extends AbstractField
{
    public function formFieldInit($name, array &$data, Form $form)
    {
        if (!isset($data['#value'])) {
            if (!isset($data['#default_value'])) {
                $data['#value'] = 'submit';
            }
        }
        if (!isset($data['#btn_label'])) {
            $data['#btn_label'] = __('Submit', 'directories');
            $data['#btn_label_noescape'] = false;
        }
        $button_class = DRTS_BS_PREFIX . 'btn ' . DRTS_BS_PREFIX . 'btn-' . (isset($data['#btn_color']) ? $data['#btn_color'] : 'secondary');
        if (isset($data['#btn_size'])
            && in_array($data['#btn_size'], array('sm', 'lg'))
        ) {
            $button_class .= ' ' . DRTS_BS_PREFIX . 'btn-' . $data['#btn_size'];
        }
        if (!empty($data['#btn_block'])) {
            $button_class .= ' ' . DRTS_BS_PREFIX . 'btn-block';
        }
        if (isset($data['#attributes']['class'])) {
            $data['#attributes']['class'] .= ' ' . $button_class;
        } else {
            $data['#attributes']['class'] = $button_class;
        }
    }

    public function formFieldSubmit(&$value, array &$data, Form $form)
    {
        if (!isset($value)
            || !$form->isClickedButton($data)
        ) return; // the button was not clicked

        // Move validate/submit handlers for this button to the global scope
        if (!empty($data['#validate'])) {
            foreach ($data['#validate'] as $callback) {
                $form->settings['#validate'][] = $callback;
            }
        }
        if (!empty($data['#submit'])) {
            foreach ($data['#submit'] as $weight => $submits) {
                foreach ($submits as $callback) {
                    $form->settings['#submit'][$weight][] = $callback;
                }
            }
        }
        
        if (!empty($data['#skip_validate'])) {
            $form->settings['#skip_validate'] = true;
        }
    }

    public function formFieldRender(array &$data, Form $form)
    {        
        $data['#html'][] = sprintf(
            '<button type="submit" name="%s" value="%s"%s>%s</button>',
            $this->_application->H($data['#name']),
            $this->_application->H($data['#default_value']),
            $this->_application->Attr($data['#attributes']),
            empty($data['#btn_label_noescape']) ? $this->_application->H($data['#btn_label']) : $data['#btn_label']
        );
    }
}