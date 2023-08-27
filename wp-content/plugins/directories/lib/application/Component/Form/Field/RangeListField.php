<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

class RangeListField extends AbstractField
{
    public function formFieldInit($name, array &$data, Form $form)
    {
        if (!isset($data['#input_type'])) $data['#input_type'] = 'number';
        $form->settings['#pre_render'][__CLASS__] = [$this, 'preRenderCallback'];
    }

    public function formFieldRender(array &$data, Form $form)
    {
        if ($data['#input_type'] === 'number') {
            $step = empty($data['#step']) ? 1 : $data['#step'];
            $min_value = isset($data['#min_value']) ? $data['#min_value'] : null;
            $max_value = isset($data['#max_value']) ? $data['#max_value'] : null;
        }
        $input = '<div class="%1$sinput-group drts-form-field-rangelist-option%9$s %1$smb-2">'
            . '<input type="%2$s" name="%3$s[%15$d][min]" value="%4$s" placeholder="%6$s" class="%1$sform-control"%10$s%13$s />'
            . '<input type="%2$s" name="%3$s[%15$d][max]" value="%5$s" placeholder="%7$s" class="%1$sform-control"%10$s%14$s />'
            . '<input type="text" name="%3$s[%15$d][label]" value="%11$s" placeholder="%12$s" class="%1$sform-control" />'
            . '<div class="%1$sinput-group-append">'
            . '<a href="#" class="%1$sbtn %1$sbtn-success" onclick="DRTS.Form.field.rangelist.add(\'%3$s\', this); return false;"><i class="fas fa-plus"></i></a>'
            . '<a href="#" class="%1$sbtn %1$sbtn-danger" onclick="DRTS.Form.field.rangelist.remove(this, \'%8$s\'); return false;"><i class="fas fa-minus"></i></a>'
            . '<span class="%1$sbtn %1$sbtn-secondary drts-form-field-rangelist-option-sort"><i class="fas fa-arrows-alt-v"></i></span>'
            . '</div></div>';
        $inputs = [];
        $min_title = $this->_application->H(isset($data['#min_title']) ? (string)$data['#min_title'] : __('Min value', 'directories'));
        $max_title = $this->_application->H(isset($data['#max_title']) ? (string)$data['#max_title'] : __('Max value', 'directories'));
        $label_title = $this->_application->H(isset($data['#label_title']) ? (string)$data['#label_title'] : __('Label', 'directories'));

        $i = -1;
        if (!empty($data['#default_value'])) {
            foreach ($data['#default_value'] as $value) {
                $inputs[] = sprintf(
                    $input,
                    DRTS_BS_PREFIX,
                    $data['#input_type'],
                    $data['#name'],
                    isset($value['min']) ? $this->_application->H($value['min']) : '',
                    isset($value['max']) ? $this->_application->H($value['max']) : '',
                    $min_title,
                    $max_title,
                    __('Are you sure?', 'directories'),
                    '',
                    isset($step) ? ' step="' . $step . '"' : '',
                    isset($value['label']) ? $this->_application->H($value['label']) : '',
                    $label_title,
                    isset($min_value) ? ' min="' . $min_value . '"' : '',
                    isset($max_value) ? ' max="' . $max_value . '"' : '',
                    ++$i
                );
            }
            if (empty($data['#disable_add'])) {
                $inputs[] = sprintf(
                    $input,
                    DRTS_BS_PREFIX,
                    $data['#input_type'],
                    $data['#name'],
                    '',
                    '',
                    $min_title,
                    $max_title,
                    __('Are you sure?', 'directories'),
                    ' drts-form-field-rangelist-option-new',
                    isset($step) ? ' step="' . $step . '"' : '',
                    '',
                    $label_title,
                    isset($min_value) ? ' min="' . $min_value . '"' : '',
                    isset($max_value) ? ' max="' . $max_value . '"' : '',
                    ++$i
                );
            }
        } else {
            if (empty($data['#disable_add'])) {
                for ($i = $i + 1; $i < 3; $i++) {
                    $inputs[] = sprintf(
                        $input,
                        DRTS_BS_PREFIX,
                        $data['#input_type'],
                        $data['#name'],
                        '',
                        '',
                        $min_title,
                        $max_title,
                        __('Are you sure?', 'directories'),
                        ' drts-form-field-rangelist-option-new',
                        isset($step) ? ' step="' . $step . '"' : '',
                        '',
                        $label_title,
                        isset($min_value) ? ' min="' . $min_value . '"' : '',
                        isset($max_value) ? ' max="' . $max_value . '"' : '',
                        $i
                    );
                }
            }
        }
        $markup = array('<div class="drts-form-field-rangelist">');
        if (empty($inputs)) {
            if (isset($data['#empty_message'])) {
                $markup[] = $data['#empty_message'];
            }
        } else {
            $markup[] = implode('', $inputs);
        }
        $markup[] = '</div>';

        $this->_render(implode('', $markup), $data, $form);
    }

    public function formFieldSubmit(&$value, array &$data, Form $form)
    {
        if (!empty($value)) {
            foreach (array_keys($value) as $key) {
                if ((!isset($value[$key]['min']) || !strlen($value[$key]['min'] = trim($value[$key]['min'])) || ($data['#input_type'] === 'number' && !is_numeric($value[$key]['min'])))
                    && (!isset($value[$key]['max']) || !strlen($value[$key]['max'] = trim($value[$key]['max'])) || ($data['#input_type'] === 'number' && !is_numeric($value[$key]['max'])))
                    && (!isset($value[$key]['label']) || !strlen($value[$key]['label'] = trim($value[$key]['label'])))
                ) {
                    unset($value[$key]);
                }
            }
        }

        // Is it a required field?
        if (empty($value)) {
            if ($form->isFieldRequired($data)) {
                $form->setError(
                    isset($data['#required_error_message']) ? $data['#required_error_message'] : __('Selection required.', 'directories'),
                    $data
                );
            }
            $value = [];
        }
    }

    public function preRenderCallback(Form $form)
    {
        $this->_application->getPlatform()->loadJqueryUiJs(['sortable'])
            ->addJsFile('form-field-rangelist.min.js', 'drts-form-field-rangelist', ['drts-form']);
        $form->settings['#js_ready'][] = sprintf(
            '(function() {
    $("#%s").find(".drts-form-type-rangelist").each(function(){
        DRTS.Form.field.rangelist($(this)); 
    });
})();',
            $form->settings['#id']
        );
    }
}
