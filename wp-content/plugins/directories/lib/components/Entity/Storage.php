<?php
namespace SabaiApps\Directories\Component\Entity;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Field;

class Storage
{
    private static $_instance;
    protected $_application, $_parsers = [], $_queries = [], $_fieldValueCountCacheLifetime;
    
    private function __construct(Application $application)
    {
        $this->_application = $application;
    }
    
    public static function getInstance(Application $application)
    {
        if (!isset(static::$_instance)) {
            static::$_instance = new static($application);
        }
        return static::$_instance;
    }

    public function saveValues(Type\IEntity $entity, array $fieldValues)
    {
        $db = $this->_application->getDB();
        $db->begin();
        $entity_type_escaped = $db->escapeString($entity->getType());
        $bundle_name_escaped = $db->escapeString($this->_application->Entity_Bundle($entity)->name);
        foreach ($fieldValues as $field_name => $field_values) {
            if (!$schema_type = $this->getFieldSchemaType($field_name)) continue;
            
            $column_types = $this->getFieldColumnType($schema_type);
            
            // Skip if no schema defined for this field
            if (empty($column_types)) continue;
            
            $field_name_escaped = $db->escapeString($field_name);

            // Delete current values of the entity
            try {
                $db->exec(sprintf(
                    'DELETE FROM %sentity_field_%s WHERE entity_type = %s AND entity_id = %d AND field_name = %s',
                    $db->getResourcePrefix(),
                    $schema_type,
                    $entity_type_escaped,
                    $entity->getId(),
                    $field_name_escaped
                ));
            } catch (\Exception $e) {
                $db->rollback();
                throw $e;
            }

            if (!is_array($field_values)) {
                $this->_application->logWarning('Invalid field values for field (name: ' . $field_name . ') saving entity (type: ' . $entity->getType() . ').');
                continue;
            }

            // Insert values
            foreach ($field_values as $weight => $field_value) {
                if (!is_array($field_value)) continue;
                
                $values = [];
                foreach (array_intersect_key($field_value, $column_types) as $column => $value) {
                    $values[$column] = $this->escapeFieldValue($value, $column_types[$column]);
                }
                try {
                    $sql = sprintf(
                        'INSERT INTO %sentity_field_%s (entity_type, bundle_name, entity_id, field_name, weight%s) VALUES (%s, %s, %d, %s, %d%s)',
                        $db->getResourcePrefix(),
                        $schema_type,
                        empty($values) ? '' : ', ' . implode(', ', array_keys($values)),
                        $entity_type_escaped,
                        $bundle_name_escaped,
                        $entity->getId(),
                        $field_name_escaped,
                        $weight,
                        empty($values) ? '' : ', ' . implode(', ', $values)
                    );
                    $db->exec($sql);
                } catch (\Exception $e) {
                    $db->rollback();
                    throw $e;
                }
            }
        }
        $db->commit();
    }

    public function fetchValues($entityType, array $entityIds, array $fields)
    {
        $values = [];
        $db = $this->_application->getDB();
        $entity_type_escaped = $db->escapeString($entityType);
        $entity_ids_escaped = implode(',', array_map('intval', $entityIds));
        foreach ($fields as $field_name) {
            if (!$schema_type = $this->getFieldSchemaType($field_name)) continue;
                    
            $column_types = $this->getFieldColumnType($schema_type);
            
            // Skip if no schema defined for this field
            if (empty($column_types)) continue;

            try {
                $rs = $db->query(sprintf(
                    'SELECT entity_id, %s FROM %sentity_field_%s WHERE entity_type = %s AND entity_id IN (%s) AND field_name = %s ORDER BY weight ASC',
                    implode(', ', array_keys($column_types)),
                    $db->getResourcePrefix(),
                    $schema_type,
                    $entity_type_escaped,
                    $entity_ids_escaped,
                    $db->escapeString($field_name)
                ));
            } catch (\Exception $e) {
                $this->_application->logError($e);
                continue;
            }
            foreach ($rs as $row) {
                $entity_id = $row['entity_id'];
                unset($row['entity_id']);
                foreach ($column_types as $column => $column_type) {
                    switch ($column_type) {
                        case Application::COLUMN_INTEGER:
                            $row[$column] = intval($row[$column]);
                            break;
                        case Application::COLUMN_DECIMAL:
                            $row[$column] = str_replace(',', '.', floatval($row[$column]));
                            break;
                        case Application::COLUMN_BOOLEAN:
                            $row[$column] = (bool)$row[$column];
                            break;
                    }
                }
                $values[$entity_id][$field_name][] = $row;
            }
        }

        return $values;
    }

