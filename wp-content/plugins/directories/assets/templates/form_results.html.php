<?php if (!empty($results)):?>
<?php   foreach (array('success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info', 'notice' => '') as $level => $color):?>
<?php     if (!empty($results[$level])): $is_first = true;?>
<div class="<?php if ($color):?><?php echo DRTS_BS_PREFIX;?>alert <?php echo DRTS_BS_PREFIX;?>alert-<?php echo $color;?><?php endif;?>">
<?php       foreach ((array)$results[$level] as $message):?>
<?php         if ($is_first): $is_first = false;?>
    <p class="<?php echo DRTS_BS_PREFIX;?>p-0 <?php echo DRTS_BS_PREFIX;?>m-0"><?php echo $message;?></p>
<?php         else:?>
    <p class="<?php echo DRTS_BS_PREFIX;?>p-0 <?php echo DRTS_BS_PREFIX;?>m-0 <?php echo DRTS_BS_PREFIX;?>pt-2"><?php echo $message;?></p>
<?php         endif?>
<?php       endforeach;?>
</div>
<?php     endif;?>
<?php   endforeach;?>
<?php endif;?>