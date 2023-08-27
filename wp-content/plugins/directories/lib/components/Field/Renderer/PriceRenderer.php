<?php
namespace SabaiApps\Directories\Component\Field\Renderer;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

class PriceRenderer extends AbstractRenderer
{
    protected function _fieldRendererInfo()
    {
        return [
            'field_types' => ['price'],
            'default_settings' => [
                'symbol_pos' => '',
                '_separator' => ', ',
            ],
        ];
    }

    protected function _fieldRendererSettingsForm(IField $field, array $settings, array $parents = [])
    {
        return [
            'symbol_pos' => [
                '#type' => 'select',
                '#title' => __('Currency symbol position', 'directories'),
                '#options' => $this->_getCurrencySymbolPositionOptions(),
                '#default_value' => $settings['symbol_pos'],
            ]
        ];
    }

    protected function _getCurrencySymbolPositionOptions()
    {
        return [
            '' => __('Auto', 'directories'),
            'before' => __('Before value', 'directories'),
            'after' => __('After value', 'directories'),
        ];
    }

    protected function _fieldRendererRenderField(IField $field, array &$settings, IEntity $entity, array $values, $more = 0)
    {
        switch ($settings['symbol_pos']) {
            case 'before':
                $currency_position = 0;
                break;
            case 'after':
                $currency_position = 1;
                break;
            default:
                $currency_position = true;
        }
        $currencies = $this->_application->System_Currency_formats();
        foreach (array_keys($values) as $i) {
            $currency = $values[$i]['currency'];
            $values[$i] = $this->_application->System_Currency_format(
                $values[$i]['value'],
                $currency,
                $currency_position,
                isset($currencies[$currency]) ? $currencies[$currency] : [2]
            );
        }
        return implode($settings['_separator'], $values);
    }

    protected function _fieldRendererReadableSettings(IField $field, array $settings)
    {
        $symbol_positions = $this->_getCurrencySymbolPositionOptions();
        return [
            'symbol_pos' => [
                'label' => __('Currency symbol position', 'directories'),
                'value' => isset($symbol_positions[$settings['symbol_pos']]) ? $symbol_positions[$settings['symbol_pos']] : __('Auto', 'directories'),
            ],
        ];
    }
}