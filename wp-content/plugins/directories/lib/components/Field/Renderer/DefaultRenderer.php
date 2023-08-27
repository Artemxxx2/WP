<?php
namespace SabaiApps\Directories\Component\Field\Renderer;

use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Type\ILabellable;

class DefaultRenderer extends AbstractRenderer
{
    protected function _fieldRendererInfo()
    {
        $info = [
            'field_types' => array($this->_name),
            'default_settings' => [],
            'inlineable' => true,
        ];
        switch ($this->_name) {
            case 'boolean':
                $info['default_settings'] = array(
                    'on_label' => __('Yes', 'directories'),
                    'off_label' => __('No', 'directories'),
                );
                break;
            case 'email':
                $info['field_types'][] = 'user_email';
                $info['default_settings'] = [
                    'type' => 'default',
                    'label' => null,
                    'field' => null,
                    'button' => false,
                    'target' => '_self',
                    '_separator' => ', ',
                ];
                break;
            case 'choice':
                $info['default_settings'] = array(
                    'sort' => false,
                    '_separator' => ', ',
                );
                break;
            case 'user':
                $info['default_settings'] = array(
                    'format' => 'thumb_s_l',
                    '_separator' => ' ',
                );
                break;
            case 'number':
                $info['default_settings'] = array(
                    'dec_point' => '.',
                    'thousands_sep' => ',',
                    'trim_zeros' => false,
                    '_separator' => ' ',
                );
                break;
            case 'range':
                $info['default_settings'] = array(
                    'dec_point' => '.',
                    'thousands_sep' => ',',
                    'range_sep' => ' - ',
                    'trim_zeros' => false,
                    '_separator' => ' ',
                );
                break;
            case 'url':
                $info['field_types'][] = 'user_url';
                $info['default_settings'] = array(
                    'type' => 'default',
                    'label' => null,
                    'field' => null,
                    'button' => false,
                    'target' => '_blank',
                    'rel' => array('nofollow', 'external'),
                    '_separator' => ', ',
                );
                break;
            case 'phone':
                $info['default_settings'] = array(
                    'type' => 'default',
                    'label' => null,
                    'field' => null,
                    'button' => false,
                    '_separator' => ', ',
                );
                break;
            case 'time':
                $info['default_settings'] = array(
                    'daytime_sep' => ' ',
                    'time_sep' => ' - ',
                    '_separator' => ', ',
                );
                break;
            default:
                $info['default_settings'] = array(
                    '_separator' => ', ',
                );
                break;
        }
        return $info;
    }

