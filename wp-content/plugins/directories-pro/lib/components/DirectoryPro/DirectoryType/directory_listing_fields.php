<?php
return array (
  'field_phone' => 
  array (
    'type' => 'phone',
    'settings' => 
    array (
      'min_length' => NULL,
      'max_length' => NULL,
      'char_validation' => 'none',
      'mask' => '(999) 999-9999',
    ),
    'realm' => 0,
    'data' => 
    array (
      'label' => __('Phone Number', 'directories-pro'),
      'description' => '',
      'default_value' => NULL,
      'required' => false,
      'disabled' => false,
      'weight' => 4,
      'max_num_items' => 1,
      'widget' => 'phone',
      'widget_settings' => 
      array (
        'phone' => 
        array (
          'autopopulate' => '',
          'field_prefix' => NULL,
          'field_suffix' => NULL,
          'mask' => NULL,
        ),
      ),
    ),
  ),
  'field_fax' => 
  array (
    'type' => 'phone',
    'settings' => 
    array (
      'min_length' => NULL,
      'max_length' => NULL,
      'char_validation' => 'none',
      'mask' => '(999) 999-9999',
    ),
    'realm' => 0,
    'data' => 
    array (
      'label' => __('Fax Number', 'directories-pro'),
      'description' => '',
      'default_value' => NULL,
      'required' => false,
      'disabled' => false,
      'weight' => 5,
      'max_num_items' => 1,
      'widget' => 'phone',
      'widget_settings' => 
      array (
        'phone' => 
        array (
          'autopopulate' => '',
          'field_prefix' => NULL,
          'field_suffix' => NULL,
          'mask' => NULL,
        ),
      ),
    ),
  ),
  'field_email' => 
  array (
    'type' => 'email',
    'settings' => 
    array (
      'min_length' => NULL,
      'max_length' => NULL,
      'char_validation' => 'email',
      'check_mx' => false,
    ),
    'realm' => 0,
    'data' => 
    array (
      'label' => __('E-mail Address', 'directories-pro'),
      'description' => '',
      'default_value' => NULL,
      'required' => false,
      'disabled' => false,
      'weight' => 6,
      'max_num_items' => 1,
      'widget' => 'email',
      'widget_settings' => 
      array (
        'email' => 
        array (
          'autopopulate' => false,
          'field_prefix' => NULL,
          'field_suffix' => NULL,
          'mask' => NULL,
        ),
      ),
    ),
  ),
  'field_website' => 
  array (
    'type' => 'url',
    'settings' => 
    array (
      'min_length' => '',
      'max_length' => '',
      'regex' => '',
      'char_validation' => 'url',
    ),
    'realm' => 0,
    'data' => 
    array (
      'label' => __('Website URL', 'directories-pro'),
      'description' => '',
      'default_value' => NULL,
      'required' => false,
      'disabled' => false,
      'weight' => 7,
      'max_num_items' => 1,
      'widget' => 'url',
      'widget_settings' => 
      array (
        'url' => array(
          'mask' => '',
          'autopopulate' => false,
        ),
      ),
    ),
  ),
  'field_social_accounts' => 
  array (
    'type' => 'social_accounts',
    'settings' => 
    array (
      'medias' => 
      array (
        0 => 'facebook',
        1 => 'twitter',
        2 => 'instagram',
        3 => 'rss',
      ),
    ),
    'realm' => 0,
    'data' => 
    array (
      'label' => __('Social Accounts', 'directories-pro'),
      'description' => '',
      'default_value' => NULL,
      'required' => false,
      'disabled' => false,
      'weight' => 8,
      'max_num_items' => 0,
      'widget' => 'social_accounts',
      'widget_settings' => 
      array (
        'social_accounts' =>
          array (
          ),
      ),
    ),
  ),
  'field_opening_hours' => 
  array (
    'type' => 'directory_opening_hours',
    'settings' => 
    array (),
    'realm' => 0,
    'data' => 
    array (
      'label' => __('Opening Hours', 'directories-pro'),
      'description' => '',
      'default_value' => NULL,
      'required' => false,
      'disabled' => false,
      'weight' => 13,
      'max_num_items' => 0,
      'widget' => 'directory_opening_hours',
      'widget_settings' => 
      array (
        'directory_opening_hours' =>
        array (
        ),
      ),
    ),
  ),
  'field_price_range' => 
  array (
    'type' => 'range',
    'settings' => 
    array (
      'decimals' => '2',
      'prefix' => '$',
      'suffix' => '',
      'min' => NULL,
      'max' => NULL,
    ),
    'realm' => 0,
    'data' => 
    array (
      'label' => __('Price Range', 'directories-pro'),
      'description' => '',
      'default_value' => NULL,
      'required' => false,
      'disabled' => false,
      'weight' => 12,
      'max_num_items' => 1,
      'widget' => 'range',
      'widget_settings' => 
      array (
        'range' => 
        array (
          'step' => '1',
        ),
      ),
    ),
  ),
  'directory_photos' => 
  array (
    'type' => 'wp_image',
    'settings' => 
    array (
    ),
    'realm' => 2,
    'data' => 
    array (
      'label' => __('Photos', 'directories-pro'),
      'description' => '',
      'default_value' => NULL,
      'required' => false,
      'disabled' => false,
      'weight' => 10,
      'max_num_items' => 0,
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
  'field_date_established' => 
  array (
    'type' => 'date',
    'settings' => 
    array (
      'enable_time' => false,
      'date_range_enable' => false,
      'date_range' => NULL,
    ),
    'realm' => 0,
    'data' => 
    array (
      'label' => __('Date Established', 'directories-pro'),
      'description' => '',
      'default_value' => NULL,
      'required' => false,
      'disabled' => false,
      'weight' => 14,
      'max_num_items' => 1,
      'widget' => 'date',
      'widget_settings' => 
      array (
        'date' => 
        array (
          'current_date_selected' => false,
        ),
      ),
    ),
  ),
  'field_videos' => 
  array (
    'type' => 'video',
    'settings' => 
    array (
    ),
    'realm' => 0,
    'data' => 
    array (
      'label' => __('Videos', 'directories-pro'),
      'description' => '',
      'default_value' => NULL,
      'required' => false,
      'disabled' => false,
      'weight' => 11,
      'max_num_items' => 0,
      'widget' => 'video',
      'widget_settings' => 
      array (
      ),
    ),
  ),
);