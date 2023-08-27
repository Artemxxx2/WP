<?php
namespace SabaiApps\Directories\Component\Entity\FieldType;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Entity\Model\Bundle;

trait QueryableTermsTrait
{
    public function fieldQueryableInfo(IField $field, $inAdmin = false)
    {
        $tip = __('Enter taxonomy term IDs or slugs (may be mixed) separated with commas.', 'directories');
        if (!$inAdmin) {
            $tip .= ' '. __('Enter "_current_" for the current taxonomy term or taxonomy terms of the current post if any.', 'directories');
        }
        return [
            'example' => 'term,3,another-term,12',
            'tip' => $tip,
        ];
    }
    
    public function fieldQueryableQuery(Query $query, $fieldName, $paramStr, Bundle $bundle)
    {
        if (!$term_ids = $this->_queryableParams($paramStr)) return;

        if (empty($bundle->info['is_taxonomy'])) {
            if (!$taxonomy_bundle = $this->_application->Entity_Bundle($fieldName, $bundle->component, $bundle->group)) return;
        } else {
            $taxonomy_bundle = $bundle;
        }

        $include = $slugs = [];
        foreach (array_keys($term_ids) as $k) {
            // ID
            if (is_numeric($term_ids[$k])) {
                $include[] = $term_ids[$k];
                continue;
            }

            // Current post
            if ($term_ids[$k] === '_current_'
                || $term_ids[$k] === '_current_parent_'
            ) {
                if ($entity = $this->_getCurrentEntity()) {
                    if ($entity->isTaxonomyTerm()) {
                        if ($entity->getBundleType() === $taxonomy_bundle->type) {
                            $include[] = $entity->getId();
                        }
                    } else {
                        if ($terms = $entity->getFieldValue($taxonomy_bundle->type)) {
                            foreach ($terms as $term) {
                                if ($term_ids[$k] === '_current_parent_'
                                    && ($parent_slugs = $term->getCustomProperty('parent_slugs'))
                                    && ($parent_slug = array_pop($parent_slugs))
                                ) {
                                    $slugs[] = $parent_slug;
                                } else {
                                    $include[] = $term->getId();
                                }
                            }
                        }
                    }
                }
                continue;
            }

            // Slug
            $slugs[] = $term_ids[$k];
        }
        if (!empty($slugs)) {
            foreach ($this->_application->Entity_Types_impl($taxonomy_bundle->entitytype_name)->entityTypeEntitiesBySlugs($taxonomy_bundle->name, $slugs) as $term) {
                $include[] = $term->getId();
            }
        }

        if (!empty($include)) {
            if (empty($bundle->info['is_taxonomy'])) {
                $query->taxonomyTermIdIn(
                    $taxonomy_bundle->type,
                    $include,
                    empty($taxonomy_bundle->info['is_hierarchical']),
                    $fieldName . '_entity_query', // table alias
                    $fieldName . '_entity_query' // criteria name
                );
            } else {
                $query->fieldIsIn($fieldName, $include);
            }
        }
    }
}