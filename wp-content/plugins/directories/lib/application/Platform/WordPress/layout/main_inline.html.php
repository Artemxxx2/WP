<?php if (($TABS = $CONTEXT->getTabs()) && ($TAB_CURRENT = $CONTEXT->getCurrentTab())): $TAB_MENU = $CONTEXT->getTabMenus(); $TAB_BREADCRUMBS = $CONTEXT->getTabInfo();?>
<div id="drts-nav" class="<?php echo DRTS_BS_PREFIX;?>clearfix">
<?php   foreach (array_keys($TAB_CURRENT) as $_TAB_SET): $_TAB_CURRENT = $TAB_CURRENT[$_TAB_SET];?>
  <div class="<?php echo DRTS_BS_PREFIX;?>nav <?php echo DRTS_BS_PREFIX;?>nav-tabs <?php echo DRTS_BS_PREFIX;?>nav-justified">
<?php     foreach ($TABS[$_TAB_SET] as $_TAB_NAME => $_TAB):?>
    <?php echo $this->LinkTo($_TAB['title'], $_TAB['url'], [], array('class' => DRTS_BS_PREFIX . 'nav-item ' . DRTS_BS_PREFIX . 'nav-link' . ($_TAB_NAME == $_TAB_CURRENT ? ' ' . DRTS_BS_PREFIX . 'active' : '')));?>
<?php     endforeach;?>
  </div>
<?php   endforeach;?>
<?php   if (!empty($TAB_BREADCRUMBS[$_TAB_SET]) && count($TAB_BREADCRUMBS[$_TAB_SET]) > 1): $_TAB_BREADCRUMB_LAST = array_pop($TAB_BREADCRUMBS[$_TAB_SET]);?>
  <div class="drts-breadcrumbs drts-tab-breadcrumbs">
<?php     foreach ($TAB_BREADCRUMBS[$_TAB_SET] as $_TAB_BREADCRUMB):?>
    <span><?php echo $this->LinkTo($_TAB_BREADCRUMB['title'], $_TAB_BREADCRUMB['url']);?></span>
    <span> &raquo; </span>
<?php     endforeach;?>
<?php echo $this->H($_TAB_BREADCRUMB_LAST['title']);?>
  </div>
<?php   endif;?>
<?php   if (!empty($TAB_MENU[$_TAB_SET])):?>
  <ul class="drts-tab-menu">
<?php     foreach ($TAB_MENU[$_TAB_SET] as $_TAB_MENU): $class = DRTS_BS_PREFIX . 'btn ' . DRTS_BS_PREFIX . 'btn-secondary';
            $attr =  + (isset($_TAB_MENU['data']['link_attr']) ? $_TAB_MENU['data']['link_attr'] : []);
            $options = isset($_TAB_MENU['data']['link_options']) ? $_TAB_MENU['data']['link_options'] : [];?>
    <li><?php echo $this->LinkTo($_TAB_MENU['title'], $_TAB_MENU['url'], $options, $attr + array('class' => $class));?></li>
<?php     endforeach;?>
  </ul>
<?php   endif;?>
</div>
<?php endif;?>
<div id="drts-body">
<?php echo $CONTENT;?>
</div>