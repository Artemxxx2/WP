<?php
namespace SabaiApps\Directories\Component\Entity\FieldRenderer;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class PublishedFieldRenderer extends Field\Renderer\DateRenderer
{
    protected function _fieldRendererInfo()
    {
        $info = parent::_fieldRendererInfo();
        $info['default_settings']['permalink'] = false;
        return $info;
    }

    protected function _fieldRendererSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        return parent::_fieldRendererSettingsForm($field, $settings, $parents) + [
            'permalink' => [
                '#type' => 'checkbox',
                '#title' => __('Link to page', 'directories'),
                '#default_value' => !empty($settings['permalink']),
            ],
        ];
    }

    protected function _renderField(Field\IField $field, array &$settings, Entity\Type\IEntity $entity, $value)
    {
        if (!$value = $this->_getTimestamp($field, $settings, $entity)) return '';

        $html = parent::_renderField($field, $settings, $entity, $value);
        return $settings['permalink'] && ($url = $this->_application->Entity_PermalinkUrl($entity))
            ? '<a href="' . $url . '">' . $html . '</a>'
            : $html;
    }

    protected function _getTimestamp(Field\IField $field, array &$settings, Entity\Type\IEntity $entity)
    {
        return $entity->getTimestamp();
    }

    protected function _fieldRendererReadableSettings(Field\IField $field, array $settings)
    {
        return parent::_fieldRendererReadableSettings($field, $settings) + [
            'permalink' => [
                'label' => __('Link to page', 'directories'),
                'value' => !empty($settings['permalink']),
                'is_bool' => true,
            ],
        ];
    }
}
