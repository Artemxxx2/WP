<?php
if ($this->Map_Gdpr_IsConsentRequired()) {
    echo $this->Map_Gdpr_consentForm();
    return;
}

$markers = [];
if ($display) {
    $pre_rendered = $this->Entity_Display_preRender($entities, $display);
    foreach ($pre_rendered['entities'] as $entity) {
        ob_start();
        $this->Entity_Display($entity, $display, $CONTEXT->getAttributes());
        $content = ob_get_clean();
        foreach ($this->Map_Marker($entity, $field, $settings, $content) as $marker) {
            $markers[] = $marker;
        }
    }
} else {
    foreach ($entities as $entity) {
        foreach ($this->Map_Marker($entity, $field, $settings) as $marker) {
            $markers[] = $marker;
        }
    }
}
$settings += [
    'text_control_fullscreen' => __('Full screen', 'directories'),
    'text_control_exit_fullscreen' => __('Exit full screen', 'directories'),
    'text_control_search_this_area' => __('Search this area', 'directories'),
    'text_control_search_my_location' => __('Search my location', 'directories'),
];
?>
<script type="text/javascript">
<?php if (\SabaiApps\Directories\Request::isXhr()):?>
jQuery(function($) {
<?php else:?>
document.addEventListener('DOMContentLoaded', function() {
    var $ = jQuery;
<?php endif;?>
    var settings = <?php echo $this->JsonEncode($settings);?>;
    var map = DRTS.Map.api.getMap('<?php echo $CONTEXT->getContainer();?>', settings)
        .setMarkers(<?php echo $this->JsonEncode($markers);?>)
        .draw(<?php if (isset($draw_options)):?><?php echo $this->JsonEncode($draw_options);?><?php endif;?>);
});
</script>
<div class="drts-map-container">
    <?php $this->Action('map_before_map', [$CONTEXT]);?>
    <div class="drts-map-map" style="height:<?php echo intval($settings['height']);?>px;"></div>
    <?php $this->Action('map_after_map', [$CONTEXT]);?>
</div>