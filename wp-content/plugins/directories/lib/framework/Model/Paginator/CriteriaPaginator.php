<?php
namespace SabaiApps\Framework\Model\Paginator;

use SabaiApps\Framework\Model\AbstractEntityRepository;
use SabaiApps\Framework\Criteria\AbstractCriteria;

class CriteriaPaginator extends AbstractPaginator
{
    protected $_criteria;

    public function __construct(AbstractEntityRepository $repository, AbstractCriteria $criteria, $perpage, $sort, $order, $limit = 0)
    {
        parent::__construct($repository, $perpage, $sort, $order, $limit);
        $this->_criteria = $criteria;
    }

    protected function _getElementCount()
    {
        return $this->_repository->countByCriteria($this->_criteria);
    }

    protected function _getElements($limit, $offset)
    {
        return $this->_repository->fetchByCriteria($this->_criteria, $limit, $offset, $this->_sort, $this->_order);
    }
}