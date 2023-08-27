<?php
namespace SabaiApps\Directories\Component\Field\Renderer;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

class ImageRenderer extends AbstractRenderer
{
    protected function _fieldRendererInfo()
    {
        return [
            'field_types' => ['wp_image', 'file_image'],
            'default_settings' => [
                'size' => 'thumbnail',
                'width' => 100,
                'height' => 0,
                'cols' => 4,
                'gutter_width' => 'none',
                'link' => 'photo',
                'link_image_size' => 'large',
                'link_target' => '',
            ],
            'separatable' => false,
            'no_imageable' => true,
        ];
    }

    public function fieldRendererSettingsForm(IField $field, array $settings, array $parents = [])
    {
        $form = parent::fieldRendererSettingsForm($field, $settings, $parents);
        if (isset($form['_limit'])) {
            $background_setting_selector = sprintf('[name="%s[_render_background]"]', $this->_application->Form_FieldName($parents));
            $form['_limit']['#states']['invisible'][$background_setting_selector] = ['type' => 'checked', 'value' => true];
        }

        return $form;
    }

    protected function _fieldRendererSettingsForm(IField $field, array $settings, array $parents = [])
    {
        $background_setting_selector = sprintf('[name="%s[_render_background]"]', $this->_application->Form_FieldName($parents));
        $form = [
            'size' => [
                '#title' => __('Image size', 'directories'),
                '#type' => 'select',
                '#options' => $this->_getImageSizeOptions(),
                '#default_value' => $settings['size'],
                '#weight' => 1,
            ],
            'width' => [
                '#title' => __('Image width', 'directories'),
                '#type' => 'slider',
                '#min_value' => 1,
                '#max_value' => 100,
                '#integer' => true,
                '#default_value' => $settings['width'],
                '#weight' => 2,
                '#field_suffix' => '%',
                '#states' => [
                    'invisible' => [
                        $background_setting_selector => ['type' => 'checked', 'value' => true],
                    ],
                ],
            ],
            'height' => [
                '#title' => __('Image height', 'directories'),
                '#type' => 'slider',
                '#min_value' => 0,
                '#min_text' => __('Auto', 'directories'),
                '#max_value' => 500,
                '#integer' => true,
                '#default_value' => $settings['height'],
                '#weight' => 2,
                '#field_suffix' => 'px',
            ],
            'link' => [
                '#type' => 'select',
                '#title' => __('Link image to', 'directories'),
                '#options' => $this->_getImageLinkTypeOptions($field->Bundle),
                '#default_value' => $settings['link'],
                '#weight' => 5,
            ],
            'link_image_size' => [
                '#title' => __('Linked image size', 'directories'),
                '#type' => 'select',
                '#options' => $this->_getLinkedImageSizeOptions(),
                '#default_value' => $settings['link_image_size'],
                '#states' => [
                    'visible' => [
                        sprintf('select[name="%s[link]"]', $this->_application->Form_FieldName($parents)) => ['value' => 'photo'],
                    ],
                ],
                '#weight' => 6,
            ],
            'link_target' => [
                '#title' => __('Open link in', 'directories'),
                '#type' => 'select',
                '#options' => $this->_getLinkTargetOptions(),
                '#default_value' => $settings['link_target'],
                '#states' => [
                    'visible' => [
                        sprintf('select[name="%s[link]"]', $this->_application->Form_FieldName($parents)) => ['type' => 'one', 'value' => ['parent', 'page', 'photo']],
                    ],
                ],
                '#weight' => 7,
            ],
        ];
        if ($field->getFieldMaxNumItems() !== 1) {
            $form['cols'] = [
                '#title' => __('Number of columns', 'directories'),
                '#type' => 'select',
                '#options' => [1 => 1, 2 => 2, 3 => 3, 4 => 4, 6 => 6, 12 => 12],
                '#default_value' => $settings['cols'],
                '#weight' => 3,
                '#states' => [
                    'invisible' => [
                        $background_setting_selector => ['type' => 'checked', 'value' => true],
                    ],
                ],
            ];
            $form['gutter_width'] = [
                '#title' => __('Space between photos', 'directories'),
                '#type' => 'select',
                '#default_value' => $settings['gutter_width'],
                '#weight' => 4,
                '#options' => [
                    'none' => __('None', 'directories'),
                    'xs' => __('Small', 'directories'),
                    'sm' => __('Default', 'directories'),
                    'md' => __('Medium', 'directories'),
                    'lg' => __('Large', 'directories'),
                ],
                '#states' => [
                    'invisible_or' => [
                        $background_setting_selector => ['type' => 'checked', 'value' => true],
                        sprintf('select[name="%s[cols]"]', $this->_application->Form_FieldName($parents)) => ['value' => 1],
                    ],
                ],
            ];
        }
        $form['_render_background'] = [
            '#type' => 'checkbox',
            '#title' => __('Render as background image', 'directories'),
            '#default_value' => !empty($settings['_render_background']),
            '#horizontal' => true,
            '#weight' => 250,
        ];
        $form['_hover_zoom'] = [
            '#type' => 'checkbox',
            '#title' => __('Zoom on hover', 'directories'),
            '#default_value' => !empty($settings['_hover_zoom']),
            '#horizontal' => true,
            '#weight' => 251,
        ];
        $form['_hover_brighten'] = [
            '#type' => 'checkbox',
            '#title' => __('Brighten on hover', 'directories'),
            '#default_value' => !empty($settings['_hover_brighten']),
            '#horizontal' => true,
            '#weight' => 252,
        ];

        return $form;
    }
    
