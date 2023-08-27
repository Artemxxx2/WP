<?php
namespace SabaiApps\Directories\Component\Entity\Model;

use SabaiApps\Framework\Model\EntityCollection\ForeignEntitiesEntityCollectionDecorator;
use SabaiApps\Framework\Model\EntityCollection\AbstractEntityCollection;

class BundlesWithFields extends ForeignEntitiesEntityCollectionDecorator
{
    public function __construct(AbstractEntityCollection $collection)
    {
        parent::__construct('field_bundle_name', 'Field', $collection, 'Fields');
    }
}