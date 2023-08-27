<?php
namespace SabaiApps\Directories\Component\Payment\Model;

use SabaiApps\Framework\Model\EntityCollection\ForeignEntityEntityCollectionDecorator;
use SabaiApps\Framework\Model\EntityCollection\AbstractEntityCollection;

class FeaturesWithFeatureGroup extends ForeignEntityEntityCollectionDecorator
{
    public function __construct(AbstractEntityCollection $collection)
    {
        parent::__construct('featuregroup_id', 'FeatureGroup', $collection);
    }
}