    protected function _fieldRendererSettingsForm(IField $field, array $settings, array $parents = [])
    {
        switch ($this->_name) {
            case 'boolean':
                return array(
                    'on_label' => array(
                        '#type' => 'textfield',
                        '#size' => 10,
                        '#title' => __('ON label', 'directories'),
                        '#default_value' => $settings['on_label'],
                    ),
                    'off_label' => array(
                        '#type' => 'textfield',
                        '#size' => 10,
                        '#title' => __('OFF label', 'directories'),
                        '#default_value' => $settings['off_label'],
                    ),
                );
            case 'email':
                $field_name_prefix = $this->_application->Form_FieldName($parents);
                $form = array(
                    'type' => array(
                        '#title' => __('Display format', 'directories'),
                        '#type' => 'select',
                        '#options' => $this->_getEmailDisplayFormatOptions(),
                        '#default_value' => $settings['type'],
                    ),
                    'label' => array(
                        '#placeholder' => __('Custom label', 'directories'),
                        '#type' => 'textfield',
                        '#default_value' => $settings['label'],
                        '#states' => array(
                            'visible' => array(
                                sprintf('select[name="%s[type]"]', $field_name_prefix) => array('value' => 'label'),
                            ),
                        ),
                    ),
                    'field' => [
                        '#type' => 'select',
                        '#default_value' => $settings['field'],
                        '#states' => [
                            'visible' => [
                                sprintf('select[name="%s[type]"]', $field_name_prefix) => ['value' => 'field'],
                            ],
                        ],
                    ],
                    'button' => [
                        '#title' => __('Show as button', 'directories'),
                        '#type' => 'checkbox',
                        '#default_value' => !empty($settings['button']),
                        '#states' => [
                            'visible' => [
                                sprintf('select[name="%s[type]"]', $field_name_prefix) => ['type' => 'one', 'value' => ['default', 'field', 'label']],
                            ],
                        ],
                    ],
                    'button_color' => [
                        '#type' => 'radios',
                        '#title' => __('Button color', 'directories'),
                        '#default_value' => isset($settings['button_color']) ? $settings['button_color'] : null,
                        '#options' => $this->_application->System_Util_colorOptions(true, false),
                        '#option_no_escape' => true,
                        '#columns' => 6,
                        '#states' => [
                            'visible' => [
                                sprintf('[name="%s[button]"]', $field_name_prefix) => ['type' => 'checked', 'value' => true],
                                sprintf('select[name="%s[type]"]', $field_name_prefix) => ['type' => 'one', 'value' => ['default', 'field', 'label']],
                            ],
                        ],
                    ],
                    'target' => array(
                        '#title' => __('Open link in', 'directories'),
                        '#type' => 'select',
                        '#options' => $this->_getLinkTargetOptions(),
                        '#default_value' => $settings['target'],
                        '#states' => array(
                            'invisible' => array(
                                sprintf('select[name="%s[type]"]', $field_name_prefix) => array('value' => 'nolink'),
                            ),
                        ),
                    ),
                );
                $this->_setLabellableFieldOptions($form, $field);
                return $form;
            case 'url':
                $field_name_prefix = $this->_application->Form_FieldName($parents);
                $form = array(
                    'type' => array(
                        '#title' => __('Display format', 'directories'),
                        '#type' => 'select',
                        '#options' => $this->_getUrlDisplayFormatOptions(),
                        '#default_value' => $settings['type'],
                    ),
                    'label' => array(
                        '#placeholder' => __('Custom label', 'directories'),
                        '#type' => 'textfield',
                        '#default_value' => $settings['label'],
                        '#states' => array(
                            'visible' => array(
                                sprintf('select[name="%s[type]"]', $field_name_prefix) => array('value' => 'label'),
                            ),
                        ),
                    ),
                    'field' => [
                        '#type' => 'select',
                        '#default_value' => $settings['field'],
                        '#states' => [
                            'visible' => [
                                sprintf('select[name="%s[type]"]', $field_name_prefix) => ['value' => 'field'],
                            ],
                        ],
                    ],
                    'remove_protocol' => array(
                        '#title' => __('Remove protocol', 'directories'),
                        '#type' => 'checkbox',
                        '#default_value' => !empty($settings['remove_protocol']),
                        '#states' => array(
                            'visible' => array(
                                sprintf('select[name="%s[type]"]', $field_name_prefix) => array('type' => 'one', 'value' => ['default', 'nolink']),
                            ),
                        ),
                    ),
                    'button' => [
                        '#title' => __('Show as button', 'directories'),
                        '#type' => 'checkbox',
                        '#default_value' => !empty($settings['button']),
                        '#states' => [
                            'visible' => [
                                sprintf('select[name="%s[type]"]', $field_name_prefix) => ['type' => 'one', 'value' => ['default', 'field', 'label']],
                            ],
                        ],
                    ],
                    'button_color' => [
                        '#type' => 'radios',
                        '#title' => __('Button color', 'directories'),
                        '#default_value' => isset($settings['button_color']) ? $settings['button_color'] : null,
                        '#options' => $this->_application->System_Util_colorOptions(true, false),
                        '#option_no_escape' => true,
                        '#columns' => 6,
                        '#states' => [
                            'visible' => [
                                sprintf('[name="%s[button]"]', $field_name_prefix) => ['type' => 'checked', 'value' => true],
                                sprintf('select[name="%s[type]"]', $field_name_prefix) => ['type' => 'one', 'value' => ['default', 'field', 'label']],
                            ],
                        ],
                    ],
                    'max_len' => array(
                        '#title' => __('Max URL display length', 'directories'),
                        '#type' => 'slider',
                        '#min_text' => __('Unlimited', 'directories'),
                        '#default_value' => $settings['max_len'],
                        '#states' => array(
                            'visible' => array(
                                sprintf('select[name="%s[type]"]', $field_name_prefix) => array('type' => 'one', 'value' => ['default', 'nolink']),
                            ),
                        ),
                    ),
                    'target' => array(
                        '#title' => __('Open link in', 'directories'),
                        '#type' => 'select',
                        '#options' => $this->_getLinkTargetOptions(),
                        '#default_value' => $settings['target'],
                        '#states' => array(
                            'invisible' => array(
                                sprintf('select[name="%s[type]"]', $field_name_prefix) => array('value' => 'nolink'),
                            ),
                        ),
                    ),
                    'rel' => array(
                        '#title' => __('Link "rel" attribute', 'directories'),
                        '#type' => 'checkboxes',
                        '#options' => $this->_getLinkRelAttrOptions(),
                        '#default_value' => $settings['rel'],
                        '#states' => array(
                            'invisible' => array(
                                sprintf('select[name="%s[type]"]', $field_name_prefix) => array('value' => 'nolink'),
                            ),
                        ),
                    ),
                );
                $this->_setLabellableFieldOptions($form, $field);
                return $form;
            case 'choice':
                return [
                    'sort' => [
                        '#title' => __('Sort by label', 'directories'),
                        '#type' => 'checkbox',
                        '#default_value' => !empty($settings['sort']),
                    ],
                ];
            case 'user':
                return array(
                    'format' => array(
                        '#title' => __('Display format', 'directories'),
                        '#type' => 'select',
                        '#options' => $this->_application->UserIdentityHtml(),
                        '#default_value' => $settings['format'],
                    ),
                );
            case 'number':
                return array(
                    'dec_point' => array(
                        '#type' => 'textfield',
                        '#size' => 3,
                        '#title' => __('Decimal point', 'directories'),
                        '#default_value' => $settings['dec_point'],
                    ),
                    'thousands_sep' => array(
                        '#type' => 'textfield',
                        '#size' => 3,
                        '#title' => __('Thousands separator', 'directories'),
                        '#default_value' => $settings['thousands_sep'],
                    ),
                    'trim_zeros' => [
                        '#type' => 'checkbox',
                        '#title' => __('Remove trailing zeros', 'directories'),
                        '#default_value' => $settings['trim_zeros'],
                    ],
                );
            case 'phone':
                $field_name_prefix = $this->_application->Form_FieldName($parents);
                $form = array(
                    'type' => array(
                        '#title' => __('Display format', 'directories'),
                        '#type' => 'select',
                        '#options' => $this->_getPhoneDisplayFormatOptions(),
                        '#default_value' => $settings['type'],
                    ),
                    'label' => array(
                        '#placeholder' => __('Custom label', 'directories'),
                        '#type' => 'textfield',
                        '#default_value' => $settings['label'],
                        '#states' => array(
                            'visible' => array(
                                sprintf('select[name="%s[type]"]', $field_name_prefix) => array('value' => 'label'),
                            ),
                        ),
                    ),
                    'field' => [
                        '#type' => 'select',
                        '#default_value' => $settings['field'],
                        '#states' => [
                            'visible' => [
                                sprintf('select[name="%s[type]"]', $field_name_prefix) => ['value' => 'field'],
                            ],
                        ],
                    ],
                    'button' => [
                        '#title' => __('Show as button', 'directories'),
                        '#type' => 'checkbox',
                        '#default_value' => !empty($settings['button']),
                        '#states' => [
                            'visible' => [
                                sprintf('select[name="%s[type]"]', $field_name_prefix) => ['type' => 'one', 'value' => ['default', 'field', 'label']],
                            ],
                        ],
                    ],
                    'button_color' => [
                        '#type' => 'radios',
                        '#title' => __('Button color', 'directories'),
                        '#default_value' => isset($settings['button_color']) ? $settings['button_color'] : 'primary',
                        '#options' => $this->_application->System_Util_colorOptions(true, false),
                        '#option_no_escape' => true,
                        '#columns' => 6,
                        '#states' => [
                            'visible' => [
                                sprintf('[name="%s[button]"]', $field_name_prefix) => ['type' => 'checked', 'value' => true],
                                sprintf('select[name="%s[type]"]', $field_name_prefix) => ['type' => 'one', 'value' => ['default', 'field', 'label']],
                            ],
                        ],
                    ],
                );
                $this->_setLabellableFieldOptions($form, $field);
                return $form;
            case 'range':
                return array(
                    'dec_point' => array(
                        '#type' => 'textfield',
                        '#size' => 3,
                        '#title' => __('Decimal point', 'directories'),
                        '#default_value' => $settings['dec_point'],
                    ),
                    'thousands_sep' => array(
                        '#type' => 'textfield',
                        '#size' => 3,
                        '#title' => __('Thousands separator', 'directories'),
                        '#default_value' => $settings['thousands_sep'],
                    ),
                    'range_sep' => array(
                        '#type' => 'textfield',
                        '#title' => __('Range separator', 'directories'),
                        '#default_value' => $settings['range_sep'],
                        '#no_trim' => true,
                        '#size' => 10,
                    ),
                    'trim_zeros' => [
                        '#type' => 'checkbox',
                        '#title' => __('Remove trailing zeros', 'directories'),
                        '#default_value' => $settings['trim_zeros'],
                    ],
                );
            default:
                return [];
        }
    }

