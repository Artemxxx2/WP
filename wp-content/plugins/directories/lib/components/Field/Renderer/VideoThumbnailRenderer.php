<?php
namespace SabaiApps\Directories\Component\Field\Renderer;

use SabaiApps\Directories\Component\Field\IField;

class VideoThumbnailRenderer extends ImageRenderer
{
    protected function _fieldRendererInfo()
    {
        return [
                'label' => __('Thumbnail URL', 'directories'),
                'field_types' => ['video'],
            ] + parent::_fieldRendererInfo();
    }

    public function fieldRendererSettingsForm(IField $field, array $settings, array $parents = [])
    {
        $form = parent::fieldRendererSettingsForm($field, $settings, $parents);
        $form['size'] = [
            '#type' => 'hidden',
            '#value' => 'thumbnail',
        ];
        unset($form['link_image_size']);
        return $form;
    }

    protected function _getImageUrl(IField $field, array $settings, $value, $size)
    {
        return $value['thumbnail_url'];
    }

    protected function _getImageAlt(IField $field, array $settings, $value)
    {
        return $value['title'];
    }

    protected function _getImageTitle(IField $field, array $settings, $value)
    {
        return $value['title'];
    }

    protected function _fieldRendererReadableSettings(IField $field, array $settings)
    {
        $settings = parent::_fieldRendererReadableSettings($field, $settings);
        unset($settings['link_image_size']);
        return $settings;
    }
}