<?php
namespace SabaiApps\Directories\Component\WordPressContent;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity\Type\FieldQuery;
use SabaiApps\Directories\Component\Field;

class Query
{
    protected $_application;
    
    public function __construct(Application $application)
    {
        $this->_application = $application;
        
        // Enable querying posts by custom fields through WP_Query
        add_action('pre_get_posts', array($this, 'preGetPostsAction'));
        add_action('pre_get_terms', array($this, 'preGetTermsAction'));
        add_filter('get_meta_sql', array($this, 'getMetaSqlFilter'), 10, 6);
        add_filter('posts_orderby', array($this, 'postsOrderbyFilter'), 10, 2);
        add_filter('get_terms_orderby', array($this, 'getTermsOrderbyFilter'), 10, 3);
    }
    
    public function preGetPostsAction($wpQuery)
    {
        if ((!$post_type = $wpQuery->get('post_type'))
            || !is_string($post_type)
            || !$this->_application->getComponent('WordPressContent')->hasPostType($post_type)
        ) return;
        
        if ($orderby = $wpQuery->get('orderby')) {            
            if ($_orderby = $this->_extractSabaiOrderby($orderby)) {
                $wpQuery->set('drts_orderby', $_orderby);
                // Prepend if first orderby param 
                $wpQuery->set('drts_orderby_prepend', isset($_orderby[0]));
            }
            $wpQuery->set('orderby', $orderby);
        }
    }
    
    public function preGetTermsAction($wpTermQuery)
    {
        $qv =& $wpTermQuery->query_vars;
        if (!isset($qv['taxonomy'])) return;
        
        foreach ($qv['taxonomy'] as $taxonomy) {
            if (!$this->_application->getComponent('WordPressContent')->hasTaxonomy($taxonomy)) return;
        }
        
        if (!empty($qv['orderby'])) {
            if ($_orderby = $this->_extractSabaiOrderby($qv['orderby'])) {
                $qv['drts_orderby'] = $_orderby;
                // Prepend if first orderby param 
                $qv['drts_orderby_prepend'] = isset($_orderby[0]);
            }
            if (is_array($qv['orderby'])) $qv['orderby'] = implode(' ', $qv['orderby']); // WP_Term_Query supports sting orderby only
        }
    }
    
    protected function _extractSabaiOrderby(&$orderby)
    {
        $_orderby = [];
        if (is_string($orderby)) { // Ex. '_drts_field_aaa _drts_field_bbb'
            if (strpos($orderby, '_drts_') !== false) {
                $orderby = explode(' ', trim($orderby));
                foreach (array_keys($orderby) as $k) {
                    $orderby[$k] = trim($orderby[$k]);
                    if (strpos($orderby[$k], '_drts_') === 0) {
                        $_orderby[$k] = substr($orderby[$k], strlen('_drts_'));
                        unset($orderby[$k]);
                    }
                }
                $orderby = implode(' ', $_orderby);
            }
        } else { // Ex. array('_drts_field_aaa' => 'DESC', '_drts_field_bbb' => 'ASC')
            foreach (array_keys($orderby) as $k => $field) {
                if (strpos($field, '_drts_') === 0) {
                    $_orderby[$k] = array(substr($field, strlen('_drts_')), $orderby[$field]);
                    unset($orderby[$field]);
                }
            }
        }
        return $_orderby;
    }
    
    public function postsOrderbyFilter($orderby, $query)
    {
        if ($_orderby_sql = $query->get('drts_post_orderby_sql')) {
            if (strlen($orderby)) {
                if ($query->get('drts_orderby_prepend')) {
                    $orderby = $_orderby_sql . ',' . $orderby; 
                } else {
                    $orderby .= ',' . $_orderby_sql;
                }
            } else {
                $orderby = $_orderby_sql;
            }
        }
        return $orderby;
    }
    
    public function getTermsOrderbyFilter($orderby, $queryVars, $taxonomy)
    {
        if (isset($queryVars['drts_term_orderby_sql'])) {
            $_orderby_sql = $queryVars['drts_term_orderby_sql'];
            if (strlen($orderby)) {
                if (!empty($queryVars['drts_orderby_prepend'])) {
                    $orderby = $_orderby_sql . ',' . $orderby; 
                } else {
                    $orderby .= ',' . $_orderby_sql;
                }
            } else {
                $orderby = $_orderby_sql;
            }
        }
        return $orderby;
    }
    
