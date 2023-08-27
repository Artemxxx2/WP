<?php
namespace SabaiApps\Directories\Component\Field\Renderer;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class TextRenderer extends AbstractRenderer
{
    protected function _fieldRendererInfo()
    {
        return array(
            'field_types' => array('text'),
            'default_settings' => array(
                'trim' => false,
                'trim_length' => 200,
                'trim_marker' => '...',
                'trim_link' => false,
                '_separator' => ' ',
            ),
        );
    }

    protected function _fieldRendererSettingsForm(IField $field, array $settings, array $parents = [])
    {
        return array(
            'trim' => array(
                '#type' => 'checkbox',
                '#title' => __('Show excerpt', 'directories'),
                '#default_value' => !empty($settings['trim']),
            ),
            'trim_length' => array(
                '#title' => __('Max number of characters', 'directories'),
                '#type' => 'number',
                '#integer' => true,
                '#min_value' => 1,
                '#default_value' => $settings['trim_length'],
                '#size' => 5,
                '#states' => array(
                    'visible' => array(
                        sprintf('input[name="%s[trim]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'checked', 'value' => true),
                    ),
                ),
            ),
            'trim_marker' => array(
                '#title' => __('Suffix text', 'directories'),
                '#type' => 'textfield',
                '#default_value' => $settings['trim_marker'],
                '#size' => 10,
                '#states' => array(
                    'visible' => array(
                        sprintf('input[name="%s[trim]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'checked', 'value' => true),
                    ),
                ),
            ),
            'trim_link' => array(
                '#type' => 'checkbox',
                '#title' => __('Link suffix to content page', 'directories'),
                '#default_value' => !empty($settings['trim_link']),
                '#states' => array(
                    'visible' => array(
                        sprintf('input[name="%s[trim]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'checked', 'value' => true),
                    ),
                ),
            ),
        );
    }

    protected function _fieldRendererRenderField(IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        $ret = [];
        foreach (array_keys($values) as $i) {
            if (!empty($settings['trim'])) {
                $marker = isset($settings['trim_marker']) ? $settings['trim_marker'] : '...';
                $ret[] = $this->_getTrimmedContent($values[$i], $settings['trim_length'], $marker, !empty($settings['trim_link']), $settings, $entity);
            } else {
                $ret[] = $this->_getContent($values[$i], $settings, $entity);
            }
        }
        return isset($settings['_separator']) ? implode($settings['_separator'], $ret) : $ret[0];
    }

    protected function _getContent($value, array $settings, Entity\Type\IEntity $entity)
    {
        $value = is_array($value) ? $value['value'] : $value;

        return $this->_application->Htmlize($this->_application->Entity_Tokens_replace($value, $entity));
    }

    protected function _getTrimmedContent($value, $length, $marker, $link, array $settings, Entity\Type\IEntity $entity)
    {
        $value = $this->_application->Entity_Tokens_replace(is_array($value) ? $value['value'] : $value, $entity);

        if (!empty($link)) {
            return $this->_application->Summarize($value, $length - $this->_application->System_MB_strlen($marker), '')
                . $this->_application->Entity_Permalink($entity, array('title' => $marker, 'class' => 'drts-trim-marker'));
        }
        return $this->_application->Summarize($value, $length, $marker);
    }

    protected function _fieldRendererReadableSettings(IField $field, array $settings)
    {
        $ret = [
            'trim' => [
                'label' => __('Show excerpt', 'directories'),
                'value' => !empty($settings['trim']),
                'is_bool' => true,
            ],
        ];
        if (!empty($settings['trim'])) {
            $ret += [
                'trim_length' => [
                    'label' => __('Max number of characters', 'directories'),
                    'value' => $settings['trim_length'],
                ],
                'trim_marker' => [
                    'label' => __('Suffix text', 'directories'),
                    'value' => $settings['trim_marker'],
                ],
                'trim_link' => [
                    'label' => __('Link suffix to content page', 'directories'),
                    'value' => !empty($settings['trim_link']),
                    'is_bool' => true,
                ],
            ];
        }
        return $ret;
    }
}
