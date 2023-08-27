<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

class SelectField extends AbstractField
{
    public function formFieldInit($name, array &$data, Form $form)
    {
        if (!isset($data['#options'])) {
            $data['#options'] = [];
        }
        if (!isset($data['#options_disabled'])) {
            $data['#options_disabled'] = [];
        }
        if ($data['#multiple'] = !empty($data['#multiple'])) {
            $data['#size'] = isset($data['#size']) ? $data['#size'] : ((10 < $count = count($data['#options'])) ? 10 : $count);
        }

        if (!empty($data['#select2'])) {
            $this->_select2($data, $form);
        } elseif (!empty($data['#multiple'])
            && !empty($data['#multiselect'])
        ) {
            $this->_bsMultiSelect($data, $form);
        }
    }

    public function formFieldSubmit(&$value, array &$data, Form $form)
    {
        // Is it a required field?
        if (is_null($value)) {
            if ($form->isFieldRequired($data)) {
                $form->setError(isset($data['#required_error_message']) ? $data['#required_error_message'] : __('Selection is required for this field.', 'directories'), $data);
            }
            $value = $data['#multiple'] ? [] : null;

            return;
        }

        // No options
        if (empty($data['#options'])) {
            if (empty($data['#skip_validate_option'])) {
                $value = $data['#multiple'] ? [] : null;

                return;
            }
        }

         $new_value = (array)$value;

        // Are all the selected options valid?
        foreach ($new_value as $k => $_value) {
            if (empty($data['#skip_validate_option']) && !isset($data['#options'][$_value])) {
                $form->setError(__('Invalid option selected.', 'directories'), $data);

                return;
            }
            if (isset($data['#empty_value']) && $_value == $data['#empty_value']) {
                unset($new_value[$k]);
            }
        }

        if (empty($new_value) && $form->isFieldRequired($data)) {
            $form->setError(isset($data['#required_error_message']) ? $data['#required_error_message'] : __('Selection is required for this field.', 'directories'), $data);
            return;
        }

        if (!$data['#multiple']) {
            $value = isset($new_value[0]) ? $new_value[0] : (isset($data['#empty_value']) ? $data['#empty_value'] : null);
            return;
        }

        if (!empty($data['#max_selection']) && count($new_value) > $data['#max_selection']) {
            $form->setError(sprintf(__('Maximum of %d selections is allowed for this field.', 'directories'), $data['#max_selection']), $data);
            return;
        }
        $value = $new_value;
    }

