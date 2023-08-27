<div class="drts-system-widget-list">
<?php   foreach ($content as $entry): $is_first = !isset($is_first);?>
  <div class="drts-system-widget-entry <?php echo DRTS_BS_PREFIX;?>media<?php if (!$is_first):?> <?php echo DRTS_BS_PREFIX;?>mt-4<?php endif;?>">
<?php     if (is_array($entry)):?>
<?php       if (isset($entry['image'])):?>
<?php         if (isset($entry['url'])):?>
        <a href="<?php echo $this->Url($entry['url']);?>"><img class="<?php echo DRTS_BS_PREFIX;?>d-flex <?php echo DRTS_BS_PREFIX;?>mr-3<?php if (isset($entry['image']['class'])):?> <?php echo $this->H($entry['image']['class']);?><?php endif;?>" src="<?php echo $entry['image']['src'];?>"<?php if (isset($entry['image']['style'])):?> style="<?php echo $this->H($entry['image']['style']);?>"<?php endif;?> alt="<?php if (isset($entry['image']['alt'])):?><?php echo $this->H($entry['image']['alt']);?><?php endif;?>" /></a>
<?php         else:?>
        <img class="<?php echo DRTS_BS_PREFIX;?>d-flex <?php echo DRTS_BS_PREFIX;?>mr-3<?php if (isset($entry['image']['class'])):?> <?php echo $this->H($entry['image']['class']);?><?php endif;?>" src="<?php echo $entry['image']['src'];?>"<?php if (isset($entry['image']['style'])):?> style="<?php echo $this->H($entry['image']['style']);?>"<?php endif;?> alt="<?php if (isset($entry['image']['alt'])):?><?php echo $this->H($entry['image']['alt']);?><?php endif;?>" />
<?php         endif;?>
<?php       endif;?>
    <div class="drts-system-widget-body <?php echo DRTS_BS_PREFIX;?>media-body">
<?php       if (isset($entry['title']) || isset($entry['title_link'])):?>
      <div class="drts-system-widget-title <?php echo DRTS_BS_PREFIX;?>mt-0 <?php echo DRTS_BS_PREFIX;?>mb-1">
<?php         if (isset($entry['title_link'])):?>
        <?php echo $entry['title_link'];?>
<?php         else:?>
        <a href="<?php echo $this->Url($entry['url']);?>"><?php echo empty($entry['title_no_escape']) ? $this->H($entry['title']) : $entry['title'];?></a>
<?php         endif;?>
      </div>
<?php       endif;?>
<?php       if (isset($entry['summary'])):?>
      <p class="drts-system-widget-text <?php echo DRTS_BS_PREFIX;?>media-body <?php echo DRTS_BS_PREFIX;?>m-0 <?php echo DRTS_BS_PREFIX;?>p-0"><?php echo $this->H($entry['summary']);?></p>
<?php       endif;?>
<?php       if (!empty($entry['meta'])):?>
      <ul class="drts-system-widget-meta <?php echo DRTS_BS_PREFIX;?>m-0<?php if (isset($entry['summary'])):?> <?php echo DRTS_BS_PREFIX;?>mt-1<?php endif;?>"><li class="<?php echo DRTS_BS_PREFIX;?>m-0 <?php echo DRTS_BS_PREFIX;?>mr-1"><?php echo implode('</li><li class="' . DRTS_BS_PREFIX . 'm-0 ' . DRTS_BS_PREFIX . 'mr-1">', $entry['meta']);?></li></ul>
<?php       endif;?>
    </div>
<?php     else:?>
    <div class="drts-system-widget-body <?php echo DRTS_BS_PREFIX;?>media-body">
      <?php echo $entry;?>
    </div>
<?php     endif;?>
  </div>
<?php   endforeach;?>
</div>
<?php if (isset($link)):?>
<a class="drts-system-widget-link <?php echo DRTS_BS_PREFIX;?>m-0 <?php echo DRTS_BS_PREFIX;?>mt-2 <?php echo DRTS_BS_PREFIX;?>btn <?php echo DRTS_BS_PREFIX;?>btn-link " href="<?php echo $this->H($link['url']);?>"><?php echo $this->H($link['title']);?></a>
<?php endif;?>
