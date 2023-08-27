<?php
namespace SabaiApps\Directories\Component\Display\Element;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display;

class CardElement extends AbstractElement
{
    protected function _displayElementInfo(Entity\Model\Bundle $bundle)
    {
        return [
            'type' => 'utility',
            'label' => _x('Card', 'display element name', 'directories'),
            'description' => __('Show content with card styling', 'directories'),
            'default_settings' => [
                'title_field' => empty($bundle->info['is_user']) ? (empty($bundle->info['is_taxonomy']) ? 'post_title' : 'term_title') : 'user_name',
                'title_font' => [
                    'font_size' => 'rel',
                    'font_size_rel' => 1.4,
                    'font_weight' => 'bold',
                    'font_style' => '',
                ],
                'subtitle_field' => null,
                'subtitle_font' => [
                    'font_size' => 'rel',
                    'font_size_rel' => 0.9,
                    'font_weight' => 'light',
                    'font_style' => '',
                ],
                'cover_field' => null,
                'cover_height' => 170,
                'thumbnail_field' => null,
                'thumbnail_width' => 60,
                'text_align' => 'center',
                'no_border' => false,
            ],
            'child_element_type' => ['field', 'content'],
            'containable' => true,
            'icon' => 'far fa-address-card',
            'designable' => ['margin'],
        ];
    }

    protected function _displayElementSupports(Entity\Model\Bundle $bundle, Display\Model\Display $display)
    {
        return $display->type === 'entity'
            && $this->_application->Entity_Field_options($bundle, ['interface' => 'Entity\FieldType\ITitleFieldType', 'return_disabled' => false]);
    }

