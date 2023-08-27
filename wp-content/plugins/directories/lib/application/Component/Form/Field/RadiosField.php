<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

class RadiosField extends AbstractField
{
    protected $_type = 'radio';
    
    public function formFieldInit($name, array &$data, Form $form)
    {
        if (!isset($data['#options'])) {
            $data['#options'] = [];
        }
        if (isset($data['#default_value'])
            && !is_array($data['#default_value'])
            && !isset($data['#options'][$data['#default_value']])
        ) {
            $data['#default_value'] = null;
        }
        if (!isset($data['#default_value'])
            || (is_array($data['#default_value']) && empty($data['#default_value']))
        ) {
            if (isset($data['#empty_value'])) {
                $data['#default_value'] = $data['#empty_value'];
            } else {
                if (!empty($data['#options']) && !empty($data['#default_value_auto'])) {
                    $data['#default_value'] = current(array_keys($data['#options']));
                }
            }
        }
        if (!isset($data['#options_disabled'])) {
            $data['#options_disabled'] = [];
        }
    }

    public function formFieldSubmit(&$value, array &$data, Form $form)
    {
        // No options
        if (empty($data['#options'])) {
            $value = [];

            return;
        }
        
        // Is it a required field?
        if (is_null($value)) {
            if ($form->isFieldRequired($data)) {
                $form->setError(
                    isset($data['#required_error_message']) ? $data['#required_error_message'] : __('Selection required.', 'directories'),
                    $data
                );
            }
            $value = [];

            return;
        }
        
        if (!empty($data['#integer'])) $value = array_map('intval', (array)$value);
        
        if (!empty($data['#skip_validate_option'])) return;

        // Are all the selected options valid?
        $options_valid = isset($data['#options_valid']) ? $data['#options_valid'] : array_keys($data['#options']);
        foreach ((array)$value as $_value) {
            if (!in_array($_value, $options_valid)) {
                $form->setError(__('Invalid option selected.', 'directories'), $data);

                return;
            }
        }
    }

    public function formFieldRender(array &$data, Form $form)
    {
        if (empty($data['#options'])) return;
        
        $values = isset($data['#default_value']) ? (array)$data['#default_value'] : [];
        $html = [];
        if (isset($data['#field_prefix'])) {
            $html[] = $data['#field_prefix'];
        }
        $html[] = $this->_getHtml($data, $form, $values);
        if (isset($data['#field_suffix'])) {
            $html[] = $data['#field_suffix'];
        }
        
        $this->_render(implode(PHP_EOL, $html), $data, $form);
    }

    protected function _getHtml(array &$data, Form $form, array $values)
    {
        $data['#id'] = $form->getFieldId($data['#name']);
        $data['#horizontal_label_padding'] = false;
        $name = $this->_application->H($data['#name']);
        if ($this->_type == 'checkbox') {
            $name .= '[]';
        }
        $columns = empty($data['#columns']) ? 1 : $data['#columns'];
        $class = 'drts-form-field-radio-options';
        if (empty($data['#options_visible_count'])
            || $columns > 1
        ) {
            $data['#options_visible_count'] = count($data['#options']);
            if (!empty($data['#options_scroll'])) {
                $class .= ' drts-form-field-radio-options-scroll';
            }
        } else {
            if (!empty($values)) {
                $option_keys = array_keys($data['#options']);
                foreach ($values as $value) {
                    if (($pos = array_keys($option_keys, $value))
                        && $pos[0] + 1 > $data['#options_visible_count']
                    ) {
                        $data['#options_visible_count'] = $pos[0] + 1;
                    }
                }
            }
        }
        $class .= ' drts-form-field-radio-options-column ' . DRTS_BS_PREFIX . 'custom-controls-stacked';
        $html[] = '<div class="' . DRTS_BS_PREFIX . 'form-row" id="' . $data['#id'] . '-options">';
        foreach ($this->_application->SliceArray($data['#options'], $columns) as $options) {
            $html[] = '<div class="' . DRTS_BS_PREFIX . 'col-sm-' . intval(12 / $columns) . '"><div class="' . $class . '">';
            $i = 0;
            $has_hidden = false;
            foreach ($options as $option_value => $option_label) {
                if (!$has_hidden
                    && ($has_hidden = $i >= $data['#options_visible_count'])
                ) {
                    $html[] = '<div class="' . DRTS_BS_PREFIX . 'collapse" id="' . $data['#id'] . '-options-hidden">'
                        . '<div class="drts-form-field-radio-options ' . DRTS_BS_PREFIX . 'custom-controls-stacked">';
                }
                $html[] = $this->_doRenderOption($data, $form, $name, $values, $option_value, $option_label);
                ++$i;
            }
            if ($has_hidden) {
                $html[] = '</div></div>' . $this->_getMoreLessLink($data['#id'] . '-options-hidden');
            }
            $html[] = '</div></div>';
        }
        $html[] = '</div>';

        if ($this->_type == 'checkbox'
            && !empty($data['#options_hidden'])
        ) {
            foreach ($data['#options_hidden'] as $option_value) {
                $attr = isset($data['#attributes'][$option_value]) ? $data['#attributes'][$option_value] : [];
                if (!empty($data['#options_disabled']) && in_array($option_value, $data['#options_disabled'])) {
                    $attr['disabled'] = 'disabled';
                }
                $html[] = sprintf(
                    '<input type="hidden" name="%s" value="%s"%s />',
                    $name,
                    $this->_application->H($option_value),
                    empty($attr) ? '' : $this->_application->Attr($attr)
                );
            }
        }

        return implode(PHP_EOL, $html);
    }
    
