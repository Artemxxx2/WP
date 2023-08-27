<?php
namespace SabaiApps\Directories\Component\WordPressContent\Elementor;

use Elementor\Controls_Manager;
use Elementor\Core\DynamicTags\Data_Tag;
use ElementorPro\Modules\DynamicTags\Module;

class ImageDynamicTag extends Data_Tag
{
    use DynamicTagTrait;

    protected $_dynamicTagFieldTypes = ['wp_image'];

    public function get_name()
    {
        return 'drts-image';
    }

    public function get_title()
    {
        return 'Directories' . ' - ' .  __('Image Field', 'directories');
    }

    public function get_categories()
    {
        return [
            Module::IMAGE_CATEGORY,
        ];
    }

    public function get_value(array $options = [])
    {
        if ((!$field_key = $this->get_settings('field'))
            || (!$entity = $this->_getEntity())
            || (!$field = $this->_getDynamicTagField($entity, $field_key))
            || (!$value = $entity->getSingleFieldValue($field->getFieldName()))
            || (!$url = wp_get_attachment_image_src($value['attachment_id'], 'large'))
        ) return $this->get_settings('fallback');

        return  [
            'id' => $value['attachment_id'],
            'url' => $url,
        ];
    }

    protected function register_controls()
    {
        $this->add_control(
            'field',
            [
                'label' => __('Field name', 'directories'),
                'type' => Controls_Manager::SELECT,
                'groups' => $this->_getDynamicTagFields($this->_dynamicTagFieldTypes),
            ]
        );
        $this->add_control(
            'fallback',
            [
                'label' => __('Fallback image', 'directories'),
                'type' => Controls_Manager::MEDIA,
            ]
        );
    }
}