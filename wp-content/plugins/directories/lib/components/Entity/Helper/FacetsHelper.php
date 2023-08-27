<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;

class FacetsHelper
{
    public function help(Application $application, Entity\Model\Field $field, Entity\Type\FieldQuery $fieldQuery = null, array $options = [])
    {
        $options += [
            'facet_type' => 'default',
            'column' => 'value',
            'filters' => [],
            'cache' => true,
        ];

        if (!$options['cache']
            || (!$facets = $application->getPlatform()->getCache($cache_id = $this->_getCacheId($application, $field, $fieldQuery, $options), 'entity_facets'))
        ) {
            if (!$bundle = $application->Entity_Bundle($field->bundle_name)) return;

            if ($property = $field->isPropertyField()) {
                // Property field
                $entity_type_info = $application->Entity_Types_impl($bundle->entitytype_name)->entityTypeInfo();
                if (!isset($entity_type_info['properties'][$property])) return;

                if (!isset($entity_type_info['properties'][$property]['field_name'])) {
                    // Query entity property table
                    $table = $entity_type_info['table_name'];
                    $id_column = $entity_type_info['properties']['id']['column'];
                    $value_column = $entity_type_info['properties'][$property]['column'];
                    $value_column_type = $entity_type_info['properties'][$property]['column_type'];
                    $where = [];
                    foreach (['filters', 'filters_not'] as $option_key) {
                        if (!empty($options[$option_key])) {
                            foreach ($options[$option_key] as $filter_column => $filter_value) {
                                $column_type = null;
                                if (isset($entity_type_info['properties'][$filter_column])) {
                                    $column_type = $entity_type_info['properties'][$filter_column]['column_type'];
                                    $filter_column = $entity_type_info['properties'][$filter_column]['column'];
                                } else {
                                    $filter_column = sprintf($filter_column, $value_column);
                                }
                                $where[] = $this->_getWhere($application, $filter_column, $filter_value, $column_type, $option_key === 'filters_not');
                            }
                        }
                    }
                } else {
                    $field_name = $entity_type_info['properties'][$property]['field_name'];
                    if (!isset($options['column'])) {
                        $options['column'] = $entity_type_info['properties'][$property]['column'];
                    }
                }
            } else {
                // Custom field
                $field_name = $field->getFieldName();
            }

            // Query entity field table
            if (isset($field_name)) {
                if (!$schema_type = $application->Entity_Field_schemaType($field_name)) return;

                $table = $application->getDB()->getResourcePrefix() . 'entity_field_' . $schema_type;
                $id_column = 'entity_id';
                $value_column = isset($options['column']) ? $options['column'] : 'entity_id';
                $column_types = $application->Entity_Field_columnType($schema_type);
                $value_column_type = isset($column_types[$value_column]) ? $column_types[$value_column] : null;
                $where = [
                    'entity_type = ' . $application->getDB()->escapeString($bundle->entitytype_name),
                    'field_name = ' .  $application->getDB()->escapeString($field_name),
                ];
                foreach (['filters', 'filters_not'] as $option_key) {
                    if (!empty($options[$option_key])) {
                        foreach ($options[$option_key] as $filter_column => $filter_value) {
                            $column_type = null;
                            if (isset($column_types[$filter_column])) {
                                $column_type = $column_types[$filter_column];
                            }
                            $where[] = $this->_getWhere($application, $filter_column, $filter_value, $column_type, $option_key === 'filters_not');
                        }
                    }
                }
            }

            // Filter by already fetched entities
            if (isset($fieldQuery)) {
                $storage = $application->Entity_Storage();
                if (!$bundle = $application->Entity_Bundle($field->bundle_name)) return;

                $lang = null;
                if ($application->getPlatform()->isTranslatable($bundle->entitytype_name, $bundle->name)) {
                    $lang = $application->getPlatform()->getCurrentLanguage();
                }
                $saved_ids = $storage->getSavedEntityIds($bundle->entitytype_name, $fieldQuery, $lang);
                if (!empty($saved_ids)) {
                    $where[] = $id_column . ' IN (' . implode(',', $saved_ids) . ')';
                } else {
                    // No items to show, so return empty facet counts
                    return;
                }
            }

            // Count
            switch ($options['facet_type']) {
                case 'first_letter':
                    $facets = $this->_countFirstLetter($application, $options, $table, $id_column, $value_column, $where);
                    break;
                case 'range':
                    $facets = $this->_countRange($application, $options, $table, $id_column, $value_column, $value_column_type, $where);
                    break;
                default:
                    $facets = $this->_count($application, $table, $id_column, $value_column, $where);
            }

            if (empty($facets)) return;

            // Cache
            if ($options['cache']) {
                $cache_lifetime = is_numeric($options['cache']) ? $options['cache'] : 3600;
                $application->getPlatform()->setCache($facets, $cache_id, $cache_lifetime, 'entity_facets');
            }
        }

        return $facets;
    }

