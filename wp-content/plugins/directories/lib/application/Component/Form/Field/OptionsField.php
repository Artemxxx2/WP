<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

class OptionsField extends AbstractField
{
    public function formFieldInit($name, array &$data, Form $form)
    {
        if (!isset($data['#id'])) {
            $data['#id'] = $form->getFieldId($name);
        }
        if (isset($data['#default_value']['options'])) {
            $data['#options'] = $data['#default_value']['options'];
            $data['#icons'] = isset($data['#default_value']['icons']) ? $data['#default_value']['icons'] : [];
            $data['#colors'] = isset($data['#default_value']['colors']) ? $data['#default_value']['colors'] : [];
            $data['#default_value'] = isset($data['#default_value']['default']) ? $data['#default_value']['default'] : [];
        } else {
            if (!isset($data['#options'])) {
                $data['#options'] = [];
            }
            if (!isset($data['#icons'])) {
                $data['#icons'] = [];
            }
            if (!isset($data['#colors'])) {
                $data['#colors'] = [];
            }
            if (!isset($data['#default_value'])) {
                $data['#default_value'] = [];
            }
        }
        $form->settings['#pre_render'][__CLASS__] = [[$this, 'preRenderCallback'], [empty($data['#disable_icon']), !empty($data['#enable_color'])]];
    }

    protected function _loadCsvData(&$value)
    {
        $value['csv'] = trim($value['csv']);
        if (!strlen($value['csv'])) return;

        foreach (explode(PHP_EOL, $value['csv']) as $line) {
            if (!$line = trim($line)) continue;

            $_line = array_map('trim', explode(',', $line));
            if (!isset($_line[0])
                || !strlen($_line[0])
            ) continue;

            $icon = null;
            if (isset($_line[2])) {
                if (strpos($_line[2], 'far') !== false
                    || strpos($_line[2], 'fas') !== false
                    || strpos($_line[2], 'fab') !== false
                ) {
                    $icon = $_line[2];
                } else {
                    if (strpos($_line[2], 'fa-') === 0) {
                        $icon = 'fas ' . $_line[2];
                    }
                }
            }
            $color = null;
            if (isset($_line[3])
                && $this->_isValidColor($_line[3])
            ) {
                $color = $_line[3];
            }
            $value['options'][] = [
                'label' => $_line[0],
                'value' => isset($_line[1]) && strlen($_line[1]) ? $_line[1] : $_line[0],
                'icon' => $icon,
                'color' => $color,
            ];
        }
    }

    protected function _isValidColor($color)
    {
        return ($length = strlen($color))
            && ($length === 4 || $length === 7)
            && strpos($color, '#') === 0
            && ctype_xdigit(substr($color, 1));
    }

    public function formFieldSubmit(&$value, array &$data, Form $form)
    {
        if (isset($value['csv'])) {
            $this->_loadCsvData($value);
        }
        $options = $icons = $colors = [];
        $default_value = [];
        if (!isset($value['default'])) {
            $value['default'] = [];
        } else {
            settype($value['default'], 'array');
        }
        if (!empty($data['#options_disabled']) && !empty($data['#default_value'])) {
            // Add options that are disabled but selected by default
            foreach ($data['#options_disabled'] as $option_disabled) {
                if (in_array($option_disabled, $data['#default_value'])) {
                    $default_value[] = $option_disabled;
                }
            }
        }
        if (!empty($data['#slugify_value']) && !isset($data['#value_regex'])) {
            $data['#value_regex'] = '/^[a-z0-9_]+$/';
        }
        foreach ((array)@$value['options'] as $key => $option) {
            $option['value'] = trim($option['value']);
            if (!strlen($option['value'])) continue;

            if (isset($data['#value_regex']) && strlen($data['#value_regex'])) {
                if (!preg_match($data['#value_regex'], $option['value'])) {
                    $error = isset($data['#value_regex_error_message'])
                        ? $data['#value_regex_error_message']
                        : sprintf(__('The input value did not match the regular expression: %s', 'directories'), $data['#value_regex']);
                    $form->setError($error, $data);
                }
            } elseif (!empty($data['#value_max_length'])) {
                if (strlen($option['value']) > $data['#value_max_length']) {
                    $form->setError(sprintf(__('The input value must be shorter than %d characters.', 'directories'), $data['#value_max_length']), $data);
                }
            }

            // May have duplicates if value is non-alphanumeric
            if (isset($options[$option['value']])) {
                $suffix = 2;
                while (isset($options[$option['value'] . $suffix])) {
                    ++$suffix;
                }
                $option['value'] .= $suffix;
            }
            if (in_array($key, $value['default']) && !in_array($option['value'], $default_value)) {
                $options[$option['value']] = isset($option['label']) ? $option['label'] : null; // may be empty if option is disabled
                $default_value[] = $option['value'];
            } else {
                if (empty($data['#default_options_only'])) {
                    $options[$option['value']] = isset($option['label']) ? $option['label'] : null; // may be empty if option is disabled
                }
            }
            if (!empty($option['icon'])) {
                $icons[$option['value']] = $option['icon'];
            }
            if (isset($option['color'])
                && $this->_isValidColor($color = trim($option['color']))
            ) {
                $colors[$option['value']] = $option['color'];
            }
        }

        if (!$form->hasError($data)) {
            if (empty($options)) {
                if ($form->isFieldRequired($data)) {
                    $form->setError(isset($data['#required_error_message']) ? $data['#required_error_message'] : __('Please fill out this field.', 'directories'), $data);
                }
            } else {
                if (empty($default_value)
                    && !empty($data['#require_default'])
                ) {
                    $form->setError(isset($data['#default_required_error_message']) ? $data['#default_required_error_message'] : __('Please select at least one option.', 'directories'), $data);
                }
            }
        }

        $value = empty($data['#default_options_only']) ? array('options' => $options, 'icons' => $icons, 'colors' => $colors, 'default' => $default_value) : $options;
    }

