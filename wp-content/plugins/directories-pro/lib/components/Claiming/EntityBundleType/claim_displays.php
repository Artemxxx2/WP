<?php
return array (
  'entity' => 
  array (
    'dashboard_row' => 
    array (
      'name' => 'dashboard_row',
      'type' => 'entity',
      'data' => 
      array (
      ),
      'elements' => 
      array (
        225 => 
        array (
          'id' => 225,
          'name' => 'entity_field_post_title',
          'data' => 
          array (
            'settings' => 
            array (
              'label' => 'custom',
              'label_custom' => __('Message', 'directories-pro'),
              'label_icon' => NULL,
              'label_icon_size' => '',
              'label_as_heading' => 1,
              'renderer_settings' => 
              array (
                'entity_title' => 
                array (
                  'link' => '',
                  'link_target' => '_self',
                  'max_chars' => 100,
                  'icon' => false,
                  'icon_settings' => 
                  array (
                    'size' => 'sm',
                    'fallback' => false,
                    'color' => 
                    array (
                      'type' => '',
                      'custom' => '',
                    ),
                    'field' => '',
                    'is_image' => false,
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
          'parent_id' => 0,
          'weight' => 1,
          'system' => false,
        ),
        226 => 
        array (
          'id' => 226,
          'name' => 'entity_parent_field_post_title',
          'data' => 
          array (
            'settings' => 
            array (
              'label' => 'custom',
              'label_custom' => __('Listing', 'directories-pro'),
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
          'weight' => 2,
          'system' => false,
        ),
        227 => 
        array (
          'id' => 227,
          'name' => 'labels',
          'data' => 
          array (
            'settings' => 
            array (
              'arrangement' => 
              array (
                0 => 'claiming_status',
              ),
              'labels' => 
              array (
              ),
            ),
            'heading' => 
            array (
              'label' => 'custom',
              'label_custom' => __('Status', 'directories-pro'),
              'label_icon' => NULL,
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
          'weight' => 3,
          'system' => false,
        ),
        228 => 
        array (
          'id' => 228,
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
              'tooltip' => 1,
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
                    '_color' => 'outline-secondary',
                    '_icon' => 'fas fa-edit',
                    '_link_color' => '',
                  ),
                ),
                'dashboard_posts_delete' => 
                array (
                  'settings' => 
                  array (
                    '_hide_label' => false,
                    '_color' => 'danger',
                    '_icon' => 'fas fa-trash-alt',
                    '_link_color' => '',
                  ),
                ),
              ),
              'btn' => true,
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
        229 => 
        array (
          'id' => 229,
          'name' => 'entity_field_post_published',
          'data' => 
          array (
            'settings' => 
            array (
              'label' => 'custom',
              'label_custom' => __('Date', 'directories-pro'),
              'label_icon' => NULL,
              'label_icon_size' => '',
              'label_as_heading' => 1,
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
          'weight' => 4,
          'system' => false,
        ),
      ),
    ),
  ),
);