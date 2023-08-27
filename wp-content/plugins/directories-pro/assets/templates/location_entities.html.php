<?php
$container_classes = [
    'drts-location-entities-with-map-' . $settings['map']['position'],
    DRTS_BS_PREFIX . 'row',
    DRTS_BS_PREFIX . 'no-gutters',
];
$entities_container_classes = $map_container_classes = [
    DRTS_BS_PREFIX . 'col',
];
$map_container_classes[] = DRTS_BS_PREFIX . 'mb-3';
if ($settings['map']['position'] === 'bottom') {
    $container_classes[] = DRTS_BS_PREFIX . 'flex-column';
} else {
    $container_classes[] = DRTS_BS_PREFIX . 'flex-column-reverse';
    $map_container_classes[] = DRTS_BS_PREFIX . 'col-sm-' . (int)$settings['map']['span'];
    $entities_container_classes[] = DRTS_BS_PREFIX . 'col-sm-' . (12 - $settings['map']['span']);
    if ($settings['map']['position'] !== 'top') {
        $margin = isset($settings['map']['margin']) ? (int)$settings['map']['margin'] : 2;
        if ($settings['map']['position'] === 'left') {
            $container_classes[] = DRTS_BS_PREFIX . 'flex-sm-row-reverse';
            $entities_container_classes[] = DRTS_BS_PREFIX . 'pl-sm-' . $margin;
        } elseif ($settings['map']['position'] === 'right') {
            $container_classes[] = DRTS_BS_PREFIX . 'flex-sm-row';
            $entities_container_classes[] = DRTS_BS_PREFIX . 'pr-sm-' . $margin;
        }
    }
}
if (!empty($settings['map']['hide_xs'])) {
    $map_container_classes[] = DRTS_BS_PREFIX . 'd-none';
    $map_container_classes[] = DRTS_BS_PREFIX . 'd-sm-block';
}
?>
<div class="drts-location-entities-map-container drts-location-entities-with-map <?php echo $this->H(implode(' ', $container_classes));?>"<?php if (!empty($settings['map']['fullscreen_offset'])):?> data-fullscreen-offset="<?php echo $this->H($settings['map']['fullscreen_offset']);?>" <?php endif;?>>
    <div class="drts-location-entities-container <?php echo $this->H(implode(' ', $entities_container_classes));?>">
        <div class="drts-view-entities drts-location-entities">
            <?php $this->display($settings['map']['template'], $CONTEXT->getAttributes());?>
        </div>
    </div>
    <div class="drts-location-map-container-container <?php echo $this->H(implode(' ', $map_container_classes));?>" data-span="<?php echo intval($settings['map']['span']);?>" data-fullscreen-span="<?php echo intval($settings['map']['fullscreen_span']);?>" data-position="<?php echo $settings['map']['position'];?>"<?php if (!empty($settings['map']['sticky_offset'])):?> data-sticky-scroll-top="<?php echo intval($settings['map']['sticky_offset']);?>"<?php endif;?><?php if (!empty($settings['map']['scroll_offset'])):?> data-scroll-offset="<?php echo intval($settings['map']['scroll_offset']);?>"<?php endif;?> style="height:<?php echo intval($settings['map']['height']);?>px;">
        <?php $this->Action('location_before_map', [$CONTEXT]);?>
        <?php $this->display(
            $this->Platform()->getAssetsDir('directories-pro') . '/templates/map_map',
            [
                'settings' => $settings['map'],
                'field' => isset($settings['map']['coordinates_field']) ? $settings['map']['coordinates_field'] : 'location_address',
                'draw_options' => $this->Location_DrawMapOptions([], $CONTEXT),
                'display' => null,
            ] + $CONTEXT->getAttributes()
        );?>
        <?php $this->Action('location_after_map', [$CONTEXT]);?>
    </div>
</div>
<div class="drts-location-sticky-scroll-stopper"></div>
