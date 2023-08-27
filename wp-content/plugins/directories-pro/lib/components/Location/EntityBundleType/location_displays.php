<?php
return array (
    'entity' =>
        array (
            'detailed' =>
                array (
                    'name' => 'detailed',
                    'type' => 'entity',
                    'data' =>
                        array (
                            'css' => '',
                        ),
                    'elements' =>
                        array (
                            1172 =>
                                array (
                                    'id' => 1172,
                                    'name' => 'entity_field_term_content',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'label' => 'none',
                                                    'label_custom' => '',
                                                    'label_icon' => '',
                                                    'label_icon_size' => '',
                                                    'renderer_settings' =>
                                                        array (
                                                            'wp_term_description' =>
                                                                array (
                                                                    'trim_length' => 200,
                                                                    'disable_exceprt_more' => 1,
                                                                    'trim' => false,
                                                                ),
                                                        ),
                                                    'renderer' => 'wp_term_description',
                                                    'label_as_heading' => false,
                                                ),
                                            'heading' => NULL,
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'location-description',
                                                    'margin_enable' => 1,
                                                    'margin_top' => '0',
                                                    'margin_right' => '0',
                                                    'margin_bottom' => '3',
                                                    'margin_left' => '0',
                                                    'font_size' => '',
                                                    'font_size_rel' => '1',
                                                    'font_size_abs' => '16',
                                                    'font_weight' => '',
                                                    'font_style' => '',
                                                ),
                                            'visibility' =>
                                                array (
                                                    'wp_check_role' => false,
                                                    'wp_roles' =>
                                                        array (
                                                        ),
                                                    'globalize' => false,
                                                    'globalize_remove' => false,
                                                ),
                                        ),
                                    'parent_id' => 0,
                                    'weight' => 2,
                                    'system' => false,
                                ),
                            1173 =>
                                array (
                                    'id' => 1173,
                                    'name' => 'entity_child_terms',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'child_count' => '0',
                                                    'columns' => '2',
                                                    'separator' => ', ',
                                                    'show_count' => 1,
                                                    'icon' => 1,
                                                    'icon_settings' =>
                                                        array (
                                                            'size' => 'sm',
                                                            'fallback' => false,
                                                            'is_image' => true,
                                                        ),
                                                    'content_bundle_type' => 'directory__listing',
                                                    'inline' => false,
                                                    'hide_empty' => false,
                                                ),
                                            'heading' =>
                                                array (
                                                    'label' => 'none',
                                                    'label_custom' => '',
                                                    'label_icon' => '',
                                                    'label_icon_size' => '',
                                                ),
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'location-child-terms',
                                                    'margin_enable' => 1,
                                                    'margin_top' => '0',
                                                    'margin_right' => '0',
                                                    'margin_bottom' => '3',
                                                    'margin_left' => '0',
                                                    'cache' => '3600',
                                                ),
                                            'visibility' =>
                                                array (
                                                    'wp_check_role' => false,
                                                    'wp_roles' =>
                                                        array (
                                                        ),
                                                    'globalize' => false,
                                                    'globalize_remove' => false,
                                                ),
                                        ),
                                    'parent_id' => 0,
                                    'weight' => 3,
                                    'system' => false,
                                ),
                            1174 =>
                                array (
                                    'id' => 1174,
                                    'name' => 'entity_field_location_photo',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'label' => 'none',
                                                    'label_custom' => '',
                                                    'label_icon' => 'fas',
                                                    'label_icon_size' => '',
                                                    'renderer' => 'image',
                                                    'renderer_settings' =>
                                                        array (
                                                            'image' =>
                                                                array (
                                                                    'size' => 'large',
                                                                    'width' => 100,
                                                                    'height' => 0,
                                                                    'link' => 'none',
                                                                    'link_image_size' => 'large',
                                                                    '_render_background' => false,
                                                                    '_hover_zoom' => false,
                                                                    '_hover_brighten' => false,
                                                                    '_render_empty' => false,
                                                                ),
                                                            'slider_photos' =>
                                                                array (
                                                                    'size' => 'large',
                                                                    'show_thumbs' => 1,
                                                                    'thumbs_columns' => '6',
                                                                    'effect' => 'slide',
                                                                    'zoom' => 1,
                                                                    'controls' => 1,
                                                                    '_render_empty' => '1',
                                                                    'pager' => false,
                                                                    'auto' => false,
                                                                ),
                                                            'wp_gallery' =>
                                                                array (
                                                                    'cols' => '4',
                                                                    'size' => 'thumbnail',
                                                                    'no_link' => false,
                                                                ),
                                                        ),
                                                    'label_as_heading' => false,
                                                ),
                                            'heading' => NULL,
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'directory-location-photo',
                                                    'margin_enable' => 1,
                                                    'margin_top' => '0',
                                                    'margin_right' => '0',
                                                    'margin_bottom' => '3',
                                                    'margin_left' => '0',
                                                    'font_size' => '',
                                                    'font_size_rel' => '1',
                                                    'font_size_abs' => '16',
                                                    'font_weight' => '',
                                                    'font_style' => '',
                                                ),
                                            'visibility' =>
                                                array (
                                                    'wp_check_role' => false,
                                                    'wp_roles' =>
                                                        array (
                                                        ),
                                                    'globalize' => false,
                                                    'globalize_remove' => false,
                                                ),
                                        ),
                                    'parent_id' => 0,
                                    'weight' => 1,
                                    'system' => false,
                                ),
                        ),
                ),
            'summary' =>
                array (
                    'name' => 'summary',
                    'type' => 'entity',
                    'data' =>
                        array (
                            'css' => '',
                        ),
                    'elements' =>
                        array (
                            1175 =>
                                array (
                                    'id' => 1175,
                                    'name' => 'entity_field_term_title',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'label' => 'none',
                                                    'label_custom' => '',
                                                    'label_icon' => '',
                                                    'label_icon_size' => '',
                                                    'renderer_settings' =>
                                                        array (
                                                            'entity_title' =>
                                                                array (
                                                                    'link' => 'post',
                                                                    'link_target' => '_self',
                                                                    'link_custom_label' => '',
                                                                    'max_chars' => 0,
                                                                    'icon' => 1,
                                                                    'icon_settings' =>
                                                                        array (
                                                                            'size' => '',
                                                                            'fallback' => 1,
                                                                            'is_image' => true,
                                                                        ),
                                                                    'content_bundle_type' => 'directory__listing',
                                                                    '_separator' => '',
                                                                    'show_count' => false,
                                                                    'show_count_label' => false,
                                                                ),
                                                        ),
                                                    'renderer' => 'entity_title',
                                                    'label_as_heading' => false,
                                                ),
                                            'heading' => NULL,
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'directory-location-title',
                                                    'css_id' => '',
                                                    'margin_enable' => 1,
                                                    'margin_top' => '0',
                                                    'margin_right' => '0',
                                                    'margin_bottom' => '2',
                                                    'margin_left' => '0',
                                                    'font_size' => 'rel',
                                                    'font_size_rel' => '1.1',
                                                    'font_size_abs' => '16',
                                                    'font_weight' => '',
                                                    'font_style' => '',
                                                ),
                                            'visibility' =>
                                                array (
                                                    'wp_check_role' => false,
                                                    'wp_roles' =>
                                                        array (
                                                        ),
                                                ),
                                        ),
                                    'parent_id' => 0,
                                    'weight' => 1,
                                    'system' => false,
                                ),
                            1176 =>
                                array (
                                    'id' => 1176,
                                    'name' => 'entity_child_terms',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'child_count' => '0',
                                                    'columns' => '1',
                                                    'separator' => ', ',
                                                    'show_count' => 1,
                                                    'icon_settings' =>
                                                        array (
                                                            'size' => 'sm',
                                                            'fallback' => false,
                                                            'is_image' => true,
                                                        ),
                                                    'content_bundle_type' => 'directory__listing',
                                                    'inline' => false,
                                                    'hide_empty' => false,
                                                    'icon' => false,
                                                ),
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'directory-location-child-terms',
                                                    'cache' => '3600',
                                                ),
                                            'visibility' =>
                                                array (
                                                    'wp_check_role' => false,
                                                    'wp_roles' =>
                                                        array (
                                                        ),
                                                ),
                                            'heading' =>
                                                array (
                                                    'label' => 'none',
                                                    'label_custom' => '',
                                                    'label_icon' => '',
                                                    'label_icon_size' => '',
                                                ),
                                        ),
                                    'parent_id' => 0,
                                    'weight' => 2,
                                    'system' => false,
                                ),
                        ),
                ),
            'summary-image_overlay' =>
                array (
                    'name' => 'summary-image_overlay',
                    'type' => 'entity',
                    'data' =>
                        array (
                            'css' => '',
                        ),
                    'elements' =>
                        array (
                            1177 =>
                                array (
                                    'id' => 1177,
                                    'name' => 'entity_field_location_photo',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'label' => 'none',
                                                    'label_custom' => '',
                                                    'label_icon' => 'fas ',
                                                    'label_icon_size' => '',
                                                    'label_as_heading' => false,
                                                    'renderer' => 'image',
                                                    'renderer_settings' =>
                                                        array (
                                                            'image' =>
                                                                array (
                                                                    'size' => 'medium',
                                                                    'width' => 100,
                                                                    'height' => 0,
                                                                    'cols' => 1,
                                                                    'link' => 'page',
                                                                    'link_image_size' => 'large',
                                                                    '_render_background' => false,
                                                                    '_hover_zoom' => 1,
                                                                    '_hover_brighten' => 1,
                                                                    '_render_empty' => false,
                                                                    '_no_image' => 'thumbnail',
                                                                ),
                                                            'photoslider' =>
                                                                array (
                                                                    'size' => 'large',
                                                                    'show_thumbs' => 1,
                                                                    'thumbs_columns' => '6',
                                                                    'effect' => 'slide',
                                                                    'pager' => false,
                                                                    'auto' => false,
                                                                    'zoom' => 1,
                                                                    'controls' => 1,
                                                                ),
                                                            'wp_gallery' =>
                                                                array (
                                                                    'cols' => '4',
                                                                    'size' => 'thumbnail',
                                                                ),
                                                        ),
                                                ),
                                            'heading' => NULL,
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'directory-location-photo',
                                                ),
                                            'visibility' =>
                                                array (
                                                    'wp_check_role' => false,
                                                    'wp_roles' =>
                                                        array (
                                                        ),
                                                ),
                                        ),
                                    'parent_id' => 0,
                                    'weight' => 1,
                                    'system' => false,
                                ),
                            1178 =>
                                array (
                                    'id' => 1178,
                                    'name' => 'group',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'inline' => false,
                                                    'separator' => '',
                                                ),
                                            'heading' =>
                                                array (
                                                    'label' => 'none',
                                                    'label_custom' => '',
                                                    'label_icon' => 'fas ',
                                                    'label_icon_size' => '',
                                                ),
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'directory-location-overlay drts-display-element-overlay drts-display-element-overlay-center',
                                                ),
                                            'visibility' =>
                                                array (
                                                    'animate' => false,
                                                    'animation' => 'fade-down',
                                                    'wp_check_role' => false,
                                                    'wp_roles' =>
                                                        array (
                                                        ),
                                                ),
                                        ),
                                    'parent_id' => 0,
                                    'weight' => 2,
                                    'system' => false,
                                ),
                            1179 =>
                                array (
                                    'id' => 1179,
                                    'name' => 'entity_field_term_title',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'label' => 'none',
                                                    'label_custom' => '',
                                                    'label_icon' => 'fas',
                                                    'label_icon_size' => '',
                                                    'renderer_settings' =>
                                                        array (
                                                            'entity_title' =>
                                                                array (
                                                                    'link' => 'post',
                                                                    'link_target' => '_self',
                                                                    'link_custom_label' => '',
                                                                    'max_chars' => 0,
                                                                    'icon_settings' =>
                                                                        array (
                                                                            'size' => 'sm',
                                                                            'fallback' => false,
                                                                            'is_image' => true,
                                                                        ),
                                                                    'content_bundle_type' => 'directory__listing',
                                                                    '_separator' => '',
                                                                    'icon' => false,
                                                                    'show_count' => false,
                                                                    'show_count_label' => false,
                                                                ),
                                                        ),
                                                    'renderer' => 'entity_title',
                                                    'label_as_heading' => false,
                                                ),
                                            'heading' => NULL,
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'directory-location-title',
                                                    'css_id' => '',
                                                    'margin_top' => '0',
                                                    'margin_right' => '0',
                                                    'margin_bottom' => '0',
                                                    'margin_left' => '0',
                                                    'font_size' => 'rel',
                                                    'font_size_rel' => '1.3',
                                                    'font_size_abs' => '16',
                                                    'font_weight' => 'bold',
                                                    'font_style' => '',
                                                    'margin_enable' => false,
                                                ),
                                            'visibility' =>
                                                array (
                                                    'wp_check_role' => false,
                                                    'wp_roles' =>
                                                        array (
                                                        ),
                                                ),
                                        ),
                                    'parent_id' => 1178,
                                    'weight' => 3,
                                    'system' => false,
                                ),
                            1180 =>
                                array (
                                    'id' => 1180,
                                    'name' => 'entity_field_entity_term_content_count',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'label' => 'none',
                                                    'label_custom' => '',
                                                    'label_icon' => 'fas',
                                                    'label_icon_size' => '',
                                                    'renderer_settings' =>
                                                        array (
                                                            'entity_term_content_count' =>
                                                                array (
                                                                    'content_bundle_type' => 'directory__listing',
                                                                    '_separator' => '',
                                                                ),
                                                        ),
                                                    'renderer' => 'entity_term_content_count',
                                                    'label_as_heading' => false,
                                                ),
                                            'heading' => NULL,
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'directory-location-content-count',
                                                    'css_id' => '',
                                                    'margin_top' => '0',
                                                    'margin_right' => '0',
                                                    'margin_bottom' => '0',
                                                    'margin_left' => '0',
                                                    'font_size' => 'rel',
                                                    'font_size_rel' => '0.8',
                                                    'font_size_abs' => '16',
                                                    'font_weight' => '',
                                                    'font_style' => '',
                                                    'margin_enable' => false,
                                                ),
                                            'visibility' =>
                                                array (
                                                    'wp_check_role' => false,
                                                    'wp_roles' =>
                                                        array (
                                                        ),
                                                ),
                                        ),
                                    'parent_id' => 1178,
                                    'weight' => 4,
                                    'system' => false,
                                ),
                        ),
                ),
        ),
);
