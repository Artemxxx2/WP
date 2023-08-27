<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

class CheckboxField extends RadioField
{
    protected $_type = 'checkbox';
    
    public function formFieldInit($name, array &$data, Form $form)
    {
        parent::formFieldInit($name, $data, $form);
        
        if (isset($data['#switch']) && !$data['#switch']) return;
        
        $data['#switch'] = true;
    }

    public function formFieldSubmit(&$value, array &$data, Form $form)
    {
        $_value = is_array($value) ? $value : array($value);
        
        // Is it a required field?
        if (count($_value) === 0 || empty($_value[0])) {
            if ($form->isFieldRequired($data)) {
                $form->setError(isset($data['#required_error_message']) ? $data['#required_error_message'] : __('Selection required.', 'directories'), $data);
            }
            $value = isset($data['#off_value']) ? $data['#off_value'] : false;

            return;
        }

        if (isset($data['#on_value'])) {
            if ($_value[0] != $data['#on_value']) {
                $form->setError(__('Invalid option selected.', 'directories'), $data);

                return;
            }
            $value = $data['#on_value'];
        } else {
            $value = true;
        }
    }
    
    public function formFieldRender(array &$data, Form $form)
    {
        if (empty($data['#switch'])) {
            $data['#horizontal_label_padding'] = false;
            parent::formFieldRender($data, $form);
            
            return;
        }
        
        $checked = $this->_isChecked($data);
        
        if (empty($data['#switch_buttons'])) {
            $data['#horizontal_label_padding'] = false;
            $html = sprintf(
                '<label class="%8$smy-custom-switch">
    <input class="%8$smy-custom-switch-input" type="checkbox" id="%9$s-on" name="%1$s" value="%3$s"%2$s>
    <span class="%8$smy-custom-switch-indicator"></span>
    <span class="%8$smy-custom-switch-description" for="%9$s-on"></span>
</label>',
                $data['#name'],
                $checked ? ' checked="checked"' : '',
                isset($data['#on_value']) ? $this->_application->H($data['#on_value']) : 1,
                $this->_application->H(isset($data['#on_label']) ? $data['#on_label'] : __('Yes', 'directories')),
                $checked ? '' : ' checked="checked"',
                isset($data['#off_value']) ? $this->_application->H($data['#off_value']) : 0,
                $this->_application->H(isset($data['#off_label']) ? $data['#off_label'] : __('No', 'directories')),
                DRTS_BS_PREFIX,
                $form->getFieldId($data['#name'])
            );
            /*
            $html = sprintf(
                '<div class="%8$scustom-control %8$scustom-radio %8$scustom-control-inline">
    <input class="%8$scustom-control-input" type="radio" id="%9$s-on" name="%1$s" value="%3$s"%2$s>
    <label class="%8$scustom-control-label" for="%9$s-on">%4$s</label>
</div>
<div class="%8$scustom-control %8$scustom-radio %8$scustom-control-inline">
    <input class="%8$scustom-control-input" type="radio" id="%9$s-off" name="%1$s" value="%6$s"%5$s>
    <label class="%8$scustom-control-label" for="%9$s-off">%7$s</label>
</div>',
                $data['#name'],
                $checked ? ' checked="checked"' : '',
                isset($data['#on_value']) ? $this->_application->H($data['#on_value']) : 1,
                $this->_application->H(isset($data['#on_label']) ? $data['#on_label'] : __('Yes', 'directories')),
                $checked ? '' : ' checked="checked"',
                isset($data['#off_value']) ? $this->_application->H($data['#off_value']) : 0,
                $this->_application->H(isset($data['#off_label']) ? $data['#off_label'] : __('No', 'directories')),
                DRTS_BS_PREFIX,
                $form->getFieldId($data['#name'])
            );
             * 
             */
        } else {
            $html = sprintf(
                '<div class="%10$sbtn-group drts-form-switch" data-toggle="%10$sbuttons">
  <label class="%10$sbtn %10$sbtn-sm %10$sbtn-outline-secondary%2$s" data-toggle="%10$sbutton">
    <input type="radio" name="%1$s" autocomplete="off"%3$s value="%4$s"> %5$s
  </label>
  <label class="%10$sbtn %10$sbtn-sm %10$sbtn-outline-secondary%6$s" data-toggle="%10$sbutton">
    <input type="radio" name="%1$s" autocomplete="off"%7$s value="%8$s"> %9$s
  </label>
</div>',
                $data['#name'],
                $checked ? ' ' . DRTS_BS_PREFIX . 'active' : '',
                $checked ? ' checked="checked"' : '',
                isset($data['#on_value']) ? $this->_application->H($data['#on_value']) : 1,
                $this->_application->H(isset($data['#on_label']) ? $data['#on_label'] : __('Yes', 'directories')),
                $checked ? '' : ' ' . DRTS_BS_PREFIX . 'active',
                $checked ? '' : ' checked="checked"',
                isset($data['#off_value']) ? $this->_application->H($data['#off_value']) : 0,
                $this->_application->H(isset($data['#off_label']) ? $data['#off_label'] : __('No', 'directories')),
                DRTS_BS_PREFIX
            );
        }
        
        $this->_render($html, $data, $form);
    }
}
