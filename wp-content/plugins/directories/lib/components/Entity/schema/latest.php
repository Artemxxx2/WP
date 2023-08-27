<?php
use SabaiApps\Directories\Application;

$tables = [
    'entity_bundle' => [
        'fields' => [
            'bundle_name' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 40,
                'default' => '',
            ],
            'bundle_type' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 40,
                'default' => '',
            ],
            'bundle_component' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 40,
                'default' => '',
            ],
            'bundle_info' => [
                'type' => Application::COLUMN_TEXT,
                'notnull' => true,
            ],
            'bundle_entitytype_name' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 40,
                'default' => '',
            ],
            'bundle_group' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 40,
                'default' => '',
            ],
            'bundle_created' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
            'bundle_updated' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
        ],
        'indexes' => [
            'bundle_name' => [
                'primary' => true,
                'fields' => [
                    'bundle_name' => ['sorting' => 'ascending'],
                ],
            ],
            'bundle_component' => [
                'fields' => [
                    'bundle_component' => [
                    ],
                ],
            ],
            'bundle_group' => [
                'fields' => [
                    'bundle_group' => [
                    ],
                ],
            ],
            'bundle_entitytype_name' => [
                'fields' => [
                    'bundle_entitytype_name' => [
                        'sorting' => 'ascending',
                    ],
                ],
            ],
            'bundle_component_group_type' => [
                'unique' => true,
                'fields' => [
                    'bundle_component' => [
                        'sorting' => 'ascending',
                    ],
                    'bundle_group' => [
                        'sorting' => 'ascending',
                    ],
                    'bundle_type' => [
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
    'entity_fieldconfig' => [
        'fields' => [
            'fieldconfig_name' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 50,
                'default' => '',
            ],
            'fieldconfig_type' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 50,
                'default' => '',
            ],
            'fieldconfig_system' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 2,
                'default' => 0,
            ],
            'fieldconfig_settings' => [
                'type' => Application::COLUMN_TEXT,
                'notnull' => true,
            ],
            'fieldconfig_property' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 50,
                'default' => '',
            ],
            'fieldconfig_schema' => [
                'type' => Application::COLUMN_TEXT,
                'notnull' => true,
            ],
            'fieldconfig_schema_type' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 50,
                'default' => '',
            ],
            'fieldconfig_entitytype_name' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 40,
                'default' => '',
            ],
            'fieldconfig_bundle_type' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 40,
                'default' => '',
            ],
            'fieldconfig_created' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
            'fieldconfig_updated' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
        ],
        'indexes' => [
            'fieldconfig_name' => [
                'primary' => true,
                'fields' => [
                    'fieldconfig_name' => ['sorting' => 'ascending'],
                ],
            ],
            'fieldconfig_system' => [
                'fields' => [
                    'fieldconfig_system' => [
                    ],
                ],
            ],
            'fieldconfig_entitytype_name' => [
                'fields' => [
                    'fieldconfig_entitytype_name' => [
                        'sorting' => 'ascending',
                    ],
                ],
            ],
            'fieldconfig_bundle_type' => [
                'fields' => [
                    'fieldconfig_bundle_type' => [
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
    'entity_field' => [
        'fields' => [
            'field_data' => [
                'type' => Application::COLUMN_TEXT,
                'notnull' => true,
            ],
            'field_id' => [
                'autoincrement' => true,
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
            'field_created' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
            'field_updated' => [
                'type' => Application::COLUMN_INTEGER,
                'unsigned' => true,
                'notnull' => true,
                'length' => 10,
                'default' => 0,
            ],
            'field_bundle_name' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 40,
                'default' => '',
            ],
            'field_fieldconfig_name' => [
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 50,
                'default' => '',
            ],
        ],
        'indexes' => [
            'field_id' => [
                'primary' => true,
                'fields' => [
                    'field_id' => ['sorting' => 'ascending'],
                ],
            ],
            'field_bundle_name' => ['fields' => ['field_bundle_name' => []]],
            'field_fieldconfig_name' => ['fields' => ['field_fieldconfig_name' => []]],
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