    protected function _setLabellableFieldOptions(array &$form, IField $field)
    {
        $fields = $this->_application->Entity_Field_options(
            $field->getBundleName(),
            ['interface' => 'Field\Type\ILabellable', 'return_disabled' => true, 'exclude' => [$field->getFieldName()]]
        );
        if (!empty($fields[0])
            || !empty($fields[1])
        ) {
            $form['field']['#options'] = [] + $fields[0];
            $form['field']['#options_disabled'] = array_keys($fields[1]);
        } else {
            $form['type']['#options_disabled'][] = 'field';
            unset($form['field']);
        }
    }

    protected function _fieldRendererRenderField(IField $field, array &$settings, IEntity $entity, array $values, $more = 0)
    {
        $type = $field->getFieldType();
        return $this->$type($field, $settings, $values, $entity);
    }

    protected function string(IField $field, array $settings, array $values, IEntity $entity)
    {
        $ret = [];
        $field_settings = $field->getFieldSettings();
        if ($field_settings['char_validation'] === 'email') {
            $values = array_map('antispambot', $values);
        }
        $prefix = isset($field_settings['prefix']) && strlen($field_settings['prefix'])
            ? $this->_application->System_TranslateString($field_settings['prefix'], $field->getFieldName() . '_field_prefix', 'entity_field')
            : '';
        $suffix = isset($field_settings['suffix']) && strlen($field_settings['suffix'])
            ? $this->_application->System_TranslateString($field_settings['suffix'], $field->getFieldName() . '_field_suffix', 'entity_field')
            : '';
        foreach ($values as $value) {
            $ret[] = $prefix . $this->_application->H($value) . $suffix;
        }
        return implode($settings['_separator'], $ret);
    }

