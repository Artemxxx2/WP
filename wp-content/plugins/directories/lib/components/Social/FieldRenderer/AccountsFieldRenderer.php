<?php
namespace SabaiApps\Directories\Component\Social\FieldRenderer;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;

class AccountsFieldRenderer extends Field\Renderer\AbstractRenderer
{
    protected function _fieldRendererInfo()
    {
        return array(
            'field_types' => array('social_accounts'),
            'default_settings' => array(
                'size' => 'fa-lg',
                'target' => '_blank',
                'rel' => array('nofollow', 'external'),
                '_separator' => ' ',
            ),
            'inlineable' => true,
            'accept_multiple' => true,
        );
    }

    protected function _fieldRendererSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {        
        return array(
            'size' => array(
                '#title' => __('Icon size', 'directories'),
                '#type' => 'select',
                '#options' => $this->_getIconSizeOptions(),
                '#default_value' => $settings['size'],
            ),
            'target' => array(
                '#title' => __('Open link in', 'directories'),
                '#type' => 'select',
                '#options' => $this->_getLinkTargetOptions(),
                '#default_value' => $settings['target'],
            ),
            'rel' => array(
                '#title' => __('Link "rel" attribute', 'directories'),
                '#type' => 'checkboxes',
                '#options' => $this->_getLinkRelAttrOptions(),
                '#default_value' => $settings['rel'],
            ),
        );
    }
    
    protected function _getIconSizeOptions()
    {
        return [
            '' => __('Normal', 'directories'),
            'fa-lg' => __('Large', 'directories'),
            'fa-2x' => '2x',
            'fa-3x' => '3x',
            'fa-5x' => '5x',
            'fa-7x' => '7x',
            'fa-10x' => '10x',
        ];
    }

    protected function _fieldRendererRenderField(Field\IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        $field_settings = $field->getFieldSettings();
        if (empty($field_settings['medias'])) return;
     
        $ret = [];
        $medias = $this->_application->Social_Medias();
        $rel = implode(' ', $settings['rel']);
        $icon_size = $settings['size'] !== '' ? $settings['size'] : '';
        foreach ($values[0] as $media_name => $value) {
            if (!in_array($media_name, $field_settings['medias'])
                || !isset($medias[$media_name]['component'])
                || !$this->_application->isComponentLoaded($medias[$media_name]['component'])
                || (!$url = $this->_application->getComponent($medias[$media_name]['component'])->socialMediaUrl($media_name, $value))
            ) continue;
            
            $media = $medias[$media_name];
            if (!empty($media['image'])) {
                if (!isset($image_size)) $image_size = $this->_getSocialMediaImageSize($settings['size']);
                $ret[] = sprintf(
                    '<a target="%s" class="drts-social-media-account" rel="%s" href="%s"><img src="%s" alt="%s" height="%d"/></a>',
                    $settings['target'],
                    $rel,
                    $this->_application->H($url),
                    $this->_application->H($media['image']),
                    $this->_application->H($media['label']),
                    $image_size
                );
            } elseif (!empty($media['icon'])) {
                $ret[] = sprintf(
                    '<a target="%s" class="drts-social-media-account" rel="%s" href="%s" title="%s"><i class="fa-fw %s %s"></i></a>',
                    $settings['target'],
                    $rel,
                    $this->_application->H($url),
                    $this->_application->H($media['label']),
                    $this->_application->H($media['icon']),
                    $icon_size
                );
            } else {
                $ret[] = sprintf(
                    '<a target="%s" class="drts-social-media-account" rel="%s" href="%s">%s</a>',
                    $settings['target'],
                    $rel,
                    $this->_application->H($url),
                    $this->_application->H($media['label'])
                );
            }
        }
        return implode($settings['_separator'], $ret);
    }

    protected function _getSocialMediaImageSize($iconSize)
    {
        switch ($iconSize) {
            case 'fa-lg':
                $image_size = 18.6666666;
                break;
            case 'fa-2x':
                $image_size = 28;
                break;
            case 'fa-3x':
                $image_size = 42;
                break;
            case 'fa-5x':
                $image_size = 70;
                break;
            case 'fa-7x':
                $image_size = 98;
                break;
            case 'fa-10x':
                $image_size = 140;
                break;
            default:
                $image_size = 14;
        }
        return $image_size;
    }
    
    protected function _fieldRendererReadableSettings(Field\IField $field, array $settings)
    {
        $sizes = $this->_getIconSizeOptions();
        $targets = $this->_getLinkTargetOptions(true);
        $ret = [
            'size' => [
                'label' => __('Icon size', 'directories'),
                'value' => $sizes[$settings['size']],
            ],
            'target' => [
                'label' => __('Open link in', 'directories'),
                'value' => $targets[$settings['target']],
            ],
        ];
        if (!empty($settings['rel'])) {
            $rels = $this->_getLinkRelAttrOptions();
            $value = [];
            foreach ($settings['rel'] as $rel) {
                $value[] = $rels[$rel];
            }
            $ret['rel'] = [
                'label' => __('Link "rel" attribute', 'directories'),
                'value' => implode(', ', $value),
            ];
        }
        return $ret;
    }
}