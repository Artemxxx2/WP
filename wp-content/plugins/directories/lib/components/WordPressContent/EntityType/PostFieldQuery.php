<?php
namespace SabaiApps\Directories\Component\WordPressContent\EntityType;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity\Type\FieldQuery;
use SabaiApps\Framework\Criteria\ContainsCriteria;

class PostFieldQuery extends FieldQuery
{
    public function taxonomyTermTitleContains($taxonomyBundleName, $taxonomyBundleType, $string, $name = null)
    {
        if (!isset($name)) $name = $taxonomyBundleType;

        $this->addCriteria(new ContainsCriteria($this->_getTaxonomyTermNameTarget($taxonomyBundleName, $name), $string), $name);
    }
    
    protected function _getTaxonomyTermNameTarget($taxonomy, $name)
    {
        $terms = 'terms_' . $name;
        $target = [
            'tables' => [],
            'column' => $terms . '.name',
            'column_type' => Application::COLUMN_VARCHAR,
        ];
        if (!$this->hasNamedCriteria($name)) { // add tables only when they have not yet been added
            $tr = 'tr_' . $name;
            $tt = 'tt_' . $name;
            $target['tables'] = [
                $GLOBALS['wpdb']->term_relationships => array(
                    'alias' => $tr,
                    'on' => 'object_id = %3$s',
                ),
                $GLOBALS['wpdb']->term_taxonomy => array(
                    'alias' => $tt,
                    'on' => 'term_taxonomy_id = ' . $tr . '.term_taxonomy_id AND %1$s.taxonomy = \'' . esc_sql($taxonomy) . '\'',
                    //'join_type' => 'INNER',
                ),
                $GLOBALS['wpdb']->terms => array(
                    'alias' => $terms,
                    'on' => 'term_id = ' . $tt . '.term_id',
                    //'join_type' => 'INNER',
                ),
            ];
        }
        return $target;
    }
}