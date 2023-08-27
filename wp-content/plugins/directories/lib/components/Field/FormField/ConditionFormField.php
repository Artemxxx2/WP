<?php
namespace SabaiApps\Directories\Component\Field\FormField;

use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Exception;

class ConditionFormField extends Form\Field\FieldsetField
{
    static protected $_fieldsLoaded = [], $_options = [], $_optionsAttr = [], $_fieldInfo = [], $_fieldForms = [];

    public function formFieldInit($name, array &$data, Form\Form $form)
    {
        if (empty($data['#conditions'])) throw new Exception\RuntimeException('No conditions specified.');

        if (!$form_fields = $this->_getFormFields($name, $data, $form)) return;

        $data = array(
            '#tree' => true,
            '#children' => array(
                0 => $form_fields,
            ),
            '#group' => true,
        ) + $data;

        if (!isset($form->settings['#pre_render'][__CLASS__])) {
            $form->settings['#pre_render'][__CLASS__] = array($this, 'preRenderCallback');
        }

        parent::formFieldInit($name, $data, $form);
    }

    public function formFieldSubmit(&$value, array &$data, Form\Form $form)
    {
        parent::formFieldSubmit($value, $data, $form);

        if (!strlen($value['field']) || !strlen($value['compare'])) {
            $value = null;
            return;
        }
        if (!in_array($value['compare'], ['filled', 'empty'])
            && (!isset($value['value']) || !strlen($value['value']))
        ) {
           $value = null;
           return;
        }
    }

    protected function _getFormFields($name, array $data, Form\Form $form)
    {
        $field_options = $field_options_attr = $field_tips = $field_example = [];

        foreach (array_keys($data['#conditions']) as $option_name) {
            $_info = $data['#conditions'][$option_name];
            $field_options[$option_name] = $_info['label'];
            if (!empty($_info['compare'])) {
                $field_options_attr[$option_name]['data-compare'] = $this->_application->JsonEncode($_info['compare']);
            }
            if (isset($_info['tip'])) {
                $field_options_attr[$option_name]['data-tip'] = $_info['tip'];
            }
            if (isset($_info['example'])) {
                $field_options_attr[$option_name]['data-example'] = $_info['example'];
            }
        }
        if (empty($field_options)) return;

        asort($field_options);
        $field_selected = empty($data['#default_value']['field']) ? null : $data['#default_value']['field'];
        $label_select = isset($data['#label_select']) ? $data['#label_select'] : __('Select field', 'directories');
        $form = array(
            'field' => array(
                '#type' => 'select',
                '#options' => ['' => '— ' . $label_select . ' —'] + $field_options,
                '#default_value' => $field_selected,
                '#attributes' => array('class' => 'drts-field-form-fieldcondition-field'),
                '#prefix' => '<div class="' . DRTS_BS_PREFIX . 'form-row"><div class="' . DRTS_BS_PREFIX . 'col-6 ' . DRTS_BS_PREFIX . 'col-sm-4">',
                '#suffix' => '</div>',
                '#options_attr' => ['' => ['data-compare' => '[]']] + $field_options_attr,
            ),
            'compare' => array(
                '#type' => 'select',
                '#options' => [
                    'value' => __('is', 'directories'),
                    '!value' => __('is not', 'directories'),
                    'one' => __('is one of', 'directories'),
                    '>value' => __('is greater than', 'directories'),
                    '<value' => __('is less than', 'directories'),
                    '<>value' => __('is between', 'directories'),
                    '^value' => __('starts with', 'directories'),
                    '$value' => __('ends with', 'directories'),
                    '*value' => __('contains', 'directories'),
                    'empty' => __('is empty', 'directories'),
                    'filled' => __('is not empty', 'directories'),
                ],
                '#default_value' => empty($data['#default_value']['compare']) ? null : $data['#default_value']['compare'],
                '#attributes' => array('class' => 'drts-field-form-fieldcondition-compare'),
                '#prefix' => '<div class="' . DRTS_BS_PREFIX . 'col-6 ' . DRTS_BS_PREFIX . 'col-sm-2">',
                '#suffix' => '</div>',
            ),
            'value' => array(
                '#type' => 'textfield',
                '#default_value' => isset($data['#default_value']['value']) ? $data['#default_value']['value'] : null,
                '#attributes' => array(
                    'class' => 'drts-field-form-fieldcondition-value',
                    'title' => isset($field_selected) && isset($field_tips[$field_selected]) ? $field_tips[$field_selected] : '',
                    'placeholder' => isset($field_selected) && isset($field_example[$field_selected]) ? $field_example[$field_selected] : '',
                    'data-trigger' => 'focus',
                ),
                '#prefix' => '<div class="' . DRTS_BS_PREFIX . 'col-sm-6">',
                '#suffix' => '</div></div>',
            ),
        );
        if (!isset($field_selected)) {
            $form['value']['#attributes']['disabled'] = 'disabled';
        }
        return $form;
    }

    public function preRenderCallback(Form\Form $form)
    {
        $form->settings['#js_ready'][] = sprintf(
            '(function() {
    var form = $("#%s"), field_callback, compare_callback;
    field_callback = function () {
        var $this = $(this), container, field, input, option, option_val, compare;
        container = $this.closest(".drts-form-type-field-condition");
        input = container.find(".drts-field-form-fieldcondition-value");
        option = $("option:selected", this);
        if (input.length) {
            input.attr("data-original-title", "").attr("placeholder", "").attr("title", "");
            if (option.attr("value") !== "") {
                if (option.data("example")) {
                    input.attr("placeholder", option.data("example"));
                }
                if (option.data("tip")) {
                    input.attr("title", option.data("tip"));
                }
                input.prop("disabled", false);
            } else {
                input.prop("disabled", true);
            }
            input.sabaiTooltip({container: container}).sabaiTooltip("_fixTitle");
        }
        compare = container.find(".drts-field-form-fieldcondition-compare");
        if (compare.length) {
            compare.find("option").each(function(){
                var $this = $(this);
                if (!option.data("compare")
                    || -1 !== $.inArray($this.attr("value"), option.data("compare")) // found
                ) {
                    $this.prop("disabled", false);
                } else {
                    $this.prop("disabled", true).prop("selected", false);
                }
            });
            if (option.data("compare")
                && compare.find("option:selected").length === 0
            ) {
                compare.val(option.data("compare")[0]);
            }
            compare.trigger("change.sabai");
        }
    };
    compare_callback = function () {
        var $this = $(this), container, input;
        container = $this.closest(".drts-form-type-field-condition");
        input = container.find(".drts-field-form-fieldcondition-value");
        input.css("display", $this.val() === "filled" || $this.val() === "empty" ? "none" : "block");
    };

    form.find(".drts-field-form-fieldcondition-field").each(function(){
        field_callback.call(this);
    });
    form.find(".drts-field-form-fieldcondition-compare").each(function(){
        compare_callback.call(this);
    });
    form.on("change.sabai", ".drts-field-form-fieldcondition-field", field_callback)
        .on("change.sabai", ".drts-field-form-fieldcondition-compare", compare_callback);
    $(DRTS).on("clonefield.sabai", function (e, data) {
        if (data.clone.hasClass("drts-form-type-field-condition")) {
            field_callback.call(data.clone.find(".drts-field-form-fieldcondition-field"));
        }
    });
})();',
            $form->settings['#id']
        );
    }
}
