<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Form\Form;

abstract class AbstractField implements IField
{
    protected $_application;

    public function __construct(Application $application)
    {
        $this->_application = $application;
    }

    public function formFieldInit($name, array &$data, Form $form){}
    public function formFieldSubmit(&$value, array &$data, Form $form){}
    public function formFieldCleanup(array &$data, Form $form){}

    public function formFieldIsSubmittable(array $data, Form $form)
    {
        return !isset($data['#submit_for']) || $form->isClickedButton($data['#submit_for']);
    }

    protected function _render($html, array &$data, Form $form)
    {
        $error = $form->hasError($data['#name']) ? $form->getError($data['#name']) : null;
        $output_error = empty($data['#error_no_output']) && isset($error) && strlen($error) > 0;
        if (!empty($data['#description_top'])) {
            $description_top = $this->_getDescription($data, true);
            $description = '';
        } else {
            $description = !$output_error ? $this->_getDescription($data, false) : '';
            $description_top = '';
        }
        if (isset($data['#title']) && strlen($data['#title'])) {
            $title = empty($data['#title_no_escape']) ? $this->_application->H($data['#title']) : $data['#title'];
            if (!empty($data['#display_required'])) {
                $title = '<label>' . $title . $form->getRequiredFieldSuffix() . '</label>';
            } else {
                $title = '<label>' . $title . '</label>';
            }
        } else {
            $title = '';
        }
        if (empty($data['#horizontal'])) {
            $class = strlen($title) || !empty($data['#group']) ? '%1$sform-group ' : '';
            $format = '<div class="' . $class . '%2$s%3$s" style="%5$s" data-form-field-name="%6$s"%4$s%7$s>
    %8$s
    <div class="drts-form-field-main">
        %9$s
        %10$s
        <div class="%1$sform-text drts-form-error %1$stext-danger">%11$s</div>
        %12$s
    </div>
</div>';
        } else {
            $label_width = is_int($data['#horizontal']) && $data['#horizontal'] < 12 ? $data['#horizontal'] : 3;
            $format = '<div class="%1$sform-group %1$sform-row %2$s%3$s" style="%5$s" data-form-field-name="%6$s"%4$s%7$s>
        <div class="%1$scol-sm-' . $label_width . ' %1$scol-form-label %13$s">%8$s</div>
        <div class="%1$scol-sm-' . (12 - $label_width) . ' drts-form-field-main">
            %9$s
            %10$s
            <div class="%1$sform-text drts-form-error %1$stext-danger">%11$s</div>
            %12$s
        </div>
    </div>';
        }
        $data['#html'][] = sprintf(
            $format,
            DRTS_BS_PREFIX,
            $this->_application->H($data['#class']),
            isset($error) ? ' drts-form-has-error' : '',
            empty($data['#id']) ? '' : ' id="' . $this->_application->H($data['#id']) . '"',
            empty($data['#hidden']) ? '' : 'display:none;',
            $this->_application->H($data['#name']),
            empty($data['#data']) ? '' : $this->_application->Attr($data['#data'], null, 'data-'),
            $title,
            $description_top,
            $html,
            $output_error ? $this->_application->Htmlize($error, true) : '',
            $description,
            !isset($data['#horizontal_label_padding']) || $data['#horizontal_label_padding'] ? '' : DRTS_BS_PREFIX . 'pt-0'
        );
    }

    protected function _getDescription(array $data, $isTop = false)
    {
        if (!isset($data['#description']) || !strlen($data['#description'])) return '';

        $class = $isTop ? DRTS_BS_PREFIX . 'mt-0 ' . DRTS_BS_PREFIX . 'mb-3' : DRTS_BS_PREFIX . 'my-2';
        $description = empty($data['#description_no_escape']) ? $this->_application->Htmlize($data['#description'], true) : $data['#description'];

        return '<div class="' . DRTS_BS_PREFIX . 'form-text drts-form-description ' . $class . '">' . $description . '</div>';
    }

    protected function _getInput(array $data, $form, $type)
    {
        return sprintf(
            '<input name="%s" type="%s" value="%s" class="%s%s"%s />',
            $this->_application->H($data['#name']),
            $this->_application->H($type),
            isset($data['#default_value']) ? $this->_application->H($data['#default_value']) : '',
            $type !== 'hidden' ? DRTS_BS_PREFIX . 'form-control ' : '',
            isset($data['#attributes']['class']) ? $this->_application->H($data['#attributes']['class']) : '',
            $this->_application->Attr($data['#attributes'], 'class')
        );
    }
}
