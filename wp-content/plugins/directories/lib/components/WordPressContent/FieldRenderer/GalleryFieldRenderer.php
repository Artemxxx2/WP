<?php
namespace SabaiApps\Directories\Component\WordPressContent\FieldRenderer;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;

class GalleryFieldRenderer extends Field\Renderer\AbstractRenderer
{   
    protected function _fieldRendererInfo()
    {
        return array(
            'label' => _x('Gallery', 'field renderer', 'directories'),
            'field_types' => array('wp_image'),
            'default_settings' => array(
                'cols' => 4,
                'size' => 'thumbnail',
                'no_link' => false,
            ),
            'separatable' => false,
        );
    }

    protected function _fieldRendererSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        return [
            'cols' => [
                '#title' => __('Number of columns', 'directories'),
                '#type' => 'select',
                '#options' => [1 => 1, 2 => 2, 3 => 3, 4 => 4, 6 => 6, 12 => 12],
                '#default_value' => $settings['cols'],
            ],
            'size' => [
                '#title' => __('Image size', 'directories'),
                '#type' => 'select',
                '#options' => $this->_getImageSizeOptions(),
                '#default_value' => $settings['size'],
            ],
            'no_link' => [
                '#title' => __('Do not link', 'directories'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['no_link']),
            ],
        ];
    }

    protected function _fieldRendererRenderField(Field\IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        if ($is_post = $field->Bundle->entitytype_name === 'post') {
            $post = $entity->post();
            setup_postdata($post);
            $GLOBALS['post'] = $post;
        }
        $attachment_ids = [];
        foreach ($values as $value) {
            $attachment_ids[] = $value['attachment_id'];
        }
        $ret = do_shortcode(sprintf(
            '[gallery columns="%d" size="%s" ids="%s" link="%s"]',
            $settings['cols'],
            $settings['size'],
            implode(',', $attachment_ids),
            empty($settings['no_link']) ? 'file' : 'none'
        ));
        if ($is_post) wp_reset_postdata();

        return $ret;
    }
    
    protected function _fieldRendererReadableSettings(Field\IField $field, array $settings)
    {
        return [
            'cols' => [
                'label' => __('Number of columns', 'directories'),
                'value' => $settings['cols'],
            ],
            'size' => [
                'label' => __('Image size', 'directories'),
                'value' => $this->_getImageSizeOptions()[$settings['size']],
            ],
            'no_link' => [
                'label' => __('Do not link', 'directories'),
                'value' => !empty($settings['no_link']),
                'is_bool' => true,
            ],
        ];
    }
}