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
                            1181 =>
                                array (
                                    'id' => 1181,
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
                                                    'css_class' => 'directory-category-description',
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
                            1182 =>
                                array (
                                    'id' => 1182,
                                    'name' => 'entity_child_terms',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'child_count' => '0',
                                                    'columns' => '2',
                                                    'inline' => false,
                                                    'separator' => ', ',
                                                    'hide_empty' => false,
                                                    'show_count' => 1,
                                                    'content_bundle_type' => 'directory__listing',
                                                ),
                                            'heading' =>
                                                array (
                                                    'label' => 'none',
                                                    'label_custom' => '',
                                                    'label_icon' => NULL,
                                                    'label_icon_size' => '',
                                                ),
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'directory-category-child-terms',
                                                    'cache' => '3600',
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
                                    'weight' => 2,
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
                            1183 =>
                                array (
                                    'id' => 1183,
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
                                                    'css_class' => 'directory-category-title',
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
                            1184 =>
                                array (
                                    'id' => 1184,
                                    'name' => 'entity_child_terms',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'child_count' => '0',
                                                    'columns' => '1',
                                                    'inline' => false,
                                                    'separator' => ', ',
                                                    'hide_empty' => false,
                                                    'show_count' => 1,
                                                    'icon' => false,
                                                    'icon_settings' =>
                                                        array (
                                                            'size' => 'sm',
                                                            'field' => 'directory_icon',
                                                            'fallback' => false,
                                                            'color' =>
                                                                array (
                                                                    'type' => '',
                                                                    'custom' => '',
                                                                ),
                                                            'is_image' => false,
                                                        ),
                                                    'content_bundle_type' => 'directory__listing',
                                                ),
                                            'heading' =>
                                                array (
                                                    'label' => 'none',
                                                    'label_custom' => '',
                                                    'label_icon' => NULL,
                                                    'label_icon_size' => '',
                                                ),
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'directory-category-child-terms',
                                                    'cache' => '3600',
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
                                    'weight' => 2,
                                    'system' => false,
                                ),
                        ),
                ),
        ),
);
