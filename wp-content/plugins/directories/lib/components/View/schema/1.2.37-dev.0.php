<?php
use SabaiApps\Directories\Application;

$tables = [
    'view_view' => [
        'fields' => [
            'view_name' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 50,
                'default' => '',
            ],
            'view_mode' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 50,
                'default' => '',
            ],
            'view_data' => [
                'type' => Application::COLUMN_TEXT,
                'notnull' => true,
            ],
            'view_bundle_name' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 40,
                'default' => '',
            ],
            'view_default' => [
                'type' => Application::COLUMN_BOOLEAN,
                'notnull' => true,
                'default' => false,
            ],
            'view_id' => [
                'autoincrement' => true,
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
            'view_created' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
            'view_updated' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
        ],
        'indexes' => [
            'view_id' => [
                'primary' => true,
                'fields' => [
                    'view_id' => ['sorting' => 'ascending'],
                ],
            ],
            'view_bundle_name' => [
                'fields' => [
                    'view_bundle_name' => [
                    ],
                ],
            ],
            'view_name_bundle_name' => [
                'unique' => true,
                'fields' => [
                    'view_name' => [
                        'sorting' => 'ascending',
                    ],
                    'view_bundle_name' => [
                        'sorting' => 'ascending',
                    ],
                ],
            ],
        ],
   	'initialization' => [
            'insert' => [
            ],
        ],
    ],
    'view_filter' => [
        'fields' => [
            'filter_name' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 50,
                'default' => '',
            ],
            'filter_type' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 50,
                'default' => '',
            ],
            'filter_data' => [
                'type' => Application::COLUMN_TEXT,
                'notnull' => true,
            ],
            'filter_bundle_name' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 40,
                'default' => '',
            ],
            'filter_field_id' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
            'filter_display_name' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 150,
                'default' => '',
            ],
            'filter_id' => [
                'autoincrement' => true,
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
            'filter_created' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
            'filter_updated' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
        ],
        'indexes' => [
            'filter_id' => [
                'primary' => true,
                'fields' => [
                    'filter_id' => ['sorting' => 'ascending'],
                ],
            ],
            'filter_field_id' => [
                'fields' => [
                    'filter_field_id' => [
                    ],
                ],
            ],
            'filter_display_name_bundle_name' => [
                'fields' => [
                    'filter_display_name' => [
                        'sorting' => 'ascending',
                    ],
                    'filter_bundle_name' => [
                        'sorting' => 'ascending',
                    ],
                ],
            ],
        ],
   	'initialization' => [
            'insert' => [
            ],
        ],
    ],
];
return [
    'charset' => '',
    'description' => '',
    'tables' => $tables,
];