    public function formFieldRender(array &$data, Form $form)
    {
        if (!empty($data['#multiple'])) {
            $type = 'checkbox';
            $default_name = '[default][]';
        } else {
            $type = 'radio';
            $default_name = '[default]';
        }
        $input = array('<div class="%18$sinput-group drts-form-field-option%15$s %18$smb-2"><div class="%18$sinput-group-prepend">');
        $input[] = $checkbox = '<div class="%18$sinput-group-text"><input type="%9$s" name="%1$s%10$s" id="%6$s-%2$d" data-option-value="%4$s" value="%2$d"%5$s%14$s /></div>';
        if (empty($data['#disable_icon'])) {
            $input[] = '<button type="button" name="%1$s[options][%2$d][icon]" data-current="%16$s" class="%18$sbtn %18$sbtn-info drts-form-field-option-icon" data-placement="bottom"></button>';
        }
        $input[] = '</div>';
        if (!empty($data['#enable_color'])) {
            $input[] = '<input type="text" name="%1$s[options][%2$d][color]" value="%21$s" class="%18$sform-control drts-form-field-option-color" placeholder="%22$s" />';
        }
        $input[] = '<input type="text" name="%1$s[options][%2$d][label]" value="%3$s" placeholder="%7$s" class="%18$sform-control drts-form-field-option-label"%19$s />';
        $value_input_type = empty($data['#hide_value']) ? 'text' : 'hidden';
        $input[] = '<input type="' . $value_input_type . '" name="%1$s[options][%2$d][value]" value="%4$s" placeholder="%8$s" class="%18$sform-control drts-form-field-option-value" data-slugify="%17$s" />';
        $input[] = '<div class="%18$sinput-group-append">';
        if (empty($data['#disable_add'])) {
            $checked = empty($data['#default_unchecked']) && $type === 'checkbox';
            $input[] = '<a href="#" class="%18$sbtn %18$sbtn-success" onclick="DRTS.Form.field.options.add(\'#%6$s\', \'%1$s\', this, %11$s, ' . ($checked ? 'true' : 'false') . '); return false;"><i class="fas fa-plus"></i></a>';
        } else {
            if (!isset($data['#disable_remove'])) {
                $data['#disable_remove'] = true;
            }
        }
        if (empty($data['#disable_remove'])) {
            $input[] = '<a href="#" class="%18$sbtn %18$sbtn-danger" onclick="DRTS.Form.field.options.remove(\'#%6$s\', this, \'%12$s\'); return false;"><i class="fas fa-minus"></i></a>';
        }
        if (empty($data['#disable_sort'])) {
            $input[] = '<span class="%18$sbtn %18$sbtn-secondary drts-form-field-option-sort%13$s"><i class="fas fa-arrows-alt-v"></i></span>';
        }
        $input[] = '</div>';
        $input[] = '</div>';
        $input = implode('', $input);

        $inputs = [];
        $label_title = $this->_application->H(isset($data['#label_title']) ? (string)$data['#label_title'] : __('Label', 'directories'));
        $value_title = $this->_application->H(isset($data['#value_title']) ? (string)$data['#value_title'] : __('Value', 'directories'));
        $icon_title = $this->_application->H(isset($data['#icon_title']) ? (string)$data['#icon_title'] : __('Icon', 'directories'));
        $color_title = $this->_application->H(isset($data['#color_title']) ? (string)$data['#color_title'] : __('Color', 'directories'));

        if (!empty($data['#options'])) {
            $first_option = current($data['#options']);
            if (!is_array($first_option)) {
                // not coming from request, probably from saved values
                $new_options = [];
                foreach ($data['#options'] as $value => $label) {
                    $new_options[] = array(
                        'value' => $value,
                        'label' => $label,
                        'icon' => isset($data['#icons'][$value]) ? $data['#icons'][$value] : null,
                        'color' => isset($data['#colors'][$value]) ? $data['#colors'][$value] : null,
                    );
                }
                $data['#options'] = $new_options;
            }
            // Values may be keys if submitted and error occurred
            if ($form->isSubmitted()) {
                foreach (array_keys($data['#default_value']) as $key) {
                    $value = $data['#default_value'][$key];
                    if (is_numeric($value)
                        && isset($data['#options'][$value])
                    ) {
                        $data['#default_value'][$key] = $data['#options'][$value]['value'];
                    } else {
                        break; // not submit and error so abort
                    }
                }
            }
            if ($options_value_disabled = isset($data['#options_value_disabled']) ? $data['#options_value_disabled'] : []) {
                $input_disabled = array('<div class="%18$sinput-group drts-form-field-option %18$smb-2 drts-form-field-option-disabled"><div class="%18$sinput-group-prepend">');
                $input_disabled[] = $checkbox;
                if (empty($data['#disable_icon'])) {
                    $input_disabled[] = '<button type="button" name="%1$s[options][%2$d][icon]" data-current="%16$s" class="%18$sbtn %18$sbtn-info drts-form-field-option-icon" data-placement="bottom"></button>';
                }
                $input_disabled[] = '</div>';
                if (!empty($data['#enable_color'])) {
                    $input_disabled[] = '<input type="text" name="%1$s[options][%2$d][color]" value="" class="%18$sform-control drts-form-field-option-color" placeholder="%22$s" />';
                }
                $input_disabled[] = '<input type="text" name="%1$s[options][%2$d][label]" value="%3$s" placeholder="%7$s" class="%18$sform-control drts-form-field-option-label"%19$s />';
                $input_disabled[] = '<input type="' . $value_input_type . '" value="%4$s" placeholder="%8$s" disabled="disabled" class="%18$sform-control" /><input type="hidden" name="%1$s[options][%2$d][value]" value="%4$s" class="drts-form-field-option-value" data-slugify="%17$s" />';
                $input_disabled[] = '<div class="%18$sinput-group-append">';
                if (empty($data['#disable_add'])) {
                    $input_disabled[] = '<a href="#" class="%18$sbtn %18$sbtn-success %18$sdisabled" onclick="return false;"><i class="fas fa-plus"></i></a>';
                }
                if (empty($data['#disable_remove'])) {
                    $input_disabled[] = '<a href="#" class="%18$sbtn %18$sbtn-danger%20$s" onclick="return false;"><i class="fas fa-minus"></i></a>';
                }
                if (empty($data['#disable_sort'])) {
                    $input_disabled[] = '<span class="%18$sbtn %18$sbtn-secondary drts-form-field-option-sort%13$s"><i class="fas fa-arrows-alt-v"></i></span>';
                }
                $input_disabled[] = '</div>';
                $input_disabled[] = '</div>';
                $input_disabled = implode('', $input_disabled);
            }
            $options_disabled = isset($data['#options_disabled']) ? $data['#options_disabled'] : [];
            $options_sort_disabled = isset($data['#options_sort_disabled']) ? $data['#options_sort_disabled'] : [];
            $options_label_disabled = isset($data['#options_label_disabled']) ? $data['#options_label_disabled'] : [];
            foreach ($data['#options'] as $key => $option) {
                if (!strlen($option['value'])) continue;

                $option_disabled = $options_value_disabled === true || in_array($option['value'], $options_value_disabled);
                $option_remove_disabled = $option_disabled && (!isset($data['#options_remove_disabled']) || in_array($key, $data['#options_remove_disabled']));
                $inputs[] = sprintf(
                    $option_disabled ? $input_disabled : $input,
                    $data['#name'],
                    $key,
                    isset($option['label']) ? $this->_application->H($option['label']) : '',
                    $this->_application->H($option['value']),
                    in_array($option['value'], $data['#default_value']) ? ' checked="checked"' : '',
                    $data['#id'],
                    isset($data['#options_placeholder'][$option['value']]) ? $data['#options_placeholder'][$option['value']] : $label_title,
                    isset($data['#options_value_placeholder'][$option['value']]) ? $data['#options_value_placeholder'][$option['value']] : $value_title,
                    $type,
                    $default_name,
                    $type === 'checkbox' ? 'true' : 'false',
                    __('Are you sure?', 'directories'),
                    in_array($option['value'], $options_sort_disabled) ? ' ' . DRTS_BS_PREFIX . 'disabled' : '',
                    in_array($option['value'], $options_disabled) ? ' disabled="disabled"' : '',
                    '',
                    isset($option['icon']) ? $this->_application->H($option['icon']) : '',
                    empty($data['#slugify_value']) ? 'false' : 'true',
                    DRTS_BS_PREFIX,
                    in_array($option['value'], $options_label_disabled) ? ' disabled="disabled"' : '',
                    $option_remove_disabled ? ' ' . DRTS_BS_PREFIX . 'disabled' : '', // disable remove button?
                    !empty($option['color']) ? $this->_application->H($option['color']) : '',
                    $color_title
                );
            }
            if (empty($data['#disable_add'])) {
                $inputs[] = sprintf(
                    $input,
                    $data['#name'],
                    ++$key,
                    '',
                    '',
                    $type === 'checkbox' ? ' checked="checked"' : '',
                    $data['#id'],
                    $label_title,
                    $value_title,
                    $type,
                    $default_name,
                    $type === 'checkbox' ? 'true' : 'false',
                    __('Are you sure?', 'directories'),
                    '',
                    in_array($option['value'], $options_disabled) ? ' disabled="disabled"' : '',
                    ' drts-form-field-option-new',
                    '',
                    empty($data['#slugify_value']) ? 'false' : 'true',
                    DRTS_BS_PREFIX,
                    '',
                    '', // disable remove button?
                    '',
                    $color_title
                );
            }
        } else {
            if (empty($data['#disable_add'])) {
                for ($i = 0; $i < 3; $i++) {
                    $inputs[] = sprintf(
                        $input,
                        $data['#name'],
                        $i,
                        '',
                        '',
                        ($type === 'checkbox' && empty($data['#default_unchecked'])) || ($type === 'radio' && $i === 0) ? ' checked="checked"' : '',
                        $data['#id'],
                        $label_title,
                        $value_title,
                        $type,
                        $default_name,
                        $type === 'checkbox' ? 'true' : 'false',
                        __('Are you sure?', 'directories'),
                        '',
                        '',
                        ' drts-form-field-option-new',
                        '',
                        empty($data['#slugify_value']) ? 'false' : 'true',
                        DRTS_BS_PREFIX,
                        '',
                        '', // disable remove button?
                        '',
                        $color_title
                    );
                }
            }
        }
        $markup = array('<div class="drts-form-field-options">');
        if (empty($inputs)) {
            if (isset($data['#empty_message'])) {
                $markup[] = $data['#empty_message'];
            }
        } else {
            $markup[] = implode('', $inputs);
        }
        if (empty($data['#disable_add']) && empty($data['#disable_add_csv'])) {
            $markup[] = '<button type="button" class="' . DRTS_BS_PREFIX . 'btn ' . DRTS_BS_PREFIX . 'btn-sm ' . DRTS_BS_PREFIX . 'btn-outline-secondary drts-form-field-option-csv"><i class="fas fa-plus"></i> ' . __('Add from CSV', 'directories') . '</button>';
            $markup[] = sprintf(
                '<textarea placeholder="%s" name="%s[csv]" rows="2" style="margin-top:10px; width:100%%; display:none;"></textarea>',
                str_repeat(
                    $label_title . ',' . $value_title . ' ' . __('(optional)', 'directories')
                        . ',' . $icon_title . ' ' . __('(optional)', 'directories')
                        . ',' . $color_title . ' ' . __('(optional)', 'directories')
                        . PHP_EOL,
                    2
                ),
                $data['#name']
            );
        }
        $markup[] = '</div>';

        $this->_render(implode('', $markup), $data, $form);
    }

    public function preRenderCallback(Form $form, $loadIconPicker, $loadColorPicker)
    {
        $this->_application->getPlatform()->loadJqueryUiJs(array('sortable'))
            ->addJsFile('form-field-options.min.js', 'drts-form-field-options', array('drts-form'))
            ->addJsFile('latinise.min.js', 'latinise', null, null, true, true);
        if ($loadIconPicker) {
            $this->_application->Form_Scripts_iconpicker(['fontawesome']);
        }
        if ($loadColorPicker) {
            $this->_application->Form_Scripts_colorpicker();
        }

        $form->settings['#js_ready'][] = sprintf(
            '(function() {
    $("#%s").find(".drts-form-type-options").each(function(){
        DRTS.Form.field.options($(this)); 
    });
})();',
            $form->settings['#id']
        );
    }
}
