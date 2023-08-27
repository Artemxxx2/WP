<?php
namespace SabaiApps\Directories\Component\WordPressContent\FieldRenderer;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;

class PostContentFieldRenderer extends Field\Renderer\TextRenderer
{
    protected function _fieldRendererInfo()
    {
        $ret = parent::_fieldRendererInfo();
        $ret['field_types'] = array($this->_name);
        $ret['separatable'] = false;
        $ret['default_settings']['disable_exceprt_more'] = true;
        $ret['default_settings']['disable_the_content'] = false;
        return $ret;
    }
    
    protected function _fieldRendererSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        $ret = parent::_fieldRendererSettingsForm($field, $settings, $parents);
        $ret['disable_the_content'] = [
            '#type' => 'checkbox',
            '#title' => __('Disable the_content filter', 'directories'),
            '#default_value' => !empty($settings['disable_the_content']),
            '#states' => [
                'visible' => [
                    sprintf('input[name="%s[trim]"]', $this->_application->Form_FieldName($parents)) => ['type' => 'checked', 'value' => false],
                ],
            ],
        ];
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
        if (!$post = $entity->post()) return ''; // this should not happen

        if (!empty($settings['disable_the_content'])) {
            remove_all_filters('the_content');
        }

        // Make sure the following filters are applied which may have been removed by a page builder plugin.
        $filters = [
            'wptexturize' => 10,
            'wpautop' => 10,
            'convert_smilies' => 10,
            'convert_chars' => 10,
            'shortcode_unautop' => 10,
            'prepend_attachment' => 10,
            'do_shortcode' => 11,
        ];
        foreach ($filters as $filter => $priority) {
            if (!has_filter('the_content', $filter)) {
                add_filter('the_content', $filter, $priority);
            } else {
                unset($filters[$filter]);
            }
        }

        // Process content
        setup_postdata($post);
        $GLOBALS['post'] = $post;
        $value = str_replace(']]>', ']]&gt;', apply_filters('the_content', wp_kses_post(get_the_content())));
        wp_reset_postdata();

        // Remove filters if added
        if (!empty($filters)) {
            foreach ($filters as $filter => $priority) {
                remove_filter('the_content', $filter, $priority);
            }
        }

        return $value;
    }

    protected function _getTrimmedContent($value, $length, $marker, $link, array $settings, Entity\Type\IEntity $entity)
    {
        if (!$post = $entity->post()) return ''; // this should not happen

        $value = apply_filters('get_the_excerpt', $post->post_excerpt ? $post->post_excerpt : strip_shortcodes($post->post_content), $post);
        // Add WordPress trim marker
        $marker = ' ' . '[&hellip;]'; // set default trim marker of WordPress
        if (isset($settings['disable_exceprt_more'])
            && !$settings['disable_exceprt_more']
        ) {
            setup_postdata($post);
            $GLOBALS['post'] = $post;
            $marker = apply_filters('excerpt_more', $marker);
            wp_reset_postdata();
        }

        return parent::_getTrimmedContent($value, $length, $marker, $link, $settings, $entity);
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
