<?php
return array (
    'default' =>
        array (
            'mode' => 'list',
            'label' => __('Default', 'directories-pro'),
            'settings' =>
                array (
                    'list_grid' => 1,
                    'list_no_row' => false,
                    'list_grid_default' => false,
                    'list_grid_cols' =>
                        array (
                            'num' => 'responsive',
                            'num_responsive' =>
                                array (
                                    'xs' => '1',
                                    'sm' => '2',
                                    'md' => 'inherit',
                                    'lg' => '3',
                                    'xl' => 'inherit',
                                ),
                        ),
                    'list_grid_gutter_width' => 'sm',
                    'map' =>
                        array (
                            'show' => 1,
                            'position' => 'right',
                            'span' => 4,
                            'height' => 400,
                            'style' => '',
                            'scroll_to_item' => 1,
                            'sticky' => 1,
                            'fullscreen' => 1,
                            'fullscreen_span' => 5,
                            'infobox' => 1,
                            'infobox_width' => 240,
                            'trigger_infobox' => false,
                        ),
                    'sort' =>
                        array (
                            'options' =>
                                array (
                                    0 => 'post_published',
                                    1 => 'post_published,asc',
                                    2 => 'post_title',
                                    3 => 'post_title,desc',
                                    4 => 'location_address',
                                    5 => 'voting_rating',
                                    6 => 'random',
                                    7 => 'entity_child_count,review_review',
                                    8 => 'review_ratings',
                                    9 => 'voting_bookmark',
                                ),
                            'default' => 'post_published',
                            'stick_featured' => false,
                        ),
                    'pagination' =>
                        array (
                            'no_pagination' => false,
                            'perpage' => 20,
                            'allow_perpage' => 1,
                            'perpages' =>
                                array (
                                    0 => 10,
                                    1 => 20,
                                    2 => 50,
                                ),
                        ),
                    'query' =>
                        array (
                            'fields' =>
                                array (
                                ),
                            'limit' => 0,
                        ),
                    'filter' =>
                        array (
                            'show' => false,
                            'show_modal' => false,
                        ),
                    'other' =>
                        array (
                            'num' => 1,
                            'add' => ['show' => false],
                        ),
                    'directory_name' => 'directory',
                    'bundle_name' => 'directory_dir_ltg',
                    'view_id' => '6',
                ),
            'default' => true,
        ),
    'featured_listings' =>
        array (
            'mode' => 'slider_photos',
            'label' => __('Featured Listings', 'directories-pro'),
            'settings' =>
                array (
                    'photoslider_image_field' => 'directory_photos',
                    'photoslider_image_size' => 'thumbnail',
                    'photoslider_columns' => 10,
                    'photoslider_pager' => 1,
                    'photoslider_controls' => 1,
                    'photoslider_caption' => 1,
                    'photoslider_auto' => 1,
                    'photoslider_auto_speed' => 3000,
                    'photoslider_height' => 135,
                    'photoslider_padding' => '5',
                    'photoslider_thumbs_columns' => 5,
                    'photoslider_link' => 1,
                    'photoslider_center' => false,
                    'photoslider_fade' => false,
                    'photoslider_thumbs' => false,
                    'photoslider_zoom' => false,
                    'sort' =>
                        array (
                            'options' =>
                                array (
                                    0 => 'post_id',
                                ),
                            'default' => 'post_id',
                            'stick_featured' => false,
                        ),
                    'pagination' =>
                        array (
                            'no_pagination' => 1,
                            'perpage' => 20,
                            'perpages' =>
                                array (
                                    0 => 10,
                                    1 => 20,
                                    2 => 50,
                                ),
                            'allow_perpage' => false,
                        ),
                    'query' =>
                        array (
                            'fields' =>
                                array (
                                    'entity_featured' => '1',
                                    'directory_photos' => '1',
                                ),
                            'limit' => 12,
                        ),
                    'filter' =>
                        array (
                            'display' => 'default',
                            'show' => false,
                            'show_modal' => false,
                            'shown' => false,
                        ),
                    'other' =>
                        array (
                            'not_found' =>
                                array (
                                    'html' => '',
                                    'custom' => false,
                                ),
                            'num' => false,
                            'add' =>
                                array (
                                    'show' => false,
                                    'show_label' => false,
                                ),
                        ),
                    'directory_name' => 'directory',
                    'bundle_name' => 'directory_dir_ltg',
                    'view_id' => '6',
                ),
            'default' => false,
        ),
    'map' =>
        array (
            'mode' => 'map',
            'label' => __('Map', 'directories-pro'),
            'settings' =>
                array (
                    'map' =>
                        array (
                            'coordinates_field' => 'location_address',
                            'height' => 600,
                            'view_marker_icon' => 'image',
                            'fullscreen' => 1,
                            'fullscreen_offset' => 0,
                            'infobox_width' => 300,
                        ),
                    'display' => 'summary-infobox',
                    'display_cache' => '',
                    'display_cache_guest_only' => 1,
                    'sort' =>
                        array (
                            'options' =>
                                array (
                                    0 => 'post_published',
                                    1 => 'post_modified,asc',
                                    2 => 'random',
                                ),
                            'default' => 'post_published',
                            'stick_featured' => false,
                        ),
                    'pagination' =>
                        array (
                            'perpage' => 100,
                            'allow_perpage' => 1,
                            'perpages' =>
                                array (
                                    0 => 20,
                                    1 => 50,
                                    2 => 100,
                                ),
                            'no_pagination' => false,
                        ),
                    'query' =>
                        array (
                            'fields' =>
                                array (
                                ),
                            'limit' => 0,
                        ),
                    'filter' =>
                        array (
                            'show' => 1,
                            'display' => 'default',
                            'show_modal' => false,
                            'shown' => false,
                        ),
                    'other' =>
                        array (
                            'num' => 1,
                            'not_found' =>
                                array (
                                    'html' => '',
                                    'custom' => false,
                                ),
                            'add' =>
                                array (
                                    'show' => false,
                                    'show_label' => false,
                                ),
                        ),
                    'directory_name' => 'directory',
                    'bundle_name' => 'directory_dir_ltg',
                    'view_id' => '10',
                ),
            'default' => false,
        ),
    'carousel' =>
        array (
            'mode' => 'slider_carousel',
            'label' => __('Recent Listings', 'directories-pro'),
            'settings' =>
                array (
                    'carousel_columns' => 4,
                    'carousel_controls' => 1,
                    'carousel_auto' => 1,
                    'carousel_auto_speed' => 3000,
                    'display' => 'summary',
                    'display_cache' => '',
                    'display_cache_guest_only' => 1,
                    'carousel_pager' => false,
                    'carousel_fade' => false,
                    'sort' =>
                        array (
                            'options' =>
                                array (
                                    0 => 'post_published',
                                ),
                            'default' => 'post_published',
                            'stick_featured' => false,
                        ),
                    'pagination' =>
                        array (
                            'no_pagination' => 1,
                            'perpage' => 10,
                            'perpages' =>
                                array (
                                    0 => 10,
                                    1 => 20,
                                    2 => 50,
                                ),
                            'allow_perpage' => false,
                        ),
                    'query' =>
                        array (
                            'fields' =>
                                array (
                                ),
                            'limit' => 10,
                        ),
                    'filter' =>
                        array (
                            'display' => 'default',
                            'show' => false,
                            'show_modal' => false,
                            'shown' => false,
                        ),
                    'other' =>
                        array (
                            'not_found' =>
                                array (
                                    'html' => '',
                                    'custom' => false,
                                ),
                            'num' => false,
                            'add' =>
                                array (
                                    'show' => false,
                                    'show_label' => false,
                                ),
                        ),
                    'directory_name' => 'directory',
                    'bundle_name' => 'directory_dir_ltg',
                    'view_id' => '22',
                ),
            'default' => false,
        ),
);
