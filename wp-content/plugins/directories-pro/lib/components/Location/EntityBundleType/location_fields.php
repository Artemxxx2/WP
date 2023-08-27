<?php
return array (
  'location_photo' => 
  array (
    'type' => 'wp_image',
    'settings' => 
    array (
    ),
    'realm' => 2,
    'data' => 
    array (
      'label' => __('Photo', 'directories-pro'),
      'description' => '',
      'default_value' => NULL,
      'required' => false,
      'disabled' => false,
      'weight' => 7,
      'max_num_items' => 1,
      'widget' => 'wp_image',
      'widget_settings' => 
      array (
        'wp_image' => 
        array (
          'max_file_size' => 2048,
        ),
      ),
    ),
  ),
);