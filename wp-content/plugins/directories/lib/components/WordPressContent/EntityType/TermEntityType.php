<?php
namespace SabaiApps\Directories\Component\WordPressContent\EntityType;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;
use SabaiApps\Framework\User\AbstractIdentity;

class TermEntityType extends Entity\Type\AbstractType
{
    protected function _entityTypeInfo()
    {
        return [ 
            'label' => __('Taxonomy term', 'directories'),
            'table_name' => $GLOBALS['wpdb']->terms,
            'table_joins' => array(
                $GLOBALS['wpdb']->term_taxonomy => array(
                    'alias' => 'tt',
                    'on' => 'term_id = %2$s.term_id'
                ),
                $this->_application->getDB()->getResourcePrefix() . 'entity_bundle' => array(
                    'alias' => 'bundle',
                    'on' => 'bundle_name = tt.taxonomy'
                ),
            ),
            'properties' => array(
                'id' => array(
                    'type' => 'entity_id',
                    'column_type' => Application::COLUMN_INTEGER, 
                    'column' => 'term_id',
                ),
                'title' => array(
                    'type' => 'entity_title',
                    'column_type' => Application::COLUMN_VARCHAR, 
                    'column' => 'name',
                ),
                'slug' => array(
                    'type' => 'entity_slug',
                    'column_type' => Application::COLUMN_VARCHAR, 
                    'column' => 'slug',
                ),
                'bundle_name' => array(
                    'type' => 'entity_bundle_name',
                    'column_type' => Application::COLUMN_VARCHAR,
                    'column' => 'tt.taxonomy',
                ),
                'bundle_type' => array(
                    'type' => 'entity_bundle_type',
                    'column_type' => Application::COLUMN_VARCHAR,
                    'column' => 'bundle.bundle_type',
                ),
                'parent' => array(
                    'type' => 'entity_term_parent',
                    'column_type' => Application::COLUMN_INTEGER, 
                    'column' => 'tt.parent',
                ),
                'content' => array(
                    'type' => 'wp_term_description',
                    'column_type' => Application::COLUMN_TEXT, 
                    'column' => 'tt.description',
                ),
                'published' => false,
                'modified' => false,
                'author' => false,
                'status' => false,
            ),
            'unique_slug' => true,
        ];
    }
    
    public function entityTypeEntityById($entityId)
    {
        if (!$term = $this->_getTermById($entityId)) return false;
        
        return new TermEntity($term);
    }
    
    public function entityTypeEntityBySlug($bundleName, $slug)
    {
        if (!$term = get_term_by('slug', $slug, $bundleName)) return false;
        
        return new TermEntity($term);
    }
    
    public function entityTypeEntityByTitle($bundleName, $title)
    {
        if (!$term = get_term_by('name', $title, $bundleName)) return false;
        
        return new TermEntity($term);
    }

    public function entityTypeEntitiesByIds(array $entityIds, $bundleName = null, $lang = null)
    {
        //global $wpdb;
        //$terms = $wpdb->get_results(sprintf(
        //    'SELECT t.*, tt.* FROM %s AS t INNER JOIN %s AS tt ON t.term_id = tt.term_id WHERE t.term_id IN (%s)',
        //    $wpdb->terms,
        //    $wpdb->term_taxonomy,
        //    implode(',', array_map('intval', $entityIds))
        //));
        $entities = [];
        $args = [
            'include' => array_map('intval', $entityIds),
            'hide_empty' => false,
            'lang' => $lang, // used by PolyLang
        ];
        if (isset($bundleName)) $args ['taxonomy'] = $bundleName;
        foreach (get_terms($args) as $term) {
            $entities[$term->term_id] = new TermEntity($term);
        }
        return $entities;
    }
    
    public function entityTypeEntitiesBySlugs($bundleName, array $slugs, $lang = null)
    {
        return $this->_getEntitiesBy($bundleName, 'slug', $slugs, $lang);
    }
    
    public function entityTypeEntitiesByTitles($bundleName, array $titles)
    {
        return $this->_getEntitiesBy($bundleName, 'name', $titles);
    }
    
    protected function _getEntitiesBy($bundleName, $by, $values, $lang = null)
    {
        $terms = get_terms(array(
            'hide_empty' => false,
            'taxonomy' => $bundleName,
            $by => $values,
            'lang' => $lang, // used by PolyLang
        ));
        if (is_wp_error($terms)) {
            throw new Exception\RuntimeException($terms->get_error_message());
        }
        $entities = [];
        foreach ($terms as $term) {
            $entities[$term->term_id] = new TermEntity($term);
        }
        return $entities;
    }

