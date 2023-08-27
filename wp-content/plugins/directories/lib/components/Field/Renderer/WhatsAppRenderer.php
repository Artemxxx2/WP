<?php
namespace SabaiApps\Directories\Component\Field\Renderer;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class WhatsAppRenderer extends AbstractRenderer
{
    protected function _fieldRendererInfo()
    {
        return [
            'label' => __('WhatsApp link', 'directories'),
            'field_types' => ['phone'],
            'default_settings' => [
                'type' => 'default',
                'label' => null,
                'button' => false,
                'target' => '_self',
                'prefix_country_code' => false,
                'country_code' => null,
                'message' => null,
            ],
        ];
    }

    protected function _fieldRendererSettingsForm(IField $field, array $settings, array $parents = [])
    {
        return [
            'type' => [
                '#title' => __('Display format', 'directories'),
                '#type' => 'select',
                '#options' => $this->_getWhatsAppDisplayFormatOptions(),
                '#default_value' => $settings['type'],
            ],
            'label' => [
                '#title' => __('Custom label', 'directories'),
                '#type' => 'textfield',
                '#default_value' => $settings['label'],
                '#states' => [
                    'visible' => [
                        sprintf('select[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['type']))) => array('value' => 'label'),
                    ],
                ],
            ],
            'button' => [
                '#title' => __('Show as button', 'directories'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['button']),
            ],
            'target' => array(
                '#title' => __('Open link in', 'directories'),
                '#type' => 'select',
                '#options' => $this->_getLinkTargetOptions(),
                '#default_value' => $settings['target'],
            ),
            'prefix_country_code' => [
                '#type' => 'checkbox',
                '#title' => __('Prefix with country code', 'directories'),
                '#default_value' => !empty($settings['prefix_country_code']),
            ],
            'country_code' => [
                '#type' => 'select',
                '#select2' => true,
                '#default_value' => $settings['country_code'],
                '#options' => $this->_getCountryCodeOptions(),
                '#states' => [
                    'visible' => [
                        sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['prefix_country_code']))) => ['type' => 'checked', 'value' => true],
                    ],
                ],
            ],
            'message' => [
                '#title' => __('Pre-filled message', 'directories'),
                '#type' => 'textfield',
                '#default_value' => $settings['message'],
            ],
        ];
    }

    protected function _getWhatsAppDisplayFormatOptions()
    {
        return [
            'default' => __('WhatsApp number', 'directories'),
            'label' => __('Custom label', 'directories')
        ];
    }

    protected function _getCountryCodeOptions()
    {
        $ret = [];
        $countries = $this->_application->System_Countries();
        foreach ($this->_application->System_Countries_phone() as $country => $phone) {
            if (!isset($countries[$country])) continue;

            $ret[$phone] = $countries[$country] . ' ' . $phone;
        }
        asort($ret, SORT_LOCALE_STRING);
        return $ret;
    }

    protected function _fieldRendererRenderField(IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        $ret = [];
        $country_code = empty($settings['prefix_country_code']) || (!$country_code = preg_replace('/\D/', '', $settings['country_code'])) ? '' : $country_code;
        $label = $settings['type'] === 'label' ? $this->_application->H($settings['label']) : null;
        $text = strlen($settings['message']) ? $this->_application->H('&text=' . rawurlencode($settings['message'])) : '';
        $attr = $icon = '';
        if ($settings['target'] === '_blank') {
            $attr .= ' target="_blank" rel="noopener noreferrer"';
        }
        if (!empty($settings['button'])) {
            $classes = DRTS_BS_PREFIX . 'btn ' . DRTS_BS_PREFIX . 'btn-sm';
            $attr .= ' class="' . $classes . '" style="background-color:#25D366;color:#fff;"';
            $icon = '<i class="fab fa-whatsapp"></i> ';
        }
        foreach ($values as $value) {
            $ret[] = sprintf(
                '<a href="https://api.whatsapp.com/send?phone=%s%s%s"%s>%s%s</a>',
                $country_code,
                preg_replace('/\D/', '', $value),
                $text,
                $attr,
                $icon,
                isset($label) ? $label : $this->_application->H($value)
            );
        }

        return implode($settings['_separator'], $ret);
    }

    protected function _fieldRendererReadableSettings(IField $field, array $settings)
    {
        $formats = $this->_getWhatsAppDisplayFormatOptions();
        $ret =[
            'type' => [
                'label' => __('Display format', 'directories'),
                'value' => $formats[$settings['type']],
            ],
        ];
        if ($settings['type'] === 'label') {
            $ret['label'] = [
                'label' => __('Custom label', 'directories'),
                'value' => $settings['label'],
            ];
        }
        $ret['show_as_button'] = [
            'label' => __('Show as button', 'directories'),
            'value' => !empty($settings['button']),
            'is_bool' => true,
        ];
        $targets = $this->_getLinkTargetOptions(true);
        $ret['target'] = [
            'label' => __('Open link in', 'directories'),
            'value' => $targets[$settings['target']],
        ];
        if (!empty($settings['prefix_country_code'])) {
            $ret['country_code'] = [
                'label' => __('Country code', 'directories'),
                'value' => $settings['country_code'],
            ];
        }
        if (strlen($settings['message'])) {
            $ret['message'] = [
                'label' => __('Pre-filled message', 'directories'),
                'value' => $settings['message'],
            ];
        }
        return $ret;
    }
}
