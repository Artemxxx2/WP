<?php
namespace SabaiApps\Directories\Component\Slider\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Request;

class PhotosHelper
{
    private static $_count = 0, $_jsLoaded, $_zoomLoaded;

    public function help(Application $application, array $photos, array $options = [], IEntity $entity = null, $addJs = true)
    {
        // Init options
        if (empty($options['photoslider_columns'])) $options['photoslider_columns'] = 4;
        if ($options['photoslider_columns'] > $photo_count = count($photos)) {
            $options['photoslider_columns'] = $photo_count;
        }
        if (!isset($options['photoslider_responsive'])) {
            $options['photoslider_responsive'] = [];
            foreach ([8 => 2400, 7 => 1940, 6 => 1600, 5 => 1300, 4 => 1024] as $column_num => $breakpoint) {
                if ($options['photoslider_columns'] >= $column_num) {
                    $options['photoslider_responsive'][] = [
                        'breakpoint' => $breakpoint,
                        'settings' => [
                            'slidesToShow' => $column_num,
                        ],
                    ];
                }
            }
            if ($options['photoslider_columns'] >= 3) {
                $options['photoslider_responsive'][] = [
                    'breakpoint' => 820,
                    'settings' => [
                        'arrows' => false,
                        'centerPadding' => '40px',
                        'slidesToShow' => 3,
                    ],
                ];
                $options['photoslider_responsive'][] = [
                    'breakpoint' => 540,
                    'settings' => [
                        'arrows' => false,
                        'centerPadding' => '40px',
                        'slidesToShow' => 2,
                        'dots' => false,
                    ],
                ];
                $options['photoslider_responsive'][] = [
                    'breakpoint' => 360,
                    'settings' => [
                        'arrows' => false,
                        'centerPadding' => '40px',
                        'slidesToShow' => 1,
                        'dots' => false,
                    ],
                ];
            }
        }
        $id = isset($options['photoslider_id']) ? $options['photoslider_id'] : 'drts-slider-photos-' . uniqid() . '-' . ++self::$_count;
        $slick_options = [];
        if (!empty($options['photoslider_thumbs'])
            && is_array($options['photoslider_thumbs'])
        ) {
            $thumbs_id = $id . '-thumbs';
            $slick_options['asNavFor'] = '#' . $thumbs_id;
            $thumbs_slick_options = [
                'asNavFor' => '#' . $id,
                'dots' => !empty($options['photoslider_pager']),
                'slidesToShow' => empty($options['photoslider_thumbs_columns']) ? 5 : $options['photoslider_thumbs_columns'],
                'centerMode' => !empty($options['photoslider_thumbs_center']),
                'arrows' => !isset($options['photoslider_controls']) || !empty($options['photoslider_controls']),
                'focusOnSelect' => true,
            ];
            $options['photoslider_pager'] = false;
            $options['photoslider_controls'] = false;
        }
        $slick_options += [
            'centerMode' => !empty($options['photoslider_center']),
            'dots' => !empty($options['photoslider_pager']),
            'autoplay' => !empty($options['photoslider_auto']),
            'autoplaySpeed' => isset($options['photoslider_auto_speed']) ? $options['photoslider_auto_speed'] : 3000,
            'speed' => 260,
            'centerPadding' => '90px',
            'slidesToShow' => $options['photoslider_columns'],
            'arrows' => !isset($options['photoslider_controls']) || !empty($options['photoslider_controls']),
            'fade' => !empty($options['photoslider_fade']) && $options['photoslider_columns'] === 1,
            'adaptiveHeight' => empty($options['photoslider_height']),
            'responsive' => $options['photoslider_responsive'],
            'focusOnSelect' => $options['photoslider_columns'] > 1,
            'infinite' => !isset($options['photoslider_infinite']) || $options['photoslider_infinite'],
            'lazyLoad' => empty($options['photoslider_lazyload']) ? false : 'progressive',
            'rtl' => $is_rtl = $application->getPlatform()->isRtl(),
        ];

        // HTML
        $class = 'drts-slider-photos';
        if (isset($options['photoslider_class'])) $class .= ' ' . $application->H($options['photoslider_class']);
        if (!empty($options['photoslider_height'])) $class .= ' drts-slider-photos-fixed-height';
        if (!isset($options['photoslider_padding'])) $class .= ' drts-slider-photos-photo-no-padding';
        $html = [
            '<div class="' . $class . '">',
            '<div class="drts-slider-photos-main" id="' . $id . '" data-slick-options="' . $application->H($application->JsonEncode($slick_options)) . '" dir="' . ($is_rtl ? 'rtl' : '') . '">',
        ];
        $height = empty($options['photoslider_height']) ? '' : ' style="height:' . $application->H($options['photoslider_height']) . 'px"';
        $padding = empty($options['photoslider_padding']) ? '' : ' style="padding:0 ' . $application->H($options['photoslider_padding']) . 'px"';
        $img_zoom_attr = !empty($options['photoslider_zoom']) ? ' data-action="zoom"' : '';
        foreach (array_keys($photos) as $k) {
            if (!isset($photos[$k]['type'])) $photos[$k]['type'] = 'image';
            $photo = $photos[$k];
            switch ($photo['type']) {
                case 'image':
                    if (!isset($photo['img'])) {
                        if (empty($options['photoslider_allow_no_image'])) {
                            unset($photos[$k]);
                            continue 2;
                        }
                        if (!isset($no_image)) $no_image = $application->System_NoImage(null, false, $entity);
                        $photo['img'] = $no_image;
                    }
                    $item = '';
                    $title = isset($photo['title']) ? $application->H($photo['title']) : '';
                    $do_link = isset($photo['url']) && empty($options['photoslider_zoom']);
                    $alt = isset($photo['alt']) ? $photo['alt'] : ($do_link ? '' : $title);
                    $img = $application->H($photo['img']);
                    if (empty($options['photoslider_lazyload'])) {
                        $item .= '<figure' . $height . '><img src="' . $img . '" alt="' . $alt . '"' . $img_zoom_attr . ' />';
                    } else {
                        $item .= '<figure' . $height . '><img data-lazy="' . $img . '" alt="' . $alt . '"' . $img_zoom_attr . ' />';
                    }
                    if (isset($photo['tag'])) {
                        $item .= '<span class="drts-slider-photos-tag ' . DRTS_BS_PREFIX . 'bg-warning">' . $application->H($photo['tag']) . '</span>';
                    }
                    if (!empty($options['photoslider_caption'])) {
                        $item .= '<figcaption>' . $title . '</figcaption>';
                    }
                    $item .= '</figure>';
                    if ($do_link) {
                        $item = '<a href="' . $application->H($photo['url']) . '" title="' . $title .'">' . $item . '</a>';
                    } elseif (empty($options['photoslider_zoom'])) {
                        $full_img = !empty($photo['full_img']) ? $application->H($photo['full_img']) : $img;
                        $item = '<a href="' . $full_img . '" title="' . $title .'" data-rel="lightbox-gallery-' . $id . '">' . $item . '</a>';
                    }
                    break;
                case 'youtube':
                    $item = sprintf(
                        '<iframe height="400" src="//www.%1$s/embed/%2$s?enablejsapi=1&controls=1&fs=1&iv_load_policy=3&rel=0&showinfo=1&loop=0&start=0" frameborder="0" allowfullscreen></iframe>',
                        empty($options['photoslider_video_privacy_mode']) ? 'youtube.com' : 'youtube-nocookie.com',
                        $photo['video_id']
                    );
                    break;
                case 'vimeo':
                    $item = sprintf(
                        '<iframe height="400" src="//player.vimeo.com/video/%1$s?api=1&byline=0&portrait=1&title=1&background=0&mute=0&loop=0&autoplay=0%2$s" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>',
                        $photo['video_id'],
                        empty($options['photoslider_video_privacy_mode']) ? '' : '&dnt=1'
                    );
                    break;
                case 'video':
                    break;
                default:
                    unset($photos[$k]);
                    continue 2;
            }
            $html[] = '<div' . $padding . ' data-type="' . $photo['type'] . '" class="fitvidsignore">' . $item . '</div>';
        }
        $html[] = '</div>';
        // Add thumbnail nav?
        if (isset($thumbs_id)) {
            $html[] = '<div class="drts-slider-photos-thumbnails" id="' . $thumbs_id . '" data-slick-options="' . $application->H($application->JsonEncode($thumbs_slick_options)) . '">';
            foreach (array_keys($options['photoslider_thumbs']) as $k) {
                if (!isset($photos[$k])) continue;

                $thumb = $options['photoslider_thumbs'][$k];
                if (isset($thumb['img'])) {
                    $img = '<img src="' . $application->H($thumb['img']) . '" alt="" />';
                } else {
                    if (!isset($no_image)) $no_image = $application->System_NoImage('thumbnail', false, $entity);
                    $img = $no_image;
                }
                $html[] = '<div data-type="' . $photos[$k]['type'] . '">' . $img . '</div>';
            }
            $html[] = '</div>';
        }
        $html[] = '</div>';
        $html = $application->Filter('photoslider_html', implode(PHP_EOL, $html));

        if (!self::$_jsLoaded) {
            $application->getPlatform()
                ->addJsFile('slick.custom.min.js', 'slick', ['jquery'], 'directories-pro')
                ->addJsFile('slider-photos.min.js', 'drts-slider-photos', ['drts', 'slick'], 'directories-pro')
                ->addCssFile('slider-photos.min.css', 'drts-slider-photos', [], 'directories-pro');
            self::$_jsLoaded = true;
        }
        if (!empty($options['photoslider_zoom'])
            && !self::$_zoomLoaded
        ) {
            $application->getPlatform()
                ->addJsFile('zoom-vanilla.min.js', 'zoom-vanilla', null, 'directories-pro', true, true)
                ->addCssFile('zoom.min.css', 'zoom', null, 'directories-pro', null, true);
        }

        if ($addJs) {
            if (Request::isXhr()) {
                $html .= '<script type="text/javascript">jQuery(function($) {';
            } else {
                $html .= '<script type="text/javascript">document.addEventListener("DOMContentLoaded", function(event) {';
            }
            $html .= 'setTimeout(function(){DRTS.Slider.photos("#' . $id . '");}, 100);';
            $html .= '});</script>';
        }

        return $html;
    }
}