    protected function user_email(IField $field, array $settings, array $values, IEntity $entity)
    {
        return $this->email($field, $settings, $values, $entity);
    }

    protected function email(IField $field, array $settings, array $values, IEntity $entity)
    {
        $ret = [];
        if ($settings['type'] === 'nolink') {
            foreach ($values as $value) {
                $ret[] = antispambot($value);
            }
        } else {
            $attr = [];
            if ($settings['target'] === '_blank') {
                $attr['target'] = '_blank';
                $attr['rel'] = 'noopener noreferrer';
            }
            if (!empty($settings['button'])) {
                $classes = DRTS_BS_PREFIX . 'btn ' . DRTS_BS_PREFIX . 'btn-sm ' . DRTS_BS_PREFIX . 'btn-' . (is_string($settings['button_color']) ? $settings['button_color'] : 'primary');
                if (!isset($attr['class'])) {
                    $attr['class'] = $classes;
                } else {
                    $attr['class'] = ' ' . $classes;
                }
            }
            foreach ($values as $value) {
                $email = antispambot($value);
                $attr['href'] = 'mailto:' . $email;
                $attr = $this->_application->Filter('field_render_email_attr', $attr, [$field, $value, $entity]);
                if ($settings['type'] === 'label') {
                    $label = $this->_application->System_TranslateString($settings['label'], 'renderer_email_custom_label', 'field');
                } elseif ($settings['type'] === 'field'
                    && !empty($settings['field'])
                    && ('' !== $label = $this->_getFieldLabel($entity, $settings['field']))
                ) {

                } else {
                    $label = $email;
                }
                $ret[] = sprintf(
                    '<a%s>%s</a>',
                    $this->_application->Attr($attr),
                    $this->_application->H($label)
                );
            }
        }

        return implode($settings['_separator'], $ret);
    }

