<?php
namespace SabaiApps\Framework\Model\EntityCollection;

use SabaiApps\Framework\Criteria\InCriteria;

class AssocEntitiesCountEntityCollectionDecorator extends AbstractEntityCollectionDecorator
{
    protected $_linkEntityName, $_linkSelfKey, $_assocEntityName, $_assocEntitiesCount;

    public function __construct($linkEntityName, $linkSelfKey, $assocEntityName, AbstractEntityCollection $collection)
    {
        parent::__construct($collection);
        $this->_linkEntityName = $linkEntityName;
        $this->_linkSelfKey = $linkSelfKey;
        $this->_assocEntityName = $assocEntityName;
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->_collection->rewind();
        if (!isset($this->_assocEntitiesCount)) {
            $this->_assocEntitiesCount = [];
            if ($this->_collection->count() > 0) {
                $criteria = new InCriteria($this->_linkSelfKey, $this->_collection->getAllIds());
                $fields = [$this->_linkSelfKey, 'COUNT(*) AS cnt'];
                if ($rs = $this->_model->getGateway($this->_linkEntityName)->selectByCriteria($criteria, $fields, 0, 0, null, null, $this->_linkSelfKey)) {
                    foreach ($rs as $row) {
                        $this->_assocEntitiesCount[$row[$this->_linkSelfKey]] = $row['cnt'];
                    }
                }
                $this->_collection->rewind();
            }
        }
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        $current = $this->_collection->current();
        $count = isset($this->_assocEntitiesCount[$current->id]) ? $this->_assocEntitiesCount[$current->id] : 0;
        $current->assignObject($this->_assocEntityName . 'Count', $count);

        return $current;
    }
}