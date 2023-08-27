<?php
return array (
  'default' => 
  array (
    'mode' => 'list',
    'label' => __('Default', 'directories'),
    'settings' => 
    array (
      'list_grid' => 1,
      'list_no_row' => 1,
      'list_grid_default' => false,
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
        'no_pagination' => false,
        'perpage' => 100,
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
          'term_parent' => '0',
        ),
        'limit' => 0,
      ),
      'other' => 
      array (
        'num' => false,
      ),
      'directory_name' => 'directory',
      'bundle_name' => 'directory_dir_cat',
      'view_id' => '9',
    ),
    'default' => true,
  ),
);
