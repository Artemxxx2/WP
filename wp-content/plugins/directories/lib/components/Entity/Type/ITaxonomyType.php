<?php
namespace SabaiApps\Directories\Component\Entity\Type;

use SabaiApps\Directories\Component\Entity\Model;

interface ITaxonomyType extends IType
{
    /**
     * @return Traversable Instances of IEntity
     * @param string $bundleName
     * @param array $titles
     */
    public function entityTypeEntitiesByTitles($bundleName, array $titles);
    public function entityTypeParentEntityIds($entity, $bundleName = null);    
    public function entityTypeParentEntities($entity, $bundleName = null);
    public function entityTypeDescendantEntityIds($entity, $bundleName = null);
    public function entityTypeHierarchyDepth(Model\Bundle $bundle);
    public function entityTypeContentCount(Model\Bundle $bundle, $termIds);
    public function entityTypeCount($bundleName);
}