    public function getMetaSqlFilter($sql, $queries, $type, $primaryTable, $primaryIdColumn, $wpQuery)
    {
        if (!isset($wpQuery)) return $sql;
        
        $qv =& $wpQuery->query_vars;
        
        if ($type === 'post') {
            if (empty($qv['post_type'])) return $sql;
            
            foreach ((array)$qv['post_type'] as $post_type) {
                if (!$this->_application->getComponent('WordPressContent')->hasPostType($post_type)) return $sql;
            }
        } elseif ($type === 'term') {
            if (empty($qv['taxonomy'])) return $sql;
        
            foreach ((array)$qv['taxonomy'] as $taxonomy) {
                if (!$this->_application->getComponent('WordPressContent')->hasTaxonomy($taxonomy)) return $sql;
            }
        } else {
            return $sql;
        }
        
        $is_query = false;
        if (!empty($queries)) {
            $this->_extractSabaiMetaQuery($queries, $is_query);
        }
        if (!$is_query
            && !isset($qv['drts_orderby'])
        ) return $sql;
        
        // Process field queries
        if ($is_query) {
            $operator = 'AND';
            if (isset($queries['relation'])) {
                $operator = $queries['relation'] === 'OR' ? 'OR' : 'AND';
                unset($queries['relation']);
            }

            $field_query = new FieldQuery($operator);
            if (!empty($queries)) {
                $this->_buildFieldQuery($queries, $field_query);
            }
        } else {
            $field_query = new FieldQuery();
        }
        
        // Process field sorts
        if (isset($qv['drts_orderby'])) {
            $default_order = isset($qv['order']) && strtoupper($qv['order']) === 'ASC' ? 'ASC' : 'DESC';
            foreach ($qv['drts_orderby'] as $field) {
                if (is_array($field)) {
                    $order = $field[1];
                    $field = $field[0];
                } else {
                    $order = $default_order;
                }
                if (strpos($field, '.')) {
                    $parts = explode('.', $field);
                    $field = $parts[0];
                    $column = $parts[1];
                } else {
                    $column = 'value';
                }
                $field_query->sortByField($field, $order, $column);
            }
        }
        
        // Generate SQL
        $parsed = $this->_application->Entity_Storage()->parseQuery($type, $field_query);
        $sql['where'] = ' AND ' . $parsed['criteria'];
        $sql['join'] = trim($parsed['table_joins'] . ' ' . $parsed['joins']);
                
        // Set sorts to query vars to be used by posts_orderby/get_terms_orderby filter
        $qv['drts_' . $type . '_orderby_sql'] = $parsed['sorts'];
        
        return $sql;
    }
        
    protected function _extractSabaiMetaQuery(&$queries, &$isSabaiQuery)
    {
        foreach (array_keys($queries) as $key) {
            if (is_array($queries[$key])) {
                if (isset($queries[$key]['key']) || isset($queries[$key]['value'])) {
                    if (!isset($queries[$key]['key'])
                        || strpos($queries[$key]['key'], '_drts_') !== 0
                    ) {
                        unset($queries[$key]);
                    } else {
                        $queries[$key]['key'] = substr($queries[$key]['key'], strlen('_drts_')); // remove prefix
                        $isSabaiQuery = true;
                    }
                } else {
                    $this->_extractSabaiMetaQuery($queries[$key], $isSabaiQuery);   
                }
            } else {
                if ('relation' !== $key) {
                    unset($queries[$key]);
                }
            }
        }
    }
    
    protected function _buildFieldQuery($queries, Field\Query $fieldQuery)
    {
        foreach ($queries as $key => $query) {
            if (isset($query['key'])) {
                if (!isset($query['compare'])) {
                    $query['compare'] = '=';
                }
                if (strpos($query['key'], '__')) {
                    $parts = explode('__', $query['key']);
                    $query['key'] = $parts[0];
                    $column = $parts[1];
                } else {
                    $column = 'value';
                }
                switch ($query['compare']) {
                    case '=':
                        $fieldQuery->fieldIs($query['key'], $query['value'], $column);
                        break;
                    case '!=':
                        $fieldQuery->fieldIsNot($query['key'], $query['value'], $column);
                        break;
                    case '>':
                        $fieldQuery->fieldIsGreaterThan($query['key'], $query['value'], $column);
                        break;
                    case '>=':
                        $fieldQuery->fieldIsOrGreaterThan($query['key'], $query['value'], $column);
                        break;
                    case '<':
                        $fieldQuery->fieldIsSmallerThan($query['key'], $query['value'], $column);
                        break;
                    case '<=':
                        $fieldQuery->fieldIsOrSmallerThan($query['key'], $query['value'], $column);
                        break;
                    case 'LIKE':
                        $fieldQuery->fieldContains($query['key'], $query['value'], $column);
                        break;
                    case 'NOT LIKE':
                        $fieldQuery->fieldNotContains($query['key'], $query['value'], $column);
                        break;
                    case 'IN':
                        $fieldQuery->fieldIsIn($query['key'], $query['value'], $column);
                        break;
                    case 'NOT IN':
                        $fieldQuery->fieldIsNotIn($query['key'], $query['value'], $column);
                        break;
                    case 'BETWEEN':
                        $fieldQuery->startCriteriaGroup()
                            ->fieldIsOrGreaterThan($query['key'], $query['value'][0], $column)
                            ->fieldIsOrSmallerThan($query['key'], $query['value'][1], $column)
                            ->finishCriteriaGroup();
                        break;
                    case 'NOT BETWEEN':
                        $fieldQuery->startCriteriaGroup('OR')
                            ->fieldIsSmallerThan($query['key'], $query['value'][0], $column)
                            ->fieldIsGreaterThan($query['key'], $query['value'][1], $column)
                            ->finishCriteriaGroup();
                        break;
                    case 'EXISTS':
                    case 'NOT NULL':
                        $fieldQuery->fieldIsNotNull($query['key'], $column);
                        break;
                    case 'NOT EXISTS':
                    case 'NULL':
                        $fieldQuery->fieldIsNull($query['key'], $column);
                        break;
                    case 'REGEXP':
                        break;
                    case 'NOT REGEXP':
                        break;
                    case 'RLIKE':
                        break;
                }
            } else {
                if (isset($queries[$key]['relation'])) {
                    $operator = $queries[$key]['relation'] === 'OR' ? 'OR' : 'AND';
                    unset($queries[$key]['relation']);
                } else {
                    $operator = 'AND';
                }
                if (!empty($queries[$key])) {
                    $fieldQuery->startCriteriaGroup($operator);
                    $this->_buildFieldQuery($queries[$key], $fieldQuery);   
                    $fieldQuery->finishCriteriaGroup();
                }
            }
        }
    }
}