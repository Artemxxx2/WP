<?php
$vars = $CONTEXT->getAttributes();
if (empty($entities)) {
    $this->display('view_entities_none', $vars);
    return;
}
$pre_rendered = $this->Entity_Display_preRender($entities, $settings['display']);
if (!empty($pre_rendered['html'])) echo implode(PHP_EOL, $pre_rendered['html']);
if (empty($settings['list_grid'])) {
    $layout = 'row';
} else {
    if (empty($settings['list_no_row'])) {
        if (!empty($settings['list_layout_switch_cookie'])
            && ($cookie = $this->System_Cookie($settings['list_layout_switch_cookie']))
        ) {
            $layout = $cookie === 'grid' ? 'grid' : 'row';
        } else {
            $layout = empty($settings['list_grid_default']) ? 'row' : 'grid';
        }
    } else {
        $layout = 'grid';
    }
    if (isset($settings['list_grid_cols']['num'])) {
        if ($settings['list_grid_cols']['num'] === 'responsive') {
            if (!empty($settings['list_grid_cols']['num_responsive'])) {
                $_list_grid_cols = $settings['list_grid_cols']['num_responsive'];
            }
        } else {
            $_list_grid_cols = $settings['list_grid_cols']['num'];
        }
    }
    if (!isset($_list_grid_cols)
        && (!$_list_grid_cols = $this->Entity_BundleTypeInfo($bundle, 'view_list_grid_cols'))
    ) {
        $_list_grid_cols = ['xs' => 2, 'lg' => 3, 'xl' => 4];
    }
    if (empty($settings['list_grid_gutter_width'])) $settings['list_grid_gutter_width'] = 'sm';
}
$i = 0;
?>
<div class="drts-view-entities-list-<?php echo $layout;?>">
    <div class="drts-row<?php if ($settings['list_grid_gutter_width']):?> drts-gutter-<?php echo $this->H($settings['list_grid_gutter_width']);?><?php if ($layout === 'grid'):?> drts-y-gutter<?php endif;?><?php endif;?>">
<?php foreach ($pre_rendered['entities'] as $entity): ++$i; $entity->data['view_list_entity_order'] = $i;?>
        <div class="<?php echo $this->H($view->getGridClass(isset($_list_grid_cols) ? $_list_grid_cols : 1));?> drts-view-entity-container">
            <?php $this->Entity_Display($entity, $settings['display'], $vars, ['cache' => $settings['display_cache']]);?>
        </div>
        <?php $this->Action('view_entities_list_after_entity', [$entity, $i, $view_name, $settings]);?>
<?php endforeach;?>
    </div>
</div>