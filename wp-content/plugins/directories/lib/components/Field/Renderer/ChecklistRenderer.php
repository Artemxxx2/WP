<?php
namespace SabaiApps\Directories\Component\Field\Renderer;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class ChecklistRenderer extends AbstractRenderer
{    
    protected function _fieldRendererInfo()
    {
        return array(
            'label' => __('Checklist', 'directories'),
            'field_types' => array('choice'),
            'default_settings' => array(
                'checked_color' => '',
                'show_unchecked' => true,
                'unchecked_color' => '',
                'tooltip' => false,
                'inline' => false,
                'columns' => '3r',
                'sort' => false,
            ),
            'separatable' => false,
            'emptiable' => true,
        );
    }

    protected function _fieldRendererSettingsForm(IField $field, array $settings, array $parents = [])
    {
        return array(
            'checked_color' => array(
                '#type' => 'select',
                '#title' => __('Checked item color', 'directories'),
                '#default_value' => $settings['checked_color'],
                '#options' => ['' => __('System', 'directories'), 'default' => __('Default', 'directories'), 'custom' => __('Custom', 'directories')],
                '#horizontal' => true,
            ),
            'checked_color_custom' => [
                '#type' => 'colorpicker',
                '#default_value' => $settings['checked_color_custom'],
                '#horizontal' => true,
                '#states' => array(
                    'visible' => array(
                        sprintf('select[name="%s[checked_color]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'custom'),
                    ),
                ),
            ],
            'show_unchecked' => array(
                '#type' => 'checkbox',
                '#title' => __('Show unchecked items', 'directories'),
                '#default_value' => !empty($settings['show_unchecked']),
                '#horizontal' => true,
                '#states' => [
                    'visible' => [
                        sprintf('select[name="%s[checked_color]"]', $this->_application->Form_FieldName($parents)) => ['type' => 'one', 'value' => ['', 'custom']],
                    ],
                ],
            ),
            'unchecked_color' => array(
                '#type' => 'select',
                '#title' => __('Unchecked item color', 'directories'),
                '#default_value' => $settings['unchecked_color'],
                '#options' => ['' => __('System', 'directories'), 'custom' => __('Custom', 'directories')],
                '#horizontal' => true,
                '#states' => array(
                    'visible' => array(
                        sprintf('select[name="%s[checked_color]"]', $this->_application->Form_FieldName($parents)) => ['type' => 'one', 'value' => ['', 'custom']],
                        sprintf('input[name="%s[show_unchecked]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'checked', 'value' => true),
                    ),
                ),
                '#columns' => 4,
            ),
            'unchecked_color_custom' => [
                '#type' => 'colorpicker',
                '#default_value' => $settings['unchecked_color_custom'],
                '#horizontal' => true,
                '#states' => array(
                    'visible' => array(
                        sprintf('select[name="%s[checked_color]"]', $this->_application->Form_FieldName($parents)) => ['type' => 'one', 'value' => ['', 'custom']],
                        sprintf('input[name="%s[show_unchecked]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'checked', 'value' => true),
                        sprintf('select[name="%s[unchecked_color]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'custom'),
                    ),
                ),
            ],
            'tooltip' => array(
                '#type' => 'checkbox',
                '#title' => __('Show item label in tooltip', 'directories'),
                '#default_value' => !empty($settings['tooltip']),
                '#horizontal' => true,
            ),
            'inline' => array(
                '#type' => 'checkbox',
                '#title' => __('Display inline', 'directories'),
                '#default_value' => !empty($settings['inline']),
                '#horizontal' => true,
            ),
            'columns' => [
                '#type' => 'select',
                '#options' => [
                    1 => 1,
                    2 => 2,
                    '2r' => 2 . ' - ' . __('Responsive', 'directories'),
                    3 => 3,
                    '3r' => 3 . ' - ' . __('Responsive', 'directories'),
                    '4r' => 4 . ' - ' . __('Responsive', 'directories'),
                    '6r' => 6 . ' - ' . __('Responsive', 'directories'),
                ],
                '#title' => __('Number of columns', 'directories'),
                '#default_value' => $settings['columns'],
                '#horizontal' => true,
                '#states' => array(
                    'visible' => array(
                        sprintf('input[name="%s[inline]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'checked', 'value' => false),
                    ),
                ),
            ],
            'sort' => [
                '#title' => __('Sort by label', 'directories'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['sort']),
            ],
        );
    }

    protected function _fieldRendererRenderField(IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        $ret = [];
        $checked_icon = 'fas fa-check';
        $tooltip = !empty($settings['tooltip']);
        $inline = !empty($settings['inline']);
        $columns = $settings['columns'];
        $options = $this->_application->Field_ChoiceOptions($field, !empty($settings['sort']));
        if ($settings['show_unchecked']
            && $settings['checked_color'] !== 'default'
        ) {
            $unchecked_icon = 'fas fa-times';
            foreach ($options['options'] as $option => $option_label) {
                $icon = isset($options['icons'][$option]) ? $options['icons'][$option] : null;
                if (in_array($option, $values)) {
                    $color = $settings['checked_color'] === 'custom' ? $settings['checked_color_custom'] : null;
                    $ret[] = $this->_renderColumn($icon ? $icon : $checked_icon, true, $settings['checked_color'], $color, $option_label, $tooltip, $inline, $columns);
                } else {
                    $ret[] = $this->_renderColumn($icon ? $icon : $unchecked_icon, false, $settings['unchecked_color'], $settings['unchecked_color_custom'], $option_label, $tooltip, $inline, $columns);
                }
            }
        } else {
            foreach ($values as $value) {
                if (!isset($options['options'][$value])) continue;
                
                $icon = isset($options['icons'][$value]) ? $options['icons'][$value] : $checked_icon;
                if ($settings['checked_color'] === 'custom') {
                    $color = $settings['checked_color_custom'];
                } else {
                    $color = isset($options['colors'][$value]) ? $options['colors'][$value] : null;
                }
                $ret[] = $this->_renderColumn($icon, true, $settings['checked_color'], $color, $options['options'][$value], $tooltip, $inline, $columns);
            }
        }
        if (empty($ret)) return '';

        $ret = implode(PHP_EOL, $ret);
        return $inline ? $ret : '<div class="drts-row">' . $ret . '</div>';
    }
    
    protected function _renderIcon($icon, $checked, $colorType, $color)
    {
        $class = $style = '';
        if ($colorType === '') {
            $class = DRTS_BS_PREFIX . 'text-' . ($checked ? 'success' : 'danger');
        } else {
            if (!empty($color)) {
                $style = 'color:' . $this->_application->H($color);
            }
        }
        return sprintf(
            '<span class="fa-stack" style="%3$s"><i class="far fa-circle fa-stack-2x %2$s"></i><i class="fa-stack-1x %1$s %2$s"></i></span>',
            $icon,
            $class,
            $style
        );
    }
    
    protected function _renderColumn($icon, $checked, $colorType, $color, $label, $tooltip, $inline, $columns)
    {
        $icon = $this->_renderIcon($icon, $checked, $colorType, $color);
        $label = $this->_application->H($label);
        if ($tooltip) {
            $label = '<span rel="sabaitooltip" title="' . $label . '">' . $icon . '</span>';
        } else {
            $label = $icon . ' ' . $label;
        }
        if ($inline) return $label;

        $class = DRTS_BS_PREFIX . 'mb-1 ';
        switch ($columns) {
            case '6r':
                $class .= 'drts-col-4 drts-col-sm-3 drts-col-md-2';
                break;
            case '4r':
                $class .= 'drts-col-6 drts-col-sm-4 drts-col-md-3';
                break;
            case '3r':
                $class .= 'drts-col-6 drts-col-md-4';
                break;
            case '2r':
                $class .= 'drts-col-12 drts-col-md-6';
                break;
            case 3:
                $class .= 'drts-col-4';
                break;
            case 2:
                $class .= 'drts-col-6';
                break;
            case 1:
                $class .= 'drts-col-12';
                break;
        }
        
        return '<div class="' . $class . '">' . $label . '</div>';
    }
    
    protected function _fieldRendererReadableSettings(IField $field, array $settings)
    {
        $ret = [
            'checked_color' => [
                'label' => __('Checked item color', 'directories'),
                'value' => empty($settings['checked_color'])
                    ? __('System', 'directories')
                    : ($settings['checked_color'] === 'custom' ? $settings['checked_color_custom'] : __('Default', 'directories')),
                'weight' => 1,
            ],
            'show_unchecked' => [
                'label' => __('Show unchecked items', 'directories'),
                'value' => !empty($settings['show_unchecked']),
                'is_bool' => true,
                'weight' => 2,
            ],
        ];
        if (!empty($settings['show_unchecked'])
            && $settings['checked_color'] !== 'default'
        ) {
            $ret['unchecked_color'] = [
                'label' => __('Unchecked item color', 'directories'),
                'value' => empty($settings['unchecked_color']) || $settings['unchecked_color'] === 'default'
                    ? __('System', 'directories')
                    : $settings['unchecked_color_custom'],
                'weight' => 3,
            ];
        }
        $ret += [
            'tooltip' => [
                'label' => __('Show item label in tooltip', 'directories'),
                'value' => !empty($settings['tooltip']),
                'is_bool' => true,
                'weight' => 4,
            ],
            'inline' => [
                'label' => __('Display inline', 'directories'),
                'value' => !empty($settings['inline']),
                'is_bool' => true,
                'weight' => 5,
            ],
        ];
        if (empty($settings['inline'])) {
            $ret['columns'] = [
                'label' => __('Number of columns', 'directories'),
                'value' => strpos($settings['columns'], 'r') ?
                    substr($settings['columns'], 0, 1) . ' - ' . __('Responsive', 'directories') :
                    $settings['columns'],
                'weight' => 6,
            ];
        }
        
        return $ret;
    }
}
