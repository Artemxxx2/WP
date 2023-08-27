<?php
$vars = $CONTEXT->getAttributes();
if (empty($entities)) {
    $this->display('view_entities_none', $vars);
    return;
}
$this->display(
    $this->Platform()->getAssetsDir('directories') . '/templates/map_map',
    [
        'settings' => $settings['map'],
        'field' => $settings['map']['coordinates_field'],
        'display' => isset($settings['display']) ? $settings['display'] : null,
    ] + $CONTEXT->getAttributes()
);?>