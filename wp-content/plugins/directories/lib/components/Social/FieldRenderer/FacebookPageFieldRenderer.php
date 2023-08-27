<?php
namespace SabaiApps\Directories\Component\Social\FieldRenderer;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;

class FacebookPageFieldRenderer extends Field\Renderer\AbstractRenderer
{
    protected function _fieldRendererInfo()
    {
        return array(
            'label' => __('Facebook Page', 'directories'),
            'field_types' => array('social_accounts'),
            'default_settings' => array(
                'height' => 600,
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
            || !in_array('facebook', $field_settings['medias'])
            || empty($values[0]['facebook'])
            || (!$medias = $this->_application->Social_Medias())
            || !isset($medias['facebook'])
            || (!$href = $this->_application->getComponent($medias['facebook']['component'])->socialMediaUrl('facebook', $values[0]['facebook']))
        ) return;

        return sprintf(
            '<iframe src="%s" height="%d" style="border:none;overflow:hidden;width:100%%;" scrolling="no" frameborder="0" allowTransparency="true"></iframe>',
            $this->_application->Url(array(
                'script_url' => 'https://www.facebook.com/plugins/page.php',
                'params' => array(
                    'href' => $href,
                    'tabs' => 'timeline',
                    'height' => $settings['height'],
                    'show_facepile' => 'true',
                    'adapt_container_width' => 'true',
                    'small_header' => 'false',
                )
            )),
            $settings['height']
        );
    }
}