<?php
namespace SabaiApps\Framework\Model\Paginator;

use SabaiApps\Framework\Model\AbstractEntityRepository;

class EntityPaginator extends AbstractPaginator
{
    protected $_entityName, $_entityId;

    public function __construct(AbstractEntityRepository $repository, $entityName, $entityId, $perpage, $sort, $order, $limit = 0)
    {
        parent::__construct($repository, $perpage, $sort, $order, $limit);
        $this->_entityName = $entityName;
        $this->_entityId = $entityId;
    }

    protected function _getElementCount()
    {
        $method = 'countBy' . $this->_entityName;
        return $this->_repository->$method($this->_entityId);
    }

    protected function _getElements($limit, $offset)
    {
        $method = 'fetchBy' . $this->_entityName;
        return $this->_repository->$method($this->_entityId, $limit, $offset, $this->_sort, $this->_order);
    }
}