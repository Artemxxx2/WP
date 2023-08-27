<div class="drts-display-elements">
    <div class="drts-bs-nav drts-bs-nav-pills drts-display-tabs drts-bs-mb-3">
        <a href="#" data-target="#drts-display-element-type-all" class="drts-content-tab drts-bs-nav-item drts-bs-nav-link<?php if (!isset($default_type)):?> drts-bs-active<?php endif;?>" data-toggle="<?php echo DRTS_BS_PREFIX;?>tab"><?php echo _x('All', 'all elements tab', 'directories');?></a>
<?php foreach (array_keys($element_types) as $type):?>
        <a href="#" data-target="#drts-display-element-type-<?php echo $type;?>" class="drts-content-tab drts-bs-nav-item drts-bs-nav-link<?php if ($default_type === $type):?> drts-bs-active<?php endif;?>" data-toggle="<?php echo DRTS_BS_PREFIX;?>tab"><?php echo $this->H($element_types[$type]);?></a>
<?php endforeach;?>
    </div>
    <div class="drts-bs-tab-content">
        <div class="drts-bs-tab-pane drts-bs-fade<?php if (!isset($default_type)):?> drts-bs-active drts-bs-show<?php endif;?>" id="drts-display-element-type-all">
            <div class="drts-row">
<?php foreach (array_keys($element_types) as $type):?>
<?php   if (!empty($elements[$type])):?>
<?php     foreach ($elements[$type] as $element_name => $element):?>
                <div class="drts-col-12 drts-col-sm-6 drts-col-md-4 drts-col-lg-3 drts-col-xl-3">
                    <a href="#" class="drts-display-element drts-bs-btn drts-bs-btn-light" data-element-name="<?php echo $element_name;?>" data-element-type="<?php echo $element['type'];?>" data-element-type-label="<?php echo $this->H($element_types[$element['type']]);?>" data-element-label="<?php echo $this->H($element['label']);?>">
                        <span class="drts-display-element-label drts-bs-mb-1"><?php if (isset($element['icon'])):?><i class="fa-fw <?php echo $element['icon'];?>"></i><?php endif;?><?php echo $this->H($element['label']);?></span>
                        <span class="drts-display-element-description"><?php echo $this->H($element['description']);?></span>
                    </a>
                </div>
<?php     endforeach;?>
<?php   endif;?>
<?php endforeach;?>  
            </div>
        </div>
<?php foreach (array_keys($element_types) as $type):?>
        <div class="drts-bs-tab-pane drts-bs-fade<?php if ($default_type === $type):?> drts-bs-active drts-bs-show<?php endif;?>" id="drts-display-element-type-<?php echo $type;?>">
            <div class="drts-row">
<?php   if (!empty($elements[$type])):?>
<?php     foreach ($elements[$type] as $element_name => $element):?>
                <div class="drts-col-12 drts-col-sm-6 drts-col-md-4 drts-col-lg-3 drts-col-xl-3">
                    <a href="#" class="drts-display-element drts-bs-btn drts-bs-btn-light" data-element-name="<?php echo $element_name;?>" data-element-type="<?php echo $element['type'];?>" data-element-type-label="<?php echo $this->H($element_types[$element['type']]);?>" data-element-label="<?php echo $this->H($element['label']);?>">
                        <span class="drts-display-element-label drts-bs-mb-1"><?php if (isset($element['icon'])):?><i class="fa-fw <?php echo $element['icon'];?>"></i><?php endif;?><?php echo $this->H($element['label']);?></span>
                        <span class="drts-display-element-description"><?php echo $this->H($element['description']);?></span>
                    </a>
                </div>
<?php     endforeach;?>
<?php   endif;?>
            </div>
        </div>
<?php endforeach;?>
    </div>
    <div class="drts-display-elements-buttons">
        <a href="#" class="drts-bs-btn drts-bs-btn-link drts-form-cancel"><?php echo $this->H(__('cancel', 'directories'));?></a>
    </div>
</div>