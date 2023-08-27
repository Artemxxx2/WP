<?php $this->Platform()->getTemplate()->setContext($CONTEXT)->render();?>
<div id="<?php echo substr($CONTEXT->getContainer(), 1);?>" class="drts drts-main<?php if ($this->Platform()->isRtl()):?> drts-rtl<?php endif;?>">
    <?php echo $CONTENT;?>
</div>