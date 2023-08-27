<?php
namespace SabaiApps\Directories\Component\WordPressContent\Elementor;

use Elementor\Controls_Manager;
use Elementor\Core\DynamicTags\Tag;
use ElementorPro\Modules\DynamicTags\Module;
use SabaiApps\Directories\Component\Field\Type\IHumanReadable;

class TextDynamicTag extends Tag
{
    use DynamicTagTrait;

    public function get_name()
    {
        return 'drts-text';
    }

    public function get_title()
    {
        return 'Directories' . ' - ' .  __('Field', 'directories');
    }

    public function get_categories()
    {
        return [
            Module::TEXT_CATEGORY,
            Module::POST_META_CATEGORY,
        ];
    }

    public function render()
    {
        if ((!$field_key = $this->get_settings('field'))
            || (!$entity = $this->_getEntity())
            || (!$field = $this->_getDynamicTagField($entity, $field_key))
            || (!$field_type = drts()->Field_Type($field->getFieldType(), true))
            || !$field_type instanceof IHumanReadable
        ) return;

        echo wp_kses_post($field_type->fieldHumanReadableText($field, $entity, ', ', null));
    }

    protected function register_controls()
    {
        $this->add_control(
            'field',
            [
                'label' => __('Field name', 'directories'),
                'type' => Controls_Manager::SELECT,
                'groups' => $this->_getDynamicTagFields(null, 'Field\Type\IHumanReadable'),
            ]
        );
    }
}