    protected function user_url(IField $field, array $settings, array $values, IEntity $entity)
    {
        return $this->url($field, $settings, $values, $entity);
    }

    protected function url(IField $field, array $settings, array $values, IEntity $entity)
    {
        $ret = [];
        if ($settings['type'] === 'nolink') {
            foreach ($values as $value) {
                $label = $value;
                if (!empty($settings['remove_protocol'])) {
                    $label = preg_replace('#^https?://#', '', $label);
                }
                if (!empty($settings['max_len'])) {
                    $label = $this->_application->System_MB_strimwidth($label, 0, $settings['max_len'], '...');
                }
                $ret[] = $this->_application->H($label);
            }
        } else {
            $attr = [];
            if ($settings['target'] === '_blank') {
                $attr['target'] = '_blank';
                $settings['rel'][] = 'noopener noreferrer';
            }
            $attr['rel'] = implode(' ', $settings['rel']);
            if (!empty($settings['button'])) {
                $classes = DRTS_BS_PREFIX . 'btn ' . DRTS_BS_PREFIX . 'btn-sm ' . DRTS_BS_PREFIX . 'btn-' . $settings['button_color'];
                if (!isset($attr['class'])) {
                    $attr['class'] = $classes;
                } else {
                    $attr['class'] = ' ' . $classes;
                }
            }
            foreach ($values as $value) {
                $attr['href'] = $value;
                $attr = $this->_application->Filter('field_render_url_attr', $attr, [$field, $value, $entity]);
                if ($settings['type'] === 'label') {
                    $label = $this->_application->System_TranslateString($settings['label'], 'renderer_url_custom_label', 'field');
                } elseif ($settings['type'] === 'field'
                    && !empty($settings['field'])
                    && ('' !== $label = $this->_getFieldLabel($entity, $settings['field']))
                ) {

                } else {
                    $label = $value;
                    if (!empty($settings['remove_protocol'])) {
                        $label = preg_replace('#^https?://#', '', $label);
                    }
                    if (!empty($settings['max_len'])) {
                        $label = $this->_application->System_MB_strimwidth($label, 0, $settings['max_len'], '...');
                    }
                }
                $ret[] = sprintf(
                    '<a%s>%s</a>',
                    $this->_application->Attr($attr),
                    $this->_application->H($label)
                );
            }
        }
        return implode($settings['_separator'], $ret);
    }

    protected function _getFieldLabel(IEntity $entity, $fieldName)
    {
        if (!$field = $this->_application->Entity_Field($entity, $fieldName)) return '';

        if (($field_type = $this->_application->Field_Type($field->getFieldType(), true))
            && $field_type instanceof ILabellable
            && ($labels = $field_type->fieldLabellableLabels($field, $entity))
            && isset($labels[0])
            && strlen($labels[0])
        ) return $labels[0];

        return (string)$field->getFieldDefaultValue();
    }

