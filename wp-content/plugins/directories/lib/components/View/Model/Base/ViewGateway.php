<?php
/* This file has been auto-generated. Do not edit this file directly. */
namespace SabaiApps\Directories\Component\View\Model\Base;

use SabaiApps\Framework\Model\Model;

abstract class ViewGateway extends \SabaiApps\Framework\Model\AbstractGateway
{
    public function getName()
    {
        return 'view_view';
    }

    public function getFields()
    {
        return ['view_name' => Model::KEY_TYPE_VARCHAR, 'view_mode' => Model::KEY_TYPE_VARCHAR, 'view_data' => Model::KEY_TYPE_TEXT, 'view_bundle_name' => Model::KEY_TYPE_VARCHAR, 'view_default' => Model::KEY_TYPE_BOOL, 'view_id' => Model::KEY_TYPE_INT, 'view_created' => Model::KEY_TYPE_INT, 'view_updated' => Model::KEY_TYPE_INT];
    }

    protected function _getIdFieldName()
    {
        return 'view_id';
    }

    protected function _getSelectByIdQuery($id, $fields)
    {
        return sprintf(
            'SELECT %s FROM %sview_view WHERE view_id = %d',
            empty($fields) ? '*' : implode(', ', $fields),
            $this->_db->getResourcePrefix(),
            $id
        );
    }

    protected function _getSelectByIdsQuery($ids, $fields)
    {
        return sprintf(
            'SELECT %s FROM %sview_view WHERE view_id IN (%s)',
            empty($fields) ? '*' : implode(', ', $fields),
            $this->_db->getResourcePrefix(),
            implode(', ', array_map('intval', $ids))
        );
    }

    protected function _getSelectByCriteriaQuery($criteriaStr, $fields)
    {
        return sprintf(
            'SELECT %1$s FROM %2$sview_view view_view WHERE %3$s',
            empty($fields) ? '*' : implode(', ', $fields),
            $this->_db->getResourcePrefix(),
            $criteriaStr
        );
    }

    protected function _getInsertQuery(&$values)
    {
        $values['view_created'] = time();
        $values['view_updated'] = 0;
        return sprintf('INSERT INTO %sview_view(view_name, view_mode, view_data, view_bundle_name, view_default, view_id, view_created, view_updated) VALUES(%s, %s, %s, %s, %u, %s, %d, %d)', $this->_db->getResourcePrefix(), $this->_db->escapeString($values['view_name']), $this->_db->escapeString($values['view_mode']), $this->_db->escapeString(serialize($values['view_data'])), $this->_db->escapeString($values['view_bundle_name']), $this->_db->escapeBool($values['view_default']), empty($values['view_id']) ? 'NULL' : intval($values['view_id']), $values['view_created'], $values['view_updated']);
    }

    protected function _getUpdateQuery($id, $values)
    {
        $last_update = $values['view_updated'];
        $values['view_updated'] = time();
        return sprintf('UPDATE %sview_view SET view_name = %s, view_mode = %s, view_data = %s, view_bundle_name = %s, view_default = %u, view_updated = %d WHERE view_id = %d AND view_updated = %d', $this->_db->getResourcePrefix(), $this->_db->escapeString($values['view_name']), $this->_db->escapeString($values['view_mode']), $this->_db->escapeString(serialize($values['view_data'])), $this->_db->escapeString($values['view_bundle_name']), $this->_db->escapeBool($values['view_default']), $values['view_updated'], $id, $last_update);
    }

    protected function _getDeleteQuery($id)
    {
        return sprintf('DELETE FROM %1$sview_view WHERE view_id = %2$d', $this->_db->getResourcePrefix(), $id);
    }

    protected function _getUpdateByCriteriaQuery($criteriaStr, $sets)
    {
        $sets['view_updated'] = 'view_updated=' . time();
        return sprintf('UPDATE %sview_view view_view SET %s WHERE %s', $this->_db->getResourcePrefix(), implode(', ', $sets), $criteriaStr);
    }

    protected function _getDeleteByCriteriaQuery($criteriaStr)
    {
        return sprintf('DELETE view_view FROM %1$sview_view view_view WHERE %2$s', $this->_db->getResourcePrefix(), $criteriaStr);
    }

    protected function _getCountByCriteriaQuery($criteriaStr)
    {
        return sprintf('SELECT COUNT(*) FROM %1$sview_view view_view WHERE %2$s', $this->_db->getResourcePrefix(), $criteriaStr);
    }
}