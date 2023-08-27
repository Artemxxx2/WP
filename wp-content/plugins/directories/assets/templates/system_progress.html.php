<?php if (isset($form)):?>
<?php   echo $this->Form_Render($form);?>
<?php endif;?>
<div class="drts-system-progress <?php echo DRTS_BS_PREFIX;?>mb-4" style="display:none;" data-more="0">
    <div class="drts-system-progress-message <?php echo DRTS_BS_PREFIX;?>mb-2"><?php echo $this->H(isset($progress_message) ? $progress_message : __('Preparing...', 'directories'));?></div>
    <div class="drts-system-progress-bar <?php echo DRTS_BS_PREFIX;?>progress" style="height:20px;">
        <div class="<?php echo DRTS_BS_PREFIX;?>progress-bar <?php echo DRTS_BS_PREFIX;?>progress-bar-striped <?php echo DRTS_BS_PREFIX;?>progress-bar-animated" role="progressbar" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" style="width:100%;overflow-x:hidden;">
            <span class="drts-system-progress-percent"></span>
        </div>
    </div>
</div>
<a style="display:none;" class="drts-system-download drts-bs-btn drts-bs-btn-primary drts-bs-btn-lg" href="<?php echo $this->System_Download_url(null, 86400);?>"><?php echo $this->H(__('Download', 'directories'));?></a>