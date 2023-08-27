<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

class ButtonsField extends RadiosField
{
    protected function _getHtml(array &$data, Form $form, array $values)
    {
        $data['#id'] = $form->getFieldId($data['#name']);
        $name = $data['#name'];
        if ($this->_type == 'checkbox') {
            $name .= '[]';
        }
        $html[] = '<div class="' . DRTS_BS_PREFIX . 'btn-group-toggle" data-toggle="drts-buttons">';
        foreach ($data['#options'] as $option_value => $option_label) {
            $html[] = $this->_doRenderOption($data, $form, $name, $values, $option_value, $option_label);
        }
        $html[] = '</div>';

        return implode(PHP_EOL, $html);
    }

    protected function _getOptionFormat(array $data, $checked = false)
    {
        $active = $checked ? ' %9$sactive' : '';
        $btn_class = isset($data['#btn_class']) ? $data['#btn_class'] : 'outline-secondary';

        return'<label class="%9$sbtn %9$sbtn-sm %9$sbtn-' . $btn_class . $active . ' %9$smb-1%8$s">'
            . '<input type="%1$s" name="%2$s" value="%3$s"%4$s> %5$s'
            . '</label>';
    }
}