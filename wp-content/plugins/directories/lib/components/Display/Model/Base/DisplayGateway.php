<?php
/* This file has been auto-generated. Do not edit this file directly. */
namespace SabaiApps\Directories\Component\Display\Model\Base;

use SabaiApps\Framework\Model\Model;

abstract class DisplayGateway extends \SabaiApps\Framework\Model\AbstractGateway
{
    public function getName()
    {
        return 'display_display';
    }

    public function getFields()
    {
        return ['display_name' => Model::KEY_TYPE_VARCHAR, 'display_bundle_name' => Model::KEY_TYPE_VARCHAR, 'display_type' => Model::KEY_TYPE_VARCHAR, 'display_data' => Model::KEY_TYPE_TEXT, 'display_id' => Model::KEY_TYPE_INT, 'display_created' => Model::KEY_TYPE_INT, 'display_updated' => Model::KEY_TYPE_INT];
    }

    protected function _getIdFieldName()
    {
        return 'display_id';
    }

    protected function _getSelectByIdQuery($id, $fields)
    {
        return sprintf(
            'SELECT %s FROM %sdisplay_display WHERE display_id = %d',
            empty($fields) ? '*' : implode(', ', $fields),
            $this->_db->getResourcePrefix(),
            $id
        );
    }

    protected function _getSelectByIdsQuery($ids, $fields)
    {
        return sprintf(
            'SELECT %s FROM %sdisplay_display WHERE display_id IN (%s)',
            empty($fields) ? '*' : implode(', ', $fields),
            $this->_db->getResourcePrefix(),
            implode(', ', array_map('intval', $ids))
        );
    }

    protected function _getSelectByCriteriaQuery($criteriaStr, $fields)
    {
        return sprintf(
            'SELECT %1$s FROM %2$sdisplay_display display_display WHERE %3$s',
            empty($fields) ? '*' : implode(', ', $fields),
            $this->_db->getResourcePrefix(),
            $criteriaStr
        );
    }

    protected function _getInsertQuery(&$values)
    {
        $values['display_created'] = time();
        $values['display_updated'] = 0;
        return sprintf('INSERT INTO %sdisplay_display(display_name, display_bundle_name, display_type, display_data, display_id, display_created, display_updated) VALUES(%s, %s, %s, %s, %s, %d, %d)', $this->_db->getResourcePrefix(), $this->_db->escapeString($values['display_name']), $this->_db->escapeString($values['display_bundle_name']), $this->_db->escapeString($values['display_type']), $this->_db->escapeString(serialize($values['display_data'])), empty($values['display_id']) ? 'NULL' : intval($values['display_id']), $values['display_created'], $values['display_updated']);
    }

    protected function _getUpdateQuery($id, $values)
    {
        $last_update = $values['display_updated'];
        $values['display_updated'] = time();
        return sprintf('UPDATE %sdisplay_display SET display_name = %s, display_bundle_name = %s, display_type = %s, display_data = %s, display_updated = %d WHERE display_id = %d AND display_updated = %d', $this->_db->getResourcePrefix(), $this->_db->escapeString($values['display_name']), $this->_db->escapeString($values['display_bundle_name']), $this->_db->escapeString($values['display_type']), $this->_db->escapeString(serialize($values['display_data'])), $values['display_updated'], $id, $last_update);
    }

    protected function _getDeleteQuery($id)
    {
        return sprintf('DELETE FROM %1$sdisplay_display WHERE display_id = %2$d', $this->_db->getResourcePrefix(), $id);
    }

    protected function _getUpdateByCriteriaQuery($criteriaStr, $sets)
    {
        $sets['display_updated'] = 'display_updated=' . time();
        return sprintf('UPDATE %sdisplay_display display_display SET %s WHERE %s', $this->_db->getResourcePrefix(), implode(', ', $sets), $criteriaStr);
    }

    protected function _getDeleteByCriteriaQuery($criteriaStr)
    {
        return sprintf('DELETE display_display, table1 FROM %1$sdisplay_display display_display LEFT JOIN %1$sdisplay_element table1 ON display_display.display_id = table1.element_display_id WHERE %2$s', $this->_db->getResourcePrefix(), $criteriaStr);
    }

    protected function _getCountByCriteriaQuery($criteriaStr)
    {
        return sprintf('SELECT COUNT(*) FROM %1$sdisplay_display display_display WHERE %2$s', $this->_db->getResourcePrefix(), $criteriaStr);
    }

    protected function _beforeDelete1($id, array $old)
    {
        $this->_db->exec(sprintf('DELETE table0 FROM %1$sdisplay_element table0 WHERE table0.element_display_id = %2$d', $this->_db->getResourcePrefix(), $id));
    }

    protected function _beforeDelete($id, array $old)
    {
        $this->_beforeDelete1($id, $old);
    }
}