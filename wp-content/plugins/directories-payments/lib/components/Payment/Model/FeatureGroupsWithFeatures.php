<?php
namespace SabaiApps\Directories\Component\Payment\Model;

use SabaiApps\Framework\Model\EntityCollection\ForeignEntitiesEntityCollectionDecorator;
use SabaiApps\Framework\Model\EntityCollection\AbstractEntityCollection;

class FeatureGroupsWithFeatures extends ForeignEntitiesEntityCollectionDecorator
{
    public function __construct(AbstractEntityCollection $collection)
    {
        parent::__construct('feature_featuregroup_id', 'Feature', $collection, 'Features');
    }
}