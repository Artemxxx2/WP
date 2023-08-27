<?php
namespace SabaiApps\Directories\Component\Social\FieldRenderer;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;

class FacebookMessengerLinkFieldRenderer extends Field\Renderer\AbstractRenderer
{
    protected function _fieldRendererInfo()
    {
        return [
            'label' => __('Facebook Messenger Link', 'directories'),
            'field_types' => ['social_accounts'],
            'default_settings' => [
                'type' => 'default',
                'label' => null,
                'target' => '_self',
            ],
            'accept_multiple' => true,
        ];
    }

    protected function _fieldRendererSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        return [
            'type' => [
                '#title' => __('Display format', 'directories'),
                '#type' => 'select',
                '#options' => $this->_getFacebookMessengerLinkDisplayFormatOptions(),
                '#default_value' => $settings['type'],
            ],
            'label' => [
                '#title' => __('Custom label', 'directories'),
                '#type' => 'textfield',
                '#default_value' => $settings['label'],
                '#states' => [
                    'visible' => [
                        sprintf('select[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['type']))) => ['value' => 'label'],
                    ],
                ],
            ],
            'target' => array(
                '#title' => __('Open link in', 'directories'),
                '#type' => 'select',
                '#options' => $this->_getLinkTargetOptions(),
                '#default_value' => $settings['target'],
            ),
        ];
    }

    protected function _getFacebookMessengerLinkDisplayFormatOptions()
    {
        return [
            'default' => __('Facebook username', 'directories'),
            'label' => __('Custom label', 'directories')
        ];
    }

    protected function _fieldRendererRenderField(Field\IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        $field_settings = $field->getFieldSettings();
        if (empty($field_settings['medias'])
            || !in_array('facebook', $field_settings['medias'])
            || empty($values[0]['facebook'])
            || (!$medias = $this->_application->Social_Medias())
            || !isset($medias['facebook'])
        ) return;

        return sprintf(
            '<a href="http://m.me/%s"%s>%s</a>',
            $this->_application->H($_value = $values[0]['facebook']),
            $settings['target'] === '_blank' ? ' target="_blank" rel="noopener noreferrer"' : '',
            $settings['type'] === 'label' ? $this->_application->H($settings['label']) : $_value
        );
    }

    protected function _fieldRendererReadableSettings(Field\IField $field, array $settings)
    {
        $formats = $this->_getFacebookMessengerLinkDisplayFormatOptions();
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
        $targets = $this->_getLinkTargetOptions(true);
        $ret['target'] = [
            'label' => __('Open link in', 'directories'),
            'value' => $targets[$settings['target']],
        ];

        return $ret;
    }
}