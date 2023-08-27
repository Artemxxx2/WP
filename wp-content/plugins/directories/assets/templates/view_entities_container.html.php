<div class="drts-view-entities-header <?php echo DRTS_BS_PREFIX;?>mb-4"><?php if (!empty($nav[0])):?><?php echo $this->View_Nav($CONTEXT, $nav[0]);?><?php endif;?></div>

<?php if (!empty($settings['filter']['show']) && isset($filter['form'])):?>
<div class="drts-view-entities-filter-form <?php echo DRTS_BS_PREFIX;?>collapse<?php if (!empty($settings['filter']['shown'])):?> <?php echo DRTS_BS_PREFIX;?>show<?php endif;?> <?php echo DRTS_BS_PREFIX;?>mb-4" id="<?php echo $this->H(substr(isset($settings['filter']['target']) ? $settings['filter']['target'] : $CONTEXT->getContainer(), 1));?>-view-filter-form"<?php if (empty($settings['filter']['shown'])):?> data-collapsible="1"<?php endif;?><?php if (empty($settings['filter']['shown'])):?> style="display:none"<?php endif;?>>
    <?php echo $filter['form'];?>
</div>
<?php endif;?>

<?php $this->Action('view_before_entities', array($entities, $CONTEXT));?>
<div class="drts-view-entities drts-view-<?php echo $bundle->entitytype_name;?>-entities drts-view-entities-<?php echo $view;?>">
    <?php $this->display($settings['template'], $CONTEXT->getAttributes());?>
</div>
<?php $this->Action('view_after_entities', array($entities, $CONTEXT));?>

<div class="drts-view-entities-footer <?php echo DRTS_BS_PREFIX;?>mt-4"><?php if (!empty($nav[1])):?><?php echo $this->View_Nav($CONTEXT, $nav[1], true);?><?php endif;?></div>