    public function displayElementSettingsForm(Entity\Model\Bundle $bundle, array $settings, Display\Model\Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        $image_fields = $this->_application->Entity_Field_options($bundle, ['interface' => 'Field\Type\IImage', 'return_disabled' => true]);
        $title_fields = $this->_application->Entity_Field_options($bundle, ['interface' => 'Entity\FieldType\ITitleFieldType', 'return_disabled' => true]);
        $subtitle_fields = $this->_application->Entity_Field_options($bundle, ['interface' => 'Field\Type\IHumanReadable', 'return_disabled' => true]);
        return [
            'title_field' => [
                '#type' => 'select',
                '#title' => $title = __('Title field', 'directories'),
                '#options' => $title_fields[0],
                '#options_disabled' => array_keys($title_fields[1]),
                '#default_value' => $settings['title_field'],
                '#horizontal' => true,
                '#required' => true,
            ],
            'title_font' => $this->_application->Display_ElementForm_fontSettingsForm($settings['title_font'], array_merge($parents, ['title_font']), true, $title . ' - '),
            'subtitle_field' => [
                '#type' => 'select',
                '#title' => $title = __('Subtitle field', 'directories'),
                '#options' => ['' => '— ' . __('Select field', 'directories') . ' —'] + $subtitle_fields[0],
                '#options_disabled' => array_keys($subtitle_fields[1]),
                '#default_value' => $settings['subtitle_field'],
                '#horizontal' => true,
            ],
            'subtitle_font' => [
                '#states' => [
                    'visible' => [
                        sprintf('select[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['subtitle_field']))) => ['type' => 'selected', 'value' => true],
                    ],
                ],
            ] + $this->_application->Display_ElementForm_fontSettingsForm($settings['subtitle_font'], array_merge($parents, ['subtitle_font']), true, $title . ' - '),
            'cover_field' => [
                '#type' => 'select',
                '#title' => __('Cover image field', 'directories'),
                '#options' => ['' => '— ' . __('Select field', 'directories') . ' —'] + $image_fields[0],
                '#options_disabled' => array_keys($image_fields[1]),
                '#default_value' => $settings['cover_field'],
                '#horizontal' => true,
            ],
            'cover_height' => [
                '#type' => 'slider',
                '#title' => __('Cover image height', 'directories'),
                '#default_value' => $settings['cover_height'],
                '#max_value' => 300,
                '#min_value' => 10,
                '#field_suffix' => 'px',
                '#horizontal' => true,
                '#states' => [
                    'visible' => [
                        sprintf('select[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['cover_field']))) => ['type' => 'selected', 'value' => true],
                    ],
                ],
            ],
            'thumbnail_field' => [
                '#type' => 'select',
                '#title' => __('Thumbnail field', 'directories'),
                '#options' => ['' => '— ' . __('Select field', 'directories') . ' —'] + $image_fields[0],
                '#options_disabled' => array_keys($image_fields[1]),
                '#default_value' => $settings['thumbnail_field'],
                '#horizontal' => true,
            ],
            'thumbnail_width' => [
                '#type' => 'slider',
                '#title' => __('Thumbnail width', 'directories'),
                '#default_value' => $settings['thumbnail_width'],
                '#max_value' => 100,
                '#min_value' => 10,
                '#field_suffix' => 'px',
                '#horizontal' => true,
                '#states' => [
                    'visible' => [
                        sprintf('select[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['thumbnail_field']))) => ['type' => 'selected', 'value' => true],
                    ],
                ],
            ],
            'text_align' => [
                '#type' => 'select',
                '#title' => __('Text alignment', 'directories'),
                '#options' => $this->_getTextAlignOptions(),
                '#default_value' => $settings['text_align'],
                '#horizontal' => true,
            ],
            'no_border' => [
                '#type' => 'checkbox',
                '#title' => __('Remove border', 'directories'),
                '#default_value' => !empty($settings['no_border']),
                '#horizontal' => true,
            ],
        ];
    }

    protected function _getTextAlignOptions()
    {
        return [
            'left' => __('Left', 'directories'),
            'center' => __('Center', 'directories'),
            'right' => __('Right', 'directories'),
        ];
    }
    
    public function displayElementRender(Entity\Model\Bundle $bundle, array $element, $var)
    {
        $settings = $element['settings'];
        $options = [
            'no_border' => !empty($settings['no_border']),
            'cover_field' => $settings['cover_field'],
            'cover_field_size' => 'medium',
            'cover_height' => $settings['cover_height'],
            'thumbnail_field' => $settings['thumbnail_field'],
            'thumbnail_width' => $settings['thumbnail_width'],
            'text_align' => $settings['text_align'],
            'title_field' => $settings['title_field'],
            'title_font' => $settings['title_font'],
            'content' => ($children_html = $this->_renderChildren($bundle, $element['children'], $var)) ? implode(PHP_EOL, $children_html) : null,
        ];
        if (!empty($settings['subtitle_field'])) {
            $options += [
                'subtitle_field' => $settings['subtitle_field'],
                'subtitle_font' => $settings['subtitle_font'],
            ];
        }
        return $this->_application->Entity_Card($var, $options);
    }

    protected function _displayElementReadableInfo(Entity\Model\Bundle $bundle, Display\Model\Element $element)
    {
        $settings = $element->data['settings'];
        $ret = [
            'title_field' => [
                'label' => __('Title field', 'directories'),
                'value' => !empty($settings['title_field']) && ($field = $this->_application->Entity_Field($bundle, $settings['title_field'])) ? $field->getFieldLabel() : '',
            ],
            'subtitle_field' => [
                'label' => __('Subtitle field', 'directories'),
                'value' => !empty($settings['subtitle_field']) && ($field = $this->_application->Entity_Field($bundle, $settings['subtitle_field'])) ? $field->getFieldLabel() : '',
            ],
            'cover_field' => [
                'label' => __('Cover image field', 'directories'),
                'value' => !empty($settings['cover_field']) && ($field = $this->_application->Entity_Field($bundle, $settings['cover_field'])) ? $field->getFieldLabel() : '',
            ],
            'cover_height' => [
                'label' => __('Cover image height', 'directories'),
                'value' => $settings['cover_height'] . 'px',
            ],
            'thumbnail_field' => [
                'label' => __('Thumbnail field', 'directories'),
                'value' => !empty($settings['thumbnail_field']) && ($field = $this->_application->Entity_Field($bundle, $settings['thumbnail_field'])) ? $field->getFieldLabel() : '',
            ],
            'thumbnail_width' => [
                'label' => __('Thumbnail width', 'directories'),
                'value' => $settings['thumbnail_width'] . 'px',
            ],
            'text_align' => [
                'label' => __('Text align', 'directories'),
                'value' => $this->_getTextAlignOptions()[$settings['text_align']],
            ],
            'no_border' => [
                'label' => __('Remove border', 'directories'),
                'value' => !empty($settings['no_border']),
                'is_bool' => true,
            ],
        ];
        return ['settings' => ['value' => $ret]];
    }
}
