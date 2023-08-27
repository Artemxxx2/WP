<?php
namespace SabaiApps\Framework\Model\EntityCollection;

use SabaiApps\Framework\Model\Model;

abstract class AbstractEntityCollection implements \Iterator, \Countable
{
    protected $_name, $_model, $_key = 0;

    protected function __construct(Model $model, $name)
    {
        $this->_name = $name;
        $this->_model = $model;
    }

    public function getName()
    {
        return $this->_name;
    }

    public function getModel()
    {
        return $this->_model;
    }

    public function with()
    {
        $args = func_get_args();
        return $this->_model->decorate($this, $args);
    }

    /**
     * @return array
     */
    public function getAllIds()
    {
        return $this->getArray('id');
    }

    public function getArray($var = null, $key = null)
    {
        $ret = [];
        $this->rewind();
        $key = isset($key) ? $key : 'id';
        while ($this->valid()) {
            $entity = $this->current();
            $ret[$entity->$key] = isset($var) ? $entity->$var : $entity;
            $this->next();
        }

        return $ret;
    }

    /**
     * Updates values of all the entities within the collection
     *
     * @param array $values
     */
    public function update(array $values, $commit = false)
    {
        $this->rewind();
        while ($this->valid()) {
            foreach ($values as $key => $value) {
                $this->current()->set($key, $value);
            }
            $this->next();
        }
        if ($commit) $this->_model->commit();
    }

    /**
     * Mark all the entities within the collection from as removed
     */
    public function delete($commit = false)
    {
        $this->rewind();
        while ($this->valid()) {
            $this->current()->markRemoved();
            $this->next();
        }
        if ($commit) $this->_model->commit();
    }

    /**
     * @return mixed
     */
    public function getNext()
    {
        $ret = false;
        if ($this->valid()) {
            $ret = $this->current();
            $this->next();
        }
        return $ret;
    }

    /**
     * @return mixed
     */
    public function getFirst()
    {
        $this->rewind();
        return $this->valid() ? $this->current() : false;
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->_key = 0;
    }

    #[\ReturnTypeWillChange]
    public function next()
    {
        ++$this->_key;
    }

    /**
     * @return SabaiApps\Framework\Model\AbstractEntity
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        $ret = $this->getCurrent($this->_key);
        $this->_model->cacheEntity($ret);

        return $ret;
    }

    abstract public function getCurrent($index);

    /**
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->_key;
    }
}