    public function formFieldRender(array &$data, Form $form)
    {
        $options = $optgroups = $options_attr = [];
        $values = isset($data['#default_value']) ? (array)$data['#default_value'] : [];
        $i = 0;
        foreach ($data['#options'] as $k => $label) {
            if (is_array($label)) {
                if (isset($label['#count'])) {
                    $_label = $label['#title'] . ' (' . $label['#count'] . ')';
                } else {
                    $_label = $label['#title'];
                }
                $data['#options_attr'][$k]['data-prefix'] = '';
                $data['#options_attr'][$k]['data-depth'] = 0;
                if (!empty($label['#depth'])) {
                    $data['#options_attr'][$k]['data-depth'] = $label['#depth'];
                    if (isset($label['#title_prefix'])) {
                        $prefix = str_repeat($label['#title_prefix'], $label['#depth']);
                        $_label = $prefix . ' ' . $_label;
                        $data['#options_attr'][$k]['data-prefix'] = $prefix;
                    }
                }
                if (!empty($label['#attributes'])) {
                    if (!isset($data['#options_attr'][$k])) $data['#options_attr'][$k] = [];
                    $data['#options_attr'][$k] += $label['#attributes'];
                }
                if (isset($label['#group'])
                    && isset($data['#optgroups'][$label['#group']])
                ) {
                    if (!isset($optgroups[$label['#group']])) {
                        $optgroups[$label['#group']] = array('options' => [], 'i' => $i);
                        ++$i;
                    }
                    $optgroups[$label['#group']]['options'][] = $this->_renderOption($form, $data, $k, $_label, $values);
                    continue;
                } else {
                    $label = $_label;
                }
            }
            $options[$i] = $this->_renderOption($form, $data, $k, $label, $values);
            ++$i;
        }
        foreach ($optgroups as $optgroup_name => $optgroup) {
            $options[$optgroup['i']] = sprintf(
                '<optgroup label="%s"%s>%s</optgroup>',
                $this->_application->H($data['#optgroups'][$optgroup_name]),
                isset($data['#optgroups_attr'][$optgroup_name]) ? $this->_application->Attr($data['#optgroups_attr'][$optgroup_name]) : '',
                implode(PHP_EOL, $optgroup['options'])
            );
        }
        ksort($options);

        if ($data['#multiple']) {
            $data['#attributes']['multiple'] = 'multiple';
            $name = $this->_application->H($data['#name']) . '[]';
            if (isset($data['#size']) && $data['#size'] > 1) {
                $data['#attributes']['size'] = $data['#size'];
            }
        } else {
            unset($data['#attributes']['multiple']);
            $name = $this->_application->H($data['#name']);

            if (isset($values[0])) {
                $data['#attributes']['data-default-value'] = $values[0];
            }
        }

        $select = sprintf(
            '<select class="%sform-control %s" name="%s"%s>%s</select>',
            DRTS_BS_PREFIX,
            isset($data['#attributes']['class']) ? $this->_application->H($data['#attributes']['class']) : '',
            $name,
            $this->_application->Attr($data['#attributes'], 'class'),
            implode(PHP_EOL, $options)
        );

        $has_addon = false;
        $html = [];
        if (isset($data['#field_prefix'])) {
            if (empty($data['#field_prefix_no_addon'])) {
                $has_addon = true;
                $html[] = '<div class="' . DRTS_BS_PREFIX . 'input-group-prepend"><span class="' . DRTS_BS_PREFIX . 'input-group-text">' . $data['#field_prefix'] . '</span></div>';
            } else {
                $html[] = $data['#field_prefix'];
            }
        }
        $html[] = $select;
        if (!empty($data['#options_hidden'])) {
            foreach ($data['#options_hidden'] as $option_value) {
                $html[] = sprintf(
                    '<input type="hidden" name="%s" value="%s"%s />',
                    $name,
                    $this->_application->H($option_value),
                    !empty($data['#options_disabled']) && in_array($option_value, $data['#options_disabled']) ? ' disabled="disabled"' : ''
                );
            }
        }
        if (isset($data['#field_suffix'])) {
            if (empty($data['#field_suffix_no_addon'])) {
                $has_addon = true;
                $html[] = '<div class="' . DRTS_BS_PREFIX . 'input-group-append"><span class="' . DRTS_BS_PREFIX . 'input-group-text">' . $data['#field_suffix'] . '</span></div>';
            } else {
                $html[] = $data['#field_suffix'];
            }
        }

        $this->_render($has_addon ? '<div class="' . DRTS_BS_PREFIX . 'input-group">' . implode(PHP_EOL, $html) . '</div>' : implode(PHP_EOL, $html), $data, $form);
    }

    protected function _renderOption(Form $form, $data, $value, $label, array $selected)
    {
        $strict = empty($value) && $value !== '0';
        return sprintf(
            '<option value="%s"%s%s%s>%s</option>',
            $this->_application->H($value),
            in_array($value, $selected, $strict) ? ' selected="selected"' : '',
            in_array($value, $data['#options_disabled'], $strict) ? ' disabled="disabled"' : '',
            isset($data['#options_attr'][$value]) ? $this->_application->Attr($data['#options_attr'][$value]) : '',
            $this->_application->H($label)
        );
    }

