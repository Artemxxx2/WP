<?php if (strlen($CONTENT_TITLE = $CONTEXT->getTitle(true))):?>
<h1 class="wp-heading-inline"><?php echo $this->H($CONTENT_TITLE);?></h1>
<?php endif;?>
<?php if ($CONTENT_MENU = $CONTEXT->getMenus()):?>
<?php   foreach ($CONTENT_MENU as $_CONTENT_MENU): $options = isset($_CONTENT_MENU['data']['link_options']) ? $_CONTENT_MENU['data']['link_options'] : []; $attr = isset($_CONTENT_MENU['data']['link_attr']) ? $_CONTENT_MENU['data']['link_attr'] : [];  unset($options['icon'], $attr['class']);?>
<?php     echo $this->LinkTo($_CONTENT_MENU['title'], $_CONTENT_MENU['url'], $options, $attr + ['class' => 'page-title-action']);?>
<?php   endforeach;?>
<?php endif;?>
<?php if (($CONTENT_BREADCRUMBS = $CONTEXT->getInfo()) && count($CONTENT_BREADCRUMBS) > 1): $_CONTENT_BREADCRUMB_LAST = array_pop($CONTENT_BREADCRUMBS);?>
<div class="drts-breadcrumbs">
<?php   foreach ($CONTENT_BREADCRUMBS as $_CONTENT_BREADCRUMB):?>
    <span><?php echo $this->LinkTo($_CONTENT_BREADCRUMB['title'], $_CONTENT_BREADCRUMB['url']);?></span>
    <span> &raquo; </span>
<?php   endforeach;?>
<?php echo $this->H($_CONTENT_BREADCRUMB_LAST['title']);?>
</div>
<?php endif;?>
<hr class="wp-header-end" style="margin:-2px 0 0; border:0; border-top:1px solid #ddd; border-bottom:1px solid #fafafa;" />
<?php if (($TABS = $CONTEXT->getTabs()) && ($TAB_CURRENT = $CONTEXT->getCurrentTab())): $TAB_MENU = $CONTEXT->getTabMenus(); $TAB_BREADCRUMBS = $CONTEXT->getTabInfo();?>
<div id="drts-nav" class="drts-bs-clearfix">
<?php   foreach (array_keys($TAB_CURRENT) as $_TAB_SET): $_TAB_CURRENT = $TAB_CURRENT[$_TAB_SET];?>
    <h2 class="nav-tab-wrapper">
<?php     foreach ($TABS[$_TAB_SET] as $_TAB_NAME => $_TAB):?>
        <?php echo $this->LinkTo($_TAB['title'], $_TAB['url'], isset($_TAB['data']['link_options']) ? $_TAB['data']['link_options'] : [], (isset($_TAB['data']['link_attr']) ? $_TAB['data']['link_attr'] : []) + array('class' => $_TAB_NAME == $_TAB_CURRENT ? 'nav-tab nav-tab-active' : 'nav-tab'));?>
<?php     endforeach;?>
    </h2>
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
<?php     foreach ($TAB_MENU[$_TAB_SET] as $_TAB_MENU): $options = isset($_TAB_MENU['data']['link_options']) ? $_TAB_MENU['data']['link_options'] : [];?>
        <li><?php echo $this->LinkTo($_TAB_MENU['title'], $_TAB_MENU['url'], $options, (isset($_TAB_MENU['data']['link_attr']) ? $_TAB_MENU['data']['link_attr'] : []) + array('class' => 'drts-bs-btn drts-bs-btn-secondary'));?></li>
<?php     endforeach;?>
    </ul>
<?php   endif;?>
</div>
<?php endif;?>
<div id="drts-body">
    <?php echo $CONTENT;?>
</div>
