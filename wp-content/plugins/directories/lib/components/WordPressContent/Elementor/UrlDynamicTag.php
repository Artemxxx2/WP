<?php
namespace SabaiApps\Directories\Component\WordPressContent\Elementor;

use Elementor\Controls_Manager;
use Elementor\Core\DynamicTags\Data_Tag;
use ElementorPro\Modules\DynamicTags\Module;

class UrlDynamicTag extends Data_Tag
{
    use DynamicTagTrait;

    public function get_name()
    {
        return 'drts-url';
    }

    public function get_title()
    {
        return 'Directories' . ' - ' .  __('URL Field', 'directories');
    }

    public function get_categories()
    {
        return [
            Module::URL_CATEGORY,
        ];
    }

    public function get_value(array $options = [])
    {
        if ((!$field_key = $this->get_settings('field'))
            || (!$entity = $this->_getEntity())
            || (!$field = $this->_getDynamicTagField($entity, $field_key))
            || (!$value = $entity->getSingleFieldValue($field->getFieldName()))
        ) return;

        return wp_kses_post($value);
    }

    protected function register_controls()
    {
        $this->add_control(
            'field',
            [
                'label' => __('Field name', 'directories'),
                'type' => Controls_Manager::SELECT,
                'groups' => $this->_getDynamicTagFields(['url']),
            ]
        );
    }
}