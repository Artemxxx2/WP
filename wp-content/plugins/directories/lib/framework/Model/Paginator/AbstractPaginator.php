<?php
namespace SabaiApps\Framework\Model\Paginator;

use SabaiApps\Framework\Model\AbstractEntityRepository;

abstract class AbstractPaginator extends \SabaiApps\Framework\Paginator\AbstractPaginator
{
    protected $_repository, $_sort, $_order;

    public function __construct(AbstractEntityRepository $repository, $perpage, $sort, $order, $limit = 0)
    {
        parent::__construct($perpage, $limit);
        $this->_repository = $repository;
        $this->_sort = $sort;
        $this->_order = $order;
    }

    protected function _getEmptyElements()
    {
        return $this->_repository->createCollection();
    }
}