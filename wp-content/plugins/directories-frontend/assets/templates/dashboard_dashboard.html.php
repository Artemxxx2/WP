<div class="drts-dashboard<?php if (isset($dashboard_user)):?> drts-dashboard-user<?php endif;?>">
    <div class="drts-dashboard-aside">
        <div class="drts-dashboard-links<?php if (count($panels) > 1):?> <?php echo DRTS_BS_PREFIX;?>accordion <?php endif;?>" id="<?php echo $dashboard_id;?>-panels">
<?php foreach ($panels as $panel_name => $panel): $show_panel = !empty($panel['active']) || empty($accordion);?>
            <div class="<?php echo DRTS_BS_PREFIX;?>card">
<?php   if (isset($panel['title']) && strlen($panel['title'])):?>
                <div class="<?php echo DRTS_BS_PREFIX;?>card-header <?php echo DRTS_BS_PREFIX;?>m-0">
                    <button class="<?php echo DRTS_BS_PREFIX;?>btn <?php echo DRTS_BS_PREFIX;?>btn-link<?php if (!$show_panel):?> <?php echo DRTS_BS_PREFIX;?>collapsed<?php endif;?>" type="button" data-toggle="<?php echo DRTS_BS_PREFIX;?>collapse" data-target="#<?php echo $dashboard_id;?>-panel-<?php echo $panel_name;?>" aria-expanded="<?php if (!$show_panel):?>false<?php else:?>true<?php endif;?>" aria-controls="<?php echo $dashboard_id;?>-panel-<?php echo $panel_name;?>"><?php echo $this->H($panel['title']);?></button>
                </div>
<?php   endif;?>
                <div id="<?php echo $dashboard_id;?>-panel-<?php echo $panel_name;?>" class="<?php echo DRTS_BS_PREFIX;?>collapse<?php if ($show_panel):?> <?php echo DRTS_BS_PREFIX;?>show<?php endif;?>" <?php if (!empty($accordion)):?>data-parent="#<?php echo $dashboard_id;?>-panels"<?php endif;?>>
                    <div class="drts-dashboard-panel-links <?php echo DRTS_BS_PREFIX;?>list-group <?php echo DRTS_BS_PREFIX;?>list-group-flush">
<?php   foreach ($panel['links'] as $link_name => $link):?>
                        <button type="button" class="<?php echo DRTS_BS_PREFIX;?>d-flex <?php echo DRTS_BS_PREFIX;?>justify-content-between <?php echo DRTS_BS_PREFIX;?>align-items-center <?php echo $this->H($link['attr']['class']);?> <?php echo DRTS_BS_PREFIX;?>list-group-item <?php echo DRTS_BS_PREFIX;?>list-group-item-action"<?php echo $this->Attr($link['attr'], 'class');?>><?php echo $link['title'];?></button>
<?php   endforeach;?>
                    </div>
                </div>
            </div>
<?php endforeach;?>
        </div>
<?php if (isset($logout_btn)):?>
        <div class="drts-dashboard-logout <?php echo DRTS_BS_PREFIX;?>mt-3"><?php echo $logout_btn;?></div>
<?php endif;?>
    </div>
    <div id="<?php echo $dashboard_id;?>-main" class="drts-dashboard-main">
        <?php $this->display($dashboard_templates, $CONTEXT->getAttributes());?>
    </div>
</div>
<?php echo $this->Dashboard_Panels_js('#' . $dashboard_id . '-main', false, !$CONTEXT->isEmbed());?>
