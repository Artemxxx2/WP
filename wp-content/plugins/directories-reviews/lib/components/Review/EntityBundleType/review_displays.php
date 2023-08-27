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
                            1409 =>
                                array (
                                    'id' => 1409,
                                    'name' => 'button',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'size' => '',
                                                    'dropdown' => 1,
                                                    'dropdown_icon' => 'fas fa-cog',
                                                    'dropdown_label' => '',
                                                    'dropdown_right' => 1,
                                                    'separate' => 1,
                                                    'arrangement' =>
                                                        array (
                                                            0 => 'dashboard_posts_edit',
                                                            1 => 'dashboard_posts_delete',
                                                        ),
                                                    'buttons' =>
                                                        array (
                                                            'dashboard_posts_edit' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_hide_label' => false,
                                                                            '_icon' => 'fas fa-edit',
                                                                            '_color' => 'outline-secondary',
                                                                        ),
                                                                ),
                                                            'dashboard_posts_delete' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_hide_label' => false,
                                                                            '_icon' => 'fas fa-trash-alt',
                                                                            '_color' => 'danger',
                                                                        ),
                                                                ),
                                                        ),
                                                    'btn' => true,
                                                    'tooltip' => true,
                                                ),
                                            'heading' => NULL,
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'drts-entity-admin-buttons review-admin-buttons',
                                                ),
                                            'visibility' =>
                                                array (
                                                    'animate' => 1,
                                                    'animation' => 'fade-left',
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
                            1410 =>
                                array (
                                    'id' => 1410,
                                    'name' => 'entity_field_post_content',
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
                                                            'wp_post_content' =>
                                                                array (
                                                                    'trim_length' => 200,
                                                                    'disable_exceprt_more' => 1,
                                                                    'trim' => false,
                                                                ),
                                                        ),
                                                    'renderer' => 'wp_post_content',
                                                    'label_as_heading' => false,
                                                ),
                                            'heading' => NULL,
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'review-body',
                                                    'margin_enable' => 1,
                                                    'margin_top' => '3',
                                                    'margin_right' => '0',
                                                    'margin_bottom' => '0',
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
                                    'weight' => 7,
                                    'system' => false,
                                ),
                            1411 =>
                                array (
                                    'id' => 1411,
                                    'name' => 'entity_field_review_rating',
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
                                                            'review_rating' =>
                                                                array (
                                                                    'format' => 'bars',
                                                                    'color' =>
                                                                        array (
                                                                            'type' => '',
                                                                            'value' => '',
                                                                        ),
                                                                    'decimals' => '1',
                                                                    'inline' => 1,
                                                                    'bar_height' => 5,
                                                                ),
                                                        ),
                                                    'renderer' => 'review_rating',
                                                    'label_as_heading' => false,
                                                ),
                                            'heading' => NULL,
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'review-rating-bars',
                                                    'margin_enable' => 1,
                                                    'margin_top' => '3',
                                                    'margin_right' => '0',
                                                    'margin_bottom' => '0',
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
                                    'weight' => 8,
                                    'system' => false,
                                ),
                            1412 =>
                                array (
                                    'id' => 1412,
                                    'name' => 'entity_field_review_photos',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'label' => 'none',
                                                    'label_custom' => '',
                                                    'label_icon' => 'fas',
                                                    'label_icon_size' => '',
                                                    'renderer' => 'slider_photos',
                                                    'renderer_settings' =>
                                                        array (
                                                            'image' =>
                                                                array (
                                                                    'size' => 'thumbnail',
                                                                    'width' => 100,
                                                                    'height' => 0,
                                                                    'cols' => '6',
                                                                    'link' => 'photo',
                                                                    'link_image_size' => 'medium',
                                                                    '_limit' => 0,
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
                                                                    '_limit' => 0,
                                                                    '_render_empty' => '1',
                                                                    'pager' => false,
                                                                    'auto' => false,
                                                                ),
                                                            'wp_gallery' =>
                                                                array (
                                                                    'cols' => '6',
                                                                    'size' => 'thumbnail',
                                                                    '_limit' => 0,
                                                                    'no_link' => false,
                                                                ),
                                                        ),
                                                    'label_as_heading' => false,
                                                ),
                                            'heading' => NULL,
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'review-photos',
                                                    'margin_enable' => 1,
                                                    'margin_top' => '3',
                                                    'margin_right' => '0',
                                                    'margin_bottom' => '0',
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
                                    'weight' => 9,
                                    'system' => false,
                                ),
                            1413 =>
                                array (
                                    'id' => 1413,
                                    'name' => 'button',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'size' => '',
                                                    'dropdown_icon' => 'fas fa-cog',
                                                    'dropdown_label' => '',
                                                    'separate' => 1,
                                                    'tooltip' => 1,
                                                    'arrangement' =>
                                                        array (
                                                            0 => 'voting_bookmark',
                                                            1 => 'voting_updown',
                                                            2 => 'voting_updown_down',
                                                        ),
                                                    'dropdown' => false,
                                                    'dropdown_right' => false,
                                                    'buttons' =>
                                                        array (
                                                            'voting_updown' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_color' => 'outline-success',
                                                                            '_link_color' => '',
                                                                            'show_count' => 1,
                                                                            '_hide_label' => false,
                                                                        ),
                                                                ),
                                                            'voting_updown_down' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_color' => 'outline-danger',
                                                                            '_link_color' => '',
                                                                            'show_count' => 1,
                                                                            '_hide_label' => false,
                                                                        ),
                                                                ),
                                                            'voting_bookmark' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_color' => 'outline-secondary',
                                                                            '_link_color' => '',
                                                                            '_hide_label' => false,
                                                                            'show_count' => false,
                                                                        ),
                                                                ),
                                                        ),
                                                    'btn' => true,
                                                ),
                                            'heading' => NULL,
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'review-buttons',
                                                    'margin_enable' => 1,
                                                    'margin_top' => '4',
                                                    'margin_right' => '0',
                                                    'margin_bottom' => '0',
                                                    'margin_left' => '0',
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
                                    'weight' => 10,
                                    'system' => false,
                                ),
                            1414 =>
                                array (
                                    'id' => 1414,
                                    'name' => 'wp_comments',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                ),
                                            'heading' =>
                                                array (
                                                    'label' => 'custom',
                                                    'label_custom' => __('Comments', 'directories-reviews'),
                                                    'label_icon' => 'fas ',
                                                    'label_icon_size' => '',
                                                ),
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'review-comments',
                                                ),
                                            'visibility' =>
                                                array (
                                                    'animate' => 1,
                                                    'animation' => 'fade-up',
                                                    'wp_check_role' => false,
                                                    'globalize' => false,
                                                    'globalize_remove' => false,
                                                    'wp_roles' =>
                                                        array (
                                                        ),
                                                ),
                                        ),
                                    'parent_id' => 0,
                                    'weight' => 11,
                                    'system' => false,
                                ),
                            1415 =>
                                array (
                                    'id' => 1415,
                                    'name' => 'group',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'inline' => false,
                                                    'separator' => '',
                                                ),
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'review-info',
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
                                            'heading' =>
                                                array (
                                                    'label' => 'none',
                                                    'label_custom' => '',
                                                    'label_icon' => 'fas ',
                                                    'label_icon_size' => '',
                                                ),
                                        ),
                                    'parent_id' => 0,
                                    'weight' => 2,
                                    'system' => false,
                                ),
                            1416 =>
                                array (
                                    'id' => 1416,
                                    'name' => 'entity_field_review_rating',
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
                                                            'review_rating' =>
                                                                array (
                                                                    'format' => 'stars',
                                                                    'color' =>
                                                                        array (
                                                                            'type' => '',
                                                                            'custom' => '',
                                                                        ),
                                                                    'decimals' => '1',
                                                                    'inline' => false,
                                                                    'bar_height' => 12,
                                                                ),
                                                        ),
                                                    'renderer' => 'review_rating',
                                                ),
                                            'heading' => NULL,
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'review-rating',
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
                                    'parent_id' => 1415,
                                    'weight' => 3,
                                    'system' => false,
                                ),
                            1417 =>
                                array (
                                    'id' => 1417,
                                    'name' => 'group',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'inline' => 1,
                                                    'separator' => ' · ',
                                                    'separator_margin' => 1,
                                                    'nowrap' => false,
                                                ),
                                            'heading' =>
                                                array (
                                                    'label' => 'none',
                                                    'label_custom' => '',
                                                    'label_icon' => 'fas',
                                                    'label_icon_size' => '',
                                                ),
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'review-author-container',
                                                    'margin_enable' => 1,
                                                    'margin_top' => '2',
                                                    'margin_right' => '0',
                                                    'margin_bottom' => '0',
                                                    'margin_left' => '0',
                                                    'padding_top' => '0',
                                                    'padding_right' => '0',
                                                    'padding_bottom' => '0',
                                                    'padding_left' => '0',
                                                    'font_size' => '',
                                                    'font_size_rel' => '1',
                                                    'font_size_abs' => '16',
                                                    'font_weight' => '',
                                                    'font_style' => '',
                                                    'padding_enable' => false,
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
                                    'parent_id' => 1415,
                                    'weight' => 4,
                                    'system' => false,
                                ),
                            1418 =>
                                array (
                                    'id' => 1418,
                                    'name' => 'entity_field_post_author',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'label' => 'none',
                                                    'label_custom' => '',
                                                    'label_icon' => 'fas ',
                                                    'label_icon_size' => '',
                                                    'label_as_heading' => false,
                                                    'renderer_settings' =>
                                                        array (
                                                            'entity_author' =>
                                                                array (
                                                                    'format' => 'link_thumb_s',
                                                                    '_separator' => '',
                                                                ),
                                                        ),
                                                    'renderer' => 'entity_author',
                                                ),
                                            'heading' => NULL,
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'review-author',
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
                                    'parent_id' => 1417,
                                    'weight' => 5,
                                    'system' => false,
                                ),
                            1419 =>
                                array (
                                    'id' => 1419,
                                    'name' => 'entity_field_post_published',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'label' => 'icon',
                                                    'label_custom' => '',
                                                    'label_icon' => 'fas fa-calendar-alt',
                                                    'label_icon_size' => '',
                                                    'label_as_heading' => false,
                                                    'renderer_settings' =>
                                                        array (
                                                            'entity_published' =>
                                                                array (
                                                                    'format' => 'datetime',
                                                                    'permalink' => false,
                                                                    '_separator' => '',
                                                                ),
                                                        ),
                                                    'renderer' => 'entity_published',
                                                ),
                                            'heading' => NULL,
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'review-date',
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
                                    'parent_id' => 1417,
                                    'weight' => 6,
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
                            'css' => '%class% .review-stats {
  text-align: right;
}
.drts-display-rtl.drts-display--summary .review-stats {
  text-align: left;
}',
                        ),
                    'elements' =>
                        array (
                            1420 =>
                                array (
                                    'id' => 1420,
                                    'name' => 'entity_field_post_title',
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
                                                                    '_separator' => '',
                                                                    'icon' => false,
                                                                ),
                                                        ),
                                                    'renderer' => 'entity_title',
                                                    'label_as_heading' => false,
                                                ),
                                            'heading' => NULL,
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'review-title',
                                                    'margin_top' => '0',
                                                    'margin_right' => '0',
                                                    'margin_bottom' => '0',
                                                    'margin_left' => '0',
                                                    'font_size' => 'rel',
                                                    'font_size_rel' => '1.2',
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
                            1421 =>
                                array (
                                    'id' => 1421,
                                    'name' => 'entity_field_review_rating',
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
                                                            'review_rating' =>
                                                                array (
                                                                    'format' => 'stars',
                                                                    'color' =>
                                                                        array (
                                                                            'type' => '',
                                                                            'custom' => '',
                                                                        ),
                                                                    'decimals' => '1',
                                                                    'inline' => false,
                                                                    'bar_height' => 12,
                                                                ),
                                                        ),
                                                    'renderer' => 'review_rating',
                                                ),
                                            'heading' => NULL,
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'review-rating',
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
                            1422 =>
                                array (
                                    'id' => 1422,
                                    'name' => 'entity_field_post_content',
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
                                                            'wp_post_content' =>
                                                                array (
                                                                    'trim' => 1,
                                                                    'trim_length' => 300,
                                                                    'disable_exceprt_more' => 1,
                                                                ),
                                                        ),
                                                    'renderer' => 'wp_post_content',
                                                    'label_as_heading' => false,
                                                ),
                                            'heading' => NULL,
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'review-body',
                                                    'css_id' => '',
                                                    'margin_enable' => 1,
                                                    'margin_top' => '1',
                                                    'margin_right' => '0',
                                                    'margin_bottom' => '0',
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
                                                    'hide_on_parent' => false,
                                                ),
                                        ),
                                    'parent_id' => 0,
                                    'weight' => 7,
                                    'system' => false,
                                ),
                            1423 =>
                                array (
                                    'id' => 1423,
                                    'name' => 'entity_field_review_photos',
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
                                                                    'size' => 'thumbnail',
                                                                    'width' => 100,
                                                                    'height' => 0,
                                                                    'cols' => '6',
                                                                    'link' => 'none',
                                                                    'link_image_size' => 'medium',
                                                                    '_limit' => 0,
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
                                                                    '_limit' => 0,
                                                                    '_render_empty' => '1',
                                                                    'pager' => false,
                                                                    'auto' => false,
                                                                ),
                                                            'wp_gallery' =>
                                                                array (
                                                                    'cols' => '6',
                                                                    'size' => 'thumbnail',
                                                                    '_limit' => 6,
                                                                    'no_link' => false,
                                                                ),
                                                        ),
                                                    'label_as_heading' => false,
                                                ),
                                            'heading' => NULL,
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'review-photos',
                                                    'css_id' => '',
                                                    'margin_enable' => 1,
                                                    'margin_top' => '2',
                                                    'margin_right' => '0',
                                                    'margin_bottom' => '0',
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
                                                    'hide_on_parent' => false,
                                                ),
                                        ),
                                    'parent_id' => 0,
                                    'weight' => 8,
                                    'system' => false,
                                ),
                            1424 =>
                                array (
                                    'id' => 1424,
                                    'name' => 'statistics',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'arrangement' =>
                                                        array (
                                                            0 => 'wp_comments',
                                                            1 => 'voting_updown',
                                                            2 => 'voting_updown_down',
                                                        ),
                                                    'separator' => '   ',
                                                    'hide_empty' => 1,
                                                    'stats' =>
                                                        array (
                                                            'voting_updown' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_format' => 'icon_text',
                                                                            '_link_path' => '',
                                                                            '_link_fragment' => '',
                                                                            '_link' => false,
                                                                        ),
                                                                ),
                                                            'voting_updown_down' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_format' => 'icon_num',
                                                                            '_link_path' => '',
                                                                            '_link_fragment' => '',
                                                                            '_link' => false,
                                                                        ),
                                                                ),
                                                            'voting_bookmark' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_format' => 'icon_num',
                                                                            '_link_path' => '',
                                                                            '_link_fragment' => '',
                                                                            '_link' => false,
                                                                        ),
                                                                ),
                                                            'wp_comments' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_format' => 'icon_num',
                                                                            '_icon' => 'fas fa-comment',
                                                                            '_link_path' => '',
                                                                            '_link_fragment' => '',
                                                                            '_link' => false,
                                                                        ),
                                                                ),
                                                        ),
                                                    'statistics' =>
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
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'review-stats',
                                                    'margin_enable' => 1,
                                                    'margin_top' => '3',
                                                    'margin_right' => '0',
                                                    'margin_bottom' => '0',
                                                    'margin_left' => '0',
                                                    'padding_top' => '0',
                                                    'padding_right' => '0',
                                                    'padding_bottom' => '0',
                                                    'padding_left' => '0',
                                                    'font_size' => 'rel',
                                                    'font_size_rel' => '0.9',
                                                    'font_size_abs' => '16',
                                                    'font_weight' => '',
                                                    'font_style' => '',
                                                    'padding_enable' => false,
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
                                    'weight' => 9,
                                    'system' => false,
                                ),
                            1425 =>
                                array (
                                    'id' => 1425,
                                    'name' => 'group',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'inline' => 1,
                                                    'separator' => ' · ',
                                                    'separator_margin' => 1,
                                                    'nowrap' => false,
                                                ),
                                            'heading' =>
                                                array (
                                                    'label' => 'none',
                                                    'label_custom' => '',
                                                    'label_icon' => 'fas',
                                                    'label_icon_size' => '',
                                                ),
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'review-author-container',
                                                    'margin_enable' => 1,
                                                    'margin_top' => '2',
                                                    'margin_right' => '0',
                                                    'margin_bottom' => '0',
                                                    'margin_left' => '0',
                                                    'padding_top' => '0',
                                                    'padding_right' => '0',
                                                    'padding_bottom' => '0',
                                                    'padding_left' => '0',
                                                    'font_size' => '',
                                                    'font_size_rel' => '1',
                                                    'font_size_abs' => '16',
                                                    'font_weight' => '',
                                                    'font_style' => '',
                                                    'padding_enable' => false,
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
                                    'weight' => 3,
                                    'system' => false,
                                ),
                            1426 =>
                                array (
                                    'id' => 1426,
                                    'name' => 'entity_field_post_author',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'label' => 'none',
                                                    'label_custom' => '',
                                                    'label_icon' => 'fas ',
                                                    'label_icon_size' => '',
                                                    'label_as_heading' => false,
                                                    'renderer_settings' =>
                                                        array (
                                                            'entity_author' =>
                                                                array (
                                                                    'format' => 'link_thumb_s',
                                                                    '_separator' => '',
                                                                ),
                                                        ),
                                                    'renderer' => 'entity_author',
                                                ),
                                            'heading' => NULL,
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'review-author',
                                                    'margin_enable' => 1,
                                                    'margin_top' => '0',
                                                    'margin_right' => '0',
                                                    'margin_bottom' => '1',
                                                    'margin_left' => '0',
                                                    'padding_top' => '0',
                                                    'padding_right' => '0',
                                                    'padding_bottom' => '0',
                                                    'padding_left' => '0',
                                                    'font_size' => '',
                                                    'font_size_rel' => '1',
                                                    'font_size_abs' => '16',
                                                    'font_weight' => '',
                                                    'font_style' => '',
                                                    'padding_enable' => false,
                                                ),
                                            'visibility' =>
                                                array (
                                                    'wp_check_role' => false,
                                                    'wp_roles' =>
                                                        array (
                                                        ),
                                                ),
                                        ),
                                    'parent_id' => 1425,
                                    'weight' => 4,
                                    'system' => false,
                                ),
                            1427 =>
                                array (
                                    'id' => 1427,
                                    'name' => 'entity_field_post_published',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'label' => 'icon',
                                                    'label_custom' => '',
                                                    'label_icon' => 'fas fa-calendar-alt',
                                                    'label_icon_size' => '',
                                                    'label_as_heading' => false,
                                                    'renderer_settings' =>
                                                        array (
                                                            'entity_published' =>
                                                                array (
                                                                    'format' => 'date',
                                                                    'permalink' => false,
                                                                    '_separator' => '',
                                                                ),
                                                        ),
                                                    'renderer' => 'entity_published',
                                                ),
                                            'heading' => NULL,
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'review-date',
                                                    'margin_enable' => 1,
                                                    'margin_top' => '0',
                                                    'margin_right' => '0',
                                                    'margin_bottom' => '1',
                                                    'margin_left' => '0',
                                                    'padding_top' => '0',
                                                    'padding_right' => '0',
                                                    'padding_bottom' => '0',
                                                    'padding_left' => '0',
                                                    'font_size' => '',
                                                    'font_size_rel' => '1',
                                                    'font_size_abs' => '16',
                                                    'font_weight' => '',
                                                    'font_style' => '',
                                                    'padding_enable' => false,
                                                ),
                                            'visibility' =>
                                                array (
                                                    'wp_check_role' => false,
                                                    'wp_roles' =>
                                                        array (
                                                        ),
                                                ),
                                        ),
                                    'parent_id' => 1425,
                                    'weight' => 5,
                                    'system' => false,
                                ),
                            1428 =>
                                array (
                                    'id' => 1428,
                                    'name' => 'entity_parent_field_post_title',
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
                                                            'entity_title' =>
                                                                array (
                                                                    'link' => 'post',
                                                                    'link_field' => 'field_website,value',
                                                                    'link_target' => '_self',
                                                                    'max_chars' => 0,
                                                                    'icon' => 1,
                                                                    'icon_settings' =>
                                                                        array (
                                                                            'size' => 'sm',
                                                                            'field' => 'directory_photos',
                                                                            'fallback' => false,
                                                                            'color' =>
                                                                                array (
                                                                                    'type' => '',
                                                                                    'custom' => '',
                                                                                ),
                                                                            'is_image' => true,
                                                                        ),
                                                                    '_separator' => '',
                                                                    'link_rel' =>
                                                                        array (
                                                                        ),
                                                                ),
                                                        ),
                                                    'renderer' => 'entity_title',
                                                ),
                                            'heading' => NULL,
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'review-listing',
                                                    'margin_enable' => 1,
                                                    'margin_top' => '0',
                                                    'margin_right' => '0',
                                                    'margin_bottom' => '1',
                                                    'margin_left' => '0',
                                                    'padding_top' => '0',
                                                    'padding_right' => '0',
                                                    'padding_bottom' => '0',
                                                    'padding_left' => '0',
                                                    'font_size' => '',
                                                    'font_size_rel' => '1',
                                                    'font_size_abs' => '16',
                                                    'font_weight' => '',
                                                    'font_style' => '',
                                                    'padding_enable' => false,
                                                ),
                                            'visibility' =>
                                                array (
                                                    'hide_on_parent' => 1,
                                                    'wp_check_role' => false,
                                                    'wp_roles' =>
                                                        array (
                                                        ),
                                                ),
                                        ),
                                    'parent_id' => 1425,
                                    'weight' => 6,
                                    'system' => false,
                                ),
                        ),
                ),
            'dashboard_row' =>
                array (
                    'name' => 'dashboard_row',
                    'type' => 'entity',
                    'data' =>
                        array (
                            'css' => '',
                        ),
                    'elements' =>
                        array (
                            1429 =>
                                array (
                                    'id' => 1429,
                                    'name' => 'group',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'inline' => 1,
                                                    'separator' => ' ',
                                                ),
                                            'heading' =>
                                                array (
                                                    'label' => 'custom',
                                                    'label_custom' => __('Title', 'directories-reviews'),
                                                    'label_icon' => '',
                                                    'label_icon_size' => '',
                                                ),
                                            'design' => NULL,
                                            'visibility' =>
                                                array (
                                                    'wp_check_role' => false,
                                                    'wp_roles' =>
                                                        array (
                                                        ),
                                                ),
                                            'advanced' => NULL,
                                        ),
                                    'parent_id' => 0,
                                    'weight' => 1,
                                    'system' => false,
                                ),
                            1430 =>
                                array (
                                    'id' => 1430,
                                    'name' => 'entity_field_review_rating',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'label' => 'none',
                                                    'label_custom' => '',
                                                    'label_icon' => NULL,
                                                    'label_icon_size' => '',
                                                    'label_as_heading' => 1,
                                                    'renderer_settings' =>
                                                        array (
                                                            'review_rating' =>
                                                                array (
                                                                    'format' => 'stars',
                                                                    'color' =>
                                                                        array (
                                                                            'type' => '',
                                                                            'value' => '',
                                                                        ),
                                                                    'decimals' => '1',
                                                                    'inline' => false,
                                                                    'bar_height' => 12,
                                                                ),
                                                        ),
                                                    'renderer' => 'review_rating',
                                                ),
                                            'heading' => NULL,
                                            'advanced' => NULL,
                                            'visibility' =>
                                                array (
                                                    'wp_check_role' => false,
                                                    'wp_roles' =>
                                                        array (
                                                        ),
                                                ),
                                        ),
                                    'parent_id' => 1429,
                                    'weight' => 2,
                                    'system' => false,
                                ),
                            1431 =>
                                array (
                                    'id' => 1431,
                                    'name' => 'entity_field_post_title',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'label' => 'none',
                                                    'label_custom' => '',
                                                    'label_icon' => NULL,
                                                    'label_icon_size' => '',
                                                    'label_as_heading' => 1,
                                                    'renderer_settings' =>
                                                        array (
                                                            'entity_title' =>
                                                                array (
                                                                    'link' => 'post',
                                                                    'link_target' => '_self',
                                                                    'max_chars' => 0,
                                                                    'icon' => false,
                                                                    'icon_settings' =>
                                                                        array (
                                                                            'size' => 'sm',
                                                                            'field' => 'review_photos',
                                                                            'fallback' => false,
                                                                            'color' =>
                                                                                array (
                                                                                    'type' => '',
                                                                                    'custom' => '',
                                                                                ),
                                                                            'is_image' => true,
                                                                        ),
                                                                    '_separator' => '',
                                                                ),
                                                        ),
                                                    'renderer' => 'entity_title',
                                                ),
                                            'heading' => NULL,
                                            'advanced' => NULL,
                                            'visibility' =>
                                                array (
                                                    'wp_check_role' => false,
                                                    'wp_roles' =>
                                                        array (
                                                        ),
                                                ),
                                        ),
                                    'parent_id' => 1429,
                                    'weight' => 3,
                                    'system' => false,
                                ),
                            1432 =>
                                array (
                                    'id' => 1432,
                                    'name' => 'statistics',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'arrangement' =>
                                                        array (
                                                            0 => 'voting_updown',
                                                            1 => 'voting_updown_down',
                                                            2 => 'wp_comments',
                                                        ),
                                                    'separator' => ' · ',
                                                    'hide_empty' => 1,
                                                    'stats' =>
                                                        array (
                                                            'voting_updown' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_format' => 'icon_num',
                                                                            '_color' => 'success',
                                                                            '_link' => false,
                                                                            '_link_path' => '',
                                                                            '_link_fragment' => '',
                                                                        ),
                                                                ),
                                                            'voting_updown_down' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_format' => 'icon_num',
                                                                            '_color' => 'danger',
                                                                            '_link' => false,
                                                                            '_link_path' => '',
                                                                            '_link_fragment' => '',
                                                                        ),
                                                                ),
                                                            'voting_bookmark' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_format' => 'icon_num',
                                                                            '_color' => '',
                                                                            '_link' => false,
                                                                            '_link_path' => '',
                                                                            '_link_fragment' => '',
                                                                        ),
                                                                ),
                                                            'wp_comments' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_format' => 'icon_num',
                                                                            '_icon' => 'fas fa-comment',
                                                                            '_color' => '',
                                                                            '_link' => false,
                                                                            '_link_path' => '',
                                                                            '_link_fragment' => '',
                                                                        ),
                                                                ),
                                                        ),
                                                    'statistics' =>
                                                        array (
                                                        ),
                                                ),
                                            'heading' =>
                                                array (
                                                    'label' => 'none',
                                                    'label_custom' => '',
                                                    'label_icon' => 'fas ',
                                                    'label_icon_size' => '',
                                                ),
                                            'advanced' => NULL,
                                            'visibility' =>
                                                array (
                                                    'wp_check_role' => false,
                                                    'wp_roles' =>
                                                        array (
                                                        ),
                                                ),
                                        ),
                                    'parent_id' => 1429,
                                    'weight' => 4,
                                    'system' => false,
                                ),
                            1433 =>
                                array (
                                    'id' => 1433,
                                    'name' => 'entity_parent_field_post_title',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'label' => 'custom',
                                                    'label_custom' => __('Listing', 'directories-reviews'),
                                                    'label_icon' => NULL,
                                                    'label_icon_size' => '',
                                                    'label_as_heading' => 1,
                                                    'renderer_settings' =>
                                                        array (
                                                            'entity_title' =>
                                                                array (
                                                                    'link' => 'post',
                                                                    'link_field' => 'field_website,value',
                                                                    'link_target' => '_self',
                                                                    'max_chars' => 0,
                                                                    'icon' => 1,
                                                                    'icon_settings' =>
                                                                        array (
                                                                            'size' => '',
                                                                            'field' => 'directory_photos',
                                                                            'fallback' => false,
                                                                            'color' =>
                                                                                array (
                                                                                    'type' => '',
                                                                                    'custom' => '',
                                                                                ),
                                                                            'is_image' => true,
                                                                        ),
                                                                    '_separator' => '',
                                                                    'link_rel' =>
                                                                        array (
                                                                        ),
                                                                ),
                                                        ),
                                                    'renderer' => 'entity_title',
                                                ),
                                            'heading' => NULL,
                                            'advanced' => NULL,
                                            'visibility' =>
                                                array (
                                                    'wp_check_role' => false,
                                                    'wp_roles' =>
                                                        array (
                                                        ),
                                                ),
                                        ),
                                    'parent_id' => 0,
                                    'weight' => 5,
                                    'system' => false,
                                ),
                            1434 =>
                                array (
                                    'id' => 1434,
                                    'name' => 'entity_field_post_published',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'label' => 'custom',
                                                    'label_custom' => __('Date', 'directories-reviews'),
                                                    'label_icon' => '',
                                                    'label_icon_size' => '',
                                                    'label_as_heading' => 1,
                                                    'renderer_settings' =>
                                                        array (
                                                            'entity_published' =>
                                                                array (
                                                                    'format' => 'date',
                                                                    'permalink' => false,
                                                                ),
                                                        ),
                                                    'renderer' => 'entity_published',
                                                ),
                                            'heading' => NULL,
                                            'design' => NULL,
                                            'visibility' =>
                                                array (
                                                    'wp_check_role' => false,
                                                    'wp_roles' =>
                                                        array (
                                                        ),
                                                ),
                                            'advanced' => NULL,
                                        ),
                                    'parent_id' => 0,
                                    'weight' => 6,
                                    'system' => false,
                                ),
                            1435 =>
                                array (
                                    'id' => 1435,
                                    'name' => 'button',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'size' => '',
                                                    'dropdown' => 1,
                                                    'dropdown_icon' => 'fas fa-cog',
                                                    'dropdown_label' => '',
                                                    'dropdown_right' => 1,
                                                    'separate' => 1,
                                                    'arrangement' =>
                                                        array (
                                                            0 => 'dashboard_posts_edit',
                                                            1 => 'dashboard_posts_delete',
                                                            2 => 'dashboard_posts_submit',
                                                        ),
                                                    'buttons' =>
                                                        array (
                                                            'dashboard_posts_edit' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_hide_label' => false,
                                                                            '_icon' => 'fas fa-edit',
                                                                            '_color' => 'outline-secondary',
                                                                        ),
                                                                ),
                                                            'dashboard_posts_delete' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_hide_label' => false,
                                                                            '_icon' => 'fas fa-trash-alt',
                                                                            '_color' => 'danger',
                                                                        ),
                                                                ),
                                                            'dashboard_posts_submit' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_hide_label' => false,
                                                                            '_icon' => 'fas fa-plus',
                                                                            '_color' => 'outline-secondary',
                                                                        ),
                                                                ),
                                                        ),
                                                    'btn' => true,
                                                    'tooltip' => true,
                                                ),
                                            'heading' => NULL,
                                            'advanced' => NULL,
                                            'visibility' =>
                                                array (
                                                    'wp_check_role' => false,
                                                    'wp_roles' =>
                                                        array (
                                                        ),
                                                ),
                                        ),
                                    'parent_id' => 0,
                                    'weight' => 7,
                                    'system' => false,
                                ),
                        ),
                ),
        ),
    'filters' =>
        array (
            'default' =>
                array (
                    'name' => 'default',
                    'type' => 'filters',
                    'data' => NULL,
                    'elements' =>
                        array (
                            1436 =>
                                array (
                                    'id' => 1436,
                                    'name' => 'view_filter_post_content',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'name' => 'post_content',
                                                    'label' => 'custom',
                                                    'label_custom' => __('Search reviews', 'directories-reviews'),
                                                    'label_icon' => NULL,
                                                    'label_icon_size' => '',
                                                    'label_as_heading' => false,
                                                    'filter' => 'keyword',
                                                    'field_name' => 'post_content',
                                                    'filter_name' => 'filter_post_content',
                                                    'display_name' => 'default',
                                                    'filter_settings' =>
                                                        array (
                                                            'keyword' =>
                                                                array (
                                                                    'min_length' => 3,
                                                                    'match' => 'all',
                                                                    'placeholder' => '',
                                                                    'inc_title' => true,
                                                                ),
                                                        ),
                                                ),
                                            'heading' => NULL,
                                            'advanced' => NULL,
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
                            1437 =>
                                array (
                                    'id' => 1437,
                                    'name' => 'view_filter_review_rating',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'name' => 'review_rating',
                                                    'label' => 'form',
                                                    'label_custom' => '',
                                                    'label_icon' => NULL,
                                                    'label_icon_size' => '',
                                                    'label_as_heading' => false,
                                                    'filter' => 'review_rating',
                                                    'field_name' => 'review_rating',
                                                    'filter_name' => 'filter_review_rating',
                                                    'display_name' => 'default',
                                                    'filter_settings' =>
                                                        array (
                                                            'review_rating' =>
                                                                array (
                                                                ),
                                                        ),
                                                ),
                                            'heading' => NULL,
                                            'advanced' => NULL,
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