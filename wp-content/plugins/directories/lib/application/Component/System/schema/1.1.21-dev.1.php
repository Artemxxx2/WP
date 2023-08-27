<?php
use SabaiApps\Directories\Application;

$tables = [
    'system_component' => [
        'fields' => [
            'component_name' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 40,
                'default' => '',
            ],
            'component_version' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 30,
                'default' => '',
            ],
            'component_priority' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 2,
                'default' => 0,
            ],
            'component_config' => [
                'type' => Application::COLUMN_TEXT,
                'notnull' => true,
            ],
            'component_events' => [
                'type' => Application::COLUMN_TEXT,
                'notnull' => true,
            ],
            'component_created' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
            'component_updated' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
        ],
        'indexes' => [
            'component_name' => [
                'primary' => true,
                'fields' => [
                    'component_name' => ['sorting' => 'ascending'],
                ],
            ],
            'component_priority' => [
                'fields' => [
                    'component_priority' => [
                    ],
                ],
            ],
        ],
   	'initialization' => [
            'insert' => [
                [
                    'component_name' => 'System',
                    'component_created' => '1357603200',
                    'component_updated' => '0',
                    'component_version' => '1.3.108',
                    'component_priority' => '99',
                    'component_config' => '',
                    'component_events' => '',
                ],
            ],
        ],
    ],
    'system_route' => [
        'fields' => [
            'route_path' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 255,
                'default' => '',
            ],
            'route_method' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 10,
                'default' => '',
            ],
            'route_format' => [
                'type' => Application::COLUMN_TEXT,
                'notnull' => true,
            ],
            'route_controller' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 255,
                'default' => '',
            ],
            'route_controller_component' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 40,
                'default' => '',
            ],
            'route_forward' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 255,
                'default' => '',
            ],
            'route_component' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 40,
                'default' => '',
            ],
            'route_type' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 2,
                'default' => 0,
            ],
            'route_access_callback' => [
                'type' => Application::COLUMN_BOOLEAN,
                'notnull' => true,
                'default' => false,
            ],
            'route_title_callback' => [
                'type' => Application::COLUMN_BOOLEAN,
                'notnull' => true,
                'default' => false,
            ],
            'route_callback_path' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 255,
                'default' => '',
            ],
            'route_callback_component' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 40,
                'default' => '',
            ],
            'route_weight' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 3,
                'default' => 0,
            ],
            'route_depth' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 2,
                'default' => 0,
            ],
            'route_priority' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 1,
                'default' => 5,
            ],
            'route_data' => [
                'type' => Application::COLUMN_TEXT,
                'notnull' => true,
            ],
            'route_language' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 7,
                'default' => '',
            ],
            'route_admin' => [
                'type' => Application::COLUMN_BOOLEAN,
                'notnull' => true,
                'default' => false,
            ],
            'route_id' => [
                'autoincrement' => true,
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
            'route_created' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
            'route_updated' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
        ],
        'indexes' => [
            'route_id' => [
                'primary' => true,
                'fields' => [
                    'route_id' => ['sorting' => 'ascending'],
                ],
            ],
            'route_path' => [
                'fields' => [
                    'route_path' => [
                    ],
                ],
            ],
            'route_component' => [
                'fields' => [
                    'route_component' => [
                    ],
                ],
            ],
            'route_depth' => [
                'fields' => [
                    'route_depth' => [
                    ],
                ],
            ],
            'route_language' => [
                'fields' => [
                    'route_language' => [
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