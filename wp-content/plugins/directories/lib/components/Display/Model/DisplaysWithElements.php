<?php
namespace SabaiApps\Directories\Component\Display\Model;

use SabaiApps\Framework\Model\EntityCollection\AbstractEntityCollection;
use SabaiApps\Framework\Model\EntityCollection\ForeignEntitiesEntityCollectionDecorator;

class DisplaysWithElements extends ForeignEntitiesEntityCollectionDecorator
{
    public function __construct(AbstractEntityCollection $collection)
    {
        parent::__construct('element_display_id', 'Element', $collection, 'Elements');
    }
}