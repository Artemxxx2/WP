<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

class IconPickerField extends AbstractField
{
    protected static $_iconsets = [];

    public function formFieldInit($name, array &$data, Form $form)
    {
        $data['#id'] = $form->getFieldId($name);
        if (!isset($data['#iconset'])) {
            $data['#iconset'] = 'fontawesome';
        }
        self::$_iconsets[$data['#iconset']] = $data['#iconset'];
        $form->settings['#pre_render'][__CLASS__] = array($this, 'preRenderCallback');
    }

    public function formFieldRender(array &$data, Form $form)
    {
        $data['#attributes']['data-iconset'] = $data['#iconset'];
        if (isset($data['#placement'])) {
            $data['#attributes']['data-placement'] = $data['#placement'];
        }
        if (isset($data['#default_value'])) {
            $data['#attributes']['data-current'] = trim($data['#default_value']);
        }
        if (isset($data['#rows'])) {
            $data['#attributes']['data-rows'] = (int)$data['#rows'];
        }
        if (isset($data['#cols'])
            && in_array($data['#cols'], [1, 2, 3, 4, 6, 12])
        ) {
            $data['#attributes']['data-cols'] = $data['#rows'];
        }
        foreach (['rows', 'cols'] as $key) {
            if (isset($data['#' . $key])) {
                $data['#attributes']['data-' . $key] = (int)$data['#' . $key];
            }
        }
        $html = sprintf(
            '<button type="button" name="%1$s" class="%2$sbtn %2$sbtn-outline-secondary %3$s"%4$s />',
            $data['#name'],
            DRTS_BS_PREFIX,
            isset($data['#attributes']['class']) ? $this->_application->H($data['#attributes']['class']) : '',
            $this->_application->Attr($data['#attributes'], 'class')
        );

        $this->_render($html, $data, $form);
    }

    public function preRenderCallback($form)
    {
        $this->_application->Form_Scripts_iconpicker(self::$_iconsets);
        $form->settings['#js_ready'][] = sprintf(
            '$("#%s").find(".drts-form-type-iconpicker button").each(function(val) {
    DRTS.Form.field.iconpicker.factory($(this));
});',
            $form->settings['#id']
        );
    }
}