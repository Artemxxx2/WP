<?php
$vars = $CONTEXT->getAttributes();
if (empty($entities)) {
    $this->display('view_entities_none', $vars);
    return;
}
$pre_rendered = $this->Entity_Display_preRender($entities, $settings['display']);
if (!empty($pre_rendered['html'])) echo implode(PHP_EOL, $pre_rendered['html']);

// Init slider options
if (empty($settings['carousel_columns'])) {
    $settings['carousel_columns'] = 1;
}
if ($settings['carousel_columns'] >= 3) {
    if (!isset($settings['carousel_responsive'])) {
        $settings['carousel_responsive'] = [
            [
                'breakpoint' => 960,
                'settings' => [
                    'slidesToShow' => 3,
                ],
            ],
            [
                'breakpoint' => 720,
                'settings' => [
                    'slidesToShow' => 2,
                    'dots' => false,
                ],
            ],
            [
                'breakpoint' => 540,
                'settings' => [
                    'slidesToShow' => 1,
                    'dots' => false,
                ],
            ]
        ];
    }
}
$slick_options = [
    'dots' => !empty($settings['carousel_pager']),
    'autoplay' => !empty($settings['carousel_auto']),
    'autoplaySpeed' => isset($settings['carousel_auto_speed']) ? $settings['carousel_auto_speed'] : 3000,
    'speed' => 300,
    'slidesToShow' => $settings['carousel_columns'],
    'slidesToScroll' => empty($settings['carousel_scroll']) ? 1 : $settings['carousel_scroll'],
    'arrows' => !isset($settings['carousel_controls']) || !empty($settings['carousel_controls']),
    'fade' => !empty($settings['carousel_fade']) && $settings['carousel_columns'] === 1,
    'adaptiveHeight' => !empty($settings['carousel_adaptive_height']),
    'responsive' => isset($settings['carousel_responsive']) ? $settings['carousel_responsive'] : null,
    'focusOnSelect' => false,
    'infinite' => !isset($settings['carousel_infinite']) || $settings['carousel_infinite'],
    'lazyLoad' => empty($settings['carousel_lazyload']) ? false : 'progressive',
    'rtl' => $this->Platform()->isRtl(),
    'pauseOnHover' => true,
    'swipe' => true,
    'centerMode' => !empty($settings['carousel_center']),
];
$carousel_id = substr($CONTEXT->getContainer(), 1) . '-slider-carousel';
?>
<div id="<?php echo $carousel_id;?>" class="drts-slider-carousel<?php if ($slick_options['arrows']):?> drts-slider-carousel-with-arrows<?php endif;?>" data-slick-options="<?php echo $this->H(json_encode($slick_options));?>">
    <div class="drts-slider-carousel-slider">
<?php   foreach ($pre_rendered['entities'] as $entity):?>
        <div class="drts-slider-carousel-item">
            <?php $this->Entity_Display($entity, $settings['display'], $vars, ['cache' => $settings['display_cache']]);?>
        </div>
<?php endforeach;?>
    </div>
    <div class="drts-slider-carousel-arrows"></div>
</div>
<script type="text/javascript">
<?php if (\SabaiApps\Directories\Request::isXhr()):?>
jQuery(function($) {
<?php else:?>
document.addEventListener('DOMContentLoaded', function() { var $ = jQuery;
<?php endif;?>
    setTimeout(function() {
        DRTS.Slider.carousel('#<?php echo $carousel_id;?>');
    }, <?php echo empty($settings['carousel_delay']) ? 150 : intval($settings['carousel_delay']);?>);
});
</script>