<?php
namespace SabaiApps\Directories\Component\Entity\Type;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Framework\Paginator\CustomPaginator;

class Query
{
    protected $_application, $_entityType, $_fieldQuery, $_bundleName, $_randomSortSeedLifetime, $_data = [];
    
    public function __construct(Application $application, $entityType, FieldQuery $fieldQuery, $bundleName = null)
    {
        $this->_application = $application;
        $this->_entityType = $entityType;
        $this->_fieldQuery = $fieldQuery;
        if (isset($bundleName)) {
            $this->_bundleName = $bundleName;
            if (is_array($bundleName)) {
                $this->_fieldQuery->fieldIsIn('bundle_name', $bundleName);
            } else {
                $this->_fieldQuery->fieldIs('bundle_name', $bundleName);
            }
        }
    }
    
    public function getEntityType()
    {
        return $this->_entityType;
    }
    
    /**
     * @return FieldQuery
     */
    public function getFieldQuery()
    {
        return $this->_fieldQuery;
    }

    public function __set($name, $value)
    {
        $this->_data[$name] = $value;
    }

    public function &__get($name)
    {
        return $this->_data[$name];
    }

    public function __isset($name)
    {
        return isset($this->_data[$name]);
    }

    public function __call($name, $args)
    {
        call_user_func_array(array($this->_fieldQuery, $name), $args);
        
        return $this;
    }
    
    public function fetch($limit = 0, $offset = 0, $lang = null, $loadEntityFields = true, $saveEntityIds = false)
    {
        $entities = $this->_application->Entity_Storage()->query($this->_entityType, $this->_fieldQuery, $limit, $offset, $lang, $saveEntityIds);
        if (empty($entities)) return [];
        
        $_entities = $this->_application->Entity_Types_impl($this->_entityType)->entityTypeEntitiesByIds(array_keys($entities), $this->_bundleName, $lang);
        if ($loadEntityFields) {
            $force = false;
            $cache = true;
            if (is_array($loadEntityFields)) {
                if (isset($loadEntityFields['force'])) {
                    $force = !empty($loadEntityFields['force']);
                }
                if (isset($loadEntityFields['cache'])) {
                    $cache = !empty($loadEntityFields['cache']);
                }
            }
            $this->_application->Entity_Field_load($this->_entityType, $_entities, $force, $cache);
        }

        foreach (array_keys($entities) as $entity_id) {
            if (!isset($_entities[$entity_id])) {
                unset($entities[$entity_id]);
                continue;
            }
            if (is_array($entities[$entity_id])) {
                // Set extra fields queried as entity data
                foreach ($entities[$entity_id] as $field_name => $data) {
                    $field_value = $_entities[$entity_id]->getFieldValue($field_name);
                    $new_field_value = [];
                    foreach ($data as $weight => $_data) {
                        if (isset($field_value[$weight])) {
                            $new_field_value[] = $_data + $field_value[$weight];
                        }
                    }
                    $_entities[$entity_id]->setFieldValue($field_name, $new_field_value);
                }
            }
            $entities[$entity_id] = $_entities[$entity_id];
        }

        return array_intersect_key($entities, $_entities);
    }
    
    public function count($lang = null)
    {
        return $this->_application->Entity_Storage()->queryCount($this->_entityType, $this->_fieldQuery, $lang);
    }

    public function paginate($perpage = 20, $limit = 0, $lang = null, $loadEntityFields = true, $saveEntityIds = false)
    {
        $paginator = new CustomPaginator(
            array($this, 'count'),
            array($this, 'fetch'),
            $perpage,
            array($lang, $loadEntityFields, false),
            [],
            [],
            $limit
        );
        if ($saveEntityIds) {
            if ($paginator->count() > 1) {
                // Need to save entity IDs because fetch() method will not get all entity IDs
                $this->_application->Entity_Storage()->query($this->_entityType, $this->_fieldQuery, 0, 0, $lang, true);
            } else {
                // Let fetch() method save found entity IDs
                $paginator->setExtraParams([$lang, $loadEntityFields, true]);
            }
        }
        
        return $paginator;
    }
    
    public function sort($sort, array $sorts = null, $randomSortSeedName = 'default')
    {
        if (isset($sort)) {
            foreach ((array)$sort as $_sort) {
                if ($_sort === 'random') {
                    $this->_fieldQuery->sortByRandom($this->_getRandomSortSeed($randomSortSeedName));
                } else {
                    if (!isset($sorts[$_sort])) continue;

                    $sort_info = $sorts[$_sort];
                    if (isset($sort_info['field_type'])) {
                        if (($field_type = $this->_application->Field_Type($sort_info['field_type'], true))
                            && $field_type instanceof Field\Type\ISortable
                        ) {
                            if (strpos($_sort, ',')) {
                                $args = explode(',', $_sort);
                                array_shift($args); // remove field name part
                                if (isset($sort_info['args'])) {
                                    $args += $sort_info['args'];
                                }
                            } else {
                                $args = isset($sort_info['args']) ? $sort_info['args'] : null;
                            }
                            $field_type->fieldSortableSort($this->_fieldQuery, isset($sort_info['field_name']) ? $sort_info['field_name'] : $sort_info['field_type'], $args);
                        }
                    } elseif (isset($sort_info['callback'])) {
                        $this->_fieldQuery->sortByCustom($sort_info['callback'], isset($sort_info['order']) ? $sort_info['order'] : 'ASC');
                    }
                }
            }
            $this->_fieldQuery->sortById('ASC', false); // make sure sort criteria is unique
        } else {
            $this->_fieldQuery->sortById();
        }
        
        return $this;
    }
    
    protected function _getRandomSortSeed($name)
    {
        if (!isset($this->_randomSortSeedLifetime)) {
            $this->_randomSortSeedLifetime = $this->_application->Filter('entity_query_random_sort_seed_lifetime', 3600); // defaults to 1 hour
        }
        $cache_id = 'entity_query_random_sort_seed__' . $name;
        if (!$random_seed = $this->_application->getPlatform()->getCache($cache_id)) {
            $random_seed = mt_rand(1, 9999);
            $this->_application->getPlatform()->setCache($random_seed, $cache_id, $this->_randomSortSeedLifetime);
        }
        
        return $random_seed;
    }
}