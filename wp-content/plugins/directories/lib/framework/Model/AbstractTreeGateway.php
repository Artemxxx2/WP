<?php
namespace SabaiApps\Framework\Model;

use SabaiApps\Framework\DB\AbstractRowset;

abstract class AbstractTreeGateway extends AbstractGateway
{
    /**
     * @param string $id
     * @param array $fields
     * @return AbstractRowset
     */
    public function selectDescendants($id, array $fields = [])
    {
        return $this->selectBySQL($this->_getSelectDescendantsQuery($id, $fields));
    }

    /**
     * @param string $id
     * @return int
     */
    public function countDescendants($id)
    {
        return $this->_db->query($this->_getCountDescendantsQuery($id))->fetchSingle();
    }

    /**
     * @param array $ids
     * @return AbstractRowset
     */
    public function countDescendantsByIds($ids)
    {
        return $this->_db->query($this->_getCountDescendantsByIdsQuery($ids));
    }

    /**
     * @param string $id
     * @param array $fields
     * @return AbstractRowset
     */
    public function selectParents($id, array $fields = [])
    {
        return $this->selectBySQL($this->_getSelectParentsQuery($id, $fields));
    }

    /**
     * @param string $id
     * @return int
     */
    public function countParents($id)
    {
        return $this->_db->query($this->_getCountParentsQuery($id))->fetchSingle();
    }

    /**
     * @param array $ids
     * @return AbstractRowset
     */
    public function countParentsByIds($ids)
    {
        return $this->_db->query($this->_getCountParentsByIdsQuery($ids));
    }
}