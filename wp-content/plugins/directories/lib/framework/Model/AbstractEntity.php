<?php
namespace SabaiApps\Framework\Model;

use SabaiApps\Framework\Criteria;

abstract class AbstractEntity
{
    /**
     * @var string
     */
    protected $_name;
    /**
     * @var Model
     */
    protected $_model;
    /**
     * @var array
     */
    protected $_vars = [];
    /**
     * @var array
     */
    protected $_objects = [];
    /**
     * @var string
     */
    private $_tempId = false;
    /**
     * Entities that this entity should be assigned on commit
     * @var array
     */
    private $_entitiesToAssign = [];
    /**
     * Emtities that should be assigned to this entity on commit
     * @var array
     */
    private $_entitiesToBeAssigned = [];

    /**
     * Constructor
     *
     * @param string $name
     * @param Model $model
     */
    protected function __construct($name, Model $model)
    {
        $this->_name = $name;
        $this->_model = $model;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @return Model
     */
    public function getModel()
    {
        return $this->_model;
    }

    /**
     * Shortcut method for getting the related entity repository object
     *
     * @return AbstractEntityRepository
     */
    protected function _getRepository()
    {
        return $this->_model->getRepository($this->getName());
    }

    /**
     * @param string $value
     */
    public function setTempId($value)
    {
        $this->_tempId = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getTempId()
    {
        return $this->_tempId;
    }

    /**
     * Initializes variables using row data fetched from the database
     * @param array $arr
     */
    public function initVars(array $arr)
    {
        foreach (array_keys($arr) as $name) $this->_initVar($name, $arr[$name]);

        return $this;
    }

    abstract protected function _initVar($name, $value);

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->__get($key);
    }

    abstract public function __get($name);

    /**
     * @param mixed $key string
     * @param mixed $value
     */
    public function set($key, $value)
    {
        $this->__set($key, $value);

        return $this;
    }

    abstract public function __set($name, $value);

    /**
     * @return array
     */
    public function getVars()
    {
        return $this->_vars;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @param bool $markDirty
     */
    protected function _setVar($name, $value, $markDirty = true)
    {
        $this->_vars[$name] = $value;
        if ($markDirty) $this->markDirty();
    }

    /**
     * Sets an object related to this entity
     *
     * @param string $name
     * @param mixed $object An object or null
     */
    public function assignObject($name, $object = null)
    {
        $this->_objects[$name] = $object;

        return $this;
    }

    /**
     * Gets an object related to this entity
     *
     * @param string $name
     * @return object
     */
    public function fetchObject($name)
    {
        return isset($this->_objects[$name]) ? $this->_objects[$name] : null;
    }

    /**
     * Clears a cached object related to this entity
     *
     * @param string $name
     */
    public function clearObject($name)
    {
        unset($this->_objects[$name]);

        return $this;
    }

    /**
     */
    public function markNew()
    {
        $this->_model->registerNew($this);

        return $this;
    }

    /**
     */
    public function markDirty()
    {
        $this->_model->registerDirty($this);

        return $this;
    }

    /**
     */
    public function markRemoved()
    {
        $this->_model->registerRemoved($this);
        // this is so that no entities can be assigned during commit
        $this->_entitiesToBeAssigned = [];

        return $this;
    }

    /**
     */
    public function cache()
    {
        $this->_model->cacheEntity($this);

        return $this;
    }

    /**
     * @param string $entityName
     * @param string $foreignKey
     * @return AbstractEntity
     */
    protected function _fetchEntity($entityName, $foreignKey)
    {
        if (!array_key_exists($entityName, $this->_objects)) {
            if ($id = $this->$foreignKey) {
                $this->_objects[$entityName] = $this->_model->getRepository($entityName)->fetchById($id);
            } else {
                $this->_objects[$entityName] = null;
            }
        }

        return $this->_objects[$entityName];
    }

    /**
     * @param AbstractEntity $entity
     * @param string $foreignKey
     */
    protected function _assignEntity(AbstractEntity $entity, $foreignKey, $markDirty = true)
    {
        $entity_name = $entity->getName();
        if (!$id = $entity->id) {
            if (!$temp_id = $entity->getTempId()) {
                $entity->markNew();
                $temp_id = $entity->getTempId();
            }
            $entity->addEntityToAssign($this);
            $this->_entitiesToBeAssigned[$entity_name][$foreignKey] = $temp_id;
        } else {
            if ($this->_vars[$foreignKey] != $id) {
                if ($temp_id = $entity->getTempId()) {
                    // temp id is set, meaning that the entity is being assigned on commit
                    // check if we are really allowed to assgin this entity
                    if (!isset($this->_entitiesToBeAssigned[$entity_name][$foreignKey])
                        || $this->_entitiesToBeAssigned[$entity_name][$foreignKey] != $temp_id
                    ) return;

                    $markDirty = false; // do not change state during the commit process
                }

                // Assign entity
                $this->_setVar($foreignKey, $id, $markDirty);
                unset($this->_entitiesToBeAssigned[$entity_name][$foreignKey]);
            }
        }
        $this->_objects[$entity_name] = $entity;
    }

    /**
     * @param string $entityName
     * @param string $id
     * @param string $foreignKey
     * @param bool $markDirty
     */
    protected function _assignEntityById($entityName, $id, $foreignKey, $markDirty = true)
    {
        $this->_setVar($foreignKey, $id, $markDirty);
        unset($this->_entitiesToBeAssigned[$entityName][$foreignKey]);
    }

    public function fetchEntitiesToBeAssigned()
    {
        return $this->_entitiesToBeAssigned;
    }

    /**
     * @param string $entityName
     * @param string $objectName
     * @return RowsetEntityCollection
     */
    protected function _fetchEntities($entityName, $objectName = null)
    {
        if (!isset($objectName)) {
            $method = 'fetchBy' . $this->getName();
            return $this->_model->getRepository($entityName)->$method($this->id);
        }

        if (!isset($this->_objects[$objectName])) {
            $method = 'fetchBy' . $this->getName();
            $this->_objects[$objectName] = $this->_model->getRepository($entityName)->$method($this->id);
        }

        return $this->_objects[$objectName];
    }

    /**
     * @param string $targetPrimaryKey
     * @param string $entityName
     * @param string $id
     * @return int
     */
    protected function _removeEntityById($targetPrimaryKey, $entityName, $id)
    {
        $method = 'fetchBy' . $this->getName() . 'AndCriteria';
        $criteria = new Criteria\IsCriteria($targetPrimaryKey, $id);
        $entities = $this->_model->getRepository($entityName)->$method($this->id, $criteria);
        foreach ($entities as $entity) $entity->{$this->getName()} = null;
    }

    /**
     * @param string $entityName
     * @return int
     */
    protected function _removeEntities($entityName)
    {
        $entities = $this->_fetchEntities($entityName);
        foreach ($entities as $entity) $entity->{$this->getName()} = null;
    }

    /**
     * @param string $entityName
     * @return AbstractEntity
     */
    protected function _createEntity($entityName)
    {
        $entity = $this->_model->create($entityName);
        $entity->{$this->getName()} = $this;

        return $entity;
    }

    /**
     * @param string $linkEntityName
     * @param AbstractEntity $entity
     * @return object AbstractEntity
     */
    protected function _linkEntity($linkEntityName, AbstractEntity $entity)
    {
        $link = $this->_model->create($linkEntityName)->markNew();
        $link->{$this->getName()} = $this;
        $link->{$entity->getName()} = $entity;

        return $link;
    }

    /**
     * @param string $linkEntityName
     * @param string $linkTargetKey
     * @param string $id
     * @return object AbstractEntity
     */
    protected function _linkEntityById($linkEntityName, $linkTargetKey, $id)
    {
        $link = $this->_model->create($linkEntityName)->markNew();
        $link->{$this->getName()} = $this;
        $link->$linkTargetKey = $id;

        return $link;
    }

    /**
     * @param string $linkEntityName
     * @param string $linkSelfKey
     * @param string $linkTargetKey
     * @param string $id
     */
    protected function _unlinkEntityById($linkEntityName, $linkSelfKey, $linkTargetKey, $id)
    {
        if (!$id = intval($id)) return;

        $criteria = new Criteria\CompositeCriteria();
        $criteria->addAnd(new Criteria\IsCriteria($linkSelfKey, $this->id))
            ->addAnd(new Criteria\IsCriteria($linkTargetKey, $id));
        foreach ($this->_model->getRepository($linkEntityName)->fetchByCriteria($criteria) as $link) {
            $link->markRemoved();
        }
    }

    /**
     * @param string $linkEntityName
     */
    protected function _unlinkEntities($linkEntityName)
    {
        $method = 'fetchBy' . $this->getName();
        foreach ($this->_model->getRepository($linkEntityName)->$method($this->id) as $link) {
            $link->markRemoved();
        }
    }

    /**
     * @param AbstractEntity $entity
     */
    public function addEntityToAssign(AbstractEntity $entity, $assignMethod = null)
    {
        if (isset($assignMethod)) {
            $this->_entitiesToAssign[$assignMethod][] = $entity;
        } else {
            $this->_entitiesToAssign[] = $entity;
        }

        return $this;
    }

    /**
     */
    public function clearEntitiesToAssign()
    {
        $this->_entitiesToAssign = [];

        return $this;
    }

    /**
     * @return array
     */
    public function fetchEntitiesToAssign()
    {
        return $this->_entitiesToAssign;
    }

    /**
     * Commits the changes made to this entity. You must call markNew() or markRemoved()
     * prior to this method to insert or delete this entity.
     */
    public function commit()
    {
        $this->_model->commitOne($this);

        return $this;
    }

    /**
     * A callback method called just before committing this entity
     */
    public function onCommit()
    {

    }

    /**
     * Reloads vars from the repository
     *
     * @return bool
     */
    public function reload()
    {
        $this->_model->clearEntityCache($this->_name, $this->id); // make sure not using cache
        $this->_vars = $this->_getRepository()->fetchById($this->id)->getVars();
        $this->_objects = [];

        return $this;
    }

    public function with()
    {
        $args = func_get_args();
        $collection = call_user_func_array(
            [$this->_model->getRepository($this->_name)->createCollection([$this]), 'with'],
            $args
        );
        return $collection->getFirst();
    }
}