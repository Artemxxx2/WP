<?php
use SabaiApps\Directories\Application;

$tables = [
    'directory_directory' => [
        'fields' => [
            'directory_name' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 12,
                'default' => '',
            ],
            'directory_type' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 40,
                'default' => '',
            ],
            'directory_data' => [
                'type' => Application::COLUMN_TEXT,
                'notnull' => true,
            ],
            'directory_created' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
            'directory_updated' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
        ],
        'indexes' => [
            'directory_name' => [
                'primary' => true,
                'fields' => [
                    'directory_name' => ['sorting' => 'ascending'],
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