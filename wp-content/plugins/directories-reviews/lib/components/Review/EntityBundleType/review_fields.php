<?php
return array (
  'review_rating' => 
  array (
    'type' => 'review_rating',
    'settings' => 
    array (
    ),
    'realm' => 2,
    'data' => 
    array (
      'label' => __('Rating', 'directories-reviews'),
      'description' => '',
      'default_value' => NULL,
      'required' => false,
      'disabled' => false,
      'weight' => 3,
      'max_num_items' => 0,
      'widget' => 'review_rating',
      'widget_settings' => 
      array (
        'review_rating' => 
        array (
          'criteria' => 
          array (
          ),
          'step' => '0.5',
        ),
      ),
    ),
  ),
  'review_photos' => 
  array (
    'type' => 'wp_image',
    'settings' => 
    array (
    ),
    'realm' => 2,
    'data' => 
    array (
      'label' => __('Photos', 'directories-reviews'),
      'description' => '',
      'default_value' => NULL,
      'required' => false,
      'disabled' => false,
      'weight' => 10,
      'max_num_items' => 20,
      'widget' => 'wp_image',
      'widget_settings' => 
      array (
        'max_file_size' => 2048,
      ),
    ),
  ),
);