<?php
use SabaiApps\Directories\Application;

$tables = [
    'display_display' => [
        'fields' => [
            'display_name' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 255,
                'default' => '',
            ],
            'display_bundle_name' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 40,
                'default' => '',
            ],
            'display_type' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 10,
                'default' => '',
            ],
            'display_data' => [
                'type' => Application::COLUMN_TEXT,
                'notnull' => true,
            ],
            'display_id' => [
                'autoincrement' => true,
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
            'display_created' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
            'display_updated' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
        ],
        'indexes' => [
            'display_id' => [
                'primary' => true,
                'fields' => [
                    'display_id' => ['sorting' => 'ascending'],
                ],
            ],
            'display_bundle_name' => [
                'fields' => [
                    'display_bundle_name' => [
                    ],
                ],
            ],
            'display_type' => [
                'fields' => [
                    'display_type' => [
                    ],
                ],
            ],
            'display_type_name_bundle_name' => [
                'unique' => true,
                'fields' => [
                    'display_type' => [
                        'sorting' => 'ascending',
                    ],
                    'display_name' => [
                        'sorting' => 'ascending',
                    ],
                    'display_bundle_name' => [
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
    'display_element' => [
        'fields' => [
            'element_name' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 255,
                'default' => '',
            ],
            'element_weight' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 2,
                'default' => 0,
            ],
            'element_data' => [
                'type' => Application::COLUMN_TEXT,
                'notnull' => true,
            ],
            'element_parent_id' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
            'element_system' => [
                'type' => Application::COLUMN_BOOLEAN,
                'notnull' => true,
                'default' => false,
            ],
            'element_element_id' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
            'element_id' => [
                'autoincrement' => true,
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
            'element_created' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
            'element_updated' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
            'element_display_id' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
        ],
        'indexes' => [
            'element_id' => [
                'primary' => true,
                'fields' => [
                    'element_id' => ['sorting' => 'ascending'],
                ],
            ],
            'element_name' => [
                'fields' => [
                    'element_name' => [
                    ],
                ],
            ],
            'element_element_id' => [
                'fields' => [
                    'element_element_id' => [
                    ],
                ],
            ],
            'element_display_id' => ['fields' => ['element_display_id' => []]],
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