<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

class HiddenField extends AbstractField
{
    public function formFieldRender(array &$data, Form $form)
    {
        if (isset($data['#attributes']['class'])) {
            $data['#attributes']['class'] .= ' ' . $data['#class'];
        } else {
            $data['#attributes']['class'] = $data['#class'];
        }

        $name = $this->_application->H($data['#name']);
        if (!empty($data['#multiple'])) $name .= '[]';
        $html = [];
        if (isset($data['#default_value'])) {
            $values = (array)$data['#default_value'];
            foreach ($values as $value) {
                $is_first = !isset($is_first);
                $html[] = $this->_getHtml($data, $name, $is_first && isset($data['#id']) ? $data['#id'] : null, $value);
            }
        } else {
            $html[] = $this->_getHtml($data, $name, isset($data['#id']) ? $data['#id'] : null);
        }
        $html = implode(PHP_EOL, $html);
        if (!empty($data['#render_hidden_inline'])) {
            $data['#html'][] = $html;
        } else {
            // Moves to bottom of the form
            $form->settings['#rendered_hiddens'][] = $html;
        }
    }

    protected function _getHtml(array $data, $name, $id = null, $value = null)
    {
        $data_attr = null;
        if (!empty($data['#data'])) {
            if (isset($data['#data'][$value])) {
                $data_attr = $data['#data'][$value];
            } else {
                $data_attr = $data['#data'];
            }
        }
        return sprintf(
            '<input type="hidden" name="%s" value="%s"%s%s%s>',
            $name,
            isset($value) ? $this->_application->H($value) : '',
            empty($id) ? '' : ' id="' . $this->_application->H($id) . '"',
            empty($data['#attributes']) ? '' : $this->_application->Attr($data['#attributes']),
            empty($data_attr) ? '' : $this->_application->Attr($data_attr, null, 'data-')
        );
    }
}