    public function entityTypeCreateEntity(Entity\Model\Bundle $bundle, array $properties, AbstractIdentity $identity = null)
    {
        $term = wp_insert_term(
            isset($properties['title']) ? $properties['title'] : '',
            $bundle->name,
            array(
                'description' => isset($properties['content']) ? $properties['content'] : '',
                'parent' => !empty($properties['parent']) ? $properties['parent'] : 0,
                'slug' => isset($properties['slug']) ? $properties['slug'] : '',
            )
        );
        if (is_wp_error($term)) {
            if ($term->get_error_code() !== 'term_exists') {
                throw new Exception\RuntimeException($term->get_error_message());
            }
            if (isset($properties['slug'])) {
                if (!$_term = get_term_by('slug', $properties['slug'], $bundle->name)) {
                    throw new Exception\RuntimeException('Invalid taxonomy term. Slug: ' . $properties['slug'] . '; Taxonomy: ' . $bundle->name);
                }
            } elseif (isset($properties['title'])) {
                if (!$_term = get_term_by('name', $properties['title'], $bundle->name)) {
                    throw new Exception\RuntimeException('Invalid taxonomy term. Name: ' . $properties['title'] . '; Taxonomy: ' . $bundle->name);
                }
            } else {
                throw new Exception\RuntimeException('Invalid taxonomy term. Taxonomy: ' . $bundle->name);
            }
        } else {
            if (!$_term = get_term_by('id', $term['term_id'], $bundle->name)) {
                throw new Exception\RuntimeException('Invalid taxonomy term. ID: ' . $term['term_id'] . '; Taxonomy: ' . $bundle->name);
            }
        }
        
        return new TermEntity($_term);
    }

    
    public function entityTypeUpdateEntity(Entity\Type\IEntity $entity, Entity\Model\Bundle $bundle, array $properties)
    {    
        if (!$term = get_term_by('id', $entity->getId(), $bundle->name)) {
            throw new Exception\RuntimeException(sprintf('Cannot save non existent entity (Bundle: %s, ID: %d).', $bundle->name, $entity->getId()));
        }
        
        $args = [];
        foreach ($properties as $property => $value) {
            switch ($property) {
                case 'title':
                    $args['name'] = $value;
                    break;
                case 'content':
                    $args['description'] = $value;
                    break;
                case 'slug':
                case 'parent':
                    $args[$property]= $value;
                    break;
            }
        }        
        $term = wp_update_term($term->term_id, $term->taxonomy, $args);
        if (is_wp_error($term)) {
            throw new Exception\RuntimeException($term->get_error_message());
        }
        if (!$_term = get_term_by('id', $term['term_id'], $bundle->name)) {
            throw new Exception\RuntimeException('Invalid taxonomy term. ID: ' . $term['term_id'] . '; Taxonomy: ' . $bundle->name);
        }
        
        return new TermEntity($_term);
    }
    
    public function entityTypeTrashEntities(array $entities, array $formValues = null){}

    public function entityTypeDeleteEntities(array $entities)
    {
        foreach ($entities as $entity) {
            wp_delete_term($entity->getId(), $entity->getBundleName());
        }
    }
    
    public function entityTypeParentEntityIds($entity, $bundleName = null)
    {
        return $this->_getParentTerms($entity, $bundleName, true);
    }
    
    public function entityTypeParentEntities($entity, $bundleName = null)
    {
        return $this->_getParentTerms($entity, $bundleName);
    }
    
    protected function _getParentTerms($entity, $bundleName, $idOnly = false)
    {
        $ret = [];
        if (is_object($entity)) {
            $id = $entity->getId();
            $bundleName = $entity->getBundleName();
        } else {
            $id = $entity;
        }
        if (!$term = get_term_by('id', $id, $bundleName)) {
            throw new Exception\RuntimeException('Invalid taxonomy term. ID: ' . $id . '; Taxonomy: ' . $bundleName);
        }
        $this->_loadParentTerms($term, $term->taxonomy, $ret, $idOnly);
        
        return empty($ret) ? [] : array_reverse($ret);
    }
    
    protected function _loadParentTerms($term, $taxonomy, array &$parents = [], $idOnly = false)
    {
        if ($term->parent) {
            $parent = get_term($term->parent, $taxonomy);
            if (!is_wp_error($parent)) {
                $parents[] = $idOnly ? $parent->term_id : new TermEntity($parent);
                $this->_loadParentTerms($parent, $taxonomy, $parents, $idOnly);
            }
        }
    }
    
    public function entityTypeDescendantEntityIds($entity, $bundleName = null)
    {
        if (is_object($entity)) {
            $id = $entity->getId();
            $taxonomy = $entity->getBundleName();
        } else {
            $id = $entity;
            if (isset($bundleName)) {
                $taxonomy = $bundleName;
            } else {
                if (!$term = $this->_getTermById($id)) return [];
                
                $taxonomy = $term->taxonomy;
            }
        }
        $ret = get_term_children($id, $taxonomy);
        if (is_wp_error($ret)) {
            throw new Exception\RuntimeException($ret->get_error_message());
        }
        return $ret;
    }
    
    public function entityTypeHierarchyDepth(Entity\Model\Bundle $bundle)
    {
        return $this->_application->Filter('wordpress_taxonomy_term_max_depth', 5, array($bundle->name));
    }
    
    public function entityTypeContentCount(Entity\Model\Bundle $bundle, $termIds)
    {
        $count = [];
        if (($terms = get_terms($bundle->name, array('pad_counts' => true)))
            && !is_wp_error($terms)
        ) {
            foreach (array_keys($terms) as $key) {
                if (!in_array($terms[$key]->term_id, $termIds)) {
                    unset($terms[$key]);
                    continue;
                }
                $count[$terms[$key]->term_id]['_all'] = $terms[$key]->count;
            }
        }
        return $count;
    }
    
    public function entityTypeRandomEntityIds($bundleName, $num)
    {
        $count = wp_count_terms($bundleName);
        return array_values(get_terms(array(
            'hide_empty' => false,
            'fields' => 'ids',
            'taxonomy' => $bundleName,
            'offset' => $count <= $num ? 0 : rand(0, $count - $num),
            'number' => $num,
        )));
    }
    
    public function entityTypeCount($bundleName)
    {
        return wp_count_terms($bundleName);
    }
    
    public function entityTypeEntityStatusLabel($status){}
    
    protected function _getTermById($id)
    {
        if (!$taxonomy = \SabaiApps\Directories\Component\WordPressContent\WordPressContentComponent::getTermTaxonomy($id)) return;

        return get_term_by('id', $id, $taxonomy);
    }
}