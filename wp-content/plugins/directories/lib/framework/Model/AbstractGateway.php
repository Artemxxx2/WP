<?php
namespace SabaiApps\Framework\Model;

use SabaiApps\Framework\Criteria;
use SabaiApps\Framework\Exception;
use SabaiApps\Framework\DB\AbstractDB;

abstract class AbstractGateway implements Criteria\IVisitor
{
    /**
     * @var AbstractDB
     */
    protected $_db;

    public function setDB(AbstractDB $db)
    {
        $this->_db = $db;
    }

    public function getTableName()
    {
        return $this->_db->getResourcePrefix() . $this->getName();
    }

    /**
     * @return array All fields used within this gateway
     */
    public function getAllFields()
    {
        return $this->getFields() + $this->getSortFields();
    }

    public function selectById($id, array $fields = [])
    {
        return $this->_db->query($this->_getSelectByIdQuery($id, $fields));
    }

    public function selectByIds(array $ids, array $fields = [])
    {
        return $this->_db->query($this->_getSelectByIdsQuery($ids, $fields));
    }

    public function selectByCriteria(Criteria\AbstractCriteria $criteria, array $fields = [], $limit = 0, $offset = 0, array $sort = null, array $order = null, $group = null)
    {
        $criterions = [];
        $criteria->acceptVisitor($this, $criterions);
        $query = $this->_getSelectByCriteriaQuery(implode(' ', $criterions), $fields);

        return $this->selectBySQL($query, $limit, $offset, $sort, $order, $group);
    }

    public function insert(array $values)
    {
        $this->_beforeInsert($values);
        if (1 !== $this->_db->exec($this->_getInsertQuery($values))) {
            throw new Exception(sprintf('Failed inserting a new row to the table %s. Last DB error: %s', $this->getTableName(), $this->_db->lastError()));
        }
        if (false === $id = $this->_db->lastInsertId($this->getTableName())) {
            throw new Exception(sprintf('Failed fetching the last insert ID from %s. Last DB error: %s', $this->getTableName(), $this->_db->lastError()));
        }
        if (0 === $id) { // PK is not an auto_increment field
            $id = $values[$this->_getIdFieldName()];
        } else {
            $values[$this->_getIdFieldName()] = $id;
        }
        $this->_afterInsert($id, $values);

        return $id;
    }

    protected function _beforeInsert(array &$new) {}

    protected function _afterInsert($id, array $new){}

    public function updateById($id, array $values)
    {
        if (!$old = $this->selectById($id)->fetchAssoc()) {
            throw new Exception(sprintf('Failed fetching a row with an ID of %s from %s on before update. Last DB error: %s', $id, $this->getTableName(), $this->_db->lastError()));
        }
        $this->_beforeUpdate($id, $values, $old);
        $this->_db->exec($this->_getUpdateQuery($id, $values));
        $this->_afterUpdate($id, $values, $old);
    }

    protected function _beforeUpdate($id, array &$new, array $old) {}

    protected function _afterUpdate($id, array $new, array $old){}

    public function deleteById($id)
    {
        if (!$old = $this->selectById($id)->fetchAssoc()) {
            throw new Exception(sprintf('Failed fetching a row with an ID of %s from %s on before delete. Last DB error: %s', $id, $this->getTableName(), $this->_db->lastError()));
        }
        $this->_beforeDelete($id, $old);
        $this->_db->exec($this->_getDeleteQuery($id));
        $this->_afterDelete($id, $old);
    }

    protected function _beforeDelete($id, array $old) {}

    protected function _afterDelete($id, array $old) {}

    /**
     * Enter description here...
     *
     * @param Criteria\AbstractCriteria $criteria
     * @param array $values
     * @return int Number of affected rows
     * @throws Exception
     */
    public function updateByCriteria(Criteria\AbstractCriteria $criteria, array $values)
    {
        $sets = [];
        $fields = $this->getFields();
        foreach (array_keys($values) as $k) {
            if (isset($fields[$k])) {
                $operator = '=';
                $this->_sanitizeForQuery($values[$k], $fields[$k], $operator);
                $sets[$k] = $k . $operator . $values[$k];
            }
        }
        $criterions = [];
        $criteria->acceptVisitor($this, $criterions);

        return $this->_db->exec($this->_getUpdateByCriteriaQuery(implode(' ', $criterions), $sets));
    }

    public function deleteByCriteria(Criteria\AbstractCriteria $criteria)
    {
        $criterions = [];
        $criteria->acceptVisitor($this, $criterions);

        return $this->_db->exec($this->_getDeleteByCriteriaQuery(implode(' ', $criterions)));
    }

    public function countByCriteria(Criteria\AbstractCriteria $criteria)
    {
        $criterions = [];
        $criteria->acceptVisitor($this, $criterions);

        return $this->_db->query($this->_getCountByCriteriaQuery(implode(' ', $criterions)))->fetchSingle();
    }

