<?php
namespace SabaiApps\Directories\Component\Review\SystemTool;

use SabaiApps\Directories\Component\System\Tool\AbstractTool;

class RecalculateReviewRatingsSystemTool extends AbstractTool
{
    protected function _systemToolInfo()
    {
        return [
            'label' => __('Recalculate review ratings', 'directories-reviews'),
            'description' => __('This tool will recalculate review ratings for each content item.', 'directories-reviews'),
            'weight' => 60,
        ];
    }

    public function systemToolInit(array $settings, array &$storage, array &$logs)
    {
        $ret = [];
        $voting_model = $this->_application->getModel(null, 'Voting');
        foreach ($this->_application->Entity_Bundles() as $bundle) {
            if (empty($bundle->info['review_enable'])) continue;

            if (!$review_bundle = $this->_application->Entity_Bundle('review_review', $bundle->component, $bundle->group)) continue;

            $entity_type_info = $this->_application->Entity_Types_impl($review_bundle->entitytype_name)->entityTypeInfo();

            // Delete votes for trashed reviews
            $voting_model->getGateway('Vote')->deleteEntityVotes(
                $entity_type_info['table_name'],
                $entity_type_info['properties']['id']['column'],
                @$entity_type_info['properties']['bundle_name']['column'],
                $review_bundle->name,
                @$entity_type_info['properties']['status']['column'],
                $this->_application->Entity_Status($review_bundle->entitytype_name, 'trash'),
                true
            );

            // Fetch reviews without vote entry and cast vote
            $reviews_without_votes = $voting_model->getGateway('Vote')->getMissingEntityIds(
                $entity_type_info['table_name'],
                $entity_type_info['properties']['id']['column'],
                @$entity_type_info['properties']['bundle_name']['column'],
                $review_bundle->name,
                @$entity_type_info['properties']['status']['column'],
                $this->_application->Entity_Status($review_bundle->entitytype_name, 'publish'),
                true
            );
            if (!empty($reviews_without_votes)) {
                foreach ($this->_application->Entity_Entities($review_bundle->entitytype_name, $reviews_without_votes) as $review) {
                    if (!$parent_entity = $this->_application->Entity_ParentEntity($review, false)) continue;

                    $rating = (array)$review->getSingleFieldValue('review_rating');
                    foreach (array_keys($rating) as $rating_name) {
                        $rating[$rating_name] = $rating[$rating_name]['value'];
                    }
                    $this->_application->Voting_CastVote(
                        $parent_entity,
                        'review_ratings',
                        $rating,
                        array(
                            'reference_id' => $review->getId(),
                            'user_id' => $review->getAuthorId(),
                            'allow_empty' => true,
                        )
                    );
                }
            }

            // Get count for entities with reviews
            $criteria = $voting_model->createCriteria('Vote')
                ->bundleName_is($bundle->name)
                ->fieldName_is('review_ratings');
            $paginator = $voting_model->getRepository('Vote')->paginateByCriteria($criteria, 200);
            foreach ($paginator as $page) {
                $paginator->setCurrentPage($page);
                $task_name = $bundle->name . '-' . $page;
                foreach ($paginator->getElements() as $vote) {
                    $storage[$task_name][$vote->entity_id] = 1;
                }
                $ret[$task_name] = count($storage[$task_name]);
            }
        }

        return $ret;
    }

    public function systemToolRunTask($task, array $settings, $iteration, $total, array &$storage, array &$logs)
    {
        if (empty($storage[$task])) return false; // this should never happen

        $task_parts = explode('-', $task);
        if (empty($task_parts[0])
            || (!$bundle = $this->_application->Entity_Bundle($task_parts[0]))
        ) return false;

        // Recalculate review ratings
        $entity_ids = array_keys($storage[$task]);
        unset($storage[$task]);
        $entities = $this->_application->Entity_Query($bundle->entitytype_name)
            ->fieldIsIn('id', $entity_ids)
            ->fetch();
        foreach ($entities as $entity) {
            // Calculate results and update entity
            $this->_application->Voting_Votes_recalculate($entity, 'review_ratings');
        }

        $count = count($entity_ids);
        $label = $bundle->getGroupLabel() . ' - ' . $bundle->getLabel();
        $logs['success'][] = sprintf(
            'Recalculated review ratings for %s (%d)',
            isset($lang) ? $label . '[' . $lang . ']' : $label,
            $count
        );

        return $count;
    }
}