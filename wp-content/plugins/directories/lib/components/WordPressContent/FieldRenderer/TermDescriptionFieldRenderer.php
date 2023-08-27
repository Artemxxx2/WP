<?php
namespace SabaiApps\Directories\Component\WordPressContent\FieldRenderer;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;

class TermDescriptionFieldRenderer extends Field\Renderer\TextRenderer
{    
    protected function _fieldRendererInfo()
    {
        $ret = parent::_fieldRendererInfo();
        $ret['field_types'] = array($this->_name);
        $ret['separatable'] = false;
        $ret['default_settings']['disable_exceprt_more'] = true;
        return $ret;
    }
    
    protected function _fieldRendererSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        $ret = parent::_fieldRendererSettingsForm($field, $settings, $parents);
        $ret['disable_exceprt_more'] = [
            '#type' => 'checkbox',
            '#title' => __('Disable excerpt_more filter', 'directories'),
            '#default_value' => !empty($settings['disable_exceprt_more']),
            '#states' => [
                'visible' => [
                    sprintf('input[name="%s[trim]"]', $this->_application->Form_FieldName($parents)) => ['type' => 'checked', 'value' => true],
                ],
            ],
        ];
        unset($ret['trim_marker'], $ret['trim_link']);
        return $ret;
    }
    
    protected function _getContent($value, array $settings, Entity\Type\IEntity $entity)
    {
        return parent::_getContent($this->_getTermDescription($entity), $settings, $entity);
    }
    
    protected function _getTrimmedContent($value, $length, $marker, $link, array $settings, Entity\Type\IEntity $entity)
    {
        $value = strip_shortcodes($this->_getTermDescription($entity));
        // Add WordPress trim marker
        $marker = ' ' . '[&hellip;]'; // set default trim marker of WordPress
        if (isset($settings['disable_exceprt_more'])
            && !$settings['disable_exceprt_more']
        ) {
            $marker = apply_filters('excerpt_more', $marker);
        }

        return parent::_getTrimmedContent($value, $length, $marker, $link, $settings, $entity);
    }

    protected function _getTermDescription(Entity\Type\IEntity $entity)
    {
        return term_description($entity->getId(), $entity->getBundleName());
    }

    protected function _fieldRendererReadableSettings(Field\IField $field, array $settings)
    {
        $ret = (array)parent::_fieldRendererReadableSettings($field, $settings);
        if (!empty($ret['trim'])) {
            $ret += [
                'disable_exceprt_more' => [
                    'label' => __('Disable excerpt_more filter', 'directories'),
                    'value' => !empty($settings['disable_exceprt_more']),
                    'is_bool' => true,
                ],
            ];
            unset($ret['trim_marker'], $ret['trim_link']);
        }
        return $ret;
    }
}