    public function selectBySQL($sql, $limit = 0, $offset = 0, array $sort = null, array $order = null, $group = null)
    {
        if (isset($group)) {
            $fields = $this->getFields();
            $groups = [];
            foreach ((array)$group as $_group) {
                if (isset($fields[$_group])) $groups[] = $_group;
            }
            if (!empty($groups)) $sql .= ' GROUP BY ' . implode(', ', $groups);
        }
        if (isset($sort)) {
            $sort_fields = $this->getSortFields();
            foreach (array_keys($sort) as $i) {
                if (isset($sort_fields[$sort[$i]])) {
                    $order_by[] = $sort[$i] . ' ' . (isset($order[$i]) && $order[$i] == 'DESC' ? 'DESC': 'ASC');
                }
            }
            if (isset($order_by)) $sql .= ' ORDER BY ' . implode(', ', $order_by);
        }

        return $this->_db->query($sql, $limit, $offset);
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
        $count = count($elements);
        $conditions = $criteria->getConditions();
        $criterions[] = '(';
        $elements[0]->acceptVisitor($this, $criterions);
        for ($i = 1; $i < $count; $i++) {
            $criterions[] = $conditions[$i];
            $elements[$i]->acceptVisitor($this, $criterions);
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
        $field = $criteria->getField();
        $fields = $this->getAllFields();
        if (!isset($fields[$field])) return;

        $value = $criteria->getValue();
        $this->_sanitizeForQuery($value, $fields[$field], $operator);
        $criterions[] = $field;
        $criterions[] = $operator;
        $criterions[] = $value;
    }

    public function visitCriteriaIs(Criteria\IsCriteria $criteria, &$criterions)
    {
        $this->_visitCriteriaValue($criteria, $criterions, '=');
    }

    public function visitCriteriaIsNot(Criteria\IsNotCriteria $criteria, &$criterions)
    {
        $this->_visitCriteriaValue($criteria, $criterions, '!=');
    }

    public function visitCriteriaIsSmallerThan(Criteria\IsSmallerThanCriteria $criteria, &$criterions)
    {
        $this->_visitCriteriaValue($criteria, $criterions, '<');
    }

    public function visitCriteriaIsGreaterThan(Criteria\IsGreaterThanCriteria $criteria, &$criterions)
    {
        $this->_visitCriteriaValue($criteria, $criterions, '>');
    }

    public function visitCriteriaIsOrSmallerThan(Criteria\IsOrSmallerThanCriteria $criteria, &$criterions)
    {
        $this->_visitCriteriaValue($criteria, $criterions, '<=');
    }

    public function visitCriteriaIsOrGreaterThan(Criteria\IsOrGreaterThanCriteria $criteria, &$criterions)
    {
        $this->_visitCriteriaValue($criteria, $criterions, '>=');
    }

    public function visitCriteriaIsNull(Criteria\IsNullCriteria $criteria, &$criterions)
    {
        $field = $criteria->getField();
        $fields = $this->getAllFields();
        if (!isset($fields[$field])) return;

        $criterions[] = $field;
        $criterions[] = 'IS NULL';
    }

    public function visitCriteriaIsNotNull(Criteria\IsNotNullCriteria $criteria, &$criterions)
    {
        $field = $criteria->getField();
        $fields = $this->getAllFields();
        if (!isset($fields[$field])) return;

        $criterions[] = $field;
        $criterions[] = 'IS NOT NULL';
    }

    private function _visitCriteriaArray(Criteria\AbstractArrayCriteria $criteria, &$criterions, $format)
    {
        $field = $criteria->getField();
        $fields = $this->getAllFields();
        if (!isset($fields[$field])) return;

        $values = $criteria->getArray();
        if (!empty($values)) {
            $data_type = $fields[$field];
            $operator = null;
            foreach ($values as $v) {
                $this->_sanitizeForQuery($v, $data_type, $operator);
                $value[] = $v;
            }
            $criterions[] = sprintf($format, $field, implode(',', $value));
        }
    }

    public function visitCriteriaIn(Criteria\InCriteria $criteria, &$criterions)
    {
        $this->_visitCriteriaArray($criteria, $criterions, '%s IN (%s)');
    }

    public function visitCriteriaNotIn(Criteria\NotInCriteria $criteria, &$criterions)
    {
        $this->_visitCriteriaArray($criteria, $criterions, '%s NOT IN (%s)');
    }

    private function _visitCriteriaString(Criteria\AbstractValueCriteria $criteria, &$criterions, $format, $operator = 'LIKE')
    {
        $field = $criteria->getField();
        $fields = $this->getAllFields();
        if (!isset($fields[$field])) return;

        $value = sprintf($format, $criteria->getValue());
        $this->_sanitizeForQuery($value, $fields[$field], $operator);
        $criterions[] = $field;
        $criterions[] = $operator;
        $criterions[] = $value;
    }

    public function visitCriteriaStartsWith(Criteria\StartsWithCriteria $criteria, &$criterions)
    {
        $this->_visitCriteriaString($criteria, $criterions, '%s%%');
    }

    public function visitCriteriaEndsWith(Criteria\EndsWithCriteria $criteria, &$criterions)
    {
        $this->_visitCriteriaString($criteria, $criterions, '%%%s');
    }

    public function visitCriteriaContains(Criteria\ContainsCriteria $criteria, &$criterions)
    {
        $this->_visitCriteriaString($criteria, $criterions, '%%%s%%');
    }

    public function visitCriteriaNotContains(Criteria\ContainsCriteria $criteria, &$criterions)
    {
        $this->_visitCriteriaString($criteria, $criterions, '%%%s%%', 'NOT LIKE');
    }
    
    private function _visitCriteriaField(Criteria\AbstractFieldCriteria $criteria, &$criterions, $operator)
    {
        $field = $criteria->getField();
        $field2 = $criteria->getField2();
        $fields = $this->getAllFields();
        if (!isset($fields[$field]) || !isset($fields[$field2])) return;

        $criterions[] = $field;
        $criterions[] = $operator;
        $criterions[] = $field2;
    }

    public function visitCriteriaIsField(Criteria\IsFieldCriteria $criteria, &$criterions)
    {
        $this->_visitCriteriaField($criteria, $criterions, '=');
    }

    public function visitCriteriaIsNotField(Criteria\IsNotFieldCriteria $criteria, &$criterions)
    {
        $this->_visitCriteriaField($criteria, $criterions, '!=');
    }

    public function visitCriteriaIsSmallerThanField(Criteria\IsSmallerThanFieldCriteria $criteria, &$criterions)
    {
        $this->_visitCriteriaField($criteria, $criterions, '<');
    }

    public function visitCriteriaIsGreaterThanField(Criteria\IsGreaterThanFieldCriteria $criteria, &$criterions)
    {
        $this->_visitCriteriaField($criteria, $criterions, '>');
    }

    public function visitCriteriaIsOrSmallerThanField(Criteria\IsOrSmallerThanFieldCriteria $criteria, &$criterions)
    {
        $this->_visitCriteriaField($criteria, $criterions, '<=');
    }

    public function visitCriteriaIsOrGreaterThanField(Criteria\IsOrGreaterThanFieldCriteria $criteria, &$criterions)
    {
        $this->_visitCriteriaField($criteria, $criterions, '>=');
    }

    /**
     * @param mixed $value
     * @param int $dataType
     * @param string $operator
     */
    private function _sanitizeForQuery(&$value, $dataType, &$operator)
    {
        switch ($dataType) {
            case Model::KEY_TYPE_INT_NULL:
                if (is_numeric($value)) {
                    $value = intval($value);
                } else {
                    $value = 'NULL';
                    $operator = ($operator === '!=') ? 'IS NOT' : 'IS';
                }
                return;
            case Model::KEY_TYPE_INT:
                $value = intval($value);
                return;
            case Model::KEY_TYPE_TEXT:
            case Model::KEY_TYPE_VARCHAR:
                $value = $this->_db->escapeString($value);
                return;
            case Model::KEY_TYPE_FLOAT:
                $value = str_replace(',', '.', floatval($value));
                return;
            case Model::KEY_TYPE_BOOL:
                $value = $this->_db->escapeBool($value);
                return;
            case Model::KEY_TYPE_BLOB:
                $value = $this->_db->escapeBlob($value);
                return;
            default:
                $value = $this->_db->escapeString($value);
        }
    }

    /**
     * Gets the fields that can be used for sorting.
     * This method will only be overwritten by assoc entities.
     *
     * @return array
     */
    public function getSortFields()
    {
        return $this->getFields();
    }

    /**
     * Gets the last error message returned by the database driver
     *
     * @return string
     */
    public function getError()
    {
        return $this->_db->lastError();
    }

    abstract public function getName();
    abstract public function getFields();
    abstract protected function _getIdFieldName();
    abstract protected function _getSelectByIdQuery($id, $fields);
    abstract protected function _getSelectByIdsQuery($ids, $fields);
    abstract protected function _getSelectByCriteriaQuery($criteriaStr, $fields);
    abstract protected function _getInsertQuery(&$values);
    abstract protected function _getUpdateQuery($id, $values);
    abstract protected function _getDeleteQuery($id);
    abstract protected function _getUpdateByCriteriaQuery($criteriaStr, $sets);
    abstract protected function _getDeleteByCriteriaQuery($criteriaStr);
    abstract protected function _getCountByCriteriaQuery($criteriaStr);
}