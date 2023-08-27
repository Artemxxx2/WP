<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\System\Progress;

class UpdateTermContentCountHelper
{
    public function help(Application $application, $taxonomy, array $terms, Bundle $contentBundle, Progress $progress = null)
    {
        if (!$taxonomy_bundle = $application->Entity_Bundle($taxonomy)) return;
            
        $term_ids = array_keys($terms);
        // Count the total number of child entity for each term
        $counts = $application->Entity_Query($contentBundle->entitytype_name, $contentBundle->name)
            ->taxonomyTermIdIn($taxonomy_bundle->type, $term_ids, true)
            ->fieldIs('status', $application->Entity_Status($contentBundle->entitytype_name, 'publish'))
            ->groupByField($taxonomy_bundle->type)
            ->count();
        $merged_counts = [];
            
        if (!empty($taxonomy_bundle->info['is_hierarchical'])) {
            // Fetch parent terms for each term and get its merged content counts
            $entity_type = $taxonomy_bundle->entitytype_name;
            if ($entity_type_impl = $application->Entity_Types_impl($entity_type, true)) {
                $parent_term_ids = [];
                foreach ($term_ids as $term_id) {
                    $term = $terms[$term_id];
                    if ($term->getParentId()) {
                        foreach ($entity_type_impl->entityTypeParentEntityIds($term, $taxonomy) as $parent_term_id) {
                            if (!isset($terms[$parent_term_id])) {
                                $parent_term_ids[$parent_term_id] = $parent_term_id;
                            }
                        }
                    }
                }
                $all_term_ids = $term_ids;
                if (!empty($parent_term_ids)) {
                    foreach ($application->Entity_Entities($entity_type, $parent_term_ids) as $parent_term_id => $parent_term) {
                        $terms[$parent_term_id] = $parent_term;
                        $all_term_ids[] = $parent_term_id;
                    }
                }
                $merged_counts = $entity_type_impl->entityTypeContentCount($taxonomy_bundle, $all_term_ids);
            }
        }
            
        // Update content count for each taxonomy term 
        foreach (array_keys($terms) as $term_id) {
            $term = $terms[$term_id];
            $application->Entity_Field_load($term);
            $current_count = $_current_count = (array)@$term->getSingleFieldValue('entity_term_content_count');

            // Clear deprecated data, compat with 1.1.x
            unset($current_count['directory_listing'], $current_count['_directory_listing']);

            if (empty($counts[$term_id])) {
                if (empty($merged_counts[$term_id])) {
                    // No count or merged count, so remove entry
                    unset($current_count[$contentBundle->type], $current_count['_' . $contentBundle->type]);
                } else {
                    // Update merged count
                    $current_count['_' . $contentBundle->type] = isset($merged_counts[$term_id][$contentBundle->type]) ?
                        $merged_counts[$term_id][$contentBundle->type] :
                        $merged_counts[$term_id]['_all'];
                    // Update own count as 0 if originally queried term or no current count
                    if (in_array($term_id, $term_ids)
                        || !isset($current_count[$contentBundle->type])
                    ) {
                        $current_count[$contentBundle->type] = 0;
                    }
                }
            } else {
                $value = $merged = $counts[$term_id];
                if (isset($merged_counts[$term_id][$contentBundle->type])) {
                    $merged = $merged_counts[$term_id][$contentBundle->type];
                } elseif (isset($merged_counts[$term_id]['_all'])) {
                    $merged = $merged_counts[$term_id]['_all'];
                }
                // Set the new content count
                $current_count[$contentBundle->type] = $value;
                $current_count['_' . $contentBundle->type] = $merged;
            }
            // Create an array of content count for saving
            $counts_for_save = [];
            foreach ($current_count as $content_bundle_type => $content_count) {
                if (strpos($content_bundle_type, '_') === 0) continue;

                $counts_for_save[] = array(
                    'content_bundle_name' => $content_bundle_type,
                    'value' => $content_count,
                    'merged' => $current_count['_' . $content_bundle_type],
                );
            }
            if (empty($counts_for_save)) {
                if (!empty($_current_count)) {
                    $application->Entity_Save($term, array('entity_term_content_count' => false));
                }
            } else {
                $application->Entity_Save($term, array('entity_term_content_count' => $counts_for_save));
            }
        }
    }
}