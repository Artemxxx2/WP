<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

class CardHelper
{
    public function help(Application $application, IEntity $entity, array $options = [])
    {
        $options += [
            'width' => 100,
            'no_border' => false,
            'cover_field' => null,
            'cover_field_size' => 'medium',
            'cover_height' => 170,
            'thumbnail_field' => null,
            'thumbnail_width' => 60,
            'text_align' => 'center',
            'title_field' => null,
            'title_font_weight' => 'bold',
            'title_font_size' => 1.4,
            'title_font_size_type' => 'rel',
            'title_font_style_italic' => false,
            'subtitle_field' => null,
            'subtitle_font_weight' => 'light',
            'subtitle_font_size' => 0.9,
            'subtitle_font_size_type' => 'rel',
            'subtitle_font_style_italic' => false,
            'content' => null,
        ];
        if (isset($options['title_font'])
            && is_array($options['title_font'])
        ) {
            $options['title_font_weight'] = $options['title_font']['font_weight'];
            $options['title_font_size'] = empty($options['title_font']['font_size']) ? null : $options['title_font']['font_size_' . $options['title_font']['font_size']];
            $options['title_font_size_type'] = $options['title_font']['font_size'];
            $options['title_font_style_italic'] = !empty($options['title_font']['font_style']) && $options['title_font']['font_style'];
        }
        if (!empty($options['subtitle_field'])
            && isset($options['subtitle_font'])
            && is_array($options['subtitle_font'])
        ) {
            $options['subtitle_font_weight'] = $options['subtitle_font']['font_weight'];
            $options['subtitle_font_size'] = empty($options['subtitle_font']['font_size']) ? null : $options['subtitle_font']['font_size_' . $options['subtitle_font']['font_size']];
            $options['subtitle_font_size_type'] = $options['subtitle_font']['font_size'];
            $options['subtitle_font_style_italic'] = !empty($options['subtitle_font']['font_style']) && $options['subtitle_font']['font_style'];
        }

        // Init card
        $html = ['<div class="' . DRTS_BS_PREFIX . 'card' . (empty($options['no_border']) ? '' : ' ' . DRTS_BS_PREFIX . 'border-0') . '" style="width:' . $options['width'] . '%;">'];

        // Add cover field
        if (!empty($options['cover_field'])) {
            if ((!$field = $application->Entity_Field($entity, $options['cover_field']))
                || (!$value = $entity->getSingleFieldValue($field->getFieldName()))
                || (!$url = $application->Field_Type($field->getFieldType())->fieldImageGetUrl($value, $options['cover_field_size']))
            ) {
                $url = $application->System_NoImage($options['cover_field_size'], true, $entity);
            }
            $html[] = '<div class="drts-display-element-card-cover" style="background-image:url(' . $url . ');height:' . (int)$options['cover_height'] . 'px"></div>';
        }

        // Add thumbnail field
        if (!empty($options['thumbnail_field'])
            && ($field = $application->Entity_Field($entity, $options['thumbnail_field']))
            && ($value = $entity->getSingleFieldValue($field->getFieldName()))
            && ($url = $application->Field_Type($field->getFieldType())->fieldImageGetIconUrl($value, 'xl'))
        ) {
            $width_height = 'width:' . $options['thumbnail_width'] . 'px;height:' . $options['thumbnail_width'] . 'px';
            $html[] = '<div class="drts-display-element-card-thumbnail ' . DRTS_BS_PREFIX . 'mx-auto ' . DRTS_BS_PREFIX . 'mb-n3" style="' . $width_height . ';margin-top:-' . ($options['thumbnail_width']/2) . 'px;">'
                . '<img class="' . DRTS_BS_PREFIX . 'rounded-circle" style="' . $width_height . ';border-width:' . round($options['thumbnail_width']/20, 2) . 'px" src="' . $url . '" alt="' . $application->H($entity->getTitle()) . '" /></div>';
        }

        // Start body
        $html[] = '<div class="' . DRTS_BS_PREFIX . 'card-body ' . DRTS_BS_PREFIX . 'text-' . $options['text_align'] . '">';

        // Add title
        if ($title = $this->_getTitle($application, $entity, $options)) {
            $title_classes = [];
            if (!empty($options['title_font_weight'])) {
                $title_classes[] = DRTS_BS_PREFIX . 'font-weight-' . $options['title_font_weight'];
            }
            if (!empty($options['title_font_style_italic'])) {
                $title_classes[] = DRTS_BS_PREFIX . 'font-italic';
            }
            $title_styles = [];
            if (!empty($options['title_font_size_type'])
                && !empty($options['title_font_size'])
            ) {
                $title_styles[] = 'font-size:' . $options['title_font_size'] . ($options['title_font_size_type'] === 'rel' ? $application->getPlatform()->getCssRelSize() : 'px');
            }
            $title_class = empty($title_classes) ? '' : implode(' ', $title_classes);
            $title_style = empty($title_styles) ? '' : implode(';', $title_styles);
            $html[] = '<div class="' . DRTS_BS_PREFIX . 'card-title ' . $title_class . '" style="' . $title_style . '">' . $application->Entity_Permalink($entity, ['title' => $title]) . '</div>';
        }

        // Add subtitle
        if (!empty($options['subtitle_field'])
            && ($field = $application->Entity_Field($entity, $options['subtitle_field']))
            && (null !== $subtitle = $application->Field_Type($field->getFieldType())->fieldHumanReadableText($field, $entity))
        ) {
            $subtitle_classes = [];
            if (!empty($options['subtitle_font_weight'])) {
                $subtitle_classes[] = DRTS_BS_PREFIX . 'font-weight-' . $options['subtitle_font_weight'];
            }
            if (!empty($options['subtitle_font_style_italic'])) {
                $subtitle_classes[] = DRTS_BS_PREFIX . 'font-italic';
            }
            $subtitle_styles = [];
            if (!empty($options['subtitle_font_size_type'])
                && !empty($options['subtitle_font_size'])
            ) {
                $subtitle_styles[] = 'font-size:' . $options['subtitle_font_size'] . ($options['title_font_size_type'] === 'rel' ? $application->getPlatform()->getCssRelSize() : 'px');
            }
            $subtitle_class = empty($subtitle_classes) ? '' : implode(' ', $subtitle_classes);
            $subtitle_style = empty($subtitle_styles) ? '' : implode(';', $subtitle_styles);
            $html[] = '<div class="drts-display-element-card-subtitle ' . DRTS_BS_PREFIX . 'mt-n2 ' . DRTS_BS_PREFIX . 'mb-2 ' . $subtitle_class . '" style="' . $subtitle_style . '">' . $application->H($subtitle) . '</div>';
        }

        // Add content
        if (isset($options['content'])) {
            $html[] = $options['content'];
        }

        // End body and card
        $html[] = '</div></div>';

        return implode(PHP_EOL, $html);
    }

    protected function _getTitle(Application $application, IEntity $entity, array $options)
    {
        if (empty($options['title_field'])
            || (!$field = $application->Entity_Field($entity, $options['title_field']))
            || (null === $title = $application->Field_Type($field->getFieldType())->entityFieldTypeGetTitle($field, $entity))
        ) {
            $title = $entity->getTitle();
        }
        return $title;
    }
}