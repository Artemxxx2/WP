<?php
$vars = $CONTEXT->getAttributes();
if (empty($entities)) {
    $this->display('view_entities_none', $vars);
    return;
}
$pre_rendered = $this->Entity_Display_preRender($entities, $settings['display']);
if (!empty($pre_rendered['html'])) echo implode(PHP_EOL, $pre_rendered['html']);
?>
<?php if (!empty($settings['no_js'])
    || isset($settings['masonry_cols']) // compat with <1.3.12
):?>
    <div class="drts-dw-container">
        <div class="dw <?php echo $this->H($view->getNoJsGridClass($bundle, $settings)); ?>"
             id="<?php echo substr($CONTEXT->getContainer(), 1); ?>-view-dw-container" style="display:none;">
            <?php foreach ($pre_rendered['entities'] as $entity): ?>
                <div class="dw-panel drts-view-entity-container">
                    <div class="dw-panel__content">
                        <?php $this->Entity_Display($entity, $settings['display'], $vars, ['cache' => $settings['display_cache']]); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <script type="text/javascript">
<?php   if (\SabaiApps\Directories\Request::isXhr()):?>
jQuery(function($) {
<?php   else:?>
document.addEventListener('DOMContentLoaded', function(event) {
<?php   endif;?>
    jQuery('<?php echo $CONTEXT->getContainer();?>-view-dw-container').css('display', 'block');
});
</script>
<?php else:?>
<div id="<?php echo substr($CONTEXT->getContainer(), 1);?>-view-masonry-container" class="drts-view-entities-masonry-container">
<?php   foreach ($pre_rendered['entities'] as $entity):?>
    <div class="drts-view-entity-container">
        <?php $this->Entity_Display($entity, $settings['display'], $vars, ['cache' => $settings['display_cache']]);?>
    </div>
<?php   endforeach;?>
</div>
<script type="text/javascript">
<?php   if (\SabaiApps\Directories\Request::isXhr()):?>
jQuery(function($) {
<?php   else:?>
document.addEventListener('DOMContentLoaded', function(event) {
<?php   endif;?>
    DRTS.View.masonry('<?php echo $CONTEXT->getContainer();?>-view-masonry-container', <?php echo intval($settings['js_grid']['masonry_cols']);?>);
});
</script>
<?php endif;?>
