<div class="drts-display-element drts-display-element-name-<?php echo $element['name'];?><?php if ($element['containable']):?> drts-display-element-containable<?php endif;?><?php if (!empty($element['dimmed'])):?> drts-display-element-dimmed<?php endif;?>" data-element-id="<?php echo $element['id'];?>" data-element-type="<?php echo $element['type'];?>" data-element-name="<?php echo $element['name'];?>" data-element-label="<?php echo $this->_application->H($element['label']);?>" <?php if ($element['parent_element_name']):?> data-parent-element-name="<?php echo $element['parent_element_name'];?>"<?php endif;?> data-element-data="<?php echo $this->_application->H($this->_application->JsonEncode($this->_getElementDataArray($bundleName, $element)));?>"<?php if ($element['admin_attr']):?><?php echo $this->_application->Attr($element['admin_attr']);?><?php endif;?>>
    <div class="drts-display-element-title">
        <?php if (isset($element['icon'])):?><span class="drts-display-element-icon"><i class="fa-fw <?php echo $element['icon'];?>"></i></span><?php endif;?>
<?php if (strlen($element['title'])):?>
        <span class="drts-display-element-handle"><i class="fas fa-arrows-alt fa-fw fa-lg"></i> <span class="drts-display-element-label<?php if (!empty($element['no_title'])):?> drts-display-element-no-label<?php endif;?>"><?php echo $element['title'];?></span></span>
<?php else:?>
        <span class="drts-display-element-handle"><i class="fas fa-arrows-alt fa-fw fa-lg"></i> <span class="drts-display-element-label drts-display-element-no-label"><?php echo $this->_application->H($element['label']);?></span></span>
<?php endif;?>
    </div>
    <div class="drts-display-element-control">
        <div class="drts-bs-btn-group">
            <button type="button" class="drts-display-element-info drts-bs-btn drts-bs-btn-info drts-bs-btn-sm"><i class="fa-fw fas fa-info"></i></button>
            <button type="button" class="drts-display-element-edit drts-bs-btn drts-bs-btn-primary drts-bs-btn-sm"><i class="fa-fw fas fa-cog"></i></button>
<?php if (empty($element['system'])):?>
            <button type="button" class="drts-display-element-delete drts-bs-btn drts-bs-btn-danger drts-bs-btn-sm"><i class="fa-fw fas fa-trash-alt"></i></button>
<?php endif;?>
<?php if (!empty($element['containable'])):?>
<?php   if ($element['child_element_name']):?>
            <button type="button" class="drts-display-add-element drts-bs-btn drts-bs-btn-success drts-bs-btn-sm" data-element-name="<?php echo $element['child_element_name'];?>" rel="sabaitooltip" title="<?php echo $this->_application->H($element['add_child_label']);?>" data-placement="right"><i class="fa-fw fas fa-plus"></i></button>
<?php   elseif ($element['child_element_type']):?>
            <button type="button" class="drts-display-add-element drts-bs-btn drts-bs-btn-success drts-bs-btn-sm" data-element-type="<?php echo $this->_application->H(json_encode((array)$element['child_element_type']));?>" rel="sabaitooltip" title="<?php echo $this->_application->H($element['add_child_label']);?>" data-placement="right"><i class="fa-fw fas fa-plus"></i></button>
<?php   else:?>                 
            <button type="button" class="drts-display-add-element drts-bs-btn drts-bs-btn-success drts-bs-btn-sm" rel="sabaitooltip" title="<?php echo __('Add Element', 'directories');?>" data-placement="right"><i class="fa-fw fas fa-plus"></i></button>
<?php   endif;?>
<?php endif;?>
        </div>
    </div>
    <div class="drts-display-element-settings-inline" id="drts-display-element-settings-inline-<?php echo $element['id'];?>"></div>
    <input type="hidden" name="<?php echo $name;?>[]" value="<?php echo $element['id'];?>" />
<?php if (!empty($element['containable'])):?>
    <input type="hidden" name="<?php echo $name;?>[]" value="__CHILDREN_START__" />
<?php   if (!empty($element['children'])):?>
    <div class="drts-display-element-wrapper"<?php if (!empty($element['child_element_type'])):?> data-child-element-type="<?php echo $this->_application->H(json_encode((array)$element['child_element_type']));?>"<?php endif;?><?php if (!empty($element['child_element_name'])):?> data-child-element-name="<?php echo $this->_application->H($element['child_element_name']);?>"<?php endif;?>>
<?php     foreach ($element['children'] as $child_element):?>
<?php       echo $this->_getElementHtml($name, $child_element, $bundleName);?>
<?php     endforeach;?>
    </div>
<?php   else:?>
    <div class="drts-display-element-wrapper"<?php if (!empty($element['child_element_type'])):?> data-child-element-type="<?php echo $this->_application->H(json_encode((array)$element['child_element_type']));?>"<?php endif;?><?php if (!empty($element['child_element_name'])):?> data-child-element-name="<?php echo $this->_application->H($element['child_element_name']);?>"<?php endif;?>></div>
<?php   endif;?>
    <input type="hidden" name="<?php echo $name;?>[]" value="__CHILDREN_END__" />
<?php endif;?>
</div>
