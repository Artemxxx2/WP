<div class="drts-display-elements">
    <div class="drts-row">
<?php foreach (array_keys($element_types) as $type):?>
<?php   if (!empty($elements[$type])):?>
<?php     foreach ($elements[$type] as $element_name => $element):?>
        <div class="drts-col-12 drts-col-sm-6 drts-col-md-4 drts-col-lg-3 drts-col-xl-3">
            <a href="#" class="drts-display-element drts-bs-btn drts-bs-btn-light" data-element-name="<?php echo $element_name;?>" data-element-type="<?php echo $element['type'];?>" data-element-type-label="<?php echo $this->H($element_types[$element['type']]);?>" data-element-label="<?php echo $this->H($element['label']);?>">
                <span class="drts-display-element-label"><?php if (isset($element['icon'])):?><i class="fa-fw <?php echo $element['icon'];?>"></i><?php endif;?><?php echo $this->H($element['label']);?></span>
                <span class="drts-display-element-description"><?php echo $this->H($element['description']);?></span>
            </a>
        </div>
<?php     endforeach;?>
<?php   endif;?>
<?php endforeach;?>  
    </div>
    <div class="drts-display-elements-buttons">
        <a href="#" class="drts-bs-btn drts-bs-btn-link drts-form-cancel"><?php echo $this->H(__('cancel', 'directories'));?></a>
    </div>
</div>