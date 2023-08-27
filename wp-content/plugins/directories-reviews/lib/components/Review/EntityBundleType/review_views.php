<?php
return array (
    'default' =>
        array (
            'mode' => 'list',
            'label' => __('Default', 'directories-reviews'),
            'settings' =>
                array (
                    'list_grid' => 1,
                    'list_grid_cols' =>
                        array (
                            'num' => 'responsive',
                            'num_responsive' =>
                                array (
                                    'xs' => '1',
                                    'sm' => 'inherit',
                                    'md' => 'inherit',
                                    'lg' => '2',
                                    'xl' => 'inherit',
                                ),
                        ),
                    'list_grid_gutter_width' => 'sm',
                    'display' => 'summary',
                    'display_cache' => '',
                    'display_cache_guest_only' => 1,
                    'list_no_row' => false,
                    'list_grid_default' => false,
                    'sort' =>
                        array (
                            'options' =>
                                array (
                                    0 => 'post_published',
                                    1 => 'post_published,asc',
                                    2 => 'voting_updown',
                                ),
                            'default' => 'voting_updown',
                            'secondary' => '',
                        ),
                    'pagination' =>
                        array (
                            'type' => '',
                            'perpage' => 10,
                            'load_more_label' => '',
                            'perpages' =>
                                array (
                                    0 => 10,
                                    1 => 20,
                                    2 => 50,
                                ),
                            'no_pagination' => false,
                            'allow_perpage' => false,
                        ),
                    'filter' =>
                        array (
                            'show' => 1,
                            'display' => 'default',
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
                        ),
                    'query' =>
                        array (
                            'fields' =>
                                array (
                                ),
                            'limit' => 0,
                        ),
                    'directory_name' => 'directory',
                    'bundle_name' => 'directory_rev_rev',
                    'view_id' => '78',
                ),
            'default' => true,
        ),
);