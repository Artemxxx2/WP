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
                            1057 =>
                                array (
                                    'id' => 1057,
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
                                                    'css_class' => 'drts-entity-admin-buttons directory-listing-admin-buttons',
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
                            1058 =>
                                array (
                                    'id' => 1058,
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
                                                            1 => 'frontendsubmit_add_review_review',
                                                            2 => 'claiming_claim',
                                                        ),
                                                    'dropdown' => false,
                                                    'dropdown_right' => false,
                                                    'buttons' =>
                                                        array (
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
                                                            'claiming_claim' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_color' => 'outline-warning',
                                                                            '_link_color' => '',
                                                                            '_hide_label' => false,
                                                                        ),
                                                                ),
                                                            'frontendsubmit_add_review_review' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_color' => 'outline-primary',
                                                                            '_link_color' => '',
                                                                            '_hide_label' => false,
                                                                        ),
                                                                ),
                                                        ),
                                                    'btn' => true,
                                                ),
                                            'heading' => NULL,
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'directory-listing-buttons',
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
                                    'weight' => 9,
                                    'system' => false,
                                ),
                            1059 =>
                                array (
                                    'id' => 1059,
                                    'name' => 'columns',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'gutter_width' => 'md',
                                                    'columns' => 3,
                                                ),
                                            'heading' =>
                                                array (
                                                    'label' => 'custom',
                                                    'label_custom' => __('Contact Information', 'directories-pro'),
                                                    'label_icon' => 'fas ',
                                                    'label_icon_size' => '',
                                                ),
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'directory-listing-contact-info-container',
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
                                    'weight' => 10,
                                    'system' => false,
                                ),
                            1060 =>
                                array (
                                    'id' => 1060,
                                    'name' => 'column',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'width' => 'responsive',
                                                    'responsive' =>
                                                        array (
                                                            'xs' =>
                                                                array (
                                                                    'width' => '12',
                                                                ),
                                                            'sm' =>
                                                                array (
                                                                    'width' => NULL,
                                                                ),
                                                            'md' =>
                                                                array (
                                                                    'width' => '6',
                                                                ),
                                                            'lg' =>
                                                                array (
                                                                    'width' => NULL,
                                                                ),
                                                            'xl' =>
                                                                array (
                                                                    'width' => NULL,
                                                                ),
                                                            'grow' => false,
                                                        ),
                                                ),
                                            'heading' =>
                                                array (
                                                    'label' => 'none',
                                                    'label_custom' => '',
                                                    'label_icon' => NULL,
                                                    'label_icon_size' => '',
                                                ),
                                            'advanced' => NULL,
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
                                    'parent_id' => 1059,
                                    'weight' => 11,
                                    'system' => false,
                                ),
                            1061 =>
                                array (
                                    'id' => 1061,
                                    'name' => 'entity_fieldlist',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'size' => '',
                                                    'no_border' => false,
                                                    'inline' => true,
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
                                                    'css_class' => 'directory-listing-contact-info',
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
                                    'parent_id' => 1060,
                                    'weight' => 12,
                                    'system' => false,
                                ),
                            1062 =>
                                array (
                                    'id' => 1062,
                                    'name' => 'column',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'width' => 'responsive',
                                                    'responsive' =>
                                                        array (
                                                            'xs' =>
                                                                array (
                                                                    'width' => '12',
                                                                ),
                                                            'sm' =>
                                                                array (
                                                                    'width' => NULL,
                                                                ),
                                                            'md' =>
                                                                array (
                                                                    'width' => '6',
                                                                ),
                                                            'lg' =>
                                                                array (
                                                                    'width' => NULL,
                                                                ),
                                                            'xl' =>
                                                                array (
                                                                    'width' => NULL,
                                                                ),
                                                            'grow' => false,
                                                        ),
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
                                                    'css_class' => 'drts-display-element-overflow-visible',
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
                                    'parent_id' => 1059,
                                    'weight' => 19,
                                    'system' => false,
                                ),
                            1063 =>
                                array (
                                    'id' => 1063,
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
                                                    'label' => 'custom',
                                                    'label_custom' => __('Detailed Information', 'directories-pro'),
                                                    'label_icon' => 'fas ',
                                                    'label_icon_size' => '',
                                                ),
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'directory-listing-detailed-info-container',
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
                                    'weight' => 21,
                                    'system' => false,
                                ),
                            1064 =>
                                array (
                                    'id' => 1064,
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
                                                    'css_class' => 'directory-listing-description',
                                                    'margin_enable' => 1,
                                                    'margin_top' => '0',
                                                    'margin_right' => '0',
                                                    'margin_bottom' => '4',
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
                                    'parent_id' => 1063,
                                    'weight' => 22,
                                    'system' => false,
                                ),
                            1065 =>
                                array (
                                    'id' => 1065,
                                    'name' => 'entity_fieldlist',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'size' => '',
                                                    'no_border' => 1,
                                                    'inline' => true,
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
                                                    'css_class' => 'directory-listing-extra-info',
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
                                    'parent_id' => 1063,
                                    'weight' => 23,
                                    'system' => false,
                                ),
                            1066 =>
                                array (
                                    'id' => 1066,
                                    'name' => 'entity_field_location_address',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'label' => 'icon',
                                                    'label_custom' => '',
                                                    'label_icon' => 'fas fa-map-marker-alt',
                                                    'label_icon_size' => '',
                                                    'label_as_heading' => false,
                                                    'renderer' => 'location_address',
                                                    'renderer_settings' =>
                                                        array (
                                                            'map_map' =>
                                                                array (
                                                                    'height' => 300,
                                                                    'zoom_control' => 1,
                                                                    'map_type_control' => 1,
                                                                    'fullscreen_control' => 1,
                                                                ),
                                                            'location_address' =>
                                                                array (
                                                                    'custom_format' => 0,
                                                                    'format' => '{street}, {city}, {province} {zip}',
                                                                    'link' => false,
                                                                    '_separator' => '<br />',
                                                                ),
                                                        ),
                                                ),
                                            'heading' => NULL,
                                            'advanced' => NULL,
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
                                    'parent_id' => 1061,
                                    'weight' => 13,
                                    'system' => false,
                                ),
                            1067 =>
                                array (
                                    'id' => 1067,
                                    'name' => 'entity_field_field_phone',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'label' => 'icon',
                                                    'label_custom' => '',
                                                    'label_icon' => 'fas fa-phone',
                                                    'label_icon_size' => '',
                                                    'label_as_heading' => false,
                                                    'renderer_settings' =>
                                                        array (
                                                            'phone' =>
                                                                array (
                                                                    '_separator' => ', ',
                                                                ),
                                                        ),
                                                    'renderer' => 'phone',
                                                ),
                                            'heading' => NULL,
                                            'advanced' => NULL,
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
                                    'parent_id' => 1061,
                                    'weight' => 14,
                                    'system' => false,
                                ),
                            1068 =>
                                array (
                                    'id' => 1068,
                                    'name' => 'entity_field_field_fax',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'label' => 'icon',
                                                    'label_custom' => '',
                                                    'label_icon' => 'fas fa-fax',
                                                    'label_icon_size' => '',
                                                    'label_as_heading' => false,
                                                    'renderer_settings' =>
                                                        array (
                                                            'phone' =>
                                                                array (
                                                                    '_separator' => ', ',
                                                                ),
                                                        ),
                                                    'renderer' => 'phone',
                                                ),
                                            'heading' => NULL,
                                            'advanced' => NULL,
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
                                    'parent_id' => 1061,
                                    'weight' => 15,
                                    'system' => false,
                                ),
                            1069 =>
                                array (
                                    'id' => 1069,
                                    'name' => 'entity_field_field_email',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'label' => 'icon',
                                                    'label_custom' => '',
                                                    'label_icon' => 'fas fa-envelope',
                                                    'label_icon_size' => '',
                                                    'label_as_heading' => false,
                                                    'renderer_settings' =>
                                                        array (
                                                            'email' =>
                                                                array (
                                                                    'type' => 'default',
                                                                    'label' => 'custom label',
                                                                    'target' => '_self',
                                                                    '_separator' => ', ',
                                                                ),
                                                        ),
                                                    'renderer' => 'email',
                                                ),
                                            'heading' => NULL,
                                            'advanced' => NULL,
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
                                    'parent_id' => 1061,
                                    'weight' => 16,
                                    'system' => false,
                                ),
                            1070 =>
                                array (
                                    'id' => 1070,
                                    'name' => 'entity_field_field_website',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'label' => 'icon',
                                                    'label_custom' => '',
                                                    'label_icon' => 'fas fa-globe',
                                                    'label_icon_size' => '',
                                                    'label_as_heading' => false,
                                                    'renderer_settings' =>
                                                        array (
                                                            'url' =>
                                                                array (
                                                                    'type' => 'default',
                                                                    'label' => '',
                                                                    'max_len' => '40',
                                                                    'target' => '_blank',
                                                                    'rel' =>
                                                                        array (
                                                                            0 => 'nofollow',
                                                                            1 => 'external',
                                                                        ),
                                                                    '_separator' => ', ',
                                                                ),
                                                        ),
                                                    'renderer' => 'url',
                                                ),
                                            'heading' => NULL,
                                            'advanced' => NULL,
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
                                    'parent_id' => 1061,
                                    'weight' => 17,
                                    'system' => false,
                                ),
                            1071 =>
                                array (
                                    'id' => 1071,
                                    'name' => 'entity_field_field_social_accounts',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'label' => 'none',
                                                    'label_custom' => '',
                                                    'label_icon' => NULL,
                                                    'label_icon_size' => '',
                                                    'label_as_heading' => false,
                                                    'renderer' => 'social_accounts',
                                                    'renderer_settings' =>
                                                        array (
                                                            'social_accounts' =>
                                                                array (
                                                                    'size' => 'fa-lg',
                                                                    'target' => '_blank',
                                                                    'rel' =>
                                                                        array (
                                                                            0 => 'nofollow',
                                                                            1 => 'external',
                                                                        ),
                                                                    '_limit' => 0,
                                                                    '_separator' => ' ',
                                                                ),
                                                            'social_twitter_feed' =>
                                                                array (
                                                                    'height' => 600,
                                                                    '_limit' => 0,
                                                                    '_separator' => '',
                                                                ),
                                                            'social_facebook_page' =>
                                                                array (
                                                                    'height' => 600,
                                                                    '_limit' => 0,
                                                                    '_separator' => '',
                                                                ),
                                                        ),
                                                ),
                                            'heading' => NULL,
                                            'advanced' => NULL,
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
                                    'parent_id' => 1061,
                                    'weight' => 18,
                                    'system' => false,
                                ),
                            1072 =>
                                array (
                                    'id' => 1072,
                                    'name' => 'entity_field_field_opening_hours',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'label' => 'form',
                                                    'label_custom' => '',
                                                    'label_icon' => 'fas fa-clock',
                                                    'label_icon_size' => '',
                                                    'label_as_heading' => 1,
                                                    'renderer' => 'directory_opening_hours',
                                                    'renderer_settings' =>
                                                        array (
                                                            'time' =>
                                                                array (
                                                                    '_limit' => 0,
                                                                    '_separator' => ', ',
                                                                ),
                                                            'directory_opening_hours' =>
                                                                array (
                                                                    'show_closed' => 1,
                                                                    'closed' => 'Closed',
                                                                    '_limit' => 0,
                                                                    '_separator' => ', ',
                                                                ),
                                                        ),
                                                ),
                                            'heading' => NULL,
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'directory-listing-opening-hours',
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
                                    'weight' => 26,
                                    'system' => false,
                                ),
                            1073 =>
                                array (
                                    'id' => 1073,
                                    'name' => 'entity_field_directory_photos',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'label' => 'none',
                                                    'label_custom' => '',
                                                    'label_icon' => NULL,
                                                    'label_icon_size' => '',
                                                    'label_as_heading' => false,
                                                    'renderer' => 'photoslider',
                                                    'renderer_settings' =>
                                                        array (
                                                            'image' =>
                                                                array (
                                                                    'size' => 'thumbnail',
                                                                    'width' => 100,
                                                                    'height' => 0,
                                                                    'cols' => '4',
                                                                    'link' => 'photo',
                                                                    'link_image_size' => 'medium',
                                                                    '_limit' => 0,
                                                                    '_render_background' => false,
                                                                    '_hover_zoom' => false,
                                                                    '_hover_brighten' => false,
                                                                    '_render_empty' => false,
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
                                                                    'show_videos' => 1,
                                                                    'video_field' => 'field_videos',
                                                                    'prepend_videos' => 1,
                                                                    'num_videos' => '1',
                                                                    '_limit' => 0,
                                                                ),
                                                            'wp_gallery' =>
                                                                array (
                                                                    'cols' => '4',
                                                                    'size' => 'thumbnail',
                                                                    '_limit' => 0,
                                                                ),
                                                        ),
                                                ),
                                            'heading' => NULL,
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'directory-listing-photos',
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
                                    'weight' => 8,
                                    'system' => false,
                                ),
                            1074 =>
                                array (
                                    'id' => 1074,
                                    'name' => 'entity_field_field_price_range',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'label' => 'form_icon',
                                                    'label_custom' => '',
                                                    'label_icon' => 'fas fa-dollar-sign',
                                                    'label_icon_size' => '',
                                                    'label_as_heading' => false,
                                                    'renderer_settings' =>
                                                        array (
                                                            'range' =>
                                                                array (
                                                                    'dec_point' => '.',
                                                                    'thousands_sep' => ',',
                                                                    'range_sep' => ' to ',
                                                                    '_separator' => ' ',
                                                                ),
                                                        ),
                                                    'renderer' => 'range',
                                                ),
                                            'heading' => NULL,
                                            'advanced' => NULL,
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
                                    'parent_id' => 1065,
                                    'weight' => 25,
                                    'system' => false,
                                ),
                            1075 =>
                                array (
                                    'id' => 1075,
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
                                                    'label' => 'custom',
                                                    'label_custom' => __('Reviews', 'directories-pro'),
                                                    'label_icon' => 'fas ',
                                                    'label_icon_size' => '',
                                                ),
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'directory-listing-review-container',
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
                                    'weight' => 27,
                                    'system' => false,
                                ),
                            1076 =>
                                array (
                                    'id' => 1076,
                                    'name' => 'columns',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'gutter_width' => 'lg',
                                                    'columns' => 3,
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
                                                    'css_class' => 'directory-listing-review-ratings',
                                                    'margin_enable' => 1,
                                                    'margin_top' => '0',
                                                    'margin_right' => '0',
                                                    'margin_bottom' => '4',
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
                                    'parent_id' => 1075,
                                    'weight' => 29,
                                    'system' => false,
                                ),
                            1077 =>
                                array (
                                    'id' => 1077,
                                    'name' => 'column',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'width' => 'responsive',
                                                    'responsive' =>
                                                        array (
                                                            'xs' =>
                                                                array (
                                                                    'width' => '12',
                                                                ),
                                                            'sm' =>
                                                                array (
                                                                    'width' => NULL,
                                                                ),
                                                            'md' =>
                                                                array (
                                                                    'width' => '4',
                                                                ),
                                                            'lg' =>
                                                                array (
                                                                    'width' => NULL,
                                                                ),
                                                            'xl' =>
                                                                array (
                                                                    'width' => NULL,
                                                                ),
                                                            'grow' => false,
                                                        ),
                                                ),
                                            'heading' =>
                                                array (
                                                    'label' => 'none',
                                                    'label_custom' => '',
                                                    'label_icon' => NULL,
                                                    'label_icon_size' => '',
                                                ),
                                            'advanced' => NULL,
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
                                    'parent_id' => 1076,
                                    'weight' => 30,
                                    'system' => false,
                                ),
                            1078 =>
                                array (
                                    'id' => 1078,
                                    'name' => 'column',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'width' => 'responsive',
                                                    'responsive' =>
                                                        array (
                                                            'xs' =>
                                                                array (
                                                                    'width' => '12',
                                                                    'grow' => 1,
                                                                ),
                                                            'sm' =>
                                                                array (
                                                                    'width' => NULL,
                                                                    'grow' => 1,
                                                                ),
                                                            'md' =>
                                                                array (
                                                                    'width' => '8',
                                                                    'grow' => 1,
                                                                ),
                                                            'lg' =>
                                                                array (
                                                                    'width' => NULL,
                                                                    'grow' => 1,
                                                                ),
                                                            'xl' =>
                                                                array (
                                                                    'width' => NULL,
                                                                    'grow' => 1,
                                                                ),
                                                        ),
                                                ),
                                            'heading' =>
                                                array (
                                                    'label' => 'none',
                                                    'label_custom' => '',
                                                    'label_icon' => '',
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
                                    'parent_id' => 1076,
                                    'weight' => 32,
                                    'system' => false,
                                ),
                            1079 =>
                                array (
                                    'id' => 1079,
                                    'name' => 'view_child_entities_review_review',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'view' => 'default',
                                                    'cache' => '',
                                                    'hide_empty' => true,
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
                                                    'css_class' => 'directory-listing-reviews',
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
                                    'parent_id' => 1075,
                                    'weight' => 34,
                                    'system' => false,
                                ),
                            1080 =>
                                array (
                                    'id' => 1080,
                                    'name' => 'entity_field_field_date_established',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'label' => 'form_icon',
                                                    'label_custom' => '',
                                                    'label_icon' => 'fas fa-calendar-alt',
                                                    'label_icon_size' => '',
                                                    'label_as_heading' => false,
                                                    'renderer_settings' =>
                                                        array (
                                                            'date' =>
                                                                array (
                                                                    '_separator' => ', ',
                                                                ),
                                                        ),
                                                    'renderer' => 'date',
                                                ),
                                            'heading' => NULL,
                                            'advanced' => NULL,
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
                                    'parent_id' => 1065,
                                    'weight' => 24,
                                    'system' => false,
                                ),
                            1081 =>
                                array (
                                    'id' => 1081,
                                    'name' => 'group',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'separator' => '',
                                                    'separator_margin' => 1,
                                                    'inline' => false,
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
                                                    'css_class' => 'directory-listing-info',
                                                    'margin_enable' => 1,
                                                    'margin_top' => '0',
                                                    'margin_right' => '0',
                                                    'margin_bottom' => '3',
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
                                    'parent_id' => 0,
                                    'weight' => 2,
                                    'system' => false,
                                ),
                            1082 =>
                                array (
                                    'id' => 1082,
                                    'name' => 'labels',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'arrangement' =>
                                                        array (
                                                            0 => 'entity_featured',
                                                            1 => 'directory_open_now',
                                                            2 => 'payment_plan',
                                                        ),
                                                    'style' => '',
                                                    'labels' =>
                                                        array (
                                                            'entity_featured' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_label' => 'Featured',
                                                                            '_color' =>
                                                                                array (
                                                                                    'type' => 'warning',
                                                                                    'value' => '',
                                                                                ),
                                                                        ),
                                                                ),
                                                            'directory_open_now' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_label' => 'Open Now',
                                                                            'field' => 'field_opening_hours',
                                                                        ),
                                                                ),
                                                            'entity_status' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_color' =>
                                                                                array (
                                                                                    'type' =>
                                                                                        array (
                                                                                        ),
                                                                                    'value' => NULL,
                                                                                ),
                                                                        ),
                                                                ),
                                                            'entity_new' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_label' => '',
                                                                            '_color' =>
                                                                                array (
                                                                                    'type' =>
                                                                                        array (
                                                                                        ),
                                                                                    'value' => NULL,
                                                                                ),
                                                                            'days' => '',
                                                                        ),
                                                                ),
                                                            'custom-1' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            'label_type' => NULL,
                                                                            'label_text' => '',
                                                                            'label_field' => NULL,
                                                                            '_color' =>
                                                                                array (
                                                                                    'type' =>
                                                                                        array (
                                                                                        ),
                                                                                    'value' => NULL,
                                                                                ),
                                                                            'conditions' => NULL,
                                                                        ),
                                                                ),
                                                            'custom-2' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            'label_type' => NULL,
                                                                            'label_text' => '',
                                                                            'label_field' => NULL,
                                                                            '_color' =>
                                                                                array (
                                                                                    'type' =>
                                                                                        array (
                                                                                        ),
                                                                                    'value' => NULL,
                                                                                ),
                                                                            'conditions' => NULL,
                                                                        ),
                                                                ),
                                                            'custom-3' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            'label_type' => NULL,
                                                                            'label_text' => '',
                                                                            'label_field' => NULL,
                                                                            '_color' =>
                                                                                array (
                                                                                    'type' =>
                                                                                        array (
                                                                                        ),
                                                                                    'value' => NULL,
                                                                                ),
                                                                            'conditions' => NULL,
                                                                        ),
                                                                ),
                                                            'custom-4' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            'label_type' => NULL,
                                                                            'label_text' => '',
                                                                            'label_field' => NULL,
                                                                            '_color' =>
                                                                                array (
                                                                                    'type' =>
                                                                                        array (
                                                                                        ),
                                                                                    'value' => NULL,
                                                                                ),
                                                                            'conditions' => NULL,
                                                                        ),
                                                                ),
                                                            'custom-5' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            'label_type' => NULL,
                                                                            'label_text' => '',
                                                                            'label_field' => NULL,
                                                                            '_color' =>
                                                                                array (
                                                                                    'type' =>
                                                                                        array (
                                                                                        ),
                                                                                    'value' => NULL,
                                                                                ),
                                                                            'conditions' => NULL,
                                                                        ),
                                                                ),
                                                            'claiming_claimed' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_label' => '',
                                                                            '_color' =>
                                                                                array (
                                                                                    'type' =>
                                                                                        array (
                                                                                        ),
                                                                                    'value' => NULL,
                                                                                ),
                                                                        ),
                                                                ),
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
                                                    'css_class' => 'directory-listing-labels',
                                                    'margin_enable' => 1,
                                                    'margin_top' => '0',
                                                    'margin_right' => '0',
                                                    'margin_bottom' => '2',
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
                                    'parent_id' => 1081,
                                    'weight' => 3,
                                    'system' => false,
                                ),
                            1083 =>
                                array (
                                    'id' => 1083,
                                    'name' => 'group',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'inline' => 1,
                                                    'separator' => '  ',
                                                    'separator_margin' => 1,
                                                    'nowrap' => false,
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
                                                    'css_class' => 'directory-listing-terms',
                                                    'margin_enable' => 1,
                                                    'margin_top' => '0',
                                                    'margin_right' => '0',
                                                    'margin_bottom' => '2',
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
                                    'parent_id' => 1081,
                                    'weight' => 5,
                                    'system' => false,
                                ),
                            1084 =>
                                array (
                                    'id' => 1084,
                                    'name' => 'entity_field_location_location',
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
                                                            'entity_terms' =>
                                                                array (
                                                                    'icon' => 1,
                                                                    'icon_size' => 'sm',
                                                                    '_limit' => 0,
                                                                    '_separator' => '  ',
                                                                ),
                                                        ),
                                                    'renderer' => 'entity_terms',
                                                ),
                                            'heading' => NULL,
                                            'advanced' => NULL,
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
                                    'parent_id' => 1083,
                                    'weight' => 7,
                                    'system' => false,
                                ),
                            1085 =>
                                array (
                                    'id' => 1085,
                                    'name' => 'entity_field_directory_category',
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
                                                            'entity_terms' =>
                                                                array (
                                                                    'icon' => 1,
                                                                    'icon_size' => 'sm',
                                                                    '_limit' => 0,
                                                                    '_separator' => ' ',
                                                                ),
                                                        ),
                                                    'renderer' => 'entity_terms',
                                                ),
                                            'heading' => NULL,
                                            'advanced' => NULL,
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
                                    'parent_id' => 1083,
                                    'weight' => 6,
                                    'system' => false,
                                ),
                            1086 =>
                                array (
                                    'id' => 1086,
                                    'name' => 'entity_field_location_address',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'label' => NULL,
                                                    'label_custom' => NULL,
                                                    'label_icon' => NULL,
                                                    'label_icon_size' => NULL,
                                                    'label_as_heading' => false,
                                                    'renderer' => NULL,
                                                    'renderer_settings' =>
                                                        array (
                                                        ),
                                                ),
                                            'visibility' => NULL,
                                            'advanced' => NULL,
                                        ),
                                    'parent_id' => 1062,
                                    'weight' => 20,
                                    'system' => false,
                                ),
                            1087 =>
                                array (
                                    'id' => 1087,
                                    'name' => 'entity_field_voting_rating',
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
                                                            'voting_rating' =>
                                                                array (
                                                                    '_separator' => '',
                                                                    '_render_empty' => '1',
                                                                    'hide_empty' => false,
                                                                    'hide_count' => false,
                                                                    'read_only' => false,
                                                                ),
                                                        ),
                                                    'renderer' => 'voting_rating',
                                                    'label_as_heading' => false,
                                                ),
                                            'heading' => NULL,
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'directory-listing-rating',
                                                    'margin_enable' => 1,
                                                    'margin_top' => '0',
                                                    'margin_right' => '0',
                                                    'margin_bottom' => '2',
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
                                    'parent_id' => 1081,
                                    'weight' => 4,
                                    'system' => false,
                                ),
                            1088 =>
                                array (
                                    'id' => 1088,
                                    'name' => 'entity_field_review_ratings',
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
                                                            'review_ratings' =>
                                                                array (
                                                                    'format' => 'bars',
                                                                    'color' =>
                                                                        array (
                                                                            'type' => '',
                                                                            'value' => '',
                                                                        ),
                                                                    'decimals' => '2',
                                                                    'inline' => false,
                                                                    'bar_height' => 5,
                                                                    'show_count' => false,
                                                                ),
                                                        ),
                                                    'renderer' => 'review_ratings',
                                                ),
                                            'heading' => NULL,
                                            'advanced' => NULL,
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
                                    'parent_id' => 1078,
                                    'weight' => 33,
                                    'system' => false,
                                ),
                            1089 =>
                                array (
                                    'id' => 1089,
                                    'name' => 'entity_field_review_ratings',
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
                                                            'review_ratings' =>
                                                                array (
                                                                    'format' => 'stars',
                                                                    'color' =>
                                                                        array (
                                                                            'type' => '',
                                                                            'value' => '',
                                                                        ),
                                                                    'decimals' => '1',
                                                                    'bar_height' => 10,
                                                                    'show_count' => 1,
                                                                    'hide_empty' => 1,
                                                                    '_render_empty' => '1',
                                                                    'inline' => false,
                                                                ),
                                                        ),
                                                    'renderer' => 'review_ratings',
                                                    'label_as_heading' => false,
                                                ),
                                            'heading' => NULL,
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'directory-listing-review-rating',
                                                    'margin_enable' => 1,
                                                    'margin_top' => '0',
                                                    'margin_right' => '0',
                                                    'margin_bottom' => '3',
                                                    'margin_left' => '0',
                                                    'font_size' => 'rel',
                                                    'font_size_rel' => '1.2',
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
                                    'parent_id' => 1075,
                                    'weight' => 28,
                                    'system' => false,
                                ),
                            1090 =>
                                array (
                                    'id' => 1090,
                                    'name' => 'entity_field_review_ratings',
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
                                                            'review_ratings' =>
                                                                array (
                                                                    'format' => 'bars_level',
                                                                    'color' =>
                                                                        array (
                                                                            'type' => '',
                                                                            'value' => '',
                                                                        ),
                                                                    'decimals' => '1',
                                                                    'inline' => 1,
                                                                    'bar_height' => 18,
                                                                    'show_count' => false,
                                                                ),
                                                        ),
                                                    'renderer' => 'review_ratings',
                                                ),
                                            'heading' => NULL,
                                            'advanced' => NULL,
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
                                    'parent_id' => 1077,
                                    'weight' => 31,
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
                            'css' => '.drts-display--dashboard-row .directory-listing-title a {
    white-space: normal;
}',
                        ),
                    'elements' =>
                        array (
                            1091 =>
                                array (
                                    'id' => 1091,
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
                                                    'label_custom' => __('Title', 'directories-pro'),
                                                    'label_icon' => '',
                                                    'label_icon_size' => '',
                                                ),
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'directory-listing-title-container',
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
                            1092 =>
                                array (
                                    'id' => 1092,
                                    'name' => 'entity_field_post_title',
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
                                                                    'link_field' => 'field_website,',
                                                                    'link_target' => '_self',
                                                                    'link_custom_label' => '',
                                                                    'max_chars' => 0,
                                                                    'icon' => 1,
                                                                    'icon_settings' =>
                                                                        array (
                                                                            'size' => '',
                                                                            'fallback' => false,
                                                                            'is_image' => true,
                                                                        ),
                                                                    '_separator' => '',
                                                                    'link_rel' =>
                                                                        array (
                                                                        ),
                                                                ),
                                                        ),
                                                    'renderer' => 'entity_title',
                                                    'label_as_heading' => false,
                                                ),
                                            'heading' => NULL,
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'directory-listing-title',
                                                    'margin_enable' => 1,
                                                    'margin_top' => '0',
                                                    'margin_right' => '0',
                                                    'margin_bottom' => '2',
                                                    'margin_left' => '0',
                                                    'font_size' => '',
                                                    'font_size_rel' => '1',
                                                    'font_size_abs' => '16',
                                                    'font_weight' => 'bold',
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
                                    'parent_id' => 1091,
                                    'weight' => 2,
                                    'system' => false,
                                ),
                            1093 =>
                                array (
                                    'id' => 1093,
                                    'name' => 'labels',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'arrangement' =>
                                                        array (
                                                            0 => 'entity_featured',
                                                            1 => 'payment_plan',
                                                        ),
                                                    'labels' =>
                                                        array (
                                                            'entity_featured' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_label' => 'Featured',
                                                                        ),
                                                                ),
                                                            'directory_open_now' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_label' => 'Open Now',
                                                                            'field' => 'field_opening_hours',
                                                                        ),
                                                                ),
                                                        ),
                                                    'style' => NULL,
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
                                                    'css_class' => 'directory-listing-labels',
                                                ),
                                            'visibility' =>
                                                array (
                                                    'wp_check_role' => false,
                                                    'wp_roles' =>
                                                        array (
                                                        ),
                                                ),
                                        ),
                                    'parent_id' => 1091,
                                    'weight' => 3,
                                    'system' => false,
                                ),
                            1094 =>
                                array (
                                    'id' => 1094,
                                    'name' => 'labels',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'arrangement' =>
                                                        array (
                                                            0 => 'entity_status',
                                                        ),
                                                    'labels' =>
                                                        array (
                                                            'entity_featured' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_color' => 'warning',
                                                                        ),
                                                                ),
                                                            'payment_plan' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_color' => 'secondary',
                                                                        ),
                                                                ),
                                                        ),
                                                    'style' => NULL,
                                                ),
                                            'heading' =>
                                                array (
                                                    'label' => 'custom',
                                                    'label_custom' => __('Status', 'directories-pro'),
                                                    'label_icon' => '',
                                                    'label_icon_size' => '',
                                                ),
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'directory-listing-status',
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
                                    'weight' => 5,
                                    'system' => false,
                                ),
                            1095 =>
                                array (
                                    'id' => 1095,
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
                                                            3 => 'payment_renew',
                                                            4 => 'payment_upgrade',
                                                            5 => 'payment_order_addon',
                                                        ),
                                                    'buttons' =>
                                                        array (
                                                            'payment_renew' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_hide_label' => false,
                                                                            '_icon' => 'fas fa-sync',
                                                                            '_color' => 'outline-secondary',
                                                                        ),
                                                                ),
                                                            'payment_upgrade' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_hide_label' => false,
                                                                            '_icon' => 'fas fa-arrows-alt-v',
                                                                            '_color' => 'outline-secondary',
                                                                        ),
                                                                ),
                                                            'payment_order_addon' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_hide_label' => false,
                                                                            '_icon' => 'fas fa-cart-plus',
                                                                            '_color' =>
                                                                                array (
                                                                                ),
                                                                        ),
                                                                ),
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
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'directory-listing-admin-buttons',
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
                                    'weight' => 6,
                                    'system' => false,
                                ),
                            1096 =>
                                array (
                                    'id' => 1096,
                                    'name' => 'entity_field_voting_rating',
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
                                                            'voting_rating' =>
                                                                array (
                                                                    'hide_empty' => 1,
                                                                    'hide_count' => false,
                                                                    'read_only' => 1,
                                                                    '_separator' => '',
                                                                    '_render_empty' => '1',
                                                                ),
                                                        ),
                                                    'renderer' => 'voting_rating',
                                                ),
                                            'advanced' => NULL,
                                            'visibility' =>
                                                array (
                                                    'wp_check_role' => false,
                                                    'wp_roles' =>
                                                        array (
                                                        ),
                                                ),
                                            'heading' => NULL,
                                        ),
                                    'parent_id' => 1091,
                                    'weight' => 4,
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
                            1097 =>
                                array (
                                    'id' => 1097,
                                    'name' => 'columns',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'gutter_width' => 'none',
                                                    '_labels' =>
                                                        array (
                                                            'enable' => 1,
                                                            'arrangement' =>
                                                                array (
                                                                    0 => 'entity_featured',
                                                                    1 => 'entity_new',
                                                                    2 => 'directory_open_now',
                                                                ),
                                                            'style' => '',
                                                            'position' => 'tl',
                                                        ),
                                                    '_buttons' =>
                                                        array (
                                                            'position' => 'tl',
                                                            'enable' => false,
                                                            'arrangement' =>
                                                                array (
                                                                ),
                                                            'hover' => false,
                                                        ),
                                                    'labels' =>
                                                        array (
                                                            'entity_featured' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_label' => 'Featured',
                                                                            '_color' =>
                                                                                array (
                                                                                    'type' => 'warning',
                                                                                    'value' => '',
                                                                                ),
                                                                        ),
                                                                ),
                                                            'entity_new' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_label' => 'New',
                                                                            '_color' =>
                                                                                array (
                                                                                    'type' => 'danger',
                                                                                    'value' => '',
                                                                                ),
                                                                            'days' => '1',
                                                                        ),
                                                                ),
                                                            'directory_open_now' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_label' => 'Open Now',
                                                                            'field' => 'field_opening_hours',
                                                                        ),
                                                                ),
                                                            'custom-1' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            'label_type' => NULL,
                                                                            'label_text' => '',
                                                                            'label_field' => NULL,
                                                                            '_color' =>
                                                                                array (
                                                                                    'type' =>
                                                                                        array (
                                                                                        ),
                                                                                    'value' => NULL,
                                                                                ),
                                                                            'conditions' => NULL,
                                                                        ),
                                                                ),
                                                            'custom-2' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            'label_type' => NULL,
                                                                            'label_text' => '',
                                                                            'label_field' => NULL,
                                                                            '_color' =>
                                                                                array (
                                                                                    'type' =>
                                                                                        array (
                                                                                        ),
                                                                                    'value' => NULL,
                                                                                ),
                                                                            'conditions' => NULL,
                                                                        ),
                                                                ),
                                                            'custom-3' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            'label_type' => NULL,
                                                                            'label_text' => '',
                                                                            'label_field' => NULL,
                                                                            '_color' =>
                                                                                array (
                                                                                    'type' =>
                                                                                        array (
                                                                                        ),
                                                                                    'value' => NULL,
                                                                                ),
                                                                            'conditions' => NULL,
                                                                        ),
                                                                ),
                                                            'custom-4' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            'label_type' => NULL,
                                                                            'label_text' => '',
                                                                            'label_field' => NULL,
                                                                            '_color' =>
                                                                                array (
                                                                                    'type' =>
                                                                                        array (
                                                                                        ),
                                                                                    'value' => NULL,
                                                                                ),
                                                                            'conditions' => NULL,
                                                                        ),
                                                                ),
                                                            'custom-5' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            'label_type' => NULL,
                                                                            'label_text' => '',
                                                                            'label_field' => NULL,
                                                                            '_color' =>
                                                                                array (
                                                                                    'type' =>
                                                                                        array (
                                                                                        ),
                                                                                    'value' => NULL,
                                                                                ),
                                                                            'conditions' => NULL,
                                                                        ),
                                                                ),
                                                            'payment_plan' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_color' =>
                                                                                array (
                                                                                    'type' =>
                                                                                        array (
                                                                                        ),
                                                                                    'value' => NULL,
                                                                                ),
                                                                        ),
                                                                ),
                                                            'claiming_claimed' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_label' => '',
                                                                            '_color' =>
                                                                                array (
                                                                                    'type' =>
                                                                                        array (
                                                                                        ),
                                                                                    'value' => NULL,
                                                                                ),
                                                                        ),
                                                                ),
                                                        ),
                                                    'buttons' =>
                                                        array (
                                                            'voting_updown' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_hide_label' => '1',
                                                                            '_color' => 'link',
                                                                            '_link_color' => NULL,
                                                                            'show_count' => false,
                                                                        ),
                                                                ),
                                                            'voting_updown_down' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_hide_label' => '1',
                                                                            '_color' => 'link',
                                                                            '_link_color' => NULL,
                                                                            'show_count' => false,
                                                                        ),
                                                                ),
                                                            'voting_bookmark' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_hide_label' => '1',
                                                                            '_color' => 'link',
                                                                            '_link_color' => NULL,
                                                                            'show_count' => false,
                                                                        ),
                                                                ),
                                                            'dashboard_posts_edit' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_hide_label' => '1',
                                                                            '_color' => 'link',
                                                                            '_icon' => NULL,
                                                                            '_link_color' => NULL,
                                                                        ),
                                                                ),
                                                            'dashboard_posts_delete' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_hide_label' => '1',
                                                                            '_color' => 'link',
                                                                            '_icon' => NULL,
                                                                            '_link_color' => NULL,
                                                                        ),
                                                                ),
                                                        ),
                                                    'columns' => 3,
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
                                                    'css_class' => '',
                                                    'css_id' => '',
                                                    'margin_top' => '0',
                                                    'margin_right' => '0',
                                                    'margin_bottom' => '0',
                                                    'margin_left' => '0',
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
                            1098 =>
                                array (
                                    'id' => 1098,
                                    'name' => 'column',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'width' => 'responsive',
                                                    'responsive' =>
                                                        array (
                                                            'xs' =>
                                                                array (
                                                                    'width' => '12',
                                                                ),
                                                            'sm' =>
                                                                array (
                                                                    'width' => '4',
                                                                ),
                                                            'md' =>
                                                                array (
                                                                    'width' => 'inherit',
                                                                ),
                                                            'lg' =>
                                                                array (
                                                                    'width' => 'inherit',
                                                                ),
                                                            'xl' =>
                                                                array (
                                                                    'width' => 'inherit',
                                                                ),
                                                            'grow' => false,
                                                        ),
                                                    '_labels' =>
                                                        array (
                                                            'arrangement' =>
                                                                array (
                                                                    0 => 'entity_featured',
                                                                    1 => 'entity_status',
                                                                    2 => 'entity_new',
                                                                    3 => 'custom-1',
                                                                    4 => 'custom-2',
                                                                    5 => 'custom-3',
                                                                    6 => 'custom-4',
                                                                    7 => 'custom-5',
                                                                    8 => 'payment_plan',
                                                                    9 => 'claiming_claimed',
                                                                    10 => 'directory_open_now',
                                                                ),
                                                            'style' => '',
                                                            'position' => 'tl',
                                                            'enable' => false,
                                                        ),
                                                    '_buttons' =>
                                                        array (
                                                            'enable' => 1,
                                                            'arrangement' =>
                                                                array (
                                                                    0 => 'voting_bookmark',
                                                                ),
                                                            'position' => 'bl',
                                                            'hover' => false,
                                                        ),
                                                    'labels' =>
                                                        array (
                                                            'entity_featured' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_label' => 'Featured',
                                                                            '_color' =>
                                                                                array (
                                                                                    'type' => 'warning',
                                                                                    'value' => '',
                                                                                ),
                                                                        ),
                                                                ),
                                                            'entity_new' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_label' => 'New',
                                                                            '_color' =>
                                                                                array (
                                                                                    'type' => 'danger',
                                                                                    'value' => '',
                                                                                ),
                                                                            'days' => '1',
                                                                        ),
                                                                ),
                                                            'custom-1' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            'label_type' => '',
                                                                            'label_text' => '',
                                                                            'label_field' => 'directory_category',
                                                                            '_color' =>
                                                                                array (
                                                                                    'type' => 'primary',
                                                                                    'value' => '',
                                                                                ),
                                                                            'conditions' => NULL,
                                                                        ),
                                                                ),
                                                            'custom-2' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            'label_type' => '',
                                                                            'label_text' => '',
                                                                            'label_field' => 'directory_category',
                                                                            '_color' =>
                                                                                array (
                                                                                    'type' => 'primary',
                                                                                    'value' => '',
                                                                                ),
                                                                            'conditions' => NULL,
                                                                        ),
                                                                ),
                                                            'custom-3' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            'label_type' => '',
                                                                            'label_text' => '',
                                                                            'label_field' => 'directory_category',
                                                                            '_color' =>
                                                                                array (
                                                                                    'type' => 'primary',
                                                                                    'value' => '',
                                                                                ),
                                                                            'conditions' => NULL,
                                                                        ),
                                                                ),
                                                            'custom-4' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            'label_type' => '',
                                                                            'label_text' => '',
                                                                            'label_field' => 'directory_category',
                                                                            '_color' =>
                                                                                array (
                                                                                    'type' => 'primary',
                                                                                    'value' => '',
                                                                                ),
                                                                            'conditions' => NULL,
                                                                        ),
                                                                ),
                                                            'custom-5' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            'label_type' => '',
                                                                            'label_text' => '',
                                                                            'label_field' => 'directory_category',
                                                                            '_color' =>
                                                                                array (
                                                                                    'type' => 'primary',
                                                                                    'value' => '',
                                                                                ),
                                                                            'conditions' => NULL,
                                                                        ),
                                                                ),
                                                            'payment_plan' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_color' =>
                                                                                array (
                                                                                    'type' => 'primary',
                                                                                    'value' => '',
                                                                                ),
                                                                        ),
                                                                ),
                                                            'claiming_claimed' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_label' => 'Claimed',
                                                                            '_color' =>
                                                                                array (
                                                                                    'type' => 'info',
                                                                                    'value' => '',
                                                                                ),
                                                                        ),
                                                                ),
                                                            'directory_open_now' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_label' => 'Open Now',
                                                                            'field' => 'field_opening_hours',
                                                                        ),
                                                                ),
                                                        ),
                                                    'buttons' =>
                                                        array (
                                                            'voting_bookmark' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_link_color' => '#EEEEEE',
                                                                            '_hide_label' => '1',
                                                                            '_color' => 'link',
                                                                            'show_count' => false,
                                                                        ),
                                                                ),
                                                            'voting_updown' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_hide_label' => '1',
                                                                            '_color' => 'link',
                                                                            '_link_color' => NULL,
                                                                            'show_count' => false,
                                                                        ),
                                                                ),
                                                            'voting_updown_down' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_hide_label' => '1',
                                                                            '_color' => 'link',
                                                                            '_link_color' => NULL,
                                                                            'show_count' => false,
                                                                        ),
                                                                ),
                                                            'dashboard_posts_edit' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_hide_label' => '1',
                                                                            '_color' => 'link',
                                                                            '_icon' => NULL,
                                                                            '_link_color' => NULL,
                                                                        ),
                                                                ),
                                                            'dashboard_posts_delete' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_hide_label' => '1',
                                                                            '_color' => 'link',
                                                                            '_icon' => NULL,
                                                                            '_link_color' => NULL,
                                                                        ),
                                                                ),
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
                                                    'css_class' => 'directory-listing-aside',
                                                    'css_id' => '',
                                                    'padding_top' => '0',
                                                    'padding_right' => '0',
                                                    'padding_bottom' => '0',
                                                    'padding_left' => '0',
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
                                    'parent_id' => 1097,
                                    'weight' => 2,
                                    'system' => false,
                                ),
                            1099 =>
                                array (
                                    'id' => 1099,
                                    'name' => 'column',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'width' => 'responsive',
                                                    'responsive' =>
                                                        array (
                                                            'xs' =>
                                                                array (
                                                                    'width' => '12',
                                                                ),
                                                            'sm' =>
                                                                array (
                                                                    'width' => '8',
                                                                ),
                                                            'md' =>
                                                                array (
                                                                    'width' => 'inherit',
                                                                ),
                                                            'lg' =>
                                                                array (
                                                                    'width' => 'inherit',
                                                                ),
                                                            'xl' =>
                                                                array (
                                                                    'width' => 'inherit',
                                                                ),
                                                            'grow' => false,
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
                                                    'css_class' => 'directory-listing-main',
                                                    'padding_enable' => 1,
                                                    'padding_top' => '2',
                                                    'padding_right' => '3',
                                                    'padding_bottom' => '2',
                                                    'padding_left' => '3',
                                                ),
                                            'visibility' =>
                                                array (
                                                    'wp_check_role' => false,
                                                    'wp_roles' =>
                                                        array (
                                                        ),
                                                ),
                                        ),
                                    'parent_id' => 1097,
                                    'weight' => 4,
                                    'system' => false,
                                ),
                            1100 =>
                                array (
                                    'id' => 1100,
                                    'name' => 'entity_field_post_title',
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
                                                                    'link_field' => 'field_website,',
                                                                    'link_rel' =>
                                                                        array (
                                                                            0 => 'nofollow',
                                                                            1 => 'external',
                                                                        ),
                                                                    'link_target' => '_self',
                                                                    'link_custom_label' => '',
                                                                    'max_chars' => 50,
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
                                                    'css_class' => 'directory-listing-title',
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
                                    'parent_id' => 1099,
                                    'weight' => 5,
                                    'system' => false,
                                ),
                            1101 =>
                                array (
                                    'id' => 1101,
                                    'name' => 'group',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'inline' => 1,
                                                    'separator' => '·',
                                                    'separator_margin' => 1,
                                                    'nowrap' => false,
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
                                                    'css_class' => 'directory-listing-info',
                                                    'margin_enable' => 1,
                                                    'margin_top' => '2',
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
                                    'parent_id' => 1099,
                                    'weight' => 7,
                                    'system' => false,
                                ),
                            1102 =>
                                array (
                                    'id' => 1102,
                                    'name' => 'entity_fieldlist',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'size' => 'sm',
                                                    'inline' => 1,
                                                    'no_border' => false,
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
                                                    'css_class' => 'directory-listing-contact-info',
                                                    'margin_enable' => 1,
                                                    'margin_top' => '2',
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
                                    'parent_id' => 1099,
                                    'weight' => 9,
                                    'system' => false,
                                ),
                            1103 =>
                                array (
                                    'id' => 1103,
                                    'name' => 'entity_field_directory_photos',
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
                                                                    'size' => 'thumbnail',
                                                                    'width' => 100,
                                                                    'height' => 0,
                                                                    'cols' => '1',
                                                                    'link' => 'page',
                                                                    'link_image_size' => 'medium',
                                                                    '_limit' => 1,
                                                                    '_render_background' => 1,
                                                                    '_hover_zoom' => 1,
                                                                    '_hover_brighten' => false,
                                                                    '_render_empty' => 1,
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
                                                                    'show_videos' => false,
                                                                    'video_field' => '',
                                                                    'prepend_videos' => false,
                                                                    '_limit' => 0,
                                                                ),
                                                            'wp_gallery' =>
                                                                array (
                                                                    'cols' => '4',
                                                                    'size' => 'thumbnail',
                                                                    '_limit' => 0,
                                                                ),
                                                        ),
                                                ),
                                            'heading' => NULL,
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'directory-listing-photo',
                                                ),
                                            'visibility' =>
                                                array (
                                                    'wp_check_role' => false,
                                                    'wp_roles' =>
                                                        array (
                                                        ),
                                                ),
                                        ),
                                    'parent_id' => 1098,
                                    'weight' => 3,
                                    'system' => false,
                                ),
                            1104 =>
                                array (
                                    'id' => 1104,
                                    'name' => 'entity_field_location_address',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'label' => 'icon',
                                                    'label_custom' => '',
                                                    'label_icon' => 'fas fa-map-marker-alt',
                                                    'label_icon_size' => '',
                                                    'label_as_heading' => false,
                                                    'renderer' => 'location_address',
                                                    'renderer_settings' =>
                                                        array (
                                                            'map_map' =>
                                                                array (
                                                                    'height' => 300,
                                                                    'zoom_control' => 1,
                                                                    'map_type_control' => 1,
                                                                ),
                                                            'location_address' =>
                                                                array (
                                                                    'custom_format' => 0,
                                                                    'format' => '{street}, {city}, {province} {zip}',
                                                                    'link' => false,
                                                                    '_separator' => '<br />',
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
                                    'parent_id' => 1102,
                                    'weight' => 10,
                                    'system' => false,
                                ),
                            1105 =>
                                array (
                                    'id' => 1105,
                                    'name' => 'entity_field_field_phone',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'label' => 'icon',
                                                    'label_custom' => '',
                                                    'label_icon' => 'fas fa-phone',
                                                    'label_icon_size' => '',
                                                    'label_as_heading' => false,
                                                    'renderer_settings' =>
                                                        array (
                                                            'phone' =>
                                                                array (
                                                                    '_separator' => ', ',
                                                                ),
                                                        ),
                                                    'renderer' => 'phone',
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
                                    'parent_id' => 1102,
                                    'weight' => 11,
                                    'system' => false,
                                ),
                            1106 =>
                                array (
                                    'id' => 1106,
                                    'name' => 'entity_field_directory_category',
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
                                                            'entity_terms' =>
                                                                array (
                                                                    'icon' => false,
                                                                    'icon_size' => 24,
                                                                    '_limit' => 0,
                                                                    '_separator' => ', ',
                                                                ),
                                                        ),
                                                    'renderer' => 'entity_terms',
                                                ),
                                            'advanced' => NULL,
                                            'visibility' =>
                                                array (
                                                    'wp_check_role' => false,
                                                    'wp_roles' =>
                                                        array (
                                                        ),
                                                ),
                                            'heading' => NULL,
                                        ),
                                    'parent_id' => 1101,
                                    'weight' => 8,
                                    'system' => false,
                                ),
                            1108 =>
                                array (
                                    'id' => 1108,
                                    'name' => 'entity_field_voting_rating',
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
                                                            'voting_rating' =>
                                                                array (
                                                                    'hide_empty' => 1,
                                                                    'hide_count' => false,
                                                                    'read_only' => 1,
                                                                    '_separator' => '',
                                                                ),
                                                        ),
                                                    'renderer' => 'voting_rating',
                                                ),
                                            'heading' => NULL,
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'directory-listing-rating',
                                                ),
                                            'visibility' =>
                                                array (
                                                    'wp_check_role' => false,
                                                    'wp_roles' =>
                                                        array (
                                                        ),
                                                ),
                                        ),
                                    'parent_id' => 1099,
                                    'weight' => 6,
                                    'system' => false,
                                ),
                        ),
                ),
            'summary-infobox' =>
                array (
                    'name' => 'summary-infobox',
                    'type' => 'entity',
                    'data' =>
                        array (
                            'css' => '.drts-display--summary-infobox .directory-listing-labels {
  position: absolute;
  top: 5px;
  left: 10px;
}',
                        ),
                    'elements' =>
                        array (
                            1110 =>
                                array (
                                    'id' => 1110,
                                    'name' => 'columns',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'gutter_width' => 'none',
                                                    'columns' => 3,
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
                                    'parent_id' => 0,
                                    'weight' => 1,
                                    'system' => false,
                                ),
                            1111 =>
                                array (
                                    'id' => 1111,
                                    'name' => 'column',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'width' => '4',
                                                    'responsive' =>
                                                        array (
                                                            'xs' =>
                                                                array (
                                                                    'width' => '12',
                                                                ),
                                                            'sm' =>
                                                                array (
                                                                    'width' => '4',
                                                                ),
                                                            'md' =>
                                                                array (
                                                                    'width' => 'inherit',
                                                                ),
                                                            'lg' =>
                                                                array (
                                                                    'width' => 'inherit',
                                                                ),
                                                            'xl' =>
                                                                array (
                                                                    'width' => 'inherit',
                                                                ),
                                                            'grow' => false,
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
                                                    'css_class' => 'directory-listing-aside',
                                                ),
                                            'visibility' =>
                                                array (
                                                    'wp_check_role' => false,
                                                    'wp_roles' =>
                                                        array (
                                                        ),
                                                ),
                                        ),
                                    'parent_id' => 1110,
                                    'weight' => 2,
                                    'system' => false,
                                ),
                            1112 =>
                                array (
                                    'id' => 1112,
                                    'name' => 'column',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'width' => '8',
                                                    'responsive' =>
                                                        array (
                                                            'xs' =>
                                                                array (
                                                                    'width' => '12',
                                                                ),
                                                            'sm' =>
                                                                array (
                                                                    'width' => '8',
                                                                ),
                                                            'md' =>
                                                                array (
                                                                    'width' => 'inherit',
                                                                ),
                                                            'lg' =>
                                                                array (
                                                                    'width' => 'inherit',
                                                                ),
                                                            'xl' =>
                                                                array (
                                                                    'width' => 'inherit',
                                                                ),
                                                            'grow' => false,
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
                                                    'css_class' => 'directory-listing-main',
                                                    'padding_enable' => 1,
                                                    'padding_top' => '2',
                                                    'padding_right' => '3',
                                                    'padding_bottom' => '2',
                                                    'padding_left' => '3',
                                                ),
                                            'visibility' =>
                                                array (
                                                    'wp_check_role' => false,
                                                    'wp_roles' =>
                                                        array (
                                                        ),
                                                ),
                                        ),
                                    'parent_id' => 1110,
                                    'weight' => 5,
                                    'system' => false,
                                ),
                            1113 =>
                                array (
                                    'id' => 1113,
                                    'name' => 'entity_field_post_title',
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
                                                                    'link_field' => 'field_website,',
                                                                    'link_rel' =>
                                                                        array (
                                                                            0 => 'nofollow',
                                                                            1 => 'external',
                                                                        ),
                                                                    'link_target' => '_self',
                                                                    'link_custom_label' => '',
                                                                    'max_chars' => 50,
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
                                                    'css_class' => 'directory-listing-title',
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
                                    'parent_id' => 1112,
                                    'weight' => 6,
                                    'system' => false,
                                ),
                            1114 =>
                                array (
                                    'id' => 1114,
                                    'name' => 'group',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'inline' => 1,
                                                    'separator' => '·',
                                                    'separator_margin' => 1,
                                                    'nowrap' => false,
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
                                                    'css_class' => 'directory-listing-info',
                                                    'margin_enable' => 1,
                                                    'margin_top' => '2',
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
                                    'parent_id' => 1112,
                                    'weight' => 8,
                                    'system' => false,
                                ),
                            1115 =>
                                array (
                                    'id' => 1115,
                                    'name' => 'entity_fieldlist',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'size' => 'sm',
                                                    'inline' => 1,
                                                    'no_border' => false,
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
                                                    'css_class' => 'directory-listing-contact-info',
                                                    'margin_enable' => 1,
                                                    'margin_top' => '2',
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
                                    'parent_id' => 1112,
                                    'weight' => 10,
                                    'system' => false,
                                ),
                            1116 =>
                                array (
                                    'id' => 1116,
                                    'name' => 'entity_field_directory_photos',
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
                                                                    'size' => 'thumbnail',
                                                                    'width' => 100,
                                                                    'height' => 100,
                                                                    'cols' => '1',
                                                                    'link' => 'page',
                                                                    'link_image_size' => 'medium',
                                                                    '_limit' => 1,
                                                                    '_render_background' => 1,
                                                                    '_hover_zoom' => 1,
                                                                    '_hover_brighten' => false,
                                                                    '_render_empty' => 1,
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
                                                                    'show_videos' => false,
                                                                    'video_field' => '',
                                                                    'prepend_videos' => false,
                                                                    '_limit' => 0,
                                                                ),
                                                            'wp_gallery' =>
                                                                array (
                                                                    'cols' => '4',
                                                                    'size' => 'thumbnail',
                                                                    '_limit' => 0,
                                                                ),
                                                        ),
                                                ),
                                            'heading' => NULL,
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'directory-listing-photo',
                                                ),
                                            'visibility' =>
                                                array (
                                                    'wp_check_role' => false,
                                                    'wp_roles' =>
                                                        array (
                                                        ),
                                                ),
                                        ),
                                    'parent_id' => 1111,
                                    'weight' => 3,
                                    'system' => false,
                                ),
                            1117 =>
                                array (
                                    'id' => 1117,
                                    'name' => 'entity_field_location_address',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'label' => 'icon',
                                                    'label_custom' => '',
                                                    'label_icon' => 'fas fa-map-marker-alt',
                                                    'label_icon_size' => '',
                                                    'label_as_heading' => false,
                                                    'renderer' => 'location_address',
                                                    'renderer_settings' =>
                                                        array (
                                                            'map_map' =>
                                                                array (
                                                                    'height' => 300,
                                                                    'zoom_control' => 1,
                                                                    'map_type_control' => 1,
                                                                ),
                                                            'location_address' =>
                                                                array (
                                                                    'custom_format' => 0,
                                                                    'format' => '{street}, {city}, {province} {zip}',
                                                                    'link' => false,
                                                                    '_separator' => '<br />',
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
                                    'parent_id' => 1115,
                                    'weight' => 11,
                                    'system' => false,
                                ),
                            1118 =>
                                array (
                                    'id' => 1118,
                                    'name' => 'entity_field_field_phone',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'label' => 'icon',
                                                    'label_custom' => '',
                                                    'label_icon' => 'fas fa-phone',
                                                    'label_icon_size' => '',
                                                    'label_as_heading' => false,
                                                    'renderer_settings' =>
                                                        array (
                                                            'phone' =>
                                                                array (
                                                                    '_separator' => ', ',
                                                                ),
                                                        ),
                                                    'renderer' => 'phone',
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
                                    'parent_id' => 1115,
                                    'weight' => 12,
                                    'system' => false,
                                ),
                            1119 =>
                                array (
                                    'id' => 1119,
                                    'name' => 'entity_field_directory_category',
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
                                                            'entity_terms' =>
                                                                array (
                                                                    'icon' => false,
                                                                    'icon_size' => 24,
                                                                    '_limit' => 0,
                                                                    '_separator' => ', ',
                                                                ),
                                                        ),
                                                    'renderer' => 'entity_terms',
                                                ),
                                            'advanced' => NULL,
                                            'visibility' =>
                                                array (
                                                    'wp_check_role' => false,
                                                    'wp_roles' =>
                                                        array (
                                                        ),
                                                ),
                                            'heading' => NULL,
                                        ),
                                    'parent_id' => 1114,
                                    'weight' => 9,
                                    'system' => false,
                                ),
                            1120 =>
                                array (
                                    'id' => 1120,
                                    'name' => 'labels',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'arrangement' =>
                                                        array (
                                                            0 => 'entity_featured',
                                                        ),
                                                    'labels' =>
                                                        array (
                                                            'entity_featured' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_label' => 'Featured',
                                                                        ),
                                                                ),
                                                            'directory_open_now' =>
                                                                array (
                                                                    'settings' =>
                                                                        array (
                                                                            '_label' => 'Open Now',
                                                                            'field' => 'field_opening_hours',
                                                                        ),
                                                                ),
                                                        ),
                                                    'style' => NULL,
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
                                                    'css_class' => 'directory-listing-labels',
                                                ),
                                            'visibility' =>
                                                array (
                                                    'wp_check_role' => false,
                                                    'wp_roles' =>
                                                        array (
                                                        ),
                                                ),
                                        ),
                                    'parent_id' => 1111,
                                    'weight' => 4,
                                    'system' => false,
                                ),
                            1121 =>
                                array (
                                    'id' => 1121,
                                    'name' => 'entity_field_voting_rating',
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
                                                            'voting_rating' =>
                                                                array (
                                                                    'hide_empty' => 1,
                                                                    'hide_count' => false,
                                                                    'read_only' => 1,
                                                                    '_separator' => '',
                                                                ),
                                                        ),
                                                    'renderer' => 'voting_rating',
                                                ),
                                            'heading' => NULL,
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'directory-listing-rating',
                                                ),
                                            'visibility' =>
                                                array (
                                                    'wp_check_role' => false,
                                                    'wp_roles' =>
                                                        array (
                                                        ),
                                                ),
                                        ),
                                    'parent_id' => 1112,
                                    'weight' => 7,
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
                            1122 =>
                                array (
                                    'id' => 1122,
                                    'name' => 'entity_field_directory_photos',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'label' => 'none',
                                                    'label_custom' => '',
                                                    'label_icon' => '',
                                                    'label_icon_size' => '',
                                                    'renderer' => 'image',
                                                    'renderer_settings' =>
                                                        array (
                                                            'image' =>
                                                                array (
                                                                    'size' => 'medium',
                                                                    'width' => 100,
                                                                    'height' => 0,
                                                                    'cols' => '1',
                                                                    'link' => 'page',
                                                                    'link_image_size' => 'large',
                                                                    '_limit' => 1,
                                                                    '_hover_zoom' => 1,
                                                                    '_hover_brighten' => 1,
                                                                    '_render_background' => true,
                                                                    '_render_empty' => true,
                                                                ),
                                                            'wp_gallery' =>
                                                                array (
                                                                    'cols' => '4',
                                                                    'size' => 'thumbnail',
                                                                    '_limit' => 0,
                                                                    'no_link' => false,
                                                                ),
                                                            'slider_photos' =>
                                                                array (
                                                                    'size' => 'large',
                                                                    'show_thumbs' => 1,
                                                                    'thumbs_columns' => '6',
                                                                    'effect' => 'slide',
                                                                    'zoom' => 1,
                                                                    'controls' => 1,
                                                                    'video_field' => 'field_videos',
                                                                    'num_videos' => '1',
                                                                    '_limit' => 0,
                                                                    '_render_empty' => '1',
                                                                    'pager' => false,
                                                                    'auto' => false,
                                                                    'show_videos' => false,
                                                                    'prepend_videos' => false,
                                                                    'video_privacy_mode' => false,
                                                                ),
                                                        ),
                                                    'label_as_heading' => false,
                                                ),
                                            'heading' => NULL,
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'directory-listing-photo',
                                                    'cache' => '',
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
                            1123 =>
                                array (
                                    'id' => 1123,
                                    'name' => 'group',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'separator' => '',
                                                    'separator_margin' => 1,
                                                    'inline' => false,
                                                    'nowrap' => false,
                                                ),
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'drts-display-element-overlay drts-display-element-overlay-center directory-listing-overlay',
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
                            1124 =>
                                array (
                                    'id' => 1124,
                                    'name' => 'entity_field_post_title',
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
                                                                    'link_field' => 'field_website,',
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
                                                                    'link_rel' =>
                                                                        array (
                                                                        ),
                                                                    'icon' => false,
                                                                ),
                                                        ),
                                                    'renderer' => 'entity_title',
                                                    'label_as_heading' => false,
                                                ),
                                            'heading' => NULL,
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'directory-listing-title',
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
                                    'parent_id' => 1123,
                                    'weight' => 3,
                                    'system' => false,
                                ),
                            1125 =>
                                array (
                                    'id' => 1125,
                                    'name' => 'entity_field_location_address',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'label' => 'none',
                                                    'label_custom' => '',
                                                    'label_icon' => '',
                                                    'label_icon_size' => '',
                                                    'renderer' => 'location_address',
                                                    'renderer_settings' =>
                                                        array (
                                                            'map_map' =>
                                                                array (
                                                                    'height' => 300,
                                                                    'view_marker_icon' => 'default',
                                                                    'directions' => 1,
                                                                ),
                                                            'map_street_view' =>
                                                                array (
                                                                    'height' => 300,
                                                                    'view_marker_icon' => 'default',
                                                                ),
                                                            'location_address' =>
                                                                array (
                                                                    'custom_format' => 0,
                                                                    'format' => '{street}, {city}, {province} {zip}',
                                                                    'target' => '_self',
                                                                    '_separator' => '<br />',
                                                                    'link' => false,
                                                                ),
                                                        ),
                                                    'label_as_heading' => false,
                                                ),
                                            'heading' => NULL,
                                            'advanced' =>
                                                array (
                                                    'css_class' => 'directory-listing-location-address',
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
                                    'parent_id' => 1123,
                                    'weight' => 4,
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
                    'data' =>
                        array (
                        ),
                    'elements' =>
                        array (
                            1126 =>
                                array (
                                    'id' => 1126,
                                    'name' => 'view_filter_directory_category',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'name' => 'directory_category',
                                                    'label' => 'form_icon',
                                                    'label_custom' => __('Categories', 'directories-pro'),
                                                    'label_icon' => 'fas fa-folder',
                                                    'label_icon_size' => '',
                                                    'label_as_heading' => true,
                                                    'filter' => 'view_term_list',
                                                    'field_name' => 'directory_category',
                                                    'filter_name' => 'filter_directory_category',
                                                    'display_name' => 'default',
                                                    'filter_settings' =>
                                                        array (
                                                            'view_term_list' =>
                                                                array (
                                                                    'depth' => 0,
                                                                    'hide_empty' => false,
                                                                    'hide_count' => false,
                                                                    'andor' => 'OR',
                                                                    'visible_count' => 10,
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
                            1127 =>
                                array (
                                    'id' => 1127,
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
                                                    'label' => 'custom_icon',
                                                    'label_custom' => __('Business Info', 'directories-pro'),
                                                    'label_icon' => 'fas fa-info-circle',
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
                                    'parent_id' => 0,
                                    'weight' => 7,
                                    'system' => false,
                                ),
                            1128 =>
                                array (
                                    'id' => 1128,
                                    'name' => 'view_filter_field_price_range',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'name' => 'field_price_range',
                                                    'label' => 'form',
                                                    'label_custom' => __('Price Range', 'directories-pro'),
                                                    'label_icon' => NULL,
                                                    'label_icon_size' => '',
                                                    'label_as_heading' => false,
                                                    'filter' => 'range',
                                                    'field_name' => 'field_price_range',
                                                    'filter_name' => 'filter_field_price_range',
                                                    'display_name' => 'default',
                                                    'filter_settings' =>
                                                        array (
                                                            'range' =>
                                                                array (
                                                                    'step' => '0.01',
                                                                    'ignore_min_max' => 1,
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
                                    'parent_id' => 1127,
                                    'weight' => 9,
                                    'system' => false,
                                ),
                            1129 =>
                                array (
                                    'id' => 1129,
                                    'name' => 'view_filter_field_date_established',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'name' => 'field_date_established',
                                                    'label' => 'form',
                                                    'label_custom' => '',
                                                    'label_icon' => NULL,
                                                    'label_icon_size' => '',
                                                    'label_as_heading' => false,
                                                    'filter' => 'daterange',
                                                    'field_name' => 'field_date_established',
                                                    'filter_name' => 'filter_field_date_established',
                                                    'display_name' => 'default',
                                                    'filter_settings' =>
                                                        array (
                                                            'daterange' =>
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
                                    'parent_id' => 1127,
                                    'weight' => 8,
                                    'system' => false,
                                ),
                            1130 =>
                                array (
                                    'id' => 1130,
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
                                                    'label' => 'custom_icon',
                                                    'label_custom' => __('Others', 'directories-pro'),
                                                    'label_icon' => 'fas fa-ellipsis-v',
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
                                    'parent_id' => 0,
                                    'weight' => 11,
                                    'system' => false,
                                ),
                            1131 =>
                                array (
                                    'id' => 1131,
                                    'name' => 'view_filter_field_videos',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'name' => 'field_videos',
                                                    'label' => 'form',
                                                    'label_custom' => '',
                                                    'label_icon' => NULL,
                                                    'label_icon_size' => '',
                                                    'label_as_heading' => false,
                                                    'filter' => 'video',
                                                    'field_name' => 'field_videos',
                                                    'filter_name' => 'filter_field_videos',
                                                    'display_name' => 'default',
                                                    'filter_settings' =>
                                                        array (
                                                            'video' =>
                                                                array (
                                                                    'checkbox_label' => 'Show with video only',
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
                                    'parent_id' => 1130,
                                    'weight' => 13,
                                    'system' => false,
                                ),
                            1132 =>
                                array (
                                    'id' => 1132,
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
                                                    'label' => 'custom_icon',
                                                    'label_custom' => __('Locations', 'directories-pro'),
                                                    'label_icon' => 'fas fa-map-marker-alt',
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
                                    'parent_id' => 0,
                                    'weight' => 2,
                                    'system' => false,
                                ),
                            1133 =>
                                array (
                                    'id' => 1133,
                                    'name' => 'view_filter_location_location',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'name' => 'location_location',
                                                    'label' => 'form',
                                                    'label_custom' => __('Locations', 'directories-pro'),
                                                    'label_icon' => NULL,
                                                    'label_icon_size' => '',
                                                    'label_as_heading' => false,
                                                    'filter' => 'view_term_list',
                                                    'field_name' => 'location_location',
                                                    'filter_name' => 'filter_location_location',
                                                    'display_name' => 'default',
                                                    'filter_settings' =>
                                                        array (
                                                            'view_term_list' =>
                                                                array (
                                                                    'depth' => 2,
                                                                    'hide_empty' => false,
                                                                    'hide_count' => false,
                                                                    'icon' => 1,
                                                                    'icon_size' => 'sm',
                                                                    'andor' => 'OR',
                                                                    'visible_count' => 10,
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
                                    'parent_id' => 1132,
                                    'weight' => 3,
                                    'system' => false,
                                ),
                            1134 =>
                                array (
                                    'id' => 1134,
                                    'name' => 'view_filter_location_address',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'name' => 'location_address',
                                                    'label' => 'form',
                                                    'label_custom' => __('Location', 'directories-pro'),
                                                    'label_icon' => NULL,
                                                    'label_icon_size' => '',
                                                    'label_as_heading' => false,
                                                    'filter' => 'location_address',
                                                    'field_name' => 'location_address',
                                                    'filter_name' => 'filter_location_address',
                                                    'display_name' => 'default',
                                                    'filter_settings' =>
                                                        array (
                                                            'location_address' =>
                                                                array (
                                                                    'disable_input' => 1,
                                                                    'radius' => '0',
                                                                    'disable_radius' => false,
                                                                    'placeholder' => '',
                                                                    'search_this_area' => 1,
                                                                    'search_my_loc' => 1,
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
                                    'parent_id' => 1132,
                                    'weight' => 4,
                                    'system' => false,
                                ),
                            1135 =>
                                array (
                                    'id' => 1135,
                                    'name' => 'view_filter_voting_rating',
                                    'data' =>
                                        array (
                                            'settings' =>
                                                array (
                                                    'name' => 'voting_rating',
                                                    'label' => 'form_icon',
                                                    'label_custom' => '',
                                                    'label_icon' => 'fas fa-star',
                                                    'label_icon_size' => '',
                                                    'label_as_heading' => true,
                                                    'filter' => 'voting_rating',
                                                    'field_name' => 'voting_rating',
                                                    'filter_name' => 'filter_voting_rating',
                                                    'display_name' => 'default',
                                                    'filter_settings' =>
                                                        array (
                                                            'voting_rating' =>
                                                                array (
                                                                    'inline' => false,
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
                                    'weight' => 6,
                                    'system' => false,
                                ),
                        ),
                ),
        ),
);