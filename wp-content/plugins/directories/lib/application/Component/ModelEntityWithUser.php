<?php
namespace SabaiApps\Directories\Component;

use SabaiApps\Framework\Model\EntityCollection\AbstractEntityCollection;
use SabaiApps\Framework\Model\EntityCollection\AbstractEntityCollectionDecorator;

abstract class ModelEntityWithUser extends AbstractEntityCollectionDecorator
{
    protected $_userIdentities, $_userKeyVar, $_userEntityObjectVarName;

    public function __construct(AbstractEntityCollection $collection, $userKeyVar = 'user_id', $userEntityObjectVarName = 'User')
    {
        parent::__construct($collection);
        $this->_userKeyVar = $userKeyVar;
        $this->_userEntityObjectVarName = $userEntityObjectVarName;
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->_collection->rewind();
        if (!isset($this->_userIdentities)) {
            $this->_userIdentities = [];
            if ($this->_collection->count() > 0) {
                $user_ids = [];
                while ($this->_collection->valid()) {
                    $user_ids[] = $this->_collection->current()->{$this->_userKeyVar};
                    $this->_collection->next();
                }
                $this->_userIdentities = $this->_model->UserIdentity(array_unique($user_ids));
                $this->_collection->rewind();
            }
        }
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        $current = $this->_collection->current();
        $current->assignObject($this->_userEntityObjectVarName, $this->_userIdentities[$current->{$this->_userKeyVar}]);

        return $current;
    }
}