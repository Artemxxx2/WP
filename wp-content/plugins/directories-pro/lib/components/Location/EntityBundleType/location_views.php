<?php
return array (
  'default' => 
  array (
    'mode' => 'masonry',
    'label' => __('Default', 'directories-pro'),
    'settings' => 
    array (
      'display' => 'summary-image_overlay',
      'sort' => 
      array (
        'options' => 
        array (
          0 => 'term_title',
        ),
        'default' => 'term_title',
      ),
      'pagination' => 
      array (
        'no_pagination' => 1,
        'perpage' => 20,
        'allow_perpage' => false,
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
          'location_photo' => '1',
        ),
        'limit' => 0,
      ),
      'other' => 
      array (
        'num' => false,
      ),
      'directory_name' => 'directory',
      'bundle_name' => 'directory_loc_loc',
      'view_id' => '8',
    ),
    'default' => true,
  ),
  'list' =>
        array (
            'mode' => 'list',
            'label' => __('List', 'directories-pro'),
            'settings' =>
                array (
                    'list_grid' => 1,
                    'list_no_row' => 1,
                    'list_grid_cols' =>
                        array (
                            'num' => 'responsive',
                            'num_responsive' =>
                                array (
                                    'xs' => '2',
                                    'sm' => 'inherit',
                                    'md' => 'inherit',
                                    'lg' => '3',
                                    'xl' => '4',
                                ),
                        ),
                    'list_grid_gutter_width' => 'sm',
                    'list_grid_default' => false,
                    'sort' =>
                        array (
                            'options' =>
                                array (
                                    0 => 'term_title',
                                ),
                            'default' => 'term_title',
                        ),
                    'pagination' =>
                        array (
                            'perpage' => 100,
                            'perpages' =>
                                array (
                                    0 => 10,
                                    1 => 20,
                                    2 => 50,
                                ),
                            'no_pagination' => false,
                            'allow_perpage' => false,
                        ),
                    'query' =>
                        array (
                            'fields' =>
                                array (
                                    'term_parent' => '0',
                                ),
                            'limit' => 0,
                        ),
                    'other' =>
                        array (
                            'not_found' =>
                                array (
                                    'html' => '',
                                    'custom' => false,
                                ),
                            'num' => false,
                        ),
                    'directory_name' => 'directory',
                    'bundle_name' => 'directory_loc_loc',
                    'view_id' => '68',
                ),
            'default' => false,
    ),
);