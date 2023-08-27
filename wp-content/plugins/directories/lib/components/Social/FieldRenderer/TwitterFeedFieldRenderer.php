<?php
namespace SabaiApps\Directories\Component\Social\FieldRenderer;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;

class TwitterFeedFieldRenderer extends Field\Renderer\AbstractRenderer
{
    protected function _fieldRendererInfo()
    {
        return array(
            'label' => __('Twitter Feed', 'directories'),
            'field_types' => array('social_accounts'),
            'default_settings' => array(
                'height' => 600,
                'color' => 'light',
            ),
            'accept_multiple' => true,
        );
    }
    
    protected function _fieldRendererSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        return array(
            'height' => array(
                '#type' => 'slider',
                '#title' => __('Container height', 'directories'),
                '#default_value' => $settings['height'],
                '#min_value' => 100,
                '#max_value' => 1000,
                '#step' => 50,
                '#integer' => true,
            ),
        );
    }

    protected function _fieldRendererRenderField(Field\IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        $field_settings = $field->getFieldSettings();
        if (empty($field_settings['medias'])
            || !in_array('twitter', $field_settings['medias'])
            || empty($values[0]['twitter'])
            || strpos($values[0]['twitter'], '#') === 0
            || (!$medias = $this->_application->Social_Medias())
            || !isset($medias['twitter'])
            || (!$url = $this->_application->getComponent($medias['twitter']['component'])->socialMediaUrl('twitter', $values[0]['twitter']))
        ) return;

        return sprintf(
            '<a class="twitter-timeline" data-height="%d" href="%s?ref_src=twsrc%%5Etfw" data-chrome="nofooter">Tweets by %s</a><script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>',
            $settings['height'],
            $url,
            $this->_application->H($this->_application->Entity_Title($entity))
        );
    }
}