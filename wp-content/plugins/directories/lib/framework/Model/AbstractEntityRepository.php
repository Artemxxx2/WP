<?php
namespace SabaiApps\Framework\Model;

use SabaiApps\Framework\Criteria;
use SabaiApps\Framework\DB\AbstractRowset;


abstract class AbstractEntityRepository
{
    /**
     * @var string
     */
    protected $_name;
    /**
     * @var string
     */
    private $_fieldPrefix;
    /**
     * @var Model
     */
    protected $_model;
    /**
     * @var array
     */
    private $_criteria;

    /**
     * Constructor
     */
    protected function __construct($name, Model $model)
    {
        $this->_name = $name;
        $this->_model = $model;
        $this->_fieldPrefix = strtolower($name) . '_';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    public function create()
    {
        return $this->_model->create($this->_name);
    }

    /**
     * Creates a criteria element and returns self for chainability
     *
     * @return AbstractEntityRepository
     */
    public function criteria()
    {
        $this->_criteria = $this->_model->createCriteria($this->_name);
        return $this;
    }

    /**
     * Calls a method defined in the criteria element and returns self for chainability
     *
     * @return AbstractEntityRepository
     */
    public function __call($method, $args)
    {
        if (!isset($this->_criteria)) $this->_criteria = $this->_model->createCriteria($this->_name);
        $this->_criteria = call_user_func_array(array($this->_criteria, $method), $args);

        return $this;
    }

    /**
     * @param int $id
     * @param bool $returnCollection
     * @return AbstractEntity
     */
    public function fetchById($id, $returnCollection = false, $useCache = true)
    {
        if ($useCache
            && ($entity = $this->_model->isEntityCached($this->_name, $id))
        ) {
            return $returnCollection ? $this->createCollection([$entity]) : $entity;
        }

        $collection = $this->_getCollection($this->_model->getGateway($this->_name)->selectById($id));
        if (!$returnCollection) {
            return $collection->getFirst();
        }
        $collection->rewind();
        return $collection;
    }

    /**
     * @param array $ids
     * @return AbstractEntityCollection
     */
    public function fetchByIds($ids)
    {
        return $this->_getCollection($this->_model->getGateway($this->_name)->selectByIds($ids));
    }

    /**
     * @param Criteria\AbstractCriteria
     * @param int $limit
     * @param int $offset
     * @param mixed $sort An array or string
     * @param mixed $order An array or string
     * @return RowsetEntityCollection
     */
    public function fetchByCriteria(Criteria\AbstractCriteria $criteria, $limit = 0, $offset = 0, $sort = null, $order = null)
    {
        return $this->_getCollection(
            $this->_model->getGateway($this->getName())
                ->selectByCriteria($criteria, [], $limit, $offset, array_map([$this, '_prefixSort'], (array)$sort), (array)$order)
        );
    }

    /**
     * @param int $limit
     * @param int $offset
     * @param mixed $sort An array or string
     * @param mixed $order An array or string
     * @return RowsetEntityCollection
     */
    public function fetch($limit = 0, $offset = 0, $sort = null, $order = null)
    {
        $criteria = !isset($this->_criteria) ? new Criteria\EmptyCriteria() : $this->_criteria;
        unset($this->_criteria);
        return $this->fetchByCriteria($criteria, $limit, $offset, $sort, $order);
    }

    /**
     * @param mixed $sort An array or string
     * @param mixed $order An array or string
     * @param int $offset
     * @return RowsetEntityCollection or false
     */
    public function fetchOne($sort = null, $order = null, $offset = 0)
    {
        return $this->fetch(1, $offset, $sort, $order)->getNext();
    }

    /**
     * @param Criteria\AbstractCriteria
     * @return mixed Integer if no grouping, array otherwise
     */
    public function countByCriteria(Criteria\AbstractCriteria $criteria)
    {
        return $this->_model->getGateway($this->getName())->countByCriteria($criteria);
    }

    /**
     * @return int
     */
    public function count()
    {
        $criteria = !isset($this->_criteria) ? new Criteria\EmptyCriteria() : $this->_criteria;
        unset($this->_criteria);
        return $this->countByCriteria($criteria);
    }
    
    public function delete()
    {
        $criteria = !isset($this->_criteria) ? new Criteria\EmptyCriteria() : $this->_criteria;
        unset($this->_criteria);
        return $this->_model->getGateway($this->getName())->deleteByCriteria($criteria);
    }

    /**
     * @param Criteria\AbstractCriteria $criteria
     * @param int $perpage
     * @param mixed $sort An array or string
     * @param mixed $order An array or string
     * @return Paginator\CriteriaPaginator
     */
    public function paginateByCriteria(Criteria\AbstractCriteria $criteria, $perpage = 10, $sort = null, $order = null, $limit = 0)
    {
        return new Paginator\CriteriaPaginator($this, $criteria, $perpage, $sort, $order, $limit);
    }

    /**
     * @param int $perpage
     * @param mixed $sort An array or string
     * @param mixed $order An array or string
     * @return Paginator\CriteriaPaginator
     */
    public function paginate($perpage = 10, $sort = null, $order = null, $limit = 0)
    {
        $criteria = !isset($this->_criteria) ? new Criteria\EmptyCriteria() : $this->_criteria;
        unset($this->_criteria);
        return $this->paginateByCriteria($criteria, $perpage, $sort, $order, $limit);
    }

    /**
     * Helper method for fetching entitie pages by foreign key relationship
     *
     * @param string $entityName
     * @param string $id
     * @param int $perpage
     * @param mixed $sort An array or string
     * @param mixed $order An array or string
     * @return Paginator\EntityPaginator
     */
    protected function _paginateByEntity($entityName, $id, $perpage = 10, $sort = null, $order = null, $limit = 0)
    {
        if ($criteria = @$this->_criteria) {
            unset($this->_criteria);
            return $this->_paginateByEntityAndCriteria($entityName, $id, $criteria, $perpage, $sort, $order, $limit);
        }

        return new Paginator\EntityPaginator($this, $entityName, $id, $perpage, $sort, $order, $limit);
    }

    /**
     * Helper method for fetching entitie pages by entitiy id and criteria
     *
     * @param string $entityName
     * @param string $id
     * @param Criteria\AbstractCriteria
     * @param int $perpage
     * @param mixed $sort An array or string
     * @param mixed $order An array or string
     * @return Paginator\EntityCriteriaPaginator
     */
    protected function _paginateByEntityAndCriteria($entityName, $id, Criteria\AbstractCriteria $criteria, $perpage = 10, $sort = null, $order = null, $limit = 0)
    {
        return new Paginator\EntityCriteriaPaginator($this, $entityName, $id, $criteria, $perpage, $sort, $order, $limit);
    }

    /**
     * Helper method for fetching entities by foreign key relationship
     *
     * @param string $foreignKey
     * @param string $id
     * @param int $limit
     * @param int $offset
     * @param mixed $sort An array or string
     * @param mixed $order An array or string
     * @return RowsetEntityCollection
     */
    protected function _fetchByForeign($foreignKey, $id, $limit = 0, $offset = 0, $sort = null, $order = null)
    {
        if ($criteria = @$this->_criteria) {
            unset($this->_criteria);
            return $this->_fetchByForeignAndCriteria($foreignKey, $id, $criteria, $limit, $offset, $sort, $order);
        }

        $criteria = is_array($id) ? new Criteria\InCriteria($foreignKey, $id) : new Criteria\IsCriteria($foreignKey, $id);
        return $this->fetchByCriteria($criteria, $limit, $offset, $sort, $order);
    }

    /**
     * Helper method for counting entities by foreign key relationship
     *
     * @param string $foreignKey
     * @param string $id
     * @return int
     */
    protected function _countByForeign($foreignKey, $id)
    {
        if ($criteria = @$this->_criteria) {
            unset($this->_criteria);
            return $this->_countByForeignAndCriteria($foreignKey, $id, $criteria);
        }

        $criteria = is_array($id) ? new Criteria\InCriteria($foreignKey, $id) : new Criteria\IsCriteria($foreignKey, $id);
        return $this->countByCriteria($criteria);
    }

    /**
     * Helper method for fetching entities by foreign key relationship
     *
     * @param string $foreignKey
     * @param string $id
     * @param Criteria\AbstractCriteria $criteria
     * @param int $limit
     * @param int $offset
     * @param mixed $sort An array or string
     * @param mixed $order An array or string
     * @return RowsetEntityCollection
     */
    protected function _fetchByForeignAndCriteria($foreignKey, $id, Criteria\AbstractCriteria $criteria, $limit = 0, $offset = 0, $sort = null, $order = null)
    {
        $criterion = new Criteria\CompositeCriteria([$criteria]);
        if (is_array($id)) {
            $criterion->addAnd(new Criteria\InCriteria($foreignKey, $id));
        } else {
            $criterion->addAnd(new Criteria\IsCriteria($foreignKey, $id));
        }

        return $this->fetchByCriteria($criterion, $limit, $offset, $sort, $order);
    }

    /**
     * Helper method for counting entities by foreign key relationship
     *
     * @param string $foreignKey
     * @param string $id
     * @param Criteria\AbstractCriteria
     * @return int
     */
    protected function _countByForeignAndCriteria($foreignKey, $id, Criteria\AbstractCriteria $criteria)
    {
        $criterion = new Criteria\CompositeCriteria([$criteria]);
        if (is_array($id)) {
            $criterion->addAnd(new Criteria\InCriteria($foreignKey, $id));
        } else {
            $criterion->addAnd(new Criteria\IsCriteria($foreignKey, $id));
        }

        return $this->countByCriteria($criterion);
    }

    /**
     * Helper method for fetching entities by association table relationship
     *
     * @param string $selfTable
     * @param string $assocEntity
     * @param string $assocTargetKey
     * @param string $id
     * @param int $limit
     * @param int $offset
     * @param mixed $sort An array or string
     * @param mixed $order An array or string
     * @return RowsetEntityCollection
     */
    protected function _fetchByAssoc($selfTable, $assocEntity, $assocTargetKey, $id, $limit = 0, $offset = 0, $sort = null, $order = null)
    {
        if ($criteria = @$this->_criteria) {
            unset($this->_criteria);
            return $this->_fetchByAssocAndCriteria($selfTable, $assocEntity, $assocTargetKey, $id, $criteria, $limit, $offset, $sort, $order);
        }

        $criteria = is_array($id) ? new Criteria\InCriteria($assocTargetKey, $id) : new Criteria\IsCriteria($assocTargetKey, $id);
        $fields = ['DISTINCT ' . $selfTable . '.*'];

        return $this->_getCollection(
            $this->_model->getGateway($assocEntity)
                ->selectByCriteria($criteria, $fields, $limit, $offset, array_map([$this, '_prefixSort'], (array)$sort), $order)
        );
    }

    /**
     * Helper method for counting entities by association table relationship
     *
     * @param string $selfTableId
     * @param string $assocEntity
     * @param string $assocTargetKey
     * @param string $id
     * @return int
     */
    protected function _countByAssoc($selfTableId, $assocEntity, $assocTargetKey, $id)
    {
        if ($criteria = @$this->_criteria) {
            unset($this->_criteria);
            return $this->_countByAssocAndCriteria($selfTableId, $assocEntity, $assocTargetKey, $id, $criteria);
        }

        $criteria = is_array($id) ? new Criteria\InCriteria($assocTargetKey, $id) : new Criteria\IsCriteria($assocTargetKey, $id);

        return $this->_model->getGateway($assocEntity)->selectByCriteria($criteria, ['COUNT(DISTINCT '. $selfTableId .')'])->fetchSingle();
    }

    /**
     * Helper method for fetching entities by association table relationship
     * and additional criteria
     *
     * @param string $selfTable
     * @param string $assocEntity
     * @param string $assocTargetKey
     * @param string $id
     * @param Criteria\AbstractCriteria $criteria
     * @param int $limit
     * @param int $offset
     * @param mixed $sort An array or string
     * @param mixed $order An array or string
     * @return RowsetEntityCollection
     */
    protected function _fetchByAssocAndCriteria($selfTable, $assocEntity, $assocTargetKey, $id, Criteria\AbstractCriteria $criteria, $limit = 0, $offset = 0, $sort = null, $order = null)
    {
        $criterion = new Criteria\CompositeCriteria([$criteria]);
        if (is_array($id)) {
            $criterion->addAnd(new Criteria\InCriteria($assocTargetKey, $id));
        } else {
            $criterion->addAnd(new Criteria\IsCriteria($assocTargetKey, $id));
        }
        $fields = ['DISTINCT ' . $selfTable . '.*'];

        return $this->_getCollection(
            $this->_model->getGateway($assocEntity)
                ->selectByCriteria($criterion, $fields, $limit, $offset, array_map([$this, '_prefixSort'], (array)$sort), (array)$order)
        );
    }

    /**
     * Helper method for counting entities by association table relationship
     * and additional criteria
     *
     * @param string $selfTableId
     * @param string $assocEntity
     * @param string $id
     * @param Criteria\AbstractCriteria $criteria
     * @return RowsetEntityCollection
     */
    protected function _countByAssocAndCriteria($selfTableId, $assocEntity, $assocTargetKey, $id, Criteria\AbstractCriteria $criteria)
    {
        $criterion = new Criteria\CompositeCriteria([$criteria]);
        if (is_array($id)) {
            $criterion->addAnd(new Criteria\InCriteria($assocTargetKey, $id));
        } else {
            $criterion->addAnd(new Criteria\IsCriteria($assocTargetKey, $id));
        }

        return $this->_model->getGateway($assocEntity)->selectByCriteria($criterion, ['COUNT(DISTINCT '. $selfTableId .')'])->fetchSingle();
    }

    /**
     * Prefix the requested sort value get the actual field name
     *
     * @param string $sort
     * @return array
     */
    private function _prefixSort($sort)
    {
        return $this->_fieldPrefix . $sort;
    }

    /**
     * Turns a rowset object into an entity collection object
     *
     * @param mixed AbstractRowset
     * @return Model\EntityCollection
     */
    protected function _getCollection(AbstractRowset $rs)
    {
        return $this->_getCollectionByRowset($rs);
    }

    /**
     * @param AbstractRowset $rs
     * @return Model\EntityCollection
     */
    abstract protected function _getCollectionByRowset(AbstractRowset $rs);
    /**
     * @param array $entities
     * @return Model\EntityCollection
     */
    abstract public function createCollection(array $entities = []);
}