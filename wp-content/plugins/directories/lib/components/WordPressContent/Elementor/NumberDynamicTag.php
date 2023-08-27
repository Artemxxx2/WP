<?php
namespace SabaiApps\Directories\Component\WordPressContent\Elementor;

use Elementor\Controls_Manager;
use Elementor\Core\DynamicTags\Tag;
use ElementorPro\Modules\DynamicTags\Module;
use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Field\IField;

class NumberDynamicTag extends Tag
{
    use DynamicTagTrait;

    public function get_name()
    {
        return 'drts-number';
    }

    public function get_title()
    {
        return 'Directories' . ' - ' .  __('Numeric Field', 'directories');
    }

    public function get_categories()
    {
        return [
            Module::NUMBER_CATEGORY,
            Module::TEXT_CATEGORY,
            Module::POST_META_CATEGORY,
        ];
    }

    public function render()
    {
        if ((!$field_key = $this->get_settings('field'))
            || (!$entity = $this->_getEntity())
            || (!$field = $this->_getDynamicTagField($entity, $field_key))
            || (!$value = $this->_getFieldValue($entity, $field))
            || !is_numeric($value)
        ) return;

        echo wp_kses_post($value);
    }

    protected function _getFieldValue(IEntity $entity, IField $field)
    {
        switch ($field->getFieldType()) {
            case 'price':
                return $entity->getSingleFieldValue($field->getFieldName(), 'value');
            case 'entity_reference':
                return ($referenced_entity = $entity->getSingleFieldValue($field->getFieldName())) ? $referenced_entity->getId() : null;
            default:
                return $entity->getSingleFieldValue($field->getFieldName());
        }
    }

    protected function register_controls()
    {
        $this->add_control(
            'field',
            [
                'label' => __('Field name', 'directories'),
                'type' => Controls_Manager::SELECT,
                'groups' => $this->_getDynamicTagFields(['number', 'price', 'entity_reference']),
            ]
        );
    }
}