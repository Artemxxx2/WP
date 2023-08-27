<div class="drts-payment-pricing-table drts-payment-pricing-table-<?php echo $layout;?> <?php echo DRTS_BS_PREFIX;?>card-<?php echo $layout;?><?php if ($has_featured):?> drts-payment-pricing-table-has-featured<?php endif;?>">
<?php foreach ($plans as $plan_id => $plan): $feature_count = 0;?>
    <div data-plan-id="<?php echo $plan_id;?>" class="drts-payment-plan<?php if ($plan['featured']):?> drts-payment-plan-featured<?php endif;?> <?php echo DRTS_BS_PREFIX;?>card<?php if ($plan['featured']):?> drts-payment-plan-featured <?php echo DRTS_BS_PREFIX;?>border-<?php echo $featured_border_color;?><?php endif;?>">
        <div class="drts-payment-plan-header <?php echo DRTS_BS_PREFIX;?>card-header <?php echo DRTS_BS_PREFIX;?>text-center<?php if ($plan['featured']):?> <?php echo DRTS_BS_PREFIX;?>bg-<?php echo $featured_bg_color;?> <?php echo DRTS_BS_PREFIX;?>text-<?php echo $featured_text_color;?><?php endif;?>">
            <?php echo $this->H($plan['title']);?>
        </div>
        <div class="drts-payment-plan-body <?php echo DRTS_BS_PREFIX;?>card-body">
            <h2 class="<?php echo DRTS_BS_PREFIX;?>card-title <?php echo DRTS_BS_PREFIX;?>text-center">
                <?php echo $plan['price'];?>
            </h2>
<?php   if (strlen($plan['description'])):?>
            <div class="<?php echo DRTS_BS_PREFIX;?>card-text <?php echo DRTS_BS_PREFIX;?>text-muted"><?php echo $this->Htmlize($plan['description']);?></div>
<?php   endif;?>
        </div>
        <div class="drts-payment-plan-features <?php echo DRTS_BS_PREFIX;?>text-center <?php echo DRTS_BS_PREFIX;?>list-group <?php echo DRTS_BS_PREFIX;?>list-group-flush">
<?php   foreach ($plan['features'] as $feature):?>
<?php     foreach ($feature as $_feature): ++$feature_count;?>
            <div class="<?php echo DRTS_BS_PREFIX;?>list-group-item"><i class="fa-fw <?php echo $this->H($_feature['icon']);?>"></i> <?php echo $_feature['html'];?></div>
<?php     endforeach;?>
<?php   endforeach;?>
<?php   while ($feature_count < $max_feature_count): ++$feature_count;?>
            <div class="<?php echo DRTS_BS_PREFIX;?>list-group-item">&nbsp;</div>
<?php   endwhile;?>
        </div>
        <div class="drts-payment-plan-footer <?php echo DRTS_BS_PREFIX;?>card-footer <?php echo DRTS_BS_PREFIX;?>text-center<?php if ($plan['featured']):?> <?php echo DRTS_BS_PREFIX;?>bg-<?php echo $featured_bg_color;?><?php endif;?>">
            <a href="<?php echo $plan['order_url'];?>" class="<?php echo DRTS_BS_PREFIX;?>btn <?php echo DRTS_BS_PREFIX;?>btn-<?php if ($plan['featured']):?><?php echo $featured_btn_color;?><?php else:?><?php echo $btn_color;?><?php endif;?>"><?php echo $this->H($btn_text);?></a>
        </div>
    </div>
<?php endforeach;?>
</div>