<?php
global $title, $menu, $submenu, $pagenow, $typenow, $self, $parent_file, $submenu_file, $plugin_page, $user_identity;
require_once ABSPATH . 'wp-admin/admin-header.php';
echo $this->Platform()->getHeadHtml();
?>
<div class="wrap">
    <div id="<?php echo substr($CONTEXT->getContainer(), 1);?>" class="drts drts-admin<?php if ($this->Platform()->isRtl()):?> drts-rtl<?php endif;?>">
<?php echo $CONTENT;?>
    </div>
</div>
<?php
echo $this->Platform()->getJsHtml();
require_once ABSPATH . 'wp-admin/admin-footer.php';