    protected function _select2(array &$data, Form $form)
    {
        $data['#data']['select2'] = 1;
        if (!isset($data['#select2_allow_clear'])) {
            $data['#select2_allow_clear'] = true;
        }
        if (!empty($data['#max_selection'])) {
            $data['#select2_maximum_selection_length'] = $data['#max_selection'];
        }
        if (!isset($data['#select2_minimum_input_length'])) {
            $data['#select2_minimum_input_length'] = empty($data['#select2_ajax']) ? 0 : 2;
        }
        if (!isset($data['#select2_placeholder'])) {
            if (isset($data['#placeholder'])) {
                $data['#select2_placeholder'] = $data['#placeholder'];
            } elseif ($data['#multiple']) {
                $data['#select2_placeholder'] = '';
            } else {
                if (isset($data['#empty_value'])
                    && array_key_exists($data['#empty_value'], $data['#options'])
                ) {
                    $data['#select2_placeholder'] = $data['#options'][$data['#empty_value']];
                } else {
                    $data['#select2_placeholder'] = __('— Select —', 'directories');
                }
            }
        }
        // default select2 options
        foreach (array('minimum_input_length', 'maximum_input_length', 'tags', 'placeholder', 'allow_clear',
            'close_on_select', 'maximum_selection_length', 'minimum_results_for_search') as $key
        ) {
            if (isset($data['#select2_' . $key])) {
                $value = $data['#select2_' . $key];
                $data['#attributes']['data-' . str_replace('_', '-', $key)] = is_bool($value) ? ($value ? 'true' : false) : $value;
            }
        }

        // custom select2 options
        foreach (array('ajax', 'ajax_url', 'ajax_delay', 'item_class', 'item_id_key', 'item_text_key', 'item_image_key',
            //'item_text_style', 'item_image_style', 'item_image_text_style'
        ) as $key) {
            if (isset($data['#select2_' . $key])) {
                $value = $data['#select2_' . $key];
                $data['#data']['select2-' . str_replace('_', '-', $key)] = is_bool($value) ? ($value ? 'true' : false) : $value;
            }
        }
        if (!empty($data['#select2_placehoder']) && !isset($data['#options'][''])) {
            $data['#options'] = array('' => '') + $data['#options'];
        }
        if (isset($data['#class'])) {
            $data['#class'] .= ' drts-form-select2';
        } else {
            $data['#class'] = 'drts-form-select2';
        }

        //if (!empty($data['#select2_tags'])) {
            $data['#skip_validate_option'] = true;
        //}

        // Load default items
        if (!empty($data['#default_value'])) {
            if (is_string($data['#default_value'])) {
                // Form was submitted previously
                $data['#default_value'] = explode(',', $data['#default_value']);
            } else {
                if (!is_array($data['#default_value'])) {
                    $data['#default_value'] = array($data['#default_value']);
                }
            }
            if (!$data['#multiple']) {
                $data['#default_value'] = array(array_pop($data['#default_value']));
            }
            if (!empty($data['#default_value']) && isset($data['#default_options_callback'])) {
                $this->_application->CallUserFuncArray($data['#default_options_callback'], array($data['#default_value'], &$data['#options']));
                $data['#default_value'] = array_keys($data['#options']);
            }
        }

        // Add lang data attribute
        if ($locale = $this->_application->getPlatform()->getLocale()) {
            $locale = str_replace('_', '-', $locale);
            $locale_dir = $this->_application->getPlatform()->getAssetsDir('directories', true) . '/js/select2/i18n';
            if (file_exists($locale_dir . '/' . $locale . '.min.js')) {
                $lang = $data['#data']['select2-lang'] = $locale;
            } else {
                if ($locale = explode('-', $locale)) {
                    if (file_exists($locale_dir . '/' . $locale[0] . '.min.js')) {
                        $lang = $data['#data']['select2-lang'] = $locale[0];
                    }
                }
            }
        }
        if (!isset($form->settings['#pre_render']['select2'])) {
            $form->settings['#pre_render']['select2'] = [[$this, 'select2Callback'], [isset($lang) ? $lang : null]];
        }
    }

