<?php
namespace SabaiApps\Directories\Component\Voting\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Component\Entity;

class CastVoteHelper
{
    public function help(Application $application, Entity\Type\IEntity $entity, $fieldName, array $values, array $options = [])
    {
        $default = array(
            'comment' => '',
            'user_id' => null,
            'reference_id' => null, // required for edit/delte vote
            'delete' => false,
            'edit' => false,
            'allow_empty' => false,
            'allow_multiple' => false,
        );
        $options += $default;

        $new_values = $prev_values = [];

        // Get user ID
        $user_id = isset($options['user_id']) ? $options['user_id'] : $application->getUser()->id;

        // Require a valid ip address for anonymous votes
        if (empty($user_id) && empty($options['reference_id'])) {
            if (!$ip = $this->_getIp()) {
                throw new Exception\RuntimeException(__('You do not have the permission to perform this action.', 'directories'));
            }
            $hash = hash('sha1', $entity->getId() . $fieldName . $ip);
        } else {
            $hash = '';
        }

        if (empty($options['allow_multiple'])) {
            $votes = $application->getModel('Vote', 'Voting')
                ->bundleName_is($entity->getBundleName())
                ->entityId_is($entity->getId())
                ->fieldName_is($fieldName);
            if (!empty($options['reference_id'])) {
                $votes->referenceId_is($options['reference_id']);
            } else {
                $votes->userId_is($user_id);
            }
            if (!empty($hash)) {
                $votes->hash_is($hash);
            }
            $votes = $votes->fetch('created', 'DESC')->getArray(null, 'name');
            foreach ($values as $name => $value) {
                $new_values[$name] = $value;
                if (isset($votes[$name])) {
                    // Has voted before
                    $prev_values[$name] = $votes[$name]->value;
                    if ($this->_isSkipIfEmpty($options, $value)
                        || ($votes[$name]->value == $value && !$options['edit']) // value has not changed
                    ) {
                        // Undo vote by deleting
                        $votes[$name]->markRemoved();
                        $new_values[$name] = false;
                    } else {
                        // Update vote
                        $votes[$name]->value = $value;
                        $votes[$name]->level = round($value);
                    }
                } elseif (!$options['delete']) {
                    if ($this->_isSkipIfEmpty($options, $value)) continue;

                    $prev_values[$name] = false;
                    // New vote
                    $votes[$name] = $this->_createVote($application, $entity, $fieldName, $value, $user_id, $options['comment'], $name, $options['reference_id'], $hash);
                }
            }
            foreach (array_diff(array_keys($votes), array_keys($values)) as $vote_removed_key) {
                $votes[$vote_removed_key]->markRemoved();
            }
        } else {
            foreach ($values as $name => $value) {
                if ($this->_isSkipIfEmpty($options, $value)) continue;

                $new_values[$name] = $value;
                $prev_values[$name] = false;
                // New vote
                $votes[$name] = $this->_createVote($application, $entity, $fieldName, $value, $user_id, $options['comment'], $name, null, $hash);
            }
        }

        $application->getModel(null, 'Voting')->commit();

        // Calculate results and update entity
        $results = $application->Voting_Votes_recalculate($entity, $fieldName);

        foreach (array_keys($votes) as $name) {
            if (!isset($results[$name])) {
                $results[$name] = array('count' => 0, 'sum' => '0.00', 'last_voted_at' => 0);
            }
            $results[$name]['value'] = $new_values[$name];
            $results[$name]['prev_value'] = $prev_values[$name];
            unset($votes[$name]);
        }

        // Notify voted
        $application->Action('voting_entity_voted', array($entity, $fieldName, $results));

        return $results;
    }

    protected function _isSkipIfEmpty(array $settings, $value)
    {
        return empty($value) && !$settings['allow_empty'];
    }

    private function _createVote(Application $application, Entity\Type\IEntity $entity, $fieldName, $value, $userId, $comment, $name, $referenceId = null, $hash = '')
    {
        $vote = $application->getModel(null, 'Voting')->create('Vote')->markNew();
        $vote->entity_type = $entity->getType();
        $vote->entity_id = $entity->getId();
        $vote->bundle_name = $entity->getBundleName();
        $vote->field_name = $fieldName;
        $vote->user_id = $userId;
        $vote->value = $value;
        $vote->comment = $comment;
        $vote->name = $name;
        $vote->reference_id = $referenceId;
        $vote->hash = $hash;
        $vote->level = round($value);

        return $vote;
    }

    private function _getIp()
    {
        foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR') as $key) {
            if (!empty($_SERVER[$key])) {
                return $_SERVER[$key];
            }
        }
        return '';
    }
}