    protected function _getImageLinkTypeOptions(Bundle $bundle)
    {
        $ret = [
            'none' => __('Do not link', 'directories'),
            'page' => __('Link to post', 'directories'),
        ];
        if (!empty($bundle->info['parent'])) {
            $ret['parent'] = __('Link to parent post', 'directories');
        }
        $ret['photo'] = __('Single image', 'directories');

        return $ret;
    }
    
    protected function _getLinkedImageSizeOptions()
    {
        return [
            'medium' => __('Medium size', 'directories'),
            'large' => __('Large size', 'directories'),
            'full' => __('Original size', 'directories'),
        ];
    }

    protected function _fieldRendererRenderField(IField $field, array &$settings, IEntity $entity, array $values, $more = 0)
    {
        $permalink_url = null;
        switch ($settings['link']) {
            case 'page':
                $permalink_url = $this->_application->Entity_PermalinkUrl($entity);
                break;
            case 'parent':
                if ($parent_entity = $this->_application->Entity_ParentEntity($entity, false)) {
                    $permalink_url = $this->_application->Entity_PermalinkUrl($parent_entity);
                }
                break;
            default:
        }
        $no_image = empty($values)
            || (!$field_type_impl = $this->_application->Field_Type($field->getFieldType(), true));
        $target = $this->_getLinkTarget($field, $settings);
        
        // Return image and link URLs only for rendering field as background image
        if (!empty($settings['_render_background'])) {
            $ret = [
                'html' => ' ', // add a space so that the display element is rendered
                'target' => $target,
                'class' => 'drts-display-element-with-background',
            ];
            if ($no_image) {
                $image_url = $this->_application->System_NoImage(null, true, $entity);
                $ret['class'] .= ' drts-display-element-with-background-no-image';
                $ret['url'] = in_array($settings['link'], ['page', 'parent']) ? $permalink_url : null;
            } else {
                $image_url = $this->_getImageUrl($field, $settings, $values[0], $settings['size']);
                if (!empty($settings['_hover_zoom'])
                    || !empty($settings['_hover_brighten'])
                ) {
                    $ret['class'] .= ' drts-display-element-hover-effect';
                    if (!empty($settings['_hover_zoom'])) {
                        $ret['class'] .= ' drts-display-element-hover-zoom';
                    }
                    if (!empty($settings['_hover_brighten'])) {
                        $ret['class'] .= ' drts-display-element-hover-brighten';
                    }
                }
                $ret['url'] = $this->_getImageLinkUrl($field, $settings, $values[0], $permalink_url, $image_url);
            }
            $ret['style'] = 'background-image:url(' . $this->_application->H($image_url) . ');';
            if (!empty($settings['height'])) {
                $ret['style'] .= 'min-height:' . intval($settings['height']) . 'px;';
            }

            return $ret;
        }

        if ($no_image) {
            return $this->_getEmptyImage($settings, $permalink_url, $target, $entity);
        }
        
        if ($field->getFieldMaxNumItems() !== 1) {
            $col_md = $col = 12 / $settings['cols'];
            if ($col_md < 6) {
                $col = 6;
            }
        } else {
            $col_md = $col = 12;
        }
        if ($col_md === 12 && count($values) === 1) {
            if (!$image = $this->_getImage($field, $settings, $values[0], $permalink_url, $target)) {
                return $this->_getEmptyImage($settings, $permalink_url, $target, $entity);
            }
            return isset($image['url']) ? '<a href="' . $image['url'] . '" target="' . $target . '">' . $image['html'] . '</a>' : $image['html'];
        }
        
        //unset($settings['_hover_zoom'], $settings['_hover_brighten']); // disable hover effects if multiple images
        $ret = [];
        foreach ($values as $value) {
            if (!$image = $this->_getImage($field, $settings, $value, $permalink_url, $target)) continue;


            $ret[] = sprintf(
                '<div class="drts-col-md-%1$d drts-col-%2$d">%3$s</div>',
                $col_md,
                $col,
                isset($image['url']) ? '<a href="' . $image['url'] . '" target="' . ($target === '_blank' ? $target : '_self') . '">' . $image['html'] . '</a>' : $image['html']
            );
        }
        if (empty($ret)) {
            return $this->_getEmptyImage($settings, $permalink_url, $target, $entity);
        }
        return '<div class="drts-row drts-y-gutter drts-gutter-' . $settings['gutter_width'] . '">' . implode(PHP_EOL, $ret) . '</div>';
    }