    public function select2Callback(Form $form, $lang)
    {
        $this->_application->Form_Scripts_select2($lang);
        $this->_application->getPlatform()->addJsFile('form-field-select.min.js', 'drts-form-field-select', array('drts-form'));
        $form->settings['#js_ready'][] = sprintf(
            '$("#%s").find(".drts-form-field[data-select2=\'1\']").each(function(){
    DRTS.Form.field.select($(this)); 
});;',
            $form->settings['#id']
        );
    }

    protected function _bsMultiSelect(array &$data, Form $form)
    {
        $data['#data']['bs-multi-select'] = 1;
        if (isset($data['#placeholder'])) {
            $data['#data']['bs-multi-select-placeholder'] = $data['#placeholder'];
        }
        $data['#data']['bs-multi-select-columns'] = empty($data['#columns']) ? 1 : $data['#columns'];
        $data['#data']['bs-multi-select-height'] = empty($data['#multiselect_height']) ? 0 : $data['#multiselect_height'];
        if (!isset($form->settings['#pre_render']['bsmultiselect'])) {
            $form->settings['#pre_render']['bsmultiselect'] = [$this, 'bsMultiSelectCallback'];
        }
    }

    public function bsMultiSelectCallback(Form $form)
    {
        $this->_application->getPlatform()->addJsFile('BsMultiSelect.min.js', 'bs-multi-select', ['drts-bootstrap'], null, true, true);
        $form->settings['#js_ready'][] = sprintf(
            '$("#%1$s").find(".drts-form-type-select[data-bs-multi-select=\'1\']").each(function() {
    var $this = $(this), choicesCss = {};
    if ($this.data("bs-multi-select-columns") > 1) {
        choicesCss["columnCount"] = $this.data("bs-multi-select-columns");
    }
    if ($this.data("bs-multi-select-height") > 0) {
        choicesCss["maxHeight"] = $this.data("bs-multi-select-height") + "px";
        choicesCss["overflowY"] = "auto"; 
    }
    $this.find("select").bsMultiSelect({
        placeholder: $this.data("bs-multi-select-placeholder") || "",
        css: {
            choices: DRTS.bsPrefix + "dropdown-menu " + DRTS.bsPrefix + "px-0 " + DRTS.bsPrefix + "py-2 " + DRTS.bsPrefix + "m-0 " + DRTS.bsPrefix + "mt-1",
            picks: DRTS.bsPrefix + "form-control " + DRTS.bsPrefix + "mx-0",
            pick: DRTS.bsPrefix + "badge " + DRTS.bsPrefix + "m-0",
            pickButton: DRTS.bsPrefix + "close drts-form-select-remove-selected",
            choiceContent: DRTS.bsPrefix + "custom-control " + DRTS.bsPrefix + "custom-checkbox " + DRTS.bsPrefix + "d-flex",
            choiceCheckBox: DRTS.bsPrefix + "custom-control-input",
            choiceLabel: DRTS.bsPrefix + "custom-control-label " + DRTS.bsPrefix + "justify-content-start", 
            choice: DRTS.bsPrefix + "px-md-2 " + DRTS.bsPrefix + "px-1 " + DRTS.bsPrefix + "m-0",
            filterInput_empty: DRTS.bsPrefix + "form-control"
        },
        cssPatch: {
            choices: choicesCss,
            picks: {padding: "0.375rem 0.75rem", lineHeight: "1.5"},
            choice: {breakInside: "avoid"},
            choice_hover: DRTS.bsPrefix + "text-primary " + DRTS.bsPrefix + "bg-light",
            filterInput: {minHeight: "calc(1.5em - 2px)"}
        }
    });
});',
            $form->settings['#id']
        );
    }
}
