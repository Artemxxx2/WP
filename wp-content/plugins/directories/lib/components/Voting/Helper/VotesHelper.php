<?php
namespace SabaiApps\Directories\Component\Voting\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Entity\Model\Bundle;

class VotesHelper
{
    protected $_votes = [];

    public function help(Application $application, $entityId = null, $type = null, $userId = null)
    {
        if (!isset($userId)) {
            if ($application->getUser()->isAnonymous()) return;

            $userId = $application->getUser()->id;
        }

        if (!isset($this->_votes[$userId])) return;

        if (isset($entityId)) {
            if ($entityId instanceof IEntity) $entityId = $entityId->getId();

            if (!isset($this->_votes[$userId][$entityId])) return;

            if (!isset($type)) return $this->_votes[$userId][$entityId];

            return isset($this->_votes[$userId][$entityId]['voting_' . $type]) ? $this->_votes[$userId][$entityId]['voting_' . $type] : null;
        }
        return $this->_votes;
    }

    public function load(Application $application, $bundle, array $entityIds)
    {
        if (!isset($userId)) {
            if ($application->getUser()->isAnonymous()) return;

            $userId = $application->getUser()->id;
        }

        $entity_ids = [];
        foreach ($entityIds as $entity_id) {
            if (isset($this->_votes[$userId][$entity_id])) continue;

            $entity_ids[] = $entity_id;
        }

        if (empty($entity_ids)) return;

        $votes = $application->getModel(null, 'Voting')->getGateway('Vote')->getVotes(
            $bundle instanceof Bundle ? $bundle->name : $bundle,
            $entity_ids,
            $application->getUser()->id,
            ['voting_updown', 'voting_rating', 'voting_bookmark']
        );
        foreach ($votes as $field_name => $_votes) {
            foreach ($_votes as $entity_id => $value) {
                $this->_votes[$userId][$entity_id][$field_name] = $value;
            }
        }
    }

    public function recalculate(Application $application, IEntity $entity, $fieldName)
    {
        // Make sure all the fields are loaded
        $application->Entity_Field_load($entity, null, false, false);

        // Calculate results
        $results = $application->getModel(null, 'Voting')
            ->getGateway('Vote')
            ->getResults($entity->getBundleName(), $entity->getId(), $fieldName);

        // Field values for the entity
        if (!empty($results)) {
            $values = [];
            foreach (array_keys($results) as $name) {
                $values[] = ['name' => $name] + $results[$name];
            }
        } else {
            $values = false;
        }

        // Update field and return its value
        return $application->Entity_Save($entity, [$fieldName => $values])->getSingleFieldValue($fieldName);
    }
}