<?php
namespace SabaiApps\Framework\User;

class IdentityPaginator extends \SabaiApps\Framework\Paginator\AbstractPaginator
{
    protected $_identityFetcher, $_sort, $_order;

    public function __construct(AbstractIdentityFetcher $identityFetcher, $perpage, $sort, $order, $limit = 0)
    {
        parent::__construct($perpage, $limit);
        $this->_identityFetcher = $identityFetcher;
        $this->_sort = $sort;
        $this->_order = $order;
    }

    protected function _getElementCount()
    {
        return $this->_identityFetcher->count();
    }

    protected function _getElements($limit, $offset)
    {
        return $this->_identityFetcher->fetch($limit, $offset, $this->_sort, $this->_order);
    }
}