    protected function _getLinkTarget(IField $field, array $settings)
    {
        return  !empty($settings['link_target']) && in_array($settings['link'], ['page', 'parent', 'photo']) ? $settings['link_target'] : null;
    }

    protected function _getEmptyImage(array &$settings, $permalinkUrl, $target, IEntity $entity)
    {
        return [
            'url' => $permalinkUrl,
            'target' => $target,
            'html' => '<div class="drts-no-image">' . $this->_application->System_NoImage($settings['size'], false, $entity) . '</div>',
        ];
    }
    
    protected function _getImage(IField $field, array $settings, $value, $permalinkUrl, $target)
    {
        if (!$url = $this->_getImageUrl($field, $settings, $value, $settings['size'])) return '';

        $image = sprintf(
            '<img src="%s" title="%s" alt="%s" style="width:%d%%;height:%s" />',
            $url,
            $this->_application->H($this->_getImageTitle($field, $settings, $value)),
            $this->_application->H($this->_getImageAlt($field, $settings, $value)),
            $settings['width'],
            empty($settings['height']) ? 'auto' : intval($settings['height']) . 'px'
        );

        if (!empty($settings['_hover_zoom'])
            || !empty($settings['_hover_brighten'])
        ) {
            $classes = ['drts-display-element-hover-effect'];
            if (!empty($settings['_hover_zoom'])) {
                $classes[] = 'drts-display-element-hover-zoom';

            }
            if (!empty($settings['_hover_brighten'])) {
                $classes[] = 'drts-display-element-hover-brighten';
            }
            $image = '<div class="' . implode(' ', $classes) . '">' . $image . '</div>';
        }

        return [
            'html' => $image,
            'url' => $this->_getImageLinkUrl($field, $settings, $value, $permalinkUrl, $url),
            'target' => $target,
        ];
    }

    protected function _getImageLinkUrl(IField $field, array $settings, $value, $permalinkUrl, $imageUrl)
    {
        if (in_array($settings['link'], ['page', 'parent'])) return $permalinkUrl;

        if ($settings['link'] === 'photo') {
            if ($settings['size'] == $settings['link_image_size']) return $imageUrl;

            return $this->_getImageUrl($field, $settings, $value, $settings['link_image_size']);
        }
    }

    protected function _getImageUrl(IField $field, array $settings, $value, $size)
    {
        return $this->_application->Field_Type($field->getFieldType())->fieldImageGetUrl($value, $size);
    }

    protected function _getImageAlt(IField $field, array $settings, $value)
    {
        return $this->_application->Field_Type($field->getFieldType())->fieldImageGetAlt($value);
    }

    protected function _getImageTitle(IField $field, array $settings, $value)
    {
        return $this->_application->Field_Type($field->getFieldType())->fieldImageGetTitle($value);
    }
    
    protected function _fieldRendererReadableSettings(IField $field, array $settings)
    {
        $ret = [
            'size' => [
                'label' => __('Image size', 'directories'),
                'value' => $this->_getImageSizeOptions()[$settings['size']],
            ],
            'width' => [
                'label' => __('Image width', 'directories'),
                'value' => $settings['width'] . '%',
            ],
            'height' => [
                'label' => __('Image height', 'directories'),
                'value' => empty($settings['height']) ? 'auto' : $settings['height'] . 'px',
            ],
            'link' => [
                'label' => __('Link image to', 'directories'),
                'value' => $this->_getImageLinkTypeOptions($field->Bundle)[$settings['link']],
            ],
        ];
        if ($settings['link'] === 'photo') {
            $ret['link_image_size'] = [
                'label' => __('Linked image size', 'directories'),
                'value' => $this->_getLinkedImageSizeOptions()[$settings['link_image_size']],
            ];
        }          
        if ($field->getFieldMaxNumItems() !== 1) {
            $ret['cols'] = [
                'label' => __('Number of columns', 'directories'),
                'value' => $settings['cols'],
            ];
        }
        
        return $ret;
    }
}
