<?php
namespace SabaiApps\Directories\Component\WordPressContent\Elementor;

use Elementor\Controls_Manager;
use Elementor\Core\DynamicTags\Data_Tag;
use ElementorPro\Modules\DynamicTags\Module;

class GalleryDynamicTag extends Data_Tag
{
    use DynamicTagTrait;

    public function get_name()
    {
        return 'drts-gallery';
    }

    public function get_title()
    {
        return 'Directories' . ' - ' .  __('Gallery Field', 'directories');
    }

    public function get_categories()
    {
        return [
            Module::GALLERY_CATEGORY,
        ];
    }

    public function get_value(array $options = [])
    {
        $images = [];

        if ((!$field_key = $this->get_settings('field'))
            || (!$entity = $this->_getEntity())
            || (!$field = $this->_getDynamicTagField($entity, $field_key))
            || (!$value = $entity->getFieldValue($field->getFieldName()))
        ) return $images;

        foreach ($value as $_value) {
            $images[] = [
                'id' => $_value['attachment_id'],
            ];
        }

        return $images;
    }

    protected function register_controls()
    {
        $this->add_control(
            'field',
            [
                'label' => __('Field name', 'directories'),
                'type' => Controls_Manager::SELECT,
                'groups' => $this->_getDynamicTagFields(['wp_image']),
            ]
        );
    }
}