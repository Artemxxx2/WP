<?php
return array (
    'entity' =>
        array (
            'detailed' =>
                array (
                    'name' => 'detailed',
                    'type' => 'entity',
                    'data' => false,
                    'elements' =>
                        array (
                            1185 =>
                                array (
                                    'id' => 1185,
                                    'name' => 'entity_field_term_content',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'label' => 'none',
                                                    'label_custom' => '',
                                                    'label_icon' => NULL,
                                                    'label_icon_size' => '',
                                                    'label_as_heading' => false,
                                                    'renderer_settings' =>
                                                        array (
                                                            'wp_term_description' =>
                                                                array (
                                                                    'trim' => false,
                                                                    'trim_length' => 200,
                                                                    'shortcode' => 1,
                                                                ),
                                                        ),
                                                    'renderer' => 'wp_term_description',
                                                ),
                                            'heading' => NULL,
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'directory-tag-description',
                                                ),
                                            'visibility' =>
                                                array (
                                                    'wp_check_role' => false,
                                                    'globalize' => false,
                                                    'globalize_remove' => false,
                                                    'wp_roles' =>
                                                        array (
                                                        ),
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
                    'data' => false,
                    'elements' =>
                        array (
                            1186 =>
                                array (
                                    'id' => 1186,
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
                                                                    'show_count' => 1,
                                                                    'content_bundle_type' => 'directory__listing',
                                                                    '_separator' => '',
                                                                    'show_count_label' => false,
                                                                ),
                                                        ),
                                                    'renderer' => 'entity_title',
                                                    'label_as_heading' => false,
                                                ),
                                            'heading' => NULL,
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'directory-tag-title',
                                                    'css_id' => '',
                                                    'margin_top' => '0',
                                                    'margin_right' => '0',
                                                    'margin_bottom' => '0',
                                                    'margin_left' => '0',
                                                    'font_size' => 'rel',
                                                    'font_size_rel' => '1.1',
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
                                    'parent_id' => 0,
                                    'weight' => 1,
                                    'system' => false,
                                ),
                        ),
                ),
        ),
);