<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

class RadioField extends AbstractField
{
    protected $_type = 'radio';

    public function formFieldInit($name, array &$data, Form $form)
    {
        if (!isset($data['#on_value'])) $data['#on_value'] = 1;
    }

    public function formFieldSubmit(&$value, array &$data, Form $form)
    {
        // Is it a required field?
        if (is_null($value)) {
            if ($form->isFieldRequired($data)) {
                $form->setError(isset($data['#required_error_message']) ? $data['#required_error_message'] : __('Selection required.', 'directories'), $data);
            }

            return;
        }

        if ($value != $data['#on_value']) {
            $form->setError(__('Invalid option selected.', 'directories'), $data);
        }
    }

    public function formFieldRender(array &$data, Form $form)
    {
        $this->_render($this->_renderInput($data, $form), $data, $form);
    }

    protected function _renderInput(array &$data, Form $form, $class = '')
    {
        if (isset($data['#on_label'])) {
            $label = $data['#on_label'];
        } else {
            // Use title as checkbox label
            $label = (string)$data['#title'];
            // Clear title to prevent duplicated label
            unset($data['#title']);
            $data['#option_no_escape'] = !empty($data['#title_no_escape']);
        }
        if (strlen($label)) {
            if (empty($data['#option_no_escape'])) {
                $label = $this->_application->H($label);
            }
        } else {
            $label = '&nbsp;'; // required to align radio button correctly, as of BS 4.1.1
        }
        if ($this->_isChecked($data)) {
            $data['#attributes']['checked'] = 'checked';
        }

        return sprintf(
            '<div class="drts-form-field-radio-option %9$scustom-control %9$scustom-%1$s %9$smt-1 %2$s%8$s" data-value="%4$s">
    <input class="%7$s %9$scustom-control-input" type="%1$s" id="%10$s" name="%3$s" value="%4$s"%5$s>
    <label class="%9$scustom-control-label" for="%10$s">%6$s</label>
</div>',
            $this->_type,
            $class,
            $this->_type == 'checkbox' ? $data['#name'] . '[]' : $data['#name'],
            $this->_application->H($data['#on_value']),
            $this->_application->Attr($data['#attributes'], 'class'),
            $label,
            isset($data['#attributes']['class']) ? $this->_application->H($data['#attributes']['class']) : '',
            empty($data['#disabled']) ? '' : ' ' . DRTS_BS_PREFIX . 'disabled',
            DRTS_BS_PREFIX,
            $form->getFieldId($data['#name'])
        );
    }

    protected function _isChecked(array $data)
    {
        $ret = false;
        if ($this->_type === 'checkbox') {
            if (isset($data['#default_value'])) {
                if (is_array($data['#default_value'])) {
                    // coming from request
                    $ret = $data['#default_value'][0] == $data['#on_value'];
                } else {
                    $ret = $data['#default_value'] == $data['#on_value'];
                }
            }
        } else {
            $ret = $data['#default_value'] == $data['#on_value'];
        }

        return $ret;
    }
}