    protected function phone(IField $field, array $settings, array $values, IEntity $entity)
    {
        $ret = [];
        if ($settings['type'] === 'nolink') {
            foreach ($values as $value) {
                $ret[] = antispambot(preg_replace('/[^0-9\+]/','', $value));
            }
        } else {
            $attr = [];
            if (!empty($settings['button'])) {
                $classes = DRTS_BS_PREFIX . 'btn ' . DRTS_BS_PREFIX . 'btn-sm ' . DRTS_BS_PREFIX . 'btn-' . $settings['button_color'];
                if (!isset($attr['class'])) {
                    $attr['class'] = $classes;
                } else {
                    $attr['class'] = ' ' . $classes;
                }
            }
            foreach ($values as $value) {
                if ($settings['type'] === 'label') {
                    $label = $this->_application->System_TranslateString($settings['label'], 'renderer_phone_custom_label', 'field');
                } elseif ($settings['type'] === 'field'
                    && !empty($settings['field'])
                    && ('' !== $label = $this->_getFieldLabel($entity, $settings['field']))
                ) {

                } else {
                    $label = $value;
                }
                $ret[] = sprintf(
                    '<a data-phone-number="%1$s" href="tel:%1$s"%2$s>%3$s</a>',
                    antispambot(preg_replace('/[^0-9\+]/','', $value)),
                    $this->_application->Attr($attr),
                    $this->_application->H($label)
                );
            }
        }
        return implode($settings['_separator'], $ret);
    }

    protected function number(IField $field, array $settings, array $values, IEntity $entity)
    {
        $ret = [];
        $field_settings = $field->getFieldSettings();
        $dec_point = isset($settings['dec_point']) ? $settings['dec_point'] : '.';
        $thousands_sep = isset($settings['thousands_sep']) ? $settings['thousands_sep'] : ',';
        $trim_zeros = $field_settings['decimals'] > 0 && !empty($settings['trim_zeros']);
        $prefix = isset($field_settings['prefix']) && strlen($field_settings['prefix'])
            ? $this->_application->System_TranslateString($field_settings['prefix'], $field->getFieldName() . '_field_prefix', 'entity_field')
            : '';
        $suffix = isset($field_settings['suffix']) && strlen($field_settings['suffix'])
            ? $this->_application->System_TranslateString($field_settings['suffix'], $field->getFieldName() . '_field_suffix', 'entity_field')
            : '';
        foreach ($values as $value) {
            $value = number_format($value, $field_settings['decimals'], $dec_point, $thousands_sep);
            if ($trim_zeros) {
                $value = rtrim(rtrim($value, 0), '.');
            }
            $ret[] = $prefix . $value . $suffix;
        }
        return implode($settings['_separator'], $ret);
    }

    protected function range(IField $field, array $settings, array $values, IEntity $entity)
    {
        $ret = [];
        $field_settings = $field->getFieldSettings();
        $min = isset($field_settings['min']) ? $field_settings['min'] : 0;
        $max = isset($field_settings['max']) ? $field_settings['max'] : 100;
        $dec_point = isset($settings['dec_point']) ? $settings['dec_point'] : '.';
        $thousands_sep = isset($settings['thousands_sep']) ? $settings['thousands_sep'] : ',';
        $trim_zeros = $field_settings['decimals'] > 0 && !empty($settings['trim_zeros']);
        $prefix = isset($field_settings['prefix']) && strlen($field_settings['prefix'])
            ? $this->_application->System_TranslateString($field_settings['prefix'], $field->getFieldName() . '_field_prefix', 'entity_field')
            : '';
        $suffix = isset($field_settings['suffix']) && strlen($field_settings['suffix'])
            ? $this->_application->System_TranslateString($field_settings['suffix'], $field->getFieldName() . '_field_suffix', 'entity_field')
            : '';
        $range_sep = isset($settings['range_sep']) && strlen($settings['range_sep'])
            ? $this->_application->System_TranslateString($settings['range_sep'], $field->getFieldName() . '_range_sep', 'field')
            : '';
        foreach ($values as $value) {
            if ($value['min'] == $min
                && $value['max'] == $max
            ) continue;

            $output = $prefix . number_format($value['min'], $field_settings['decimals'], $dec_point, $thousands_sep) . $suffix;
            if ($trim_zeros) {
                $output = rtrim(rtrim($output, 0), '.');
            }
            if ($value['min'] !== $value['max']) {
                $max = number_format($value['max'], $field_settings['decimals'], $dec_point, $thousands_sep);
                if ($trim_zeros) {
                    $max = rtrim(rtrim($max, 0), '.');
                }
                $output .= '<span class="drts-field-range-separator">' . $range_sep . '</span>' . $prefix . $max . $suffix;
            }
            $ret[] = $output;
        }
        return empty($ret) ? '' : implode($settings['_separator'], $ret);
    }

