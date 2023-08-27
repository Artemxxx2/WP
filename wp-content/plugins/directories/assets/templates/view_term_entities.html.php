<?php $this->Entity_Display($entity, $display, $vars = $CONTEXT->getAttributes(), ['pre_render' => true]);?>
<div id="<?php echo substr($CONTEXT->getContainer(), 1);?>-view-term-entities">
<?php $this->display('view_entities', $vars);?>
</div>