<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

class TableSelectField extends AbstractField
{
    protected static $_elements = [];

    public function formFieldInit($name, array &$data, Form $form)
    {
        if (!isset($data['#id'])) {
            $data['#id'] = $form->getFieldId($name);
        }

        // Add header
        $i = 0;
        foreach ($data['#header'] as $header_name => $header_label) {
            if (!is_array($header_label)) {
                $i += 10;
                $data['#header'][$header_name] = array(
                    'label' => $header_label,
                    'order' => $i,
                );
            } else {
                if (!isset($header_label['order'])) {
                    $i += 10;
                    $data['#header'][$header_name]['order'] = $i;
                }
            }
            if (!isset($data['#header'][$header_name]['span'])) {
                $data['#header'][$header_name]['span'] = 1;
            }
        }
        uasort($data['#header'], function ($a, $b) { return $a['order'] < $b['order'] ? -1 : 1; });

        if (!isset($data['#options_disabled'])) {
            $data['#options_disabled'] = [];
        }

        if (!isset($data['#default_value'])) {
            $data['#default_value'] = [];
        }

        if (!isset(self::$_elements[$form->settings['#id']])) {
            self::$_elements[$form->settings['#id']] = [];
        }
        self::$_elements[$form->settings['#id']][$data['#id']] = $data['#id'];

        $form->settings['#pre_render'][__CLASS__] = array($this, 'preRenderCallback');
    }

    public function formFieldSubmit(&$value, array &$data, Form $form)
    {
        // Is it a required field?
        if (is_null($value)) {
            if ($form->isFieldRequired($data)) {
                $form->setError(__('Selection required.', 'directories'), $data);
            }

            return;
        }

        // No options
        if (empty($data['#options']) || !empty($data['#skip_validate_option'])) return;

        // Are all the selected options valid?
        foreach ((array)$value as $_value) {
            if (!isset($data['#options'][$_value])) {
                $form->setError(__('Invalid option selected.', 'directories'), $data);

                return;
            }
        }
    }

    public function formFieldRender(array &$data, Form $form)
    {
        $data['#id'] = $form->getFieldId($data['#name']);
        if (!empty($data['#sortable'])) {
            $form->settings['#js_ready'][] = sprintf(
                '$("#%s").find("tbody").sortable({
        containment: "parent",
        axis: "y",
        update: function (event, ui) {}
    });',
                $data['#id']
            );
        }

        $class = DRTS_BS_PREFIX . 'table ' . DRTS_BS_PREFIX . 'my-0';
        if (isset($data['#size'])
            && $data['#size'] === 'sm'
        ) {
            $class .= ' ' . DRTS_BS_PREFIX . 'table-sm';
        }
        $table = ['<table class="' . $class . '"' . $this->_application->Attr($data['#attributes']) . '>'];
        $headers = [];
        $cols = 0;
        if (empty($data['#disabled'])
            && (!isset($data['#js_select']) || $data['#js_select'])
        ) {
            if (!empty($data['#multiple'])) {
                $checked = empty($data['#default_value']) || array_diff($data['#default_value'], array_keys($data['#options'])) ? '' : ' checked="checked"';
                $headers[] = '<th class="drts-form-tableselect-cb"><div class="' . DRTS_BS_PREFIX . 'custom-control ' . DRTS_BS_PREFIX . 'custom-checkbox">'
                    . '<input id="' . $data['#id'] . '-cb" type="checkbox" class="' . DRTS_BS_PREFIX . 'custom-control-input drts-form-tableselect-cb-trigger"' . $checked . ' />'
                    . '<label class="' . DRTS_BS_PREFIX . 'custom-control-label" for="' . $data['#id'] . '-cb">&nbsp;</label>'
                    . '</div></th>';
            } else {
                $headers[] = '<th class="drts-form-tableselect-cb"></th>';
            }
            ++$cols;
        }
        foreach ($data['#header'] as $name => $label) {
            if (empty($label['span']) || !isset($label['label'])) continue;

            $_label = empty($label['no_escape']) ? $this->_application->H($label['label']) : $label['label'];
            if ($label['span'] === 1) {
                $headers[] = '<th class="drts-form-tableselect-' . $name . '">' . $_label . '</th>';
                ++$cols;
            } elseif ($label['span'] > 1) {
                $span = intval($label['span']);
                $headers[] = '<th class="drts-form-tableselect-' . $name . '" colspan="' . $span . '">' . $_label . '</th>';
                $cols += $span;
            }
        }
        if (!empty($headers)) {
            $table[] = '<thead><tr>';
            $table[] = implode(PHP_EOL, $headers);
            $table[] = '</tr></thead>';
        }
        $table[] = '<tbody>';
        if (!empty($data['#options'])) {
            if (empty($data['#multiple'])) {
                $type = 'radio';
                $name = $data['#name'];
            } else {
                $type = 'checkbox';
                $name = $data['#name'] . '[]';
            }
            foreach ($data['#options'] as $option_id => $option_items) {
                $attr = isset($data['#row_attributes'][$option_id]['@row']) ? $data['#row_attributes'][$option_id]['@row'] : [];
                $attr['data-row-id'] = $option_id;
                $table[] = '<tr' . $this->_application->Attr($attr) . '">';
                if (empty($data['#disabled'])) {
                    $attr = '';
                    if (in_array($option_id, $data['#default_value'])) {
                        $attr .= ' checked="checked"';
                    }
                    if (in_array($option_id, $data['#options_disabled'])) {
                        $attr .= ' disabled="disabled"';
                    }
                    $input = '<div class="' . DRTS_BS_PREFIX . 'custom-control ' . DRTS_BS_PREFIX . 'custom-' . $type . '">'
                        . '<input id="' . $data['#id'] . '-' . $option_id . '" name="' . $name . '" type="' . $type . '" class="' . DRTS_BS_PREFIX . 'custom-control-input drts-form-tableselect-cb-target" value="' . $this->_application->H($option_id) . '"' . $attr . ' />'
                        . '<label for="' . $data['#id'] . '-' . $option_id . '" class="' . DRTS_BS_PREFIX . 'custom-control-label">&nbsp;</label></div>';
                    $table[] = '<td>' . $input . '</td>';
                }
                foreach (array_keys($data['#header']) as $header_name) {
                    $attr = isset($data['#row_attributes'][$option_id][$header_name]) ? $data['#row_attributes'][$option_id][$header_name] : [];
                    if (isset($data['#row_attributes']['@all'][$header_name])) {
                        $attr += $data['#row_attributes']['@all'][$header_name];
                    }
                    $attr['data-column-name'] = $header_name;
                    $item = isset($option_items[$header_name]) ? $option_items[$header_name] : '';
                    $table[] = '<td' . $this->_application->Attr($attr) . '>' . $item . '</td>';
                }
                $table[] = '</tr>';
            }
        } else {
            $no_options_msg = isset($data['#no_options_msg']) ? $data['#no_options_msg'] : __('No entries found', 'directories');
            $table[] = '<tr><td colspan="' . (empty($cols) ? 1 : $cols) . '">' . $this->_application->H($no_options_msg) . '</td></tr>';
        }
        $table[] = '</tbody></table>';
        $table = implode(PHP_EOL, $table);
        if (!isset($data['#responsive']) || $data['#responsive']) {
            $table = '<div class="' . DRTS_BS_PREFIX . 'table-responsive-md">' . $table . '</div>';
        }
        $data['#description_top'] = true;

        $this->_render($table, $data, $form);
    }

    public function preRenderCallback(Form $form)
    {
        $this->_application->getPlatform()->loadJqueryUiJs(array('sortable'));

        $form->settings['#js_ready'][] = '$("#__FORM_ID__ table input.drts-form-tableselect-cb-trigger").click(function() {
    var $this = $(this);
    $this.closest("table")
        .find("input.drts-form-tableselect-cb-target, input.drts-form-tableselect-cb-trigger")
        .not(":disabled")
        .prop("checked", $this.prop("checked"));
});';
    }
}
