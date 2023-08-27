<?php $this->Action('view_before_entity', array($entity, $display, $CONTEXT));?>
<?php $this->Entity_Display($entity, $display, $CONTEXT->getAttributes(), ['pre_render' => true]);?>
<?php $this->Action('view_after_entity', array($entity, $display, $CONTEXT));?>