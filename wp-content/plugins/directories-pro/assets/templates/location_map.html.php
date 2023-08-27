<?php
$vars = $CONTEXT->getAttributes();
if (empty($entities)) {
    $this->display('view_entities_none', $vars);
    return;
}
?>
<div class="drts-location-entities-map-container"<?php if (!empty($settings['map']['fullscreen_offset'])):?> data-fullscreen-offset="<?php echo $this->H($settings['map']['fullscreen_offset']);?>"<?php endif;?>>
    <?php $this->Action('location_before_map', [$CONTEXT]);?>
    <?php $this->display(
        $this->Platform()->getAssetsDir('directories-pro') . '/templates/map_map',
        [
            'settings' => $settings['map'],
            'field' => $settings['map']['coordinates_field'],
            'display' => isset($settings['display']) ? $settings['display'] : null,
            'draw_options' => $this->Location_DrawMapOptions([], $CONTEXT)
        ] + $CONTEXT->getAttributes()
    );?>
    <?php $this->Action('location_after_map', [$CONTEXT]);?>
</div>
