<?php
namespace SabaiApps\Directories\Component\Field\Renderer;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class IconRenderer extends AbstractRenderer
{
    protected function _fieldRendererInfo()
    {
        return [
            'label' => __('Icon', 'directories'),
            'field_types' => [$this->_name, 'number', 'price'],
            'default_settings' => [
                'size' => '',
                'color' => [
                    'type' => '',
                    'custom' => null,
                    '_separator' => ', ',
                ],
                'icon' => null,
            ],
        ];
    }

    protected function _fieldRendererSettingsForm(IField $field, array $settings, array $parents = [])
    {
        $form = [
            'size' => [
                '#type' => 'select',
                '#title' => __('Icon size', 'directories'),
                '#default_value' => $settings['size'],
                '#options' => $this->_application->System_Util_iconSizeOptions(),
                '#weight' => 5,
            ],
            'color' => ['#weight' => 6] + $this->_application->System_Util_iconColorSettingsForm(
                $field->Bundle,
                isset($settings['color']) && is_array($settings['color']) ? $settings['color'] : [],
                array_merge($parents, ['color'])
            ),
        ];
        if ($field->getFieldType() !== $this->_name) {
            $form['icon'] = [
                '#title' => __('Icon', 'directories'),
                '#type' => 'iconpicker',
                '#default_value' => $settings['icon'],
                '#weight' => 1,
            ];
        }

        return $form;
    }

    protected function _fieldRendererRenderField(IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        $style = ($color = $this->_getIconColor($field, $settings, $entity)) ? 'style="background-color:' . $color . ';color:#fff;"' : '';
        $class = $settings['size'] ? 'drts-icon-' . $settings['size'] . ' ' : '';
        $icons = [];
        foreach ($values as $value) {
            if ((!$icon_class = $this->_getIconClass($field, $settings, $value))
                || (0 >= $count = $this->_getIconCount($field, $settings, $value))
            ) continue;

            $icon = '<i ' . $style . ' class="' . $this->_application->H($class . $icon_class) . '"></i> ';
            if ($count > 1) {
                $icon = str_repeat($icon, $count);
            }
            $icons[] = $icon;
        }

        return implode($settings['_separator'], $icons);
    }
    
    protected function _fieldRendererReadableSettings(IField $field, array $settings)
    {
        $ret = [];
        if ($field->getFieldType() !== $this->_name) {
            $ret['icon'] = [
                'label' => __('Icon', 'directories'),
                'value' => $settings['icon'],
            ];
        }
        $ret['size'] = [
            'label' => __('Icon size', 'directories'),
            'value' => $this->_application->System_Util_iconSizeOptions()[$settings['size']],
        ];
        if ($color = $this->_getIconColor($field, $settings)) {
            $ret['color'] = [
                'label' => __('Icon color', 'directories'),
                'value' => $color,
            ];
        }
        return $ret;
    }

    protected function _getIconClass(IField $field, array $settings, $value)
    {
        return isset($settings['icon']) ? $settings['icon'] : $value;
    }

    protected function _getIconCount(IField $field, array $settings, $value)
    {
        return ($field->getFieldType() === $this->_name) ? 1 : round($value);
    }

    protected function _getIconColor(IField $field, array $settings, Entity\Type\IEntity $entity = null)
    {
        if ($settings['color']['type'] === '_custom') {
            return $settings['color']['custom'];
        }
        if ($settings['color']['type'] !== ''
            && isset($entity)
            && ($color = $entity->getSingleFieldValue($settings['color']['type']))
        ) {
            return $color;
        }
    }
}