<?php
namespace SabaiApps\Framework\Model\Paginator;

use SabaiApps\Framework\Model\AbstractEntityRepository;
use SabaiApps\Framework\Criteria\AbstractCriteria;

class EntityCriteriaPaginator extends AbstractPaginator
{
    protected $_entityName, $_entityId, $_criteria;

    public function __construct(AbstractEntityRepository $repository, $entityName, $entityId, AbstractCriteria $criteria, $perpage, $sort, $order, $limit = 0)
    {
        parent::__construct($repository, $perpage, $sort, $order, $limit);
        $this->_entityName = $entityName;
        $this->_entityId = $entityId;
        $this->_criteria = $criteria;
    }

    protected function _getElementCount()
    {
        $method = 'countBy' . $this->_entityName . 'AndCriteria';
        return $this->_repository->$method($this->_entityId, $this->_criteria);
    }

    protected function _getElements($limit, $offset)
    {
        $method = 'fetchBy' . $this->_entityName . 'AndCriteria';
        return $this->_repository->$method($this->_entityId, $this->_criteria, $limit, $offset, $this->_sort, $this->_order);
    }
}