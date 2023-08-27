<?php
$html = [];
if (!empty($nav[0])) {
    $html['.drts-view-entities-header'] = $this->View_Nav($CONTEXT, $nav[0]);
}
if (!empty($nav[1])) {
    $html['.drts-view-entities-footer'] = $this->View_Nav($CONTEXT, $nav[1], true);
}
if (isset($filter['form'])) {
    $html['.drts-view-entities-filter-form'] = $filter['form'];
}
if ((string)$view !== 'map') { // Map view does not need to render entities list
    $html['.drts-location-entities'] = $this->render($settings['map']['template'], $CONTEXT->getAttributes());
    $display = null;
} else {
    $display = isset($settings['display']) ? $settings['display'] : null;
}
$field = isset($settings['map']['coordinates_field']) ? $settings['map']['coordinates_field'] : 'location_address';
$markers = [];
if ($display) {
    $pre_rendered = $this->Entity_Display_preRender($entities, $display);
    foreach ($pre_rendered['entities'] as $entity) {
        ob_start();
        $this->Entity_Display($entity, $display, $CONTEXT->getAttributes());
        $content = ob_get_clean();
        foreach ($this->Map_Marker($entity, $field, $settings['map'], $content) as $marker) {
            $markers[] = $marker;
        }
    }
} else {
    foreach ($entities as $entity) {
        foreach ($this->Map_Marker($entity, $field, $settings['map']) as $marker) {
            $markers[] = $marker;
        }
    }
}

echo $this->JsonEncode(array(
    'html' => $html,
    'markers' => $markers,
    'draw_options' => $this->Location_DrawMapOptions([], $CONTEXT),
));