    protected function _escapeColumnValue(Application $application, $value, $columnType = null)
    {
        switch ($columnType) {
            case Application::COLUMN_INTEGER:
                return intval($value);
            case Application::COLUMN_DECIMAL:
                return str_replace(',', '.', floatval($value));
            case Application::COLUMN_BOOLEAN:
                return $application->getDB()->escapeBool($value);
            default:
                return $application->getDB()->escapeString($value);
        }
    }

    protected function _getWhere(Application $application, $column, $value, $columnType, $not = false)
    {
        if (is_array($value)) {
            foreach (array_keys($value) as $i) {
                $value[$i] = $this->_escapeColumnValue($application, $value[$i], $columnType);
            }
            $operator = $not ? ' NOT IN ' : ' IN ';
            return $column . $operator . '(' . implode(',', $value) . ')';
        }
        $operator = $not ? ' != ' : ' = ';
        return $column . $operator . $this->_escapeColumnValue($application, $value, $columnType);
    }

    protected function _getCacheId(Application $application, Entity\Model\Field $field, Entity\Type\FieldQuery $fieldQuery = null, array $options = [])
    {
        return 'drts-entity-facets-' . $field->bundle_name . $field->getFieldName()
            . md5(serialize([$fieldQuery, $options]))
            . $application->getPlatform()->getCurrentLanguage();
    }

    protected function _count(Application $application, $table, $idColumn, $valueColumn, array $where)
    {
        if (empty($where)) return;

        $sql = sprintf(
            'SELECT %3$s AS _val, COUNT(DISTINCT %2$s) AS _cnt FROM %1$s WHERE %4$s GROUP BY _val',
            $table,
            $idColumn,
            $valueColumn,
            implode(' AND ' , $where)
        );
        $facets = [];
        foreach ($application->getDB()->query($sql) as $row) {
            $facets[$row['_val']] = $row['_cnt'];
        }

        return $facets;
    }

    protected function _countFirstLetter(Application $application, array $options, $table, $idColumn, $valueColumn, array $where)
    {
        if (empty($options['letters'])) return;

        $valueColumn = sprintf('LOWER(SUBSTRING(%1$s, 1, 1))', $valueColumn);
        $where[] = $valueColumn . ' IN (' . implode(',', array_map([$application->getDB(), 'escapeString'], $options['letters'])) . ')';

        return $this->_count($application, $table, $idColumn, $valueColumn, $where);
    }

    protected function _countRange(Application $application, array $options, $table, $idColumn, $valueColumn, $valueColumnType, array $where)
    {
        if (empty($options['ranges'])) {
            return;
        }
        $facets = $keys = $sums = $cases = [];
        $i = 0;
        foreach ($options['ranges'] as $key => $range) {
            $keys[] = $key;
            $alias = 'range' . $i;
            $sums[] = 'SUM(' . $alias . ')';
            $has_min = strlen($range['min']) && is_numeric($range['min']);
            $has_max = strlen($range['max']) && is_numeric($range['max']);
            if ($has_min && $has_max) {
                $cases[] = sprintf(
                    'CASE WHEN %s BETWEEN %s AND %s THEN 1 ELSE 0 END AS %s',
                    $valueColumn,
                    $this->_escapeColumnValue($application, $range['min'], $valueColumnType),
                    $this->_escapeColumnValue($application, $range['max'], $valueColumnType),
                    $alias
                );
            } elseif ($has_min) {
                $cases[] = sprintf(
                    'CASE WHEN %s >= %s THEN 1 ELSE 0 END AS %s',
                    $valueColumn,
                    $this->_escapeColumnValue($application, $range['min'], $valueColumnType),
                    $alias
                );
            } elseif ($has_max) {
                $cases[] = sprintf(
                    'CASE WHEN %s <= %s THEN 1 ELSE 0 END AS %s',
                    $valueColumn,
                    $this->_escapeColumnValue($application, $range['max'], $valueColumnType),
                    $alias
                );
            }
            ++$i;
        }
        $sql = sprintf(
            'SELECT %s FROM (SELECT %s FROM %s WHERE %s) t',
            implode(', ', $sums),
            implode(', ', $cases),
            $table,
            implode(' AND ' , $where)
        );
        $row = $application->getDB()->query($sql)->fetchRow();
        foreach (array_keys($row) as $key) {
            $facets[$keys[$key]] = $row[$key];
        }

        return $facets;
    }

    public function clearCache(Application $application)
    {
        $application->getPlatform()->clearCache('entity_facets');
    }
}
