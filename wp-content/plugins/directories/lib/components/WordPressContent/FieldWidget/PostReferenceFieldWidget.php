<?php
namespace SabaiApps\Directories\Component\WordPressContent\FieldWidget;

use SabaiApps\Directories\Request;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class PostReferenceFieldWidget extends Field\Widget\AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return [
            'label' => __('Autocomplete text field', 'directories'),
            'field_types' => [$this->_name],
            'accept_multiple' => false,
            'repeatable' => true,
            'default_settings' => [
                'own_only' => false,
                'num' => 5,
            ],
        ];
    }

    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        return [
            'own_only' => [
                '#title' => __('Auto-suggest own content items only', 'directories'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['own_only']),
            ],
            'num' => [
                '#title' => __('Number of auto suggested items to display', 'directories'),
                '#type' => 'slider',
                '#default_value' => $settings['num'],
                '#max_value' => 20,
                '#min_value' => 1,
            ],
        ];
    }

    public function fieldWidgetForm(Field\IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        if ($this->_application->getUser()->isAnonymous()) return;

        $field_settings = $field->getFieldSettings();
        if (!post_type_exists($field_settings['post_type'])) return;

        return [
            '#type' => 'autocomplete',
            '#default_value' => $value,
            '#select2' => true,
            '#select2_ajax' => true,
            '#select2_ajax_url' => $this->_getAjaxUrl($field_settings['post_type'], $settings, $entity, $language),
            '#select2_item_text_key' => 'title',
            '#default_options_callback' => [[$this, '_getDefaultOptions'], [$field_settings['post_type']]],
        ];
    }

    public function _getDefaultOptions($defaultValue, array &$options, $postType)
    {
        foreach (get_posts(['post_type' => $postType, 'include' => $defaultValue]) as $post) {
            $options[$post->ID] = $post->post_title;
        }
    }

    protected function _getAjaxUrl($postType, array $settings, Entity\Type\IEntity $entity = null, $language = null)
    {
        $params = [
            'post_type' => $postType,
            Request::PARAM_CONTENT_TYPE => 'json',
            'language' => $language,
            'num' => empty($settings['num']) ? 5 : $settings['num']
        ];
        if (!empty($settings['own_only'])) {
            if (isset($entity)
                && $entity->getAuthorId()
            ) {
                $params['user_id'] = $entity->getAuthorId();
            } else {
                if (!$this->_application->getUser()->isAnonymous()) {
                    $params['user_id'] = $this->_application->getUser()->id;
                }
            }
        }

        return $this->_application->MainUrl(
            '/_drts/wp/posts',
            $params,
            '',
            '&'
        );
    }
}
