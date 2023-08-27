<?php
namespace SabaiApps\Directories\Component\Entity\Model;

use SabaiApps\Framework\Model\EntityCollection\AbstractEntityCollectionDecorator;
use SabaiApps\Framework\Model\EntityCollection\AbstractEntityCollection;

abstract class AbstractWithBundleEntityCollectionDecorator extends AbstractEntityCollectionDecorator
{
    protected $_bundles, $_bundleNameVar, $_bundleObjectVarName;

    public function __construct(AbstractEntityCollection $collection, $bundleNameVar = 'bundle_name', $bundleObjectVarName = 'Bundle')
    {
        parent::__construct($collection);
        $this->_bundleNameVar = $bundleNameVar;
        $this->_bundleObjectVarName = $bundleObjectVarName;
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->_collection->rewind();
        if (!isset($this->_bundles)) {
            $this->_bundles = [];
            if ($this->_collection->count() > 0) {
                $bundle_names = [];
                while ($this->_collection->valid()) {
                    if ($bundle_name = $this->_collection->current()->{$this->_bundleNameVar}) {
                        $bundle_names[$bundle_name] = $bundle_name;
                    }
                    $this->_collection->next();
                }
                if (!empty($bundle_names)) {
                    $this->_bundles = $this->_model->Entity_Bundles($bundle_names);
                }
                $this->_collection->rewind();
            }
        }
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        $current = $this->_collection->current();
        if (($bundle_name = $current->{$this->_bundleNameVar})
            && isset($this->_bundles[$bundle_name])
        ) {
            $current->assignObject($this->_bundleObjectVarName, $this->_bundles[$bundle_name]);
        } else {
            $current->assignObject($this->_bundleObjectVarName);
        }

        return $current;
    }
}