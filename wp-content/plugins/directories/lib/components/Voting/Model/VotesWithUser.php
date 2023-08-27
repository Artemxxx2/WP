<?php
namespace SabaiApps\Directories\Component\Voting\Model;

use SabaiApps\Directories\Component\ModelEntityWithUser;
use SabaiApps\Framework\Model\EntityCollection\AbstractEntityCollection;

class VotesWithUser extends ModelEntityWithUser
{
    public function __construct(AbstractEntityCollection $collection)
    {
        parent::__construct($collection);
    }
}