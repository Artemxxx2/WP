<?php
namespace SabaiApps\Directories\Component\Field\FormField;

use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Component\Field\Type\IQueryable;
use SabaiApps\Directories\Exception;

class QueryFormField extends Form\Field\FieldsetField
{
    static protected $_options = [], $_queryInfo = [];
    
    public function formFieldInit($name, array &$data, Form\Form $form)
    {
        if (empty($data['#fields'])) throw new Exception\RuntimeException('No fields specified.');
        
        if (!$form_fields = $this->_getFormFields($data, $form)) return;
        
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

        if (!strlen($value['field']) || !strlen($value['query'])) {
            $value = null;
        }
    }
    
    protected function _getFormFields(array $data, Form\Form $form)
    {
        $field_options = array('' => '— ' . __('Select field', 'directories') . ' —');
        foreach ($data['#fields'] as $field) {
            $field_name = $field->getFieldName();
            if (!isset(self::$_options[$field_name])) {
                if ((!$field_type = $this->_application->Field_Type($field->getFieldType(), true))
                    || !$field_type instanceof IQueryable
                ) continue;
            
                self::$_options[$field_name] = $field->getFieldLabel() . ' - ' . $field_name;
                self::$_queryInfo[$field_name] = $field_type->fieldQueryableInfo($field, !empty($data['#query_in_admin']));
            }
            $field_options[$field_name] = self::$_options[$field_name];
        }
        if (empty($field_options)) return;
            
        $field_name = empty($data['#default_value']['field']) ? null : $data['#default_value']['field'];
        $form = array(
            'field' => array(
                '#type' => 'select',
                '#options' => $field_options,
                '#default_value' => $field_name,
                '#attributes' => array('class' => 'drts-field-form-fieldquery-field'),
                '#prefix' => '<div class="' . DRTS_BS_PREFIX . 'form-row"><div class="' . DRTS_BS_PREFIX . 'col-6 ' . DRTS_BS_PREFIX . 'col-sm-4">',
                '#suffix' => '</div>',
            ),
            'query' => array(
                '#type' => 'textfield',
                '#default_value' => @$data['#default_value']['query'],
                '#attributes' => array(
                    'class' => 'drts-field-form-fieldquery-query',
                    'title' => isset($field_name) && isset(self::$_queryInfo[$field_name]) ? self::$_queryInfo[$field_name]['tip'] : '',
                    'placeholder' => isset($field_name) && isset(self::$_queryInfo[$field_name]) ? self::$_queryInfo[$field_name]['example'] : '',
                    'data-trigger' => 'focus',
                ),
                '#prefix' => '<div class="' . DRTS_BS_PREFIX . 'col-6 ' . DRTS_BS_PREFIX . 'col-sm-8">',
                '#suffix' => '</div></div>',
            ),
        );
        if (!isset($field_name)) {
            $form['query']['#attributes']['disabled'] = 'disabled';
        }
        return $form;
    }
    
    public function preRenderCallback(Form\Form $form)
    {        
        $form->settings['#js_ready'][] = sprintf(
            '(function() {
    var form = $("#%s"), info = %s, callback;
    callback = function(e){
        var $this = $(this), container, field, input;
        container = $this.closest(".drts-form-type-field-query");
        input = container.find(".drts-field-form-fieldquery-query");
        if (input.length) {
            field = $this.val();
            if (field !== "") {
                if (info[field]) {
                    input.attr("placeholder", info[field].example).attr("title", info[field].tip);
                } else {
                    input.attr("placeholder", "").attr("title", "").attr("data-original-title", "");
                }
                input.prop("disabled", false);
            } else {
                input.attr("placeholder", "").attr("title", "").attr("data-original-title", "").prop("disabled", true);
            }
            input.sabaiTooltip({container: container}).sabaiTooltip("_fixTitle");
        }
    };
    form.on("change", ".drts-form-type-field-query select", callback);
    $(DRTS).on("clonefield.sabai", function (e, data) {
        if (data.clone.hasClass("drts-form-type-field-query")) {
            callback.call(data.clone.find(".drts-field-form-fieldquery-query"));
        }
    });
})();',
            $form->settings['#id'],
            $this->_application->JsonEncode(self::$_queryInfo)
        );
    }
}