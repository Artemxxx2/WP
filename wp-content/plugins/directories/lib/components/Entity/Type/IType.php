<?php
namespace SabaiApps\Directories\Component\Entity\Type;

use SabaiApps\Directories\Component\Entity\Model;
use SabaiApps\Framework\User\AbstractIdentity;

interface IType
{
    /**
     * @return mixed Array if no key supplied
     * @param string $key
     */
    public function entityTypeInfo($key = null);
    /**
     * @return IEntity
     * @param int $entityId
     */
    public function entityTypeEntityById($entityId);
    /**
     * @return IEntity
     * @param string $bundleName
     * @param string $slug
     */
    public function entityTypeEntityBySlug($bundleName, $slug);
    /**
     * @return IEntity
     * @param string $bundleName
     * @param string $title
     */
    public function entityTypeEntityByTitle($bundleName, $title);
    /**
     * @param array $entityIds
     * @param string $bundleName
     * @return Traversable Instances of IEntity
     */
    public function entityTypeEntitiesByIds(array $entityIds, $bundleName = null, $lang = null);
        /**
     * @return Traversable Instances of IEntity
     * @param string $bundleName
     * @param array $slugs
     * @param string|null $lang
     */
    public function entityTypeEntitiesBySlugs($bundleName, array $slugs, $lang = null);
    /**
     * @return IEntity
     */
    public function entityTypeCreateEntity(Model\Bundle $bundle, array $properties, AbstractIdentity $identity = null);
    /**
     * @return IEntity
     */
    public function entityTypeUpdateEntity(IEntity $entity, Model\Bundle $bundle, array $properties);
    /**
     * @param array $entities Array of IEntity indexed by entity ID
     * @param array $formValues Array of values sent from trash entity form
     */
    public function entityTypeTrashEntities(array $entities, array $formValues = null);
    /**
     * @param array $entities Array of IEntity indexed by entity ID
     */
    public function entityTypeDeleteEntities(array $entities);
    
    public function entityTypeRandomEntityIds($bundleName, $num);
    
    public function entityTypeEntityStatusLabel($status);
    /**
     * @param string|null $operator
     * @param string|array|null $bundleName
     * @return SabaiApps\Directories\Component\Entity\Type\Query
     */
    public function entityTypeGetQuery($operator = null, $bundleName = null);
}