<?php
namespace SabaiApps\Directories\Component\Map\FieldRenderer;

use SabaiApps\Directories\Request;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Field\Renderer\AbstractRenderer;
use SabaiApps\Directories\Component\Map\Api\GoogleMapsApi;

class MapFieldRenderer extends AbstractRenderer
{
    protected $_isStreetView = false;
    protected static $_count = 0;
    
    protected function _fieldRendererInfo()
    {
        return [
            'label' => $this->_isStreetView
                ? __('Street view renderer', 'directories')
                : __('Map renderer', 'directories'),
            'field_types' => ['map_map', 'location_address'],
            'default_settings' => [
                'height' => 300,
                'view_marker_icon' => 'default',
                'directions' => true,
                'dropup' => false,
                'default_zoom' => null,
            ],
            'separatable' => false,
            'accept_multiple' => true,
        ];
    }
    
    protected function _fieldRendererSettingsForm(IField $field, array $settings, array $parents = [])
    {        
        $form = [
            'height' => [
                '#type' => 'number',
                '#size' => 4,
                '#integer' => true,
                '#field_suffix' => 'px',
                '#min_value' => 100,
                '#max_value' => 1000,
                '#default_value' => $settings['height'],
                '#title' => __('Map height', 'directories'),
                '#weight' => 1,
            ],
            'default_zoom' => [
                '#title' => __('Default zoom level', 'directories'),
                '#type' => 'slider',
                '#default_value' => isset($settings['default_zoom']) ? $settings['default_zoom'] : -1,
                '#integer' => true,
                '#min_value' => -1,
                '#max_value' => 19,
                '#min_text' => __('Default', 'directories'),
                '#weight' => 7,
            ],
        ];
        $marker_icon_options = $this->_application->Map_Marker_iconOptions($field->Bundle);
        if (count($marker_icon_options) > 1) {
            $form['view_marker_icon'] = [
                '#type' => 'select',
                '#title' => __('Map marker icon', 'directories'),
                '#default_value' => $settings['view_marker_icon'],
                '#options' => $marker_icon_options,
                '#weight' => 5,
            ];
        }
        if (!$this->_isStreetView) {
            $form += [
                'directions' => [
                    '#type' => 'checkbox',
                    '#default_value' => !empty($settings['directions']),
                    '#title' => __('Enable directions search', 'directories'),
                    '#weight' => 10,
                ],
                'dropup' => [
                    '#type' => 'checkbox',
                    '#default_value' => !empty($settings['dropup']),
                    '#title' => __('Show direction options above the directions search button', 'directories'),
                    '#weight' => 10,
                ],
            ];
        }
        
        return $form;
    }

    protected function _fieldRendererRenderField(IField $field, array &$settings, IEntity $entity, array $values, $more = 0)
    {
        if (!$map_api = $this->_application->Map_Api()) return;

        if ($this->_isStreetView
            && !$map_api instanceof GoogleMapsApi
        ) {
            return '<div class="' . DRTS_BS_PREFIX . 'alert ' . DRTS_BS_PREFIX . 'alert-danger ">' . __('Street view is available with Google Maps only.', 'directories') . '</div>';
        }
        return $this->_renderMap($entity, $field, $values, $settings);
    }
    
