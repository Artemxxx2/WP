<?php
use SabaiApps\Directories\Application;

$tables = [
    'payment_featuregroup' => [
        'fields' => [
            'featuregroup_logs' => [
                'type' => Application::COLUMN_TEXT,
                'notnull' => true,
            ],
            'featuregroup_bundle_name' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 40,
                'default' => '',
            ],
            'featuregroup_order_id' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'default' => 0,
            ],
            'featuregroup_id' => [
                'autoincrement' => true,
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'default' => 0,
                'length' => 10,
            ],
            'featuregroup_created' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
            'featuregroup_updated' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
        ],
        'indexes' => [
            'featuregroup_id' => [
                'primary' => true,
                'fields' => [
                    'featuregroup_id' => ['sorting' => 'ascending'],
                ],
            ],
            'featuregroup_bundle_name' => [
                'fields' => [
                    'featuregroup_bundle_name' => [
                    ],
                ],
            ],
        ],
   	'initialization' => [
            'insert' => [
            ],
        ],
    ],
    'payment_feature' => [
        'fields' => [
            'feature_status' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 1,
                'default' => 0,
            ],
            'feature_feature_name' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 50,
                'default' => '',
            ],
            'feature_metas' => [
                'type' => Application::COLUMN_TEXT,
                'notnull' => true,
            ],
            'feature_logs' => [
                'type' => Application::COLUMN_TEXT,
                'notnull' => true,
            ],
            'feature_id' => [
                'autoincrement' => true,
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
            'feature_created' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
            'feature_updated' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
            'feature_featuregroup_id' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
        ],
        'indexes' => [
            'feature_id' => [
                'primary' => true,
                'fields' => [
                    'feature_id' => ['sorting' => 'ascending'],
                ],
            ],
            'feature_featuregroup_id' => ['fields' => ['feature_featuregroup_id' => []]],
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