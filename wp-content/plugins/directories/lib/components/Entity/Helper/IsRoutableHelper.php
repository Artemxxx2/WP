<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Exception;

class IsRoutableHelper
{
    public function help(Application $application, $bundle, $action, Entity\Type\IEntity $entity = null, $default = true)
    {
        $bundle = $application->Entity_Bundle($bundle, null, '', true);
        $result = $this->_isRoutable($application, $bundle, $action, $entity);

        // Invoke components
        $result = $application->Filter('entity_is_routable', $result, array($bundle, $action, $entity));
        $result = $application->Filter('entity_is_' . $bundle->type . '_routable', $result, array($bundle, $action, $entity));

        if ($result === null) $result = $default;

        return $result;
    }

    protected function _isRoutable(Application $application, Entity\Model\Bundle $bundle, $action, Entity\Type\IEntity $entity = null)
    {
        switch ($action) {
            case 'link':
                if (!isset($entity)) {
                    throw new Exception\InvalidArgumentException('Missing context entity');
                }

                // Allow author only if non-public
                if (empty($bundle->info['public'])) {
                    return $entity->isPublished() // non-public bundle entity is always public
                        && $application->Entity_IsAuthor($entity);
                }

                if ($entity->isPublished()) return true;

                // Check read private permission if private
                if (!empty($bundle->info['privatable'])
                    && $entity->isPrivate()
                ) {
                    return $application->HasPermission('entity_read_private_' . $bundle->name)
                        || $application->Entity_IsAuthor($entity);
                }

                // Allow draft/pending/scheduled status if has permission to edit
                return ($entity->isDraft() || $entity->isPending() || $entity->isScheduled())
                    && ($application->Entity_IsAuthor($entity) || $application->HasPermission('entity_edit_others_' . $bundle->name));
            case 'view':
                if (!isset($entity)) {
                    throw new Exception\InvalidArgumentException('Missing context entity');
                }

                // Allow author only if non-public
                if (empty($bundle->info['public'])) {
                    return $entity->isPublished() // non-public bundle entity is always public
                        && $application->Entity_IsAuthor($entity);
                }

                // Check read permission if published
                if ($entity->isPublished()) {
                    return $application->HasPermission('entity_read_' . $bundle->name);
                }

                // Check read private permission if private
                if (!empty($bundle->info['privatable'])
                    && $entity->isPrivate()
                ) {
                    return $application->HasPermission('entity_read_private_' . $bundle->name)
                        || $application->Entity_IsAuthor($entity);
                }

                // Allow draft/pending/scheduled status if has permission to edit
                return ($entity->isDraft() || $entity->isPending() || $entity->isScheduled())
                    && ($application->Entity_IsAuthor($entity) || $application->HasPermission('entity_edit_others_' . $bundle->name));
            case 'list':
                if (isset($entity)) {
                    if (!empty($bundle->info['parent'])) {
                        if (!$application->Entity_IsRoutable($entity->getBundleName(), 'view', $entity)) {
                            return false;
                        }
                    }
                }
                return;
            case 'add':
                if (!isset($entity)) {
                    if (!empty($bundle->info['parent'])) {
                        throw new Exception\InvalidArgumentException('Missing context entity');
                    }
                }
                return $application->HasPermission('entity_create_' . $bundle->name);
            case 'edit':
                if (!isset($entity)) throw new Exception\InvalidArgumentException('Missing entity');

                if (!$application->Entity_IsAuthor($entity)) {
                    if (!$application->HasPermission('entity_edit_others_' . $entity->getBundleName())) return false;

                    if (empty($bundle->info['public'])) return true;

                    if ($entity->isPublished()) {
                        return $application->HasPermission('entity_edit_published_' . $entity->getBundleName());
                    } elseif ($entity->isPrivate()) {
                        return $application->HasPermission('entity_edit_private_' . $entity->getBundleName());
                    }
                    return true;
                }
                if (!$application->HasPermission('entity_edit_' . $entity->getBundleName())) return false;

                if (empty($bundle->info['public'])) return true;

                if ($entity->isPublished()) {
                    return $application->HasPermission('entity_edit_published_' . $entity->getBundleName());
                }
                return true;
            case 'delete':
                if (!isset($entity)) throw new Exception\InvalidArgumentException('Missing entity');

                if (!$application->Entity_IsAuthor($entity)) {
                    if (!$application->HasPermission('entity_delete_others_' . $entity->getBundleName())) return false;

                    if (empty($bundle->info['public'])) return true;

                    if ($entity->isPublished()) {
                        return $application->HasPermission('entity_delete_published_' . $entity->getBundleName());
                    } elseif ($entity->isPrivate()) {
                        return $application->HasPermission('entity_delete_private_' . $entity->getBundleName());
                    }
                    return true;
                }
                if (!$application->HasPermission('entity_delete_' . $entity->getBundleName())) return false;

                if (empty($bundle->info['public'])) return true;

                if ($entity->isPublished()) {
                    return $application->HasPermission('entity_delete_published_' . $entity->getBundleName());
                }
                return true;
            case 'submit':
                if (!isset($entity)) throw new Exception\InvalidArgumentException('Missing entity');

                if (!$application->isComponentLoaded('FrontendSubmit')) return false;

                if (!$entity->isDraft()) return false;

                if (!$application->Entity_IsAuthor($entity)) {
                    return $application->HasPermission('entity_edit_others_' . $entity->getBundleName());
                } else {
                    return $application->HasPermission('entity_edit_' . $entity->getBundleName());
                }
            default:
                if (!isset($entity)) throw new Exception\InvalidArgumentException('Missing entity');
        }
    }
}
