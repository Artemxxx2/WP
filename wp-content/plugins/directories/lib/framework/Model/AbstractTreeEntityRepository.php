<?php
namespace SabaiApps\Framework\Model;

abstract class AbstractTreeEntityRepository extends AbstractEntityRepository
{
    /**
     * Fetches all children entities
     *
     * @param string $id
     * @return AbstractEntityCollection
     */
    public function fetchDescendantsByParent($id)
    {
        return $this->_getCollection($this->_model->getGateway($this->getName())->selectDescendants($id, []));
    }

    /**
     * Counts all children entities
     *
     * @param string $id
     * @return int
     */
    public function countDescendantsByParent($id)
    {
        return $this->_model->getGateway($this->getName())->countDescendants($id);
    }

    /**
     * Fethces all parent entities
     *
     * @param string $id
     * @return AbstractEntityCollection
     */
    public function fetchParents($id)
    {        
        $children = [];
        $rs = $this->_model->getGateway($this->getName())->selectParents($id, []);
        foreach ($rs as $row) {
            $children[$row['term_parent']] = $row;
        }
        $current = 0;
        $ret = [];
        while (isset($children[$current])) {
            $ret[] = $this->create()->initVars($children[$current]);
            $current = $children[$current]['term_id'];
        }
        return $this->createCollection($ret);
    }

    /**
     * Counts all parent entities
     *
     * @param string $id
     * @return int
     */
    public function countParents($id)
    {
        return $this->_model->getGateway($this->getName())->countParents($id);
    }

    /**
     * Counts all parent entities
     *
     * @param array $ids
     * @return array
     */
    public function countParentsByIds($ids)
    {
        $ret = [];
        $rs = $this->_model->getGateway($this->getName())->countParentsByIds($ids)->getIterator();
        $rs->rewind();
        while ($rs->valid()) {
            $row = $rs->row();
            $ret[$row[0]] = $row[1];
            $rs->next();
        }
        return $ret;
    }

    /**
     * Counts all descendant entities
     *
     * @param array $ids
     * @return array
     */
    public function countDescendantsByIds($ids)
    {
        $ret = [];
        $rs = $this->_model->getGateway($this->getName())->countDescendantsByIds($ids)->getIterator();
        $rs->rewind();
        while ($rs->valid()) {
            $row = $rs->row();
            $ret[$row[0]] = $row[1];
            $rs->next();
        }
        return $ret;
    }
}