    protected function choice(IField $field, array $settings, array $values, IEntity $entity)
    {
        $ret = [];
        $options = $this->_application->Field_ChoiceOptions($field, !empty($settings['sort']));
        foreach ($values as $value) {
            if (isset($options['options'][$value])) {
                $ret[] = $this->_application->H($options['options'][$value]);
            }
        }
        return implode($settings['_separator'], $ret);
    }

    protected function boolean(IField $field, array $settings, array $values, IEntity $entity)
    {
        if (empty($values[0])) {
            return $this->_application->System_TranslateString($settings['off_label'], $field->getFieldName() . '_off_label', 'field');
        }
        return $this->_application->System_TranslateString($settings['on_label'], $field->getFieldName() . '_on_label', 'field');
    }

    protected function user(IField $field, array $settings, array $values, IEntity $entity)
    {
        $ret = [];
        foreach ($values as $value) {
            $ret[] = $this->_application->UserIdentityHtml($value, $settings['format']);
        }
        return implode($settings['_separator'], $ret);
    }

    public function date(IField $field, array $settings, array $values, IEntity $entity)
    {
        $ret = [];
        $field_settings = $field->getFieldSettings();
        foreach ($values as $value) {
            $ret[] = !empty($field_settings['enable_time'])
                ? $this->_application->System_Date_datetime($value, true)
                : $this->_application->System_Date($value, true);
        }
        return implode($settings['_separator'], $ret);
    }

    public function time(IField $field, array $settings, array $values, IEntity $entity)
    {
        $ret = [];
        $field_settings = $field->getFieldSettings();
        foreach ($values as $value) {
            $str = '';
            if (!empty($value['day'])
                && !empty($field_settings['enable_day'])
            ) {
                $str .= $this->_application->H($this->_application->Days($value['day'])) . $settings['daytime_sep'];
            }
            $str .= $this->_application->System_Date_time($value['start']);
            if (!empty($value['end'])
                && !empty($field_settings['enable_end'])
            ) {
                $str .= $settings['time_sep'] . $this->_application->System_Date_time($value['end']);
            }
            $ret[] = $str;
        }
        return implode($settings['_separator'], $ret);
    }

    protected function _getEmailDisplayFormatOptions()
    {
        return [
            'default' => __('E-mail Address', 'directories'),
            'nolink' => sprintf(__('%s (without link)', 'directories'), __('E-mail Address', 'directories')),
            'label' => __('Custom label', 'directories'),
            'field' => __('Select field', 'directories'),
        ];
    }

    protected function _getUrlDisplayFormatOptions()
    {
        return [
            'default' => __('URL', 'directories'),
            'nolink' => sprintf(__('%s (without link)', 'directories'), __('URL', 'directories')),
            'label' => __('Custom label', 'directories'),
            'field' => __('Select field', 'directories'),
        ];
    }

    protected function _getPhoneDisplayFormatOptions()
    {
        return [
            'default' => __('Phone Number', 'directories'),
            'nolink' => sprintf(__('%s (without link)', 'directories'), __('Phone Number', 'directories')),
            'label' => __('Custom label', 'directories'),
            'field' => __('Select field', 'directories'),
        ];
    }

