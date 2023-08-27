<?php
namespace SabaiApps\Directories\Component\Slider\ViewMode;

use SabaiApps\Directories\Component\View\Mode\AbstractMode;
use SabaiApps\Directories\Component\Entity\Model\Bundle;

class CarouselViewMode extends AbstractMode
{
    protected function _viewModeInfo()
    {
        return array(
            'label' => _x('Carousel slider', 'view mode label', 'directories-pro'),
            'default_settings' => array(
                'template' => $this->_application->getPlatform()->getAssetsDir('directories-pro') . '/templates/slider_carousel_entities',
                'display' => 'summary',
                'carousel_columns' => 4,
                'carousel_scroll' => 1,
                'carousel_pager' => true,
                'carousel_auto' => true,
                'carousel_controls' => true,
                'carousel_auto_speed' => 3000,
                'carousel_center' => false,
                'carousel_fade' => false,
            ),
            'default_display' => 'summary',
            'features_disabled' => ['pagination'],
        );
    }

    public function viewModeAssets(Bundle $bundle, array $settings)
    {
        return [
            'js_files' => [
                'slick' => ['slick.custom.min.js', ['jquery'], 'directories-pro', true],
                'drts-slider-carousel' => ['slider-carousel.min.js', ['drts', 'slick'], 'directories-pro', true],
            ],
            'css_files' => [
                'drts-slider-carousel' => ['slider-carousel.min.css', [], 'directories-pro']
            ],
        ];
    }

    public function viewModeSupports(Bundle $bundle)
    {
        return parent::viewModeSupports($bundle)
            && !empty($bundle->info['public'])
            && empty($bundle->info['internal']);
    }
    
    public function viewModeSettingsForm(Bundle $bundle, array $settings, array $parents = [])
    {
        $is_single_slide = array(
            sprintf('[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, array('carousel_columns')))) => array('value' => 1),
        );
        return [
            'carousel_columns' => array(
                '#title' => __('Number of columns', 'directories-pro'),
                '#type' => 'slider',
                '#min_value' => 1,
                '#max_value' => 6,
                '#default_value' => $settings['carousel_columns'],
                '#integer' => true,
                '#horizontal' => true,
            ),
            'carousel_scroll' => array(
                '#title' => __('Number of columns to scroll', 'directories-pro'),
                '#type' => 'slider',
                '#min_value' => 1,
                '#max_value' => 6,
                '#default_value' => $settings['carousel_scroll'],
                '#integer' => true,
                '#horizontal' => true,
                '#states' => [
                    'visible' => [
                        sprintf('[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['carousel_center']))) => ['type' => 'checked', 'value' => false],
                    ],
                ],
            ),
            'carousel_pager' => array(
                '#title' => __('Show slide indicators', 'directories-pro'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['carousel_pager']),
                '#horizontal' => true,
            ),
            'carousel_controls' => array(
                '#title' => __('Show prev/next arrows', 'directories-pro'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['carousel_controls']),
                '#horizontal' => true,
            ),
            'carousel_auto' => array(
                '#title' => __('Autoplay slides', 'directories-pro'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['carousel_auto']),
                '#horizontal' => true,
            ),
            'carousel_auto_speed' => array(
                '#title' => __('Autoplay speed in milliseconds', 'directories-pro'),
                '#type' => 'slider',
                '#integer' => true,
                '#min_value' => 500,
                '#max_value' => 10000,
                '#default_value' => $settings['carousel_auto_speed'],
                '#horizontal' => true,
                '#step' => 500,
                '#states' => array(
                    'visible' => array(
                        sprintf('[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, array('carousel_auto')))) => array('type' => 'checked', 'value' => 1),
                    ),
                ),
            ),
            'carousel_center' => array(
                '#title' => __('Enable centered view', 'directories-pro'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['carousel_center']),
                '#horizontal' => true,
            ),
            'carousel_fade' => array(
                '#title' => __('Fade in/out slides', 'directories-pro'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['carousel_fade']),
                '#horizontal' => true,
                '#states' => array(
                    'visible' => $is_single_slide,
                ),
            ),
        ];
    }
}
