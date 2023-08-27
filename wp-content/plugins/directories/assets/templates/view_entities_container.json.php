<?php
$html = [];
if (isset($filter['form'])) {
    $html['.drts-view-entities-filter-form'] = $filter['form'];
}
if (!empty($nav[0])) {
    $html['.drts-view-entities-header'] = $this->View_Nav($CONTEXT, $nav[0]);
}
if (!empty($nav[1])) {
    $html['.drts-view-entities-footer'] = $this->View_Nav($CONTEXT, $nav[1], true);
}
if ((string)$view !== 'map') { // Map view does not need to render entities list
    $html['.drts-view-entities'] = $this->render($settings['template'], $CONTEXT->getAttributes());
}
echo $this->JsonEncode(array(
    'html' => $html,
));