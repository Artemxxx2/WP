<?php
namespace SabaiApps\Directories\Component\WordPressContent\Elementor;

use Elementor\Controls_Manager;
use Elementor\Core\DynamicTags\Tag;
use ElementorPro\Modules\DynamicTags\Module;

class DateDynamicTag extends Tag
{
    use DynamicTagTrait;

    public function get_name()
    {
        return 'drts-date';
    }

    public function get_title()
    {
        return 'Directories' . ' - ' .  __('Date Field', 'directories');
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
            || (!$value = $entity->getSingleFieldValue($field->getFieldName()))
        ) return;

        switch ($format = $this->get_settings('format')) {
            case 'custom':
                $date_format = $format;
                break;
            case 'default':
                $date_format = null;
                break;
            default:
                $date_format = $format;
        }

        echo wp_kses_post(drts()->System_Date($value, false, $date_format));
    }

    protected function register_controls()
    {
        $this->add_control(
            'field',
            [
                'label' => __('Field name', 'directories'),
                'type' => Controls_Manager::SELECT,
                'groups' => $this->_getDynamicTagFields(['date']),
            ]
        );
        $this->add_control(
            'format',
            [
                'label' => __('Date format', 'directories'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'default' => __('Default', 'directories'),
                    'F j, Y' => date('F j, Y'),
                    'Y-m-d' => date('Y-m-d'),
                    'm/d/Y' => date('m/d/Y'),
                    'd/m/Y' => date('d/m/Y'),
                    'custom' => __('Custom date format', 'directories'),
                ],
                'default' => 'default',
            ]
        );
        $this->add_control(
            'custom_format',
            [
                'label' => __('Custom Format', 'directories'),
                'default' => '',
                'description' => __('Enter the data/time format string suitable for input to PHP date() function.', 'directories'),
                'condition' => [
                    'format' => 'custom',
                ],
            ]
        );
    }
}