    public function purgeValues($entityType, array $entityIds, array $fields)
    {
        $db = $this->_application->getDB();
        $db->begin();
        $entity_type_escaped = $db->escapeString($entityType);
        $entity_ids_escaped = implode(',', array_map('intval', $entityIds));
        foreach ($fields as $field_name) {
            if (!$schema_type = $this->getFieldSchemaType($field_name)) continue;
                    
            $column_types = $this->getFieldColumnType($schema_type);
            
            // Skip if no schema defined for this field
            if (empty($column_types)) continue;

            // Delete all values of the entity
            try {
                $db->exec(sprintf(
                    'DELETE FROM %sentity_field_%s WHERE entity_type = %s AND entity_id IN (%s) AND field_name = %s',
                    $db->getResourcePrefix(),
                    $schema_type,
                    $entity_type_escaped,
                    $entity_ids_escaped,
                    $db->escapeString($field_name)
                ));
            } catch (\Exception $e) {
                $db->rollback();
                $this->_application->logError($e);
            }
        }
        $db->commit();
    }
    
    public function purgeValuesByBundle(array $bundleNames, array $fields)
    {
        $db = $this->_application->getDB();
        $db->begin();
        $bundle_names_escaped = implode(',', array_map(array($db, 'escapeString'), $bundleNames));
        foreach ($fields as $field_name) {
            if (!$schema_type = $this->getFieldSchemaType($field_name)) continue;
                    
            $column_types = $this->getFieldColumnType($schema_type);
            
            // Skip if no schema defined for this field
            if (empty($column_types)) continue;

            // Delete all values of the entity
            try {
                $db->exec(sprintf(
                    'DELETE FROM %sentity_field_%s WHERE bundle_name IN (%s) AND field_name = %s',
                    $db->getResourcePrefix(),
                    $schema_type,
                    $bundle_names_escaped,
                    $db->escapeString($field_name)
                ));
            } catch (\Exception $e) {
                $db->rollback();
                $this->_application->logError($e);
            }
        }
        $db->commit();
    }

    public function create(array $fields)
    {
        $this->_application->getPlatform()->deleteCache('entity_storage_field_schema');
        if ($schema = $this->_getDatabaseSchema($fields)) {
            $this->_application->getPlatform()->updateDatabase($schema);
        }
    }
    
    public function update(array $fields)
    {
        $this->_application->getPlatform()->deleteCache('entity_storage_field_schema');
        $this->_application->getPlatform()->updateDatabase($this->_getDatabaseSchema($fields), $this->_getDatabaseSchema($fields, true));
    }
    
    public function delete(array $fields, $force = false)
    {
        $this->_application->getPlatform()->deleteCache('entity_storage_field_schema');
        if (!$force) {
            $field_schema_types = $this->getFieldSchemaType();
        
            foreach ($fields as $field_name => $field) {
                if ($field->schema_type
                    && in_array($field->schema_type, $field_schema_types)
                ) {
                    // Field(s) with this field type still exist, do not delete
                    unset($fields[$field_name]);
                }
            }
        }
        
        if (!$schema = $this->_getDatabaseSchema($fields)) return;

        try {
            $this->_application->getPlatform()->updateDatabase(null, $schema);
        } catch (\Exception $e) {
            $this->_application->logError($e);
        }
    }

    public function queryCount($entityType, Field\Query $fieldQuery, $lang = null)
    {
        $parsed = $this->parseQuery($entityType, $fieldQuery, null, $lang);
        if ($parsed['group']) {
            $sql = sprintf(
                'SELECT %6$s, COUNT(%1$s) AS cnt FROM %2$s %3$s %4$s WHERE %5$s GROUP BY %6$s',
                $parsed['table_id_column'],
                $parsed['table_name'],
                $parsed['table_joins'],
                $parsed['count_joins'],
                $parsed['criteria'],
                $parsed['group']
            );
            $rs = $this->_application->getDB()->query($sql);
            $ret = [];
            if (strpos($parsed['group'], ',')) { // group by multiple fields?
                foreach ($rs as $row) {
                    $count = intval(array_pop($row));
                    eval('$ret["' . implode('"]["', $row) . '"] = $count;');  
                }
            } else {
                $it = $rs->getIterator();
                $it->rewind();
                while ($it->valid()) {
                    $row = $it->row();
                    $ret[$row[0]] = (int)$row[1];
                    $it->next();
                }
            }

            return $ret;
        }
        
        $sql = sprintf(
            'SELECT COUNT(%s) FROM %s %s %s WHERE %s',
            'DISTINCT(' . $parsed['table_id_column'] . ')',
            $parsed['table_name'],
            $parsed['table_joins'],
            $parsed['count_joins'],
            $parsed['criteria']
        );

        return (int)$this->_application->getDB()->query($sql)->fetchSingle();
    }

