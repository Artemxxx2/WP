<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

class LengthsField extends ItemField
{
    public function formFieldRender(array &$data, Form $form)
    {   
        $markup = array('<div class="' . DRTS_BS_PREFIX . 'form-row" style="margin-left:0; margin-right:-10px">');
        foreach (array(
            'top' => __('Top', 'directories'),
            'right' => __('Right', 'directories'),
            'bottom' => __('Bottom', 'directories'),
            'left' => __('Left', 'directories'),
        ) as $key => $label) {
            $markup[] = sprintf(
                '<div class="%1$scol-sm-3" style="padding-left:0; padding-right:10px">
    %2$s<input class="%1$sform-control" name="%3$s[%4$s]" type="number" placeholder="%5$s" value="%6$s" />%7$s
</div>',
                DRTS_BS_PREFIX,
                isset($data['#length_suffix']) ? '<div class="' . DRTS_BS_PREFIX . 'input-group">' : '',
                $this->_application->H($data['#name']),
                $key,
                $this->_application->H($label),
                isset($data['#default_value'][$key]) && strlen($data['#default_value'][$key]) ? $this->_application->H($data['#default_value'][$key]) : null,
                isset($data['#length_suffix']) ? '<div class="' . DRTS_BS_PREFIX . 'input-group-append"><span class="' . DRTS_BS_PREFIX . 'input-group-text">' . $data['#length_suffix'] . '</span></div></div>' : ''
            );
        }
        $markup[] = '</div>';
        
        $data['#markup'] = implode(PHP_EOL, $markup);
        
        return parent::formFieldRender($data, $form);
    }
    
    public function formFieldSubmit(&$value, array &$data, Form $form)
    {
        foreach (array('top', 'right', 'bottom', 'left') as $key) {
            if (isset($value[$key]) && strlen($value[$key])) {
                if (!is_numeric($value[$key])) {
                    $form->setError(
                        sprintf(__('Invalid value: %s', 'directories'), $value[$key]),
                        $data
                    );
                    break;
                }
            }
        }
    }
}