    protected function _getMoreLessLink($targetId)
    {
        return sprintf(
            '<a style="cursor:pointer;" data-toggle="%2$scollapse" data-target="#%1$s" class="%2$scollapsed drts-form-field-radio-options-toggle">'
                . '<span class="drts-form-field-radio-options-expand">%3$s</span>'
                . '<span class="drts-form-field-radio-options-collapse">%4$s</span>'
                . '</a>',
            $targetId,
            DRTS_BS_PREFIX,
            $this->_application->H(__('more', 'directories')),
            $this->_application->H(__('less', 'directories'))
        );
    }
    
    protected function _doRenderOption(array $data, Form $form, $name, $values, $value, $label)
    {
        $attr = isset($data['#attributes'][$value]) ? $data['#attributes'][$value] : [];
        if ($checked = in_array($value, $values)) {
            $attr['checked'] = 'checked';
        }
        $disabled = false;
        if (!empty($data['#disabled'])
            || in_array($value, $data['#options_disabled'])
            || (!empty($data['#leaf_only']) && is_array($label) && empty($label['#is_leaf']))
        ) {
            $attr['disabled'] = 'disabled';
            $disabled = true;
        }
        $description = isset($data['#options_description'][$value]) ? $data['#options_description'][$value] : null;
        $depth = $prefix = null;
        if (is_array($label)) {
            $depth = !empty($label['#depth']) ? $label['#depth'] : 0;
            if (isset($label['#title_prefix'])) {
                $prefix = $label['#title_prefix'];
            }
            if (!empty($label['#attributes'])) {
                $attr += $label['#attributes'];
            }
            if (isset($label['#count'])) {
                if (empty($data['#option_no_escape'])) {
                    $_label = $this->_application->H($label['#title']);
                    $data['#option_no_escape'] = true;
                } else {
                    $_label = $label['#title'];
                }
                $label = $_label . ' <span>(' . $label['#count'] . ')</span>';
            } else {
                $label = $label['#title'];
            }
        }
        if (empty($data['#option_no_escape'])) {
            $label = $this->_application->H($label);
        }
        $format = $this->_getOptionFormat($data, $checked);

        return $this->_renderOption($format, $form, $name, $value, $label, $description, $attr, $depth, $disabled, $data['#id'], $prefix);
    }
    
    protected function _getOptionFormat(array $data, $checked = false)
    {
        $is_rtl = $this->_application->getPlatform()->isRtl();
        return '<div class="drts-form-field-radio-option %9$scustom-control %9$scustom-%1$s %9$smy-1%8$s" data-depth="%7$d" data-value="%3$s" style="margin-' . ($is_rtl ? 'right' : 'left') . ':%7$drem;">'
            . '<input class="%9$scustom-control-input" type="%1$s" id="%11$s-%3$s" name="%2$s" value="%3$s"%4$s />'
            . '<label class="%9$scustom-control-label" for="%11$s-%3$s">%10$s%5$s</label>'
            . '</div>'
            . '%6$s';
    }
        
    protected function _renderOption($format, Form $form, $name, $value, $label, $description, array $attr, $depth, $disabled, $id, $prefix = null)
    {
        return sprintf(
            $format,
            $this->_type,
            $name,
            $this->_application->H($value),
            $this->_application->Attr($attr),
            $label,
            isset($description) ? '<div class="drts-form-field-radio-option-description">' . $this->_application->Htmlize($description, true) . '</div>' : '',
            $depth,
            $disabled ? ' ' . DRTS_BS_PREFIX . 'disabled': '',
            DRTS_BS_PREFIX,
            !isset($prefix) || empty($depth) ? '' : $this->_getOptionPrefix($prefix, $depth),
            $id
        );
    }
    
    protected function _getOptionPrefix($prefix, $depth)
    {
        return str_repeat($prefix, $depth) . ' ';
    }
}