    protected function _fieldRendererReadableSettings(IField $field, array $settings)
    {
        switch ($this->_name) {
            case 'boolean':
                return [
                    'on_label' => [
                        'label' => __('ON label', 'directories'),
                        'value' => $settings['on_label'],
                    ],
                    'off_label' => [
                        'label' => __('OFF label', 'directories'),
                        'value' => $settings['off_label'],
                    ],
                ];
            case 'email':
                $formats = $this->_getEmailDisplayFormatOptions();
                $ret =[
                    'type' => [
                        'label' => __('Display format', 'directories'),
                        'value' => $formats[$settings['type']],
                    ],
                ];
                if ($settings['type'] === 'label') {
                    $ret['type']['value'] .= ' - ' . $settings['label'];
                } elseif ($settings['type'] === 'field') {
                    $ret['type']['value'] .= ' - ' . $settings['field'];
                }
                $ret['show_as_button'] = [
                    'label' => __('Show as button', 'directories'),
                    'value' => !empty($settings['button']),
                    'is_bool' => true,
                ];
                if ($settings['type'] !== 'nolink') {
                    $targets = $this->_getLinkTargetOptions(true);
                    $ret['target'] = [
                        'label' => __('Open link in', 'directories'),
                        'value' => $targets[$settings['target']],
                    ];
                }
                return $ret;
            case 'url':
                $formats = $this->_getUrlDisplayFormatOptions();
                $ret =[
                    'type' => [
                        'label' => __('Display format', 'directories'),
                        'value' => $formats[$settings['type']],
                    ],
                ];
                if ($settings['type'] === 'label') {
                    $ret['type']['value'] .= ' - ' . $settings['label'];
                } elseif ($settings['type'] === 'field') {
                    $ret['type']['value'] .= ' - ' . $settings['field'];
                } else {
                    $ret['max_len'] = [
                        'label' => __('Max URL display length', 'directories'),
                        'value' => empty($settings['max_len']) ? __('Unlimited', 'directories') : $settings['max_len'],
                    ];
                }
                $ret['show_as_button'] = [
                    'label' => __('Show as button', 'directories'),
                    'value' => !empty($settings['button']),
                    'is_bool' => true,
                ];
                if ($settings['type'] !== 'nolink') {
                    $targets = $this->_getLinkTargetOptions(true);
                    $ret['target'] = [
                        'label' => __('Open link in', 'directories'),
                        'value' => $targets[$settings['target']],
                    ];
                    if (!empty($settings['rel'])) {
                        $rels = $this->_getLinkRelAttrOptions();
                        $value = [];
                        foreach ($settings['rel'] as $rel) {
                            $value[] = $rels[$rel];
                        }
                        $ret['rel'] = [
                            'label' => __('Link "rel" attribute', 'directories'),
                            'value' => implode(', ', $value),
                        ];
                    }
                }
                return $ret;
            case 'phone':
                $formats = $this->_getPhoneDisplayFormatOptions();
                $ret =[
                    'type' => [
                        'label' => __('Display format', 'directories'),
                        'value' => $formats[$settings['type']],
                    ],
                ];
                if ($settings['type'] === 'label') {
                    $ret['type']['value'] .= ' - ' . $settings['label'];
                } elseif ($settings['type'] === 'field') {
                    $ret['type']['value'] .= ' - ' . $settings['field'];
                }
                $ret['show_as_button'] = [
                    'label' => __('Show as button', 'directories'),
                    'value' => !empty($settings['button']),
                    'is_bool' => true,
                ];
                return $ret;
            case 'user':
                $formats = $this->_application->UserIdentityHtml();
                return [
                    'type' => [
                        'label' => __('Display format', 'directories'),
                        'value' => $formats[$settings['format']],
                    ],
                ];
            case 'number':
                return [
                    'dec_point' => [
                        'label' => __('Decimal point', 'directories'),
                        'value' => $settings['dec_point'],
                    ],
                    'thousands_sep' => [
                        'label' => __('Thousands separator', 'directories'),
                        'value' => $settings['thousands_sep'],
                    ],
                ];
            case 'range':
                return [
                    'dec_point' => [
                        'label' => __('Decimal point', 'directories'),
                        'value' => $settings['dec_point'],
                    ],
                    'thousands_sep' => [
                        'label' => __('Thousands separator', 'directories'),
                        'value' => $settings['thousands_sep'],
                    ],
                    'range_sep' => [
                        'label' => __('Range separator', 'directories'),
                        'value' => $settings['range_sep'],
                    ],
                ];
        }
    }
}
