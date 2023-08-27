<?php
namespace SabaiApps\Directories\Component\WordPressContent\FieldRenderer;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class PostReferenceFieldRenderer extends Field\Renderer\AbstractRenderer
{
    protected function _fieldRendererInfo()
    {
        return [
            'field_types' => [$this->_name],
            'default_settings' => [
                '_separator' => ', ',],
            'inlineable' => true,
        ];
    }

    protected function _fieldRendererRenderField(Field\IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        $field_settings = $field->getFieldSettings();
        if (!post_type_exists($field_settings['post_type'])) return;

        $posts = get_posts(['post_type' => $field_settings['post_type'], 'include' => $values]);
        if (empty($posts)) return;

        $ret = [];
        foreach ($posts as $post) {
            if (!$url = get_permalink($post)) continue;

            $ret[] = '<a href="' . esc_url($url) . '">' . esc_html(get_the_title($post)) . '</a>';
        }

        return implode($settings['_separator'], $ret);
    }
}
