<?php
namespace SabaiApps\Directories\Component\Display\Model;

use SabaiApps\Framework\Model\EntityCollection\ForeignEntitiesEntityCollectionDecorator;
use SabaiApps\Framework\Model\EntityCollection\AbstractEntityCollection;

class ElementsWithDisplay extends ForeignEntitiesEntityCollectionDecorator
{
    public function __construct(AbstractEntityCollection $collection)
    {
        parent::__construct('display_id', 'Display', $collection);
    }
}