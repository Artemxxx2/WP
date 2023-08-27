<?php
use SabaiApps\Directories\Component\Display\Element\AbstractElement;
$vars = $CONTEXT->getAttributes();
if (empty($entities)) {
    $this->display('view_entities_none', $vars);
    return;
}
$entities_by_bundle = [];
foreach ($entities as $entity_id => $entity) {
    $entities_by_bundle[$entity->getBundleName()][$entity_id] = $entity;
}
// Sample parent ID of the last entity if parent entity ID is set
$on_parent_page = $entity->isOnParentPage();
$show_caption = count($entities_by_bundle) > 1;
?>
<?php foreach ($entities_by_bundle as $bundle_name => $_entities): $pre_rendered = $this->Entity_Display_preRenderByBundle($bundle_name, $_entities, $settings['display']);?>
<?php   if ((!$display = $this->Display_Display($bundle_name, $settings['display'])) || (!$bundle = $this->Entity_Bundle($bundle_name))) continue;?>
<?php   if (!empty($pre_rendered['html'])) echo implode(PHP_EOL, $pre_rendered['html']);?>
<table class="<?php echo DRTS_BS_PREFIX;?>table <?php echo DRTS_BS_PREFIX;?>table-responsive-md">
<?php   if ($show_caption):?>
    <caption><?php echo $this->H($this->Entity_Component($bundle)->getTitle($bundle->group));?> - <?php echo $this->H($bundle->getLabel());?></caption>
<?php   endif;?>
    <thead>
        <tr>
<?php   foreach (array_keys($display['elements']) as $ele_index):?>
<?php     if (!$on_parent_page || empty($display['elements'][$ele_index]['visibility']['hide_on_parent'])): $element =& $display['elements'][$ele_index];?>
<?php       if (isset($element['heading']['label'])):?>
            <th><?php echo $this->H($this->Display_ElementLabelSettingsForm_label($element['heading'], AbstractElement::stringId($element['name'], 'label', $element['element_id']))); unset($element['heading']);?></th>
<?php       elseif (!empty($element['settings']['label_as_heading'])):?>
            <th><?php echo $this->H($this->Display_ElementLabelSettingsForm_label($element['settings'], AbstractElement::stringId($element['name'], 'label', $element['element_id']), $element['title'])); $element['settings']['label'] = 'none';?></th>
<?php       else:?>
            <th></th>
<?php       endif;?>
<?php     endif;?>
<?php   endforeach;?>   
        </tr>
    </thead>
    <tbody>
<?php     foreach ($pre_rendered['entities'] as $entity):?>
<?php       $this->Entity_Display($entity, $display, $vars, ['tag' => 'tr', 'element_tag' => 'td', 'render_empty' => true, 'cache' => $settings['display_cache'], 'attr' => ['class' => 'drts-view-entity-container']]);?>
<?php     endforeach;?>
    </tbody>
</table>
<?php   endforeach;?>