    /**
     * Fetch entity IDs by criteria
     * @param Field\Query $fieldQuery
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function query($entityType, Field\Query $fieldQuery, $limit = 20, $offset = 0, $lang = null, $saveEntityIds = false, $hash = null)
    {
        if (!isset($hash)) $hash = md5(serialize($fieldQuery) . $lang);
        $parsed = $this->parseQuery($entityType, $fieldQuery, $hash, $lang);
        $sql = sprintf(
            'SELECT %s %s AS id%s FROM %s %s %s WHERE %s%s%s',
            $parsed['group_query'] ? '' : 'DISTINCT',
            $parsed['table_id_column'],
            $parsed['extra_fields'] ? ', ' . implode($parsed['extra_fields_separator'], $parsed['extra_fields']) : '',
            $parsed['table_name'],
            $parsed['table_joins'],
            $parsed['joins'],
            $parsed['criteria'],
            $parsed['group_query'] ? ' GROUP BY ' . $parsed['table_id_column'] : '',
            $parsed['sorts'] ? ' ORDER BY ' . implode(', ', $parsed['sorts']) : ''
        );
        if ($parsed['random_seed']) {
            $this->_application->getDB()->seedRandom($parsed['random_seed']);
        }
        $rs = $this->_application->getDB()->query($sql, $limit, $offset);
        $ret = [];
        if ($parsed['extra_fields']
            && $parsed['extra_fields_to_query']
        ) {
            foreach ($rs as $row) {
                foreach ($parsed['extra_fields_to_query'] as $column => $field_name) {
                    $weight_field_name = $column . '_weight';
                    if (strpos($row[$weight_field_name], ',')) {
                        if ($weight_field_values = explode(',', $row[$weight_field_name])) {
                            if (strpos($row[$column], ',')) {
                                if ($field_values = explode(',', $row[$column])) {
                                    foreach ($weight_field_values as $weight_key => $weight) {
                                        if (!isset($field_values[$weight_key])) break;

                                        $ret[$row['id']][$field_name][$weight][$column] = $field_values[$weight_key];
                                    }
                                }
                            } else {
                                foreach ($weight_field_values as $weight) {
                                    $ret[$row['id']][$field_name][$weight][$column] = $row[$column];
                                }
                            }
                        }
                    } else {
                        $ret[$row['id']][$field_name][$row[$weight_field_name]][$column] = $row[$column];
                    }
                }
            }
        } else {
            foreach ($rs as $row) {
                $ret[$row['id']] = $row['id'];
            }
        }
        
        if ($saveEntityIds) $this->_queries[$hash]['ids'] = array_keys($ret);

        return $ret;
    }

    public function getSavedEntityIds($entityType, Field\Query $fieldQuery, $hash = null, $lang = null)
    {
        if (!isset($hash)) $hash = md5(serialize($fieldQuery) . $lang);
        if (!isset($this->_queries[$hash]['ids'])) {
            $_fieldQuery = clone $fieldQuery;
            $this->query($entityType, $_fieldQuery->clearExtraFields()->clearGroups()->clearSorts(), 0, 0, null, true, $hash);
        }
        return $this->_queries[$hash]['ids'];
    }

    public function parseQuery($entityType, Field\Query $fieldQuery, $hash = null, $lang = null)
    {
        if (!isset($hash)) $hash = md5(serialize($fieldQuery) . $lang) ;
        if (!isset($this->_queries[$hash])) {
            if (!isset($this->_parsers[$entityType])) {
                $this->_parsers[$entityType] = new QueryParser(
                    $this,
                    $entityType,
                    $this->_application->Entity_Types_impl($entityType)->entityTypeInfo()
                );
            }
            $this->_queries[$hash] = $this->_application->Filter(
                'entity_storage_query',
                $this->_parsers[$entityType]->parse($fieldQuery),
                [$entityType, $fieldQuery, $lang]
            );
        }
        return $this->_queries[$hash];
    }

    private function _getDatabaseSchema(array $fields, $old = false)
    {
        $default_columns = array(
            'entity_type' => array(
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'unsigned' => true,
                'length' => 40,
                'was' => 'entity_type',
                'default' => '',
            ),
            'bundle_name' => array(
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 40,
                'was' => 'bundle_name',
                'default' => '',
            ),
            'entity_id' => array(
                'type' => Application::COLUMN_INTEGER,
                'notnull' => true,
                'unsigned' => true,
                'was' => 'entity_id',
                'default' => 0,
            ),
            'field_name' => array(
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 150,
                'was' => 'field_name',
                'default' => '',
            ),
            'weight' => array(
                'type' => Application::COLUMN_INTEGER,
                'notnull' => true,
                'unsigned' => true,
                'was' => 'weight',
                'default' => 0,
                'length' => 10,
            ),
        );
        $default_indexes = array(
            'primary' => array(
                'fields' => array(
                    'entity_type' => array('sorting' => 'ascending'),
                    'entity_id' => array('sorting' => 'ascending'),
                    'field_name' => array('sorting' => 'ascending'),
                    'weight' => array('sorting' => 'ascending'),
                ),
                'primary' => true,
                'was' => 'primary',
            ),
            'bundle_name' => array(
                'fields' => array('bundle_name' => array('sorting' => 'ascending')),
                'was' => 'bundle_name',
            ),
            'entity_id' => array(
                'fields' => array('entity_id' => array('sorting' => 'ascending')),
                'was' => 'entity_id',
            ),
            'weight' => array(
                'fields' => array('weight' => array('sorting' => 'ascending')),
                'was' => 'weight',
            ),
        );
        $tables = [];
        foreach ($fields as $field) {
            if ($old) {
                if (!isset($field->oldSchema)) continue;
                
                $field_schema = $field->oldSchema;
            } else {
                $field_schema = $field->schema;
            }
            if (empty($field_schema['columns'])) continue;
            
            $columns = $default_columns + $field_schema['columns'];
            $indexes = $default_indexes + (array)@$field_schema['indexes'];
            $tables['entity_field_' . $field->schema_type] = array(
                'comment' => sprintf('Field data table for %s', $field->type),
                'fields' => $columns,
                'indexes' => $indexes,
                'initialization' => [],
                'constraints' => [],
            );
        }

        if (!empty($tables)) {
            return array(
                'charset' => '',
                'description' => '',
                'tables' => $tables,
            );
        }
    }

    public function escapeFieldValue($value, $dataType = null)
    {
        switch ($dataType) {
            case Application::COLUMN_INTEGER:
                return intval($value);
            case Application::COLUMN_DECIMAL:
                return str_replace(',', '.', floatval($value));
            case Application::COLUMN_BOOLEAN:
                return $this->_application->getDB()->escapeBool($value);
            case Application::COLUMN_VARCHAR:
            case Application::COLUMN_TEXT:
                return $this->_application->getDB()->escapeString($value);
            default:
                return $value;
        }
    }
    
    public function getDB()
    {
        return $this->_application->getDB();
    }
    
    public function getFieldSchemaType($fieldName = null)
    {
        return $this->_application->Entity_Field_schemaType($fieldName);
    }
    
    public function getFieldColumnType($schemaType, $column = null)
    {
        return $this->_application->Entity_Field_columnType($schemaType, $column);
    }
}

use SabaiApps\Framework\Criteria;

class QueryParser implements Criteria\IVisitor
{
    protected $_storage, $_entityType, $_tableName, $_tableColumns, $_tableIdColumn, $_tableJoins, $_tables;
    
    public function __construct(Storage $storage, $entityType, array $entityTypeInfo)
    {
        $this->_storage = $storage;
        $this->_entityType = $entityType;
        $this->_tableName = $entityTypeInfo['table_name'];
        $this->_tableColumns = $entityTypeInfo['properties'];
        $this->_tableIdColumn = $this->_tableName . '.' . $entityTypeInfo['properties']['id']['column'];
        $this->_tableJoins = empty($entityTypeInfo['table_joins']) ? [] : $entityTypeInfo['table_joins'];
    }
    
    public function parse(Field\Query $fieldQuery)
    {      
        $this->_tables = $non_count_tables = [];
        
        $table_id_column = $fieldQuery->getTableIdColumn($this->_tableIdColumn);
        $table_joins = $fieldQuery->getTableJoins() ? $this->_tableJoins + $fieldQuery->getTableJoins() : $this->_tableJoins;
        $table_prefix = $this->_storage->getDB()->getResourcePrefix();
        if (!empty($table_joins)) {
            $_table_joins = [];
            foreach ($table_joins as $table_name => $table) {
                $_table_joins[$table['alias']] = sprintf(
                    'LEFT JOIN %1$s %2$s ON %2$s.%3$s',
                    sprintf($table_name, $table_prefix),
                    $table['alias'],
                    sprintf($table['on'], $table['alias'], $this->_tableName, $table_id_column)
                );
            }
            $table_joins = implode(' ', $_table_joins);
        } else {
            $table_joins = '';
        }
        $group_query = false;
        
        // Criteria
        $criteria = [];
        $fieldQuery->getCriteria()->acceptVisitor($this, $criteria);
        $criteria = implode(' ', $criteria);
        $criteria = strtr($criteria, ['( )' => '1=1']);
        
        // Extra fields
        $extra_fields_to_query = [];
        if ($extra_fields = $fieldQuery->getExtraFields()) {
            $extra_fields_concat = [];
            foreach ($extra_fields as $column => $extra_field) {
                if (!$table = $this->_storage->getFieldSchemaType($extra_field['field_name'])) {
                    throw new \RuntimeException('Invalid field specified for extra field: ' . $extra_field['field_name']);
                }
                
                $table_alias = $extra_field['field_name'];
                if (!isset($this->_tables[$table_alias])) {
                    $this->_tables[$table_alias] = array(
                        'name' => 'entity_field_' . $table,
                        'prefix' => true,
                        'field_name' => $extra_field['field_name'],
                    );
                    $non_count_tables[$table_alias] = $table;
                }
                $extra_fields[$column] = isset($extra_field['sql']) ? $extra_field['sql'] : $table_alias . '.' . $column;
                if (!empty($extra_field['query'])) {
                    $weight_column = $column . '_weight';
                    $weight_column_sql = $table_alias . '.weight';
                    if (!empty($extra_field['concat'])) {
                        // Concat extra field values
                        $extra_fields_concat[$column] = $this->_storage->getDB()->getGroupConcatFunc($extra_fields[$column], ',', false);
                        $group_query = true;
                        // Concat weight values for extra field values
                        $weight_column_sql = $this->_storage->getDB()->getGroupConcatFunc($weight_column_sql, ',');
                    }
                    $extra_fields[$weight_column] = $weight_column_sql;
                    $extra_fields_to_query[$column] = $extra_field['field_name'];
                }
            }
        } else {
            $extra_fields = [];
        }

        // Sorts
        if ($sorts = $fieldQuery->getSorts()) {
            $_sorts = [];
            foreach ($sorts as $sort) {
                $sort_index = isset($sort['index']) ? $sort['index'] : count($_sorts);
                if (isset($sort['field_name'])) {
                    if ($this->_isProperty($sort['field_name'])) {
                        if ($_column = $this->_getPropertyColumn($sort['field_name'])) {
                            $_sorts[$sort_index] = $_column . ' ' . $sort['order'];
                            if (!in_array($_column, $extra_fields)) {
                                $extra_fields['_' . $sort['field_name']] = $_column;
                            }
                        }
                    } elseif (!empty($sort['is_extra_field'])) {
                        $extra_field_key = $sort['field_name'];
                        if (isset($extra_fields_to_query[$sort['field_name']])) {
                            // Extra field needs to be queried, so do not override the SQL
                            $extra_field_key = '_' . $extra_field_key;
                        }
                        $_sorts[$sort_index] = $extra_field_key . ' ' . $sort['order'];
                        $extra_fields[$extra_field_key] = ($sort['order'] === 'DESC' ? 'MAX(' . $extra_fields[$sort['field_name']] . ')' : 'MIN(' . $extra_fields[$sort['field_name']] . ')');
                        $group_query = true;
                    } else {
                        if (!$schema_type = $this->_storage->getFieldSchemaType($sort['field_name'])) continue;
                        
                        $table_alias = isset($sort['table_alias']) ? $sort['table_alias'] : $sort['field_name'];
                        $table_column_alias = null;
                        if (!isset($this->_tables[$table_alias])) {
                            $this->_tables[$table_alias] = array(
                                'name' => 'entity_field_' . $schema_type,
                                'field_name' => $sort['field_name'],
                                'prefix' => true,
                                'field_extra_column' => isset($sort['field_extra_column']) ? $sort['field_extra_column'] : null,
                                'field_extra_column_value' => isset($sort['field_extra_column_value']) ? $sort['field_extra_column_value'] : null,
                            );
                            $non_count_tables[$table_alias] = $schema_type;
                        }
                        $table_column = $table_alias . '.' . $sort['column'];
                        $string_compare = false;
                        if (isset($sort['null_value'])) {
                            $null_value = $this->_storage->escapeFieldValue(
                                $sort['null_value'],
                                $this->_storage->getFieldColumnType($schema_type, $sort['column'])
                            );
                            $table_column = 'CASE WHEN ' . $table_column . ' IS NULL THEN ' . $null_value . ' ELSE ' . $table_column . ' END';
                        } elseif (!empty($sort['cases'])) {
                            $cases = '';
                            $column_type = $this->_storage->getFieldColumnType($schema_type, $sort['column']);
                            $i = $case_value_alt_max = 0;
                            foreach ($sort['cases'] as $case_value) {
                                if (is_array($case_value)) {
                                    $case_value_alt = (int)$case_value[1];
                                    $case_value  = $case_value[0];
                                    if ($case_value_alt > $case_value_alt_max) $case_value_alt_max = $case_value_alt;
                                } else {
                                    $case_value_alt = $case_value_alt_max = ++$i;
                                }
                                $case_value = $this->_storage->escapeFieldValue($case_value, $column_type);
                                $cases .= ' WHEN ' . $table_column . '=' . $case_value . ' THEN ' . $case_value_alt;
                            }
                            $table_column = 'CASE' . $cases . ' ELSE ' . ++$case_value_alt_max . ' END';
                        } elseif ($sort['order'] === 'EMPTY_LAST') {
                            $table_column = 'CASE WHEN ' . $table_column . ' IS NULL OR ' . $table_column . ' = 0 THEN 2 ELSE 1 END';
                            $sort['order'] = 'ASC';
                            $table_column_alias = '_' . $sort['field_name'] . '_' . $sort['column'] . '_empty_last';
                        } else {
                            $column_type = $this->_storage->getFieldColumnType($schema_type, $sort['column']);
                            if (in_array($column_type, [Application::COLUMN_TEXT, Application::COLUMN_VARCHAR])) {
                                $string_compare = true;
                            }
                        }
                        if (!isset($table_column_alias)) $table_column_alias = '_' . $sort['field_name'] . '_' . $sort['column'];
                        $_sorts[$sort_index] = $table_column_alias . ' ' . $sort['order'];
                        if ($string_compare) {
                            $extra_fields[$table_column_alias] = $table_column;
                        } else {
                            $extra_fields[$table_column_alias] = ($sort['order'] === 'DESC' ? 'MAX(' . $table_column . ')' : 'MIN(' . $table_column . ')');
                        }
                        $group_query = true;
                    }
                } else {
                    if (!empty($sort['is_random'])) {
                        $random_seed = $sort['random_seed'];
                        $_sorts[$sort_index] = $this->_storage->getDB()->getRandomFunc($random_seed);
                    } elseif (!empty($sort['is_id'])) {
                        $_sorts[$sort_index] = $table_id_column . ' ' . $sort['order'];
                    } elseif (!empty($sort['is_custom'])) {
                        if (is_callable($sort['is_custom'])
                            && ($custom_sort = call_user_func_array($sort['is_custom'], [$sort['order'], $this->_tableName, $table_id_column, &$this->_tables, &$extra_fields]))
                        ) {
                            $_sorts[$sort_index] = $custom_sort;
                        }
                    }
                }
            }
            ksort($_sorts);
            $sorts = $_sorts;
        } else {
            $sorts = null;
        }
           
        // Group
        if ($groups = $fieldQuery->getGroups()) {
            $_groups = [];
            foreach ($groups as $group) {
                if ($this->_isProperty($group['field_name'])) {
                    if ($_column = $this->_getPropertyColumn($group['field_name'])) {
                        $_groups[] = $_column;
                    }
                } elseif (empty($group['column'])) { // column is empty if extra field
                    if (isset($extra_fields[$group['field_name']])) {
                        $_groups[] = $extra_fields[$group['field_name']];
                    }
                } else {
                    if ($_group = $this->_getGroupByFieldClause(
                        $group['field_name'],
                        $group['column'],
                        isset($group['table_alias']) ? $group['table_alias'] : null
                    )) {
                        $_groups[] = $_group;
                    }
                }
            }
            $group = implode(', ', $_groups);
        } else {
            $group = '';
        }

        // Table joins
        if (!empty($this->_tables)) {
            $entity_type = $this->_storage->getDB()->escapeString($this->_entityType);
            foreach ($this->_tables as $table_alias => $table) {
                if (!is_array($table)) {
                    $_joins[$table_alias] = sprintf(
                        'LEFT JOIN %1$sentity_field_%2$s %3$s ON %3$s.entity_id = %4$s AND %3$s.entity_type = %5$s',
                        $table_prefix,
                        $table,
                        $table_alias,
                        $table_id_column,
                        $entity_type
                    );
                } else {
                    if (isset($table['on'])) {
                        $_joins[$table_alias] = sprintf(
                            '%5$s JOIN %1$s%2$s %3$s ON %3$s.%4$s',
                            empty($table['prefix']) ? '' : $table_prefix,
                            $table['name'],
                            $table_alias,
                            sprintf($table['on'], $table_alias, $this->_tableName, $table_id_column, $entity_type, $this->_tableIdColumn),
                            isset($table['join_type']) ? $table['join_type'] : 'LEFT'
                        );
                    } else {
                        if (!isset($table['format'])) {
                            $format = '%6$s JOIN %1$s%2$s %3$s ON %3$s.entity_id = %4$s AND %3$s.entity_type = %5$s';
                        } else {
                            $format = $table['format'];
                        }
                        if (isset($table['field_name'])) {
                            $format .= ' AND %3$s.field_name = ' . $this->_storage->getDB()->escapeString($table['field_name']);
                            if (isset($table['field_extra_column'])
                                && isset($table['field_extra_column_value'])
                            ) {
                                $format .= ' AND %3$s.' . $table['field_extra_column'] . ' = ' . $this->_storage->getDB()->escapeString($table['field_extra_column_value']);
                            }
                        }
                        $_joins[$table_alias] = sprintf(
                            $format,
                            empty($table['prefix']) ? '' : $table_prefix,
                            $table['name'],
                            $table_alias,
                            $table_id_column,
                            $entity_type,
                            isset($table['join_type']) ? $table['join_type'] : 'LEFT'
                        );
                    }
                }
            }
            if (!empty($non_count_tables)) {
                $joins = implode(' ', $_joins);
                // For the count query, remove table joins that are used for sorting purpose only
                $count_joins = implode(' ', array_diff_key($_joins, $non_count_tables));
            } else {
                $joins = $count_joins = implode(' ', $_joins);
            }
        } else {
            $joins = $count_joins = '';
        }

        // Add alias to extra fields
        if (!empty($extra_fields)) {
            if (!empty($extra_fields_concat)) {
                $extra_fields = $extra_fields_concat + $extra_fields;
            }
            foreach (array_keys($extra_fields) as $extra_field_key) {
                if ($extra_fields[$extra_field_key] !== $extra_field_key) {
                    $extra_fields[$extra_field_key] = $extra_fields[$extra_field_key] . ' AS ' . $extra_field_key;
                }
            }
        }
        
        return array(
            'table_name' => $this->_tableName,
            'table_id_column' => $table_id_column,
            'table_joins' => $table_joins,
            'criteria' => $criteria,
            'extra_fields' => $extra_fields,
            'extra_fields_separator' => ', ',
            'extra_fields_to_query' => $extra_fields_to_query,
            'sorts' => $sorts,
            'random_seed' => isset($random_seed) ? $random_seed : null,
            'group' => $group,
            'joins' => $joins,
            'count_joins' => $count_joins,
            'group_query' => $group_query,
        );
    }

    protected function _getGroupByFieldClause($fieldName, $column, $tableAlias = null)
    {
        if (!$schema_type = $this->_storage->getFieldSchemaType($fieldName)) return;

        $table_alias = isset($tableAlias) ? $tableAlias : $fieldName;
        if (!isset($this->_tables[$table_alias])) {
            $this->_tables[$table_alias] = $schema_type;
        }
        return $table_alias . '.' . $column;
    }

    public function visitCriteriaEmpty(Criteria\EmptyCriteria $criteria, &$criterions)
    {
        $criterions[] = '1=1';
    }

    public function visitCriteriaComposite(Criteria\CompositeCriteria $criteria, &$criterions)
    {
        if ($criteria->isEmpty()) {
            $criterions[] = '1=1';
            return;
        }
        $elements = $criteria->getElements();
        $conditions = $criteria->getConditions();
        $criterions[] = '(';
        $result = $condition_added = false;
        foreach (array_keys($elements) as $i) {
            if ($result !== false) {
                $criterions[] = $conditions[$i];
                $condition_added = true;
            }
            $result = $elements[$i]->acceptVisitor($this, $criterions);	  
        }
        if ($result === false
            && $condition_added
        ) {
            array_pop($criterions);
        }
        $criterions[] = ')';
    }

    public function visitCriteriaCompositeNot(Criteria\CompositeNotCriteria $criteria, &$criterions)
    {
        $criterions[] = 'NOT';
        $criterions[] = $this->visitCriteriaComposite($criteria, $criterions);
    }

    private function _visitCriteriaValue(Criteria\AbstractValueCriteria $criteria, &$criterions, $operator)
    {
        if (!$field = $this->_getField($criteria->getField())) return false;
        
        if (isset($field['tables'])) {
            foreach ($field['tables'] as $table_name => $table) {
                $this->_tables[$table['alias']] = $table + array(
                    'name' => $table_name,
                );
            }
        }
        $criterions[] = $field['column'];
        $criterions[] = $operator;
        $criterions[] = $this->_storage->escapeFieldValue($criteria->getValue(), $field['column_type']);
    }

    public function visitCriteriaIs(Criteria\IsCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaValue($criteria, $criterions, '=');
    }

    public function visitCriteriaIsNot(Criteria\IsNotCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaValue($criteria, $criterions, '!=');
    }

    public function visitCriteriaIsSmallerThan(Criteria\IsSmallerThanCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaValue($criteria, $criterions, '<');
    }

    public function visitCriteriaIsGreaterThan(Criteria\IsGreaterThanCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaValue($criteria, $criterions, '>');
    }

    public function visitCriteriaIsOrSmallerThan(Criteria\IsOrSmallerThanCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaValue($criteria, $criterions, '<=');
    }

    public function visitCriteriaIsOrGreaterThan(Criteria\IsOrGreaterThanCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaValue($criteria, $criterions, '>=');
    }
    
    protected function _visitCriteriaIsNull(Criteria\AbstractCriteria $criteria, &$criterions, $null = true)
    {
        if (!$field = $this->_getField($criteria->getField())) return false;
        
        if (isset($field['tables'])) {
            foreach ($field['tables'] as $table_name => $table) {
                $this->_tables[$table['alias']] = $table + array(
                    'name' => $table_name,
                );
            }
        }
        $criterions[] = isset($field['column']) ? $field['column'] : 'entity_id';
        $criterions[] = $null ? 'IS NULL' : 'IS NOT NULL';
    }

    public function visitCriteriaIsNull(Criteria\IsNullCriteria $criteria, &$criterions, $null = true)
    {
        return $this->_visitCriteriaIsNull($criteria, $criterions);
    }

    public function visitCriteriaIsNotNull(Criteria\IsNotNullCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaIsNull($criteria, $criterions, false);
    }

    private function _visitCriteriaArray(Criteria\AbstractArrayCriteria $criteria, &$criterions, $format)
    {
        $values = $criteria->getArray();
        if (empty($values)
            || (!$field = $this->_getField($criteria->getField()))
        ) return false;
        
        $data_type = $field['column_type'];
        foreach (array_keys($values) as $k) {
            $values[$k] = $this->_storage->escapeFieldValue($values[$k], $data_type);
        }
        if (isset($field['tables'])) {
            foreach ($field['tables'] as $table_name => $table) {
                $this->_tables[$table['alias']] = $table + array(
                    'name' => $table_name,
                );
            }
        }
        $criterions[] = sprintf($format, $field['column'], implode(',', $values));
    }

    public function visitCriteriaIn(Criteria\InCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaArray($criteria, $criterions, '%s IN (%s)');
    }

    public function visitCriteriaNotIn(Criteria\NotInCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaArray($criteria, $criterions, '%s NOT IN (%s)');
    }

    private function _visitCriteriaString(Criteria\AbstractValueCriteria $criteria, &$criterions, $format, $operator = 'LIKE')
    {
        if (!$field = $this->_getField($criteria->getField())) return false;
        
        if (isset($field['tables'])) {
            foreach ($field['tables'] as $table_name => $table) {
                $this->_tables[$table['alias']] = $table + array(
                    'name' => $table_name,
                );
            }
        }
        $criterions[] = $field['column'];
        $criterions[] = $operator;
        $criterions[] = $this->_storage->escapeFieldValue(sprintf($format, $criteria->getValue()),$field['column_type'] === Application::COLUMN_TEXT ? Application::COLUMN_TEXT : Application::COLUMN_VARCHAR);
    }

    public function visitCriteriaStartsWith(Criteria\StartsWithCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaString($criteria, $criterions, '%s%%');
    }

    public function visitCriteriaEndsWith(Criteria\EndsWithCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaString($criteria, $criterions, '%%%s');
    }

    public function visitCriteriaContains(Criteria\ContainsCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaString($criteria, $criterions, '%%%s%%');
    }
    
    public function visitCriteriaNotContains(Criteria\ContainsCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaString($criteria, $criterions, '%%%s%%', 'NOT LIKE');
    }

    private function _visitCriteriaField(Criteria\AbstractFieldCriteria $criteria, &$criterions, $operator)
    {
        if ((!$field = $this->_getField($criteria->getField()))
            || (!$field2 = $this->_getField($criteria->getField2()))
        ) return false;

        if (isset($field['tables'])) {
            foreach ($field['tables'] as $table_name => $table) {
                $this->_tables[$table['alias']] = $table + [
                    'name' => $table_name,
                ];
            }
        }
        if (isset($field2['tables'])) {
            foreach ($field2['tables'] as $table_name => $table) {
                $this->_tables[$table['alias']] = $table + [
                    'name' => $table_name,
                ];
            }
        }

        $criterions[] = $field['column'];
        $criterions[] = $operator;
        $criterions[] = $field2['column'];
    }

    public function visitCriteriaIsField(Criteria\IsFieldCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaField($criteria, $criterions, '=');
    }

    public function visitCriteriaIsNotField(Criteria\IsNotFieldCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaField($criteria, $criterions, '!=');
    }

    public function visitCriteriaIsSmallerThanField(Criteria\IsSmallerThanFieldCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaField($criteria, $criterions, '<');
    }

    public function visitCriteriaIsGreaterThanField(Criteria\IsGreaterThanFieldCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaField($criteria, $criterions, '>');
    }

    public function visitCriteriaIsOrSmallerThanField(Criteria\IsOrSmallerThanFieldCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaField($criteria, $criterions, '<=');
    }

    public function visitCriteriaIsOrGreaterThanField(Criteria\IsOrGreaterThanFieldCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaField($criteria, $criterions, '>=');
    }
    
    private function _getPropertyColumn($fieldName)
    {
        if (!isset($this->_tableColumns[$fieldName]['column'])) return;
        
        $column = $this->_tableColumns[$fieldName]['column'];
        if (strpos($column, '.')) return $column;
        
        $table_name = isset($this->_tableColumns[$fieldName]['field_name']) ? $this->_tableColumns[$fieldName]['field_name'] : $this->_tableName;
        return $table_name . '.' . $column;
    }
    
    protected function _isProperty($fieldName)
    {
        return isset($this->_tableColumns[$fieldName]);
    }
    
    protected function _getField(array $target)
    {
        // External table field
        if (!isset($target['field_name'])) {
            return isset($target['tables']) ? $target : null;
        }
        
        // Property field
        if (isset($this->_tableColumns[$target['field_name']])) {
            if (empty($this->_tableColumns[$target['field_name']])) return; // the entity type does not support this property
            
            $property = $this->_tableColumns[$target['field_name']];
            
            // Property field in an extra table, happens depending on the entity type
            if (isset($property['field_name'])) {
                if (!$schema_type = $this->_storage->getFieldSchemaType($property['field_name'])) return;
                
                $table_alias = isset($target['table_alias']) ? $target['table_alias'] : $property['field_name'];
                // Make sure a valid column is requested
                if (isset($target['column'])
                    && ($column_type = $this->_storage->getFieldColumnType($schema_type, $target['column']))
                ) {
                    $column = $target['column'];
                } else {
                    // Use default defined for the property
                    $column = $property['column'];
                    $column_type = $this->_storage->getFieldColumnType($schema_type, $column);
                }
                return [
                    'tables' => [
                        'entity_field_' . $schema_type => [
                            'alias' => $table_alias,
                            'on' => $target['on'],
                            'prefix' => true,
                            'field_name' => $property['field_name'],
                        ],
                    ],
                    'column' => $table_alias . '.' . $column,
                    'column_type' => $column_type,
                ];
            }

            if (!$column = $this->_getPropertyColumn($target['field_name'])) return;

            return [
                'column' => $column,
                'column_type' => $property['column_type'],
            ];
        }
        
        // Entity Field
        if (!$schema_type = $this->_storage->getFieldSchemaType($target['field_name'])) return;
        
        $table_alias = isset($target['table_alias']) ? $target['table_alias'] : $target['field_name'];
        if (!isset($target['column'])) {
            $target['column'] = 'entity_id';
            $column_type = Application::COLUMN_INTEGER;
        } else {
            $column_type = $this->_storage->getFieldColumnType($schema_type, $target['column']);
        }
        return array(
            'tables' => array(
                'entity_field_' . $schema_type => array(
                    'alias' => $table_alias,
                    'on' => $target['on'],
                    'prefix' => true,
                    'field_name' => $target['field_name'],
                ),
            ),
            'column' => $table_alias . '.' . $target['column'],
            'column_type' => $column_type,
        );
    }
}