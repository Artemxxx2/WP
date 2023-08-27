<?php
namespace SabaiApps\Directories\Component\Slider\ViewMode;

use SabaiApps\Directories\Component\View;
use SabaiApps\Directories\Component\Entity\Model\Bundle;

class PhotosViewMode extends View\Mode\AbstractMode
{
    protected function _viewModeInfo()
    {
        return array(
            'label' => _x('Photo slider', 'view mode label', 'directories-pro'),
            'default_settings' => array(
                'template' => $this->_application->getPlatform()->getAssetsDir('directories-pro') . '/templates/slider_photos_entities',
                'photoslider_image_field' => null,
                'photoslider_image_size' => 'thumbnail',
                'photoslider_caption' => true,
                'photoslider_columns' => 4,
                'photoslider_pager' => true,
                'photoslider_auto' => true,
                'photoslider_controls' => true,
                'photoslider_auto_speed' => 3000,
                'photoslider_fade' => false,
                'photoslider_center' => false,
                'photoslider_height' => null,
                'photoslider_padding' => null,
                'photoslider_thumbs' => false,
                'photoslider_thumbs_columns' => 5,
                'photoslider_link' => true,
                'photoslider_zoom' => true,
            ),
            'displays' => [],
            'features_disabled' => ['pagination'],
        );
    }

    public function viewModeSupports(Bundle $bundle)
    {
        return parent::viewModeSupports($bundle)
            && !empty($bundle->info['public'])
            && empty($bundle->info['internal'])
            && $this->imageSettingsForm($bundle);
    }
    
    public function viewModeSettingsForm(Bundle $bundle, array $settings, array $parents = [])
    {
        $is_single_slide = array(
            sprintf('[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, array('photoslider_columns')))) => array('value' => 1),
        );
        $required_func = function($form) { return $form->getValue(['mode']) === $this->_name; };
        return $this->imageSettingsForm($bundle, 'photoslider_', $settings, $required_func, $parents) + array(
            'photoslider_columns' => array(
                '#title' => __('Number of columns', 'directories-pro'),
                '#type' => 'slider',
                '#min_value' => 1,
                '#max_value' => 10,
                '#default_value' => $settings['photoslider_columns'],
                '#integer' => true,
                '#horizontal' => true,
            ),
            'photoslider_pager' => array(
                '#title' => __('Show slide indicators', 'directories-pro'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['photoslider_pager']),
                '#horizontal' => true,
            ),
            'photoslider_controls' => array(
                '#title' => __('Show prev/next arrows', 'directories-pro'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['photoslider_controls']),
                '#horizontal' => true,
            ),
            'photoslider_caption' => array(
                '#title' => __('Show photo captions', 'directories-pro'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['photoslider_caption']),
                '#horizontal' => true,
            ),
            'photoslider_auto' => array(
                '#title' => __('Autoplay slides', 'directories-pro'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['photoslider_auto']),
                '#horizontal' => true,
            ),
            'photoslider_auto_speed' => array(
                '#title' => __('Autoplay speed in milliseconds', 'directories-pro'),
                '#type' => 'slider',
                '#integer' => true,
                '#min_value' => 500,
                '#max_value' => 10000,
                '#default_value' => $settings['photoslider_auto_speed'],
                '#horizontal' => true,
                '#step' => 500,
                '#states' => array(
                    'visible' => array(
                        sprintf('[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, array('photoslider_auto')))) => array('type' => 'checked', 'value' => 1),
                    ),
                ),
            ),
            'photoslider_center' => array(
                '#title' => __('Enable centered view', 'directories-pro'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['photoslider_center']),
                '#horizontal' => true,
            ),
            'photoslider_fade' => array(
                '#title' => __('Fade in/out slides', 'directories-pro'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['photoslider_fade']),
                '#horizontal' => true,
                '#states' => array(
                    'visible' => $is_single_slide,
                ),
            ),
            'photoslider_height' => array(
                '#title' => __('Slider height', 'directories-pro'),
                '#type' => 'number',
                '#default_value' => $settings['photoslider_height'],
                '#horizontal' => true,
                '#field_suffix' => 'px',
                '#integer' => true,
                '#states' => array(
                    'invisible' => $is_single_slide,
                ),
            ),
            'photoslider_padding' => array(
                '#title' => __('Photo padding', 'directories-pro'),
                '#type' => 'number',
                '#default_value' => $settings['photoslider_padding'],
                '#horizontal' => true,
                '#field_suffix' => 'px',
                '#numeric' => true,
                '#states' => array(
                    'invisible' => $is_single_slide,
                ),
            ),
            'photoslider_thumbs' => array(
                '#title' => __('Show thumbnails', 'directories-pro'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['photoslider_thumbs']),
                '#horizontal' => true,
                '#states' => array(
                    'visible' => $is_single_slide,
                ),
            ),
            'photoslider_thumbs_columns' => array(
                '#title' => __('Number of thumbnail columns', 'directories-pro'),
                '#type' => 'slider',
                '#min_value' => 1,
                '#max_value' => 12,
                '#default_value' => $settings['photoslider_thumbs_columns'],
                '#integer' => true,
                '#horizontal' => true,
                '#states' => array(
                    'visible' => $is_single_slide + array(
                        sprintf('[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, array('photoslider_thumbs')))) => array('type' => 'checked', 'value' => 1),
                    ),
                ),
            ),
            'photoslider_link' => array(
                '#title' => __('Link to post', 'directories-pro'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['photoslider_link']),
                '#horizontal' => true,
            ),
            'photoslider_zoom' => array(
                '#title' => __('Zoom on click image', 'directories-pro'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['photoslider_zoom']),
                '#horizontal' => true,
                '#states' => array(
                    'invisible' => array(
                        sprintf('[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, array('photoslider_link')))) => array('type' => 'checked', 'value' => 1),
                    ),
                ),
            ),
        );
    }
}