    protected function _renderMap(IEntity $entity, IField $field, array $values, array $settings)
    {
        if ($this->_application->Map_Gdpr_IsConsentRequired()) {
            return $this->_application->Map_Gdpr_consentForm();
        }

        if (isset($settings['default_zoom'])
            && $settings['default_zoom'] < 0
        ) {
            // Unset to use the system default zoom level
            unset($settings['default_zoom']);
        }

        $id = 'drts-map-map-' . self::$_count++;
        $config = $this->_application->getComponent('Map')->getConfig('map');
        $marker_settings = [
            'marker_size' => $config['marker_size'],
        ] + $settings;
        if (!$markers = $this->_application->Map_Marker($entity, $field->getFieldName(), $marker_settings)) return;

        $this->_application->Action('map_render_field_map', [$field, $settings]);
        
        unset($config['api']);
        $settings += [
            'height' => 300,
        ];
        $settings += $config;

        if (empty($settings['directions'])) {
            $this->_application->Map_Api_load();
            return sprintf(
                '<div id="%s" style="position:relative;">
    <div class="drts-map-container">
        <div class="drts-map-map" style="height:%dpx;"></div>
    </div>
</div>
<script type="text/javascript">
%s
</script>',
                $id,
                $settings['height'],
                $this->_getJs($field, $id, $markers, $settings)
            );
        }
        
        $this->_application->Map_Api_load();
        $multi_address = count($markers) > 1; 
        if ($multi_address) {
            $addr_options = [];
            foreach (array_keys($markers) as $key) {
                $selected = $key === 0 ? ' selected="selected"' : '';
                $option = strlen($values[$key]['address']) ? $this->_application->H($values[$key]['address']) : $values[$key]['lat'] . ',' . $values[$key]['lat'];
                $addr_options[] = '<option value="' . $entity->getId() . '-' . $key . '"' . $selected . '>' . $option . '</option>';
            }
            $addr_select = sprintf(
                '<div class="%1$smt-0 %1$smb-2 %1$salign-middle">
    <select class="drts-map-directions-destination %1$sform-control">
    %2$s
    </select>
</div>',
                DRTS_BS_PREFIX,
                implode(PHP_EOL, $addr_options)
            );
        } else {
            $addr_select = '<input type="hidden" value="' . $entity->getId() . '-0" class="drts-map-directions-destination" />';
        }
        return sprintf(
            '<div id="%1$s" style="position:relative;">
    <div class="drts-map-container">
        <div class="drts-map-map" style="height:%2$dpx;"></div>
    </div>
    <form class="drts-map-directions %3$spx-2 %3$spt-2">
        <div class="%14$s">
            <div class="%4$s %3$smt-0 %3$smb-2 %3$salign-middle">
                <input type="text" class="%3$sform-control drts-map-directions-input" value="" placeholder="%5$s" />
            </div>
            %6$s
            <div class="%7$s %3$smt-0 %3$smb-2">
                <div class="%3$sbtn-group %3$sbtn-block %3$salign-middle %15$s">
                    <button class="%3$sbtn %3$sbtn-block %3$sbtn-primary drts-directory-btn-directions drts-map-directions-trigger">%8$s</button>
                    <button class="%3$sbtn %3$sbtn-primary %3$sdropdown-toggle %3$sdropdown-toggle-split" data-toggle="%3$sdropdown" aria-expanded="false"></button>
                    <div class="%3$sdropdown-menu %3$sdropdown-menu-right">
                        <a class="%3$sdropdown-item drts-map-directions-trigger" data-travel-mode="driving">%9$s</a>
                        <a class="%3$sdropdown-item drts-map-directions-trigger" data-travel-mode="transit">%10$s</a>
                        <a class="%3$sdropdown-item drts-map-directions-trigger" data-travel-mode="walking">%11$s</a>
                        <a class="%3$sdropdown-item drts-map-directions-trigger" data-travel-mode="bicycling">%12$s</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<script type="text/javascript">
%13$s
</script>',
            $id,
            $settings['height'],
            DRTS_BS_PREFIX,
            $multi_address ? '' : ' drts-col-md-8',
            $this->_application->H(__('Enter a location', 'directories')),
            $addr_select,
            $multi_address ? '' : ' drts-col-md-4',
            $this->_application->H(__('Get Directions', 'directories')),
            $this->_application->H(__('By car', 'directories')),
            $this->_application->H(__('By public transit', 'directories')),
            $this->_application->H(__('Walking', 'directories')),
            $this->_application->H(__('Bicycling', 'directories')),
            $this->_getJs($field, $id, $markers, $settings),
            $multi_address ? '' : ' drts-row',
            empty($settings['dropup']) ? '' : DRTS_BS_PREFIX . 'dropup'
        );
    }
    
    protected function _getJs(IField $field, $id, $markers, $settings)
    {
        return sprintf(
            '%1$s
    var renderMap = function (container) {
        var map = DRTS.Map.api.getMap(container, %3$s)
            .setMarkers(%4$s)
            .draw(%5$s);
        %7$s
    };
    var $map = $("#%2$s");
    if ($map.is(":visible")) {
        renderMap($map);
    } else {
        var pane = $map.closest(".%6$stab-pane, .%6$scollapse");
        if (pane.length) {
            var paneId = pane.data("original-id") || pane.attr("id");
            $("#" + (pane.hasClass("%6$stab-pane") ? paneId + "-trigger" : paneId)).on("shown.bs.tab shown.bs.collapse", function(e, data){
                if (!pane.data("map-rendered")) {
                    pane.data("map-rendered", true);
                    renderMap($map);
                }
            });
        }
    }
    $(DRTS).on("loaded.sabai", function (e, data) {
        if (data.target.find("#%2$s").length) {
            renderMap();
        }
    });
});',
            Request::isXhr() ? 'jQuery(function ($) {' : 'document.addEventListener("DOMContentLoaded", function() { var $ = jQuery;',
            $id,
            $this->_application->JsonEncode($this->_getJsMapOptions($field, $settings)),
            $this->_application->JsonEncode($markers),
            $this->_application->JsonEncode(['street_view' => $this->_isStreetView]),
            DRTS_BS_PREFIX,
            empty($settings['directions']) ? '' : 'DRTS.Map.enableDirections(map);'
        );
    }

    protected function _getJsMapOptions(IField $field, array $settings)
    {
        return [
            'marker_clusters' => false,
            'infobox' => $field->getFieldType() !== 'map_map',
            'center_default' => false,
            'fit_bounds' => true,
            'text_control_fullscreen' => __('Full screen', 'directories'),
            'text_control_exit_fullscreen' => __('Exit full screen', 'directories'),
            'text_control_search_this_area' => __('Search this area', 'directories'),
            'text_control_search_my_location' => __('Search my location', 'directories'),
        ] + $settings;
    }
}
