<?php
use SabaiApps\Directories\Application;

$tables = [
    'voting_vote' => [
        'fields' => [
            'vote_bundle_name' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 40,
                'default' => '',
            ],
            'vote_entity_id' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
            'vote_value' => [
                'type' => Application::COLUMN_DECIMAL,
                'unsigned' => false,
                'notnull' => true,
                'length' => 5,
                'scale' => 2,
                'default' => 0,
            ],
            'vote_field_name' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 50,
                'default' => '',
            ],
            'vote_name' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 40,
                'default' => '',
            ],
            'vote_comment' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 255,
                'default' => '',
            ],
            'vote_reference_id' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
            'vote_hash' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 128,
                'default' => '',
            ],
            'vote_level' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 5,
                'default' => 0,
            ],
            'vote_id' => [
                'autoincrement' => true,
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
            'vote_created' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
            'vote_updated' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
            'vote_user_id' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
        ],
        'indexes' => [
            'vote_id' => [
                'primary' => true,
                'fields' => [
                    'vote_id' => ['sorting' => 'ascending'],
                ],
            ],
            'vote_bundle_entity_field_user_name_ref' => [
                'fields' => [
                    'vote_bundle_name' => [
                        'sorting' => 'ascending',
                    ],
                    'vote_entity_id' => [
                        'sorting' => 'ascending',
                    ],
                    'vote_field_name' => [
                        'sorting' => 'ascending',
                    ],
                    'vote_user_id' => [
                        'sorting' => 'ascending',
                    ],
                    'vote_name' => [
                        'sorting' => 'ascending',
                    ],
                    'vote_reference_id' => [
                        'sorting' => 'ascending',
                    ],
                    'vote_hash' => [
                        'sorting' => 'ascending',
                    ],
                ],
            ],
            'vote_user_id' => ['fields' => ['vote_user_id' => []]],
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