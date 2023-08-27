<?php
namespace SabaiApps\Directories\Platform\WordPress;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class Util
{
    public static function createPage(Platform $platform, $slug, $title, $lang = false)
    {
        if ($page = get_page_by_path($slug)) {
            wp_publish_post($page->ID);
            return $page->ID;
        }
        if (strpos($slug, '/')) { // not a root page
            if (!$parent_page = get_page_by_path(substr($slug, 0, strrpos($slug, '/')))) {
                // parent page must exist
                return;
            }
            $slug = basename($slug);
            $parent = $parent_page->ID;
        } else {
            $parent = 0;
        }
        
        // Create a new page for this slug
        $page = array(
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_content' => '',
            'post_date' => current_time('mysql'),
            'post_date_gmt' => current_time('mysql', 1),
            'post_name' => $slug,
            'post_status' => 'publish',
            'post_title' => $title,
            'post_type' => 'page',
            'post_parent' => $parent,
        );
        return wp_insert_post($page);
    }

    public static function updateDatabase(Platform $platform, $schema, $previousSchema = null)
    {
        global $wpdb;
        if (isset($schema)) {
            if (is_string($schema)) {
                $schema = include $schema;
            }
            $schema = self::_updateDatabaseSchema($wpdb, $schema);
            if ($schema['sql']) {
                require_once ABSPATH . 'wp-admin/includes/upgrade.php';
                dbDelta($schema['sql']);
            }
            foreach ($schema['inserts'] as $table_name => $inserts) {
                foreach ($inserts as $insert) {
                    $wpdb->insert($table_name, $insert);
                }
            }
        } elseif (isset($previousSchema)) {
            if (is_string($previousSchema)) {
                $previousSchema = include $previousSchema;
            }
            if (!empty($previousSchema['tables'])) {
                foreach (array_keys($previousSchema['tables']) as $table) {
                    $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'drts_' . $table . ';');
                }
            }
        }
    }
    
    protected static function _updateDatabaseSchema($wpdb, $schema)
    {
        $ret = array('sql' => null, 'inserts' => []);
        if (empty($schema['tables'])) return $ret;
    
        $sql = [];
        $table_prefix = $wpdb->prefix . 'drts_';
        // Get charset/collation from the posts table and use them for new tables
        $posts_table_status = $wpdb->get_row("SHOW TABLE STATUS LIKE '" . $wpdb->posts . "'");
        if ($posts_table_status
            && $posts_table_status->Collation
            && ($collation_parts = explode('_', $posts_table_status->Collation))
        ) {
            $charset = $collation_parts[0];
            $collation = $posts_table_status->Collation;
        } else {
            $charset = $wpdb->charset;
            $collation = $wpdb->collate;
        }
        foreach ($schema['tables'] as $table => $table_info) {
            $table_name = $table_prefix . $table;
            if (strlen($table_name) > 64) {
                throw new Exception\RuntimeException('Table name is too long: ' . $table_name);
            }
            $columns = [];
            foreach ($table_info['fields'] as $column => $column_info) {
                switch ($column_info['type']) {
                    case Application::COLUMN_BOOLEAN:
                        $columns[] = sprintf(
                            '%s tinyint(1) DEFAULT \'%d\'%s',
                            $column,
                            !empty($column_info['default']) ? 1 : 0,
                            false === @$column_info['notnull'] ? '' : ' NOT NULL'
                        );
                        break;
                    case Application::COLUMN_DECIMAL:
                        $scale = !isset($column_info['scale']) ? 2 : $column_info['scale'];
                        $columns[] = sprintf(
                            '%s decimal(%d,%d)%s DEFAULT \'%s\'%s',
                            $column,
                            empty($column_info['length']) ? 10 : $column_info['length'],
                            $scale,
                            !empty($column_info['unsigned']) ? ' unsigned' : '',
                            isset($column_info['default']) ? $column_info['default'] : '0.' . str_repeat('0', $scale),
                            false === @$column_info['notnull'] ? '' : ' NOT NULL'
                        );
                        break;
                    case Application::COLUMN_INTEGER:
                        $length = empty($column_info['length']) ? 20 : $column_info['length'];
                        $type = $length > 10 ? 'bigint' : 'int';
                        $columns[] = sprintf(
                            '%s %s(%d)%s%s%s%s',
                            $column,
                            $type,
                            $length,
                            !empty($column_info['unsigned']) ? ' unsigned' : '',
                            empty($column_info['autoincrement']) && isset($column_info['default']) ? " DEFAULT '" . intval($column_info['default']) . "'" : '',
                            false === @$column_info['notnull'] ? '' : ' NOT NULL',
                            empty($column_info['autoincrement']) ? '' : ' AUTO_INCREMENT'
                        );
                        break;
                    case Application::COLUMN_TEXT:
                        $columns[] = sprintf(
                            '%s text%s',
                            $column,
                            false === @$column_info['notnull'] ? '' : ' NOT NULL'
                        );
                        break;
                    case Application::COLUMN_VARCHAR:
                        $columns[] = sprintf(
                            '%s varchar(%d) DEFAULT \'%s\'%s',
                            $column,
                            empty($column_info['length']) ? 255 : $column_info['length'],
                            (string)@$column_info['default'],
                            false === @$column_info['notnull'] ? '' : ' NOT NULL'
                        );
                        break;
                }
            }
            foreach ($table_info['indexes'] as $index => $index_info) {
                $index_fields = [];
                foreach ($index_info['fields'] as $field => $field_info) {
                    $index_fields[] = isset($field_info['length']) ? $field . '(' . $field_info['length'] . ')' : $field;
                }
                if (!empty($index_info['primary'])) {
                    $columns[] = sprintf('PRIMARY KEY (%s)', implode(',', $index_fields));
                } elseif (!empty($index_info['unique'])) {
                    $columns[] = sprintf('UNIQUE KEY `%s` (%s)', $index, implode(',', $index_fields));
                } else {
                    $columns[] = sprintf('KEY `%s` (%s)', $index, implode(',', $index_fields));
                }
            }
            if (!empty($table_info['initialization'])) {
                foreach ($table_info['initialization'] as $init_type => $init_data) {
                    switch ($init_type) {
                        case 'insert';
                            $ret['inserts'][$table_name] = $init_data;
                            break;
                    }
                }
            }

            $charset_collate = '';
            if (!empty($charset)) {
                $charset_collate .= ' DEFAULT CHARACTER SET ' . $charset;
            }
            if (!empty($collation)) {
                $charset_collate .= ' COLLATE ' . $collation;
            }
            $sql[$table_name] = sprintf('CREATE TABLE %s (
  %s
)%s;',
                $table_name,
                implode(",\n", $columns),
                $charset_collate
            );
        }
        if (!empty($sql)) {
            $ret['sql'] = implode("\n", $sql);
        }
        return $ret;
    }

    public static function getRewriteRules(Platform $platform, $lang)
    {
        $ret = [];
        $rewrite_path = $custom_rewrite_path = '';
        if (!empty($lang)
            && $platform->getI18n() === 'polylang'
        ) {
            $main_url = rtrim(strtok($platform->getMainUrl($lang), '?'), '/');
            $site_url = rtrim($platform->getSiteUrl(), '/');
            if ($main_url !== $site_url
                && strpos($main_url, $site_url) === 0
            ) {
                $rewrite_path = substr($main_url, strlen($site_url));
                $rewrite_path = trim($rewrite_path, '/') . '/';
                $rewrite_path = preg_quote($rewrite_path);
            }
            $custom_rewrite_path = $rewrite_path;
            if ($platform->getDefaultLanguage() === $lang
                && !PLL()->options['hide_default']
            ) {
                $custom_rewrite_path .= $lang . '/';
            }
        }
        if ($page_slugs = $platform->getPageSlugs($lang)) {
            $child_post_types = [];
            // Custom permalink rewrites should come first
            if (!empty($page_slugs[4])) {
                $custom_post_type_rewrites = $custom_taxonomy_rewrites = [];
                foreach ($page_slugs[4] as $slug_info) {
                    if ((!$slug_info['component'])
                        || !isset($slug_info['slug'])
                        || (!$component_name = $slug_info['component'])
                        || !isset($page_slugs[1][$component_name][$slug_info['slug']])
                    ) continue;

                    $post_type = null;
                    $slug = $page_slugs[1][$component_name][$slug_info['slug']]; // get the actual slug configured
                    $_slug_info = $page_slugs[5][$slug];
                    $page_name = isset($_slug_info['page_name']) ? trim($_slug_info['page_name'], '/') : null;
                    $parent_page_name = dirname(trim($slug, '/'));
                    if (isset($page_slugs[5][$slug]['taxonomy'])) {
                        $taxonomy = $_slug_info['taxonomy'];
                        foreach ($slug_info['regex'] as $regex) {
                            $custom_taxonomy_rewrites[$custom_rewrite_path . $regex['regex'] . '/?$'] = 'index.php?' . $taxonomy . '=$matches[1]'
                                . '&drts_route=' . $slug . '/$matches[1]'
                                . '&drts_pagename=' . $page_name
                                . '&drts_parent_pagename=' . $parent_page_name
                                . '&drts_lang=' . $lang;
                        }
                        if (isset($slug_info['base_route'])) {
                            $custom_taxonomy_rewrites[$custom_rewrite_path . preg_quote($slug_info['base']) . '/?$'] = 'index.php?pagename=' . $parent_page_name
                                . '&drts_route=' . $slug_info['base_route']
                                . '&drts_lang=' . $lang
                                . '&drts_redirect=1';
                        }
                    } elseif (isset($page_slugs[5][$slug]['post_type'])) {
                        $post_type = $_slug_info['post_type'];
                        foreach ($slug_info['regex'] as $regex) {
                            if ($platform->isAmpEnabled($post_type)) {
                                $custom_post_type_rewrites[$custom_rewrite_path . $regex['regex'] . '/amp/?$'] = 'index.php?amp=1&post_type=' . $post_type
                                    . '&' . ($regex['type'] === 'id' ? 'p' : 'name') . '=$matches[1]'
                                    . '&drts_route=' . $slug . '/$matches[1]'
                                    . '&drts_pagename=' . $page_name
                                    . '&drts_parent_pagename=' . $parent_page_name
                                    . '&drts_lang=' . $lang;
                            }
                            $custom_post_type_rewrites[$custom_rewrite_path . $regex['regex'] . '/?$'] = 'index.php?post_type=' . $post_type
                                . '&' . ($regex['type'] === 'id' ? 'p' : 'name') . '=$matches[1]'
                                . '&drts_route=' . $slug . '/$matches[1]'
                                . '&drts_pagename=' . $page_name
                                . '&drts_parent_pagename=' . $parent_page_name
                                . '&drts_lang=' . $lang;
                        }
                        $custom_post_type_rewrites[$custom_rewrite_path . preg_quote($slug_info['base']) . '/?$'] = 'index.php?pagename=' . $parent_page_name
                            . '&drts_route=' . (isset($slug_info['base_route']) ? $slug_info['base_route'] : $parent_page_name)
                            . '&drts_lang=' . $lang
                            . '&drts_redirect=1';
                    }
                    unset($page_slugs[0][$slug], $page_slugs[5][$slug]);
                }
                $ret += $custom_post_type_rewrites + $custom_taxonomy_rewrites;
            }

            $taxonomy_rewrites = $post_type_rewrites = $child_post_type_rewrites = [];

            // Add rewrite for post type / taxonomy
            if (!empty($page_slugs[5])) {
                foreach ($page_slugs[5] as $slug => $slug_info) {
                    $page_name = isset($slug_info['page_name']) ? trim($slug_info['page_name'], '/') : null;
                    if (isset($slug_info['taxonomy'])) {
                        $taxonomy_rewrites[$rewrite_path . preg_quote($slug) . '/([^/]+)/?$'] = 'index.php?' . $slug_info['taxonomy'] . '=$matches[1]'
                            . '&drts_route=' . $slug . '/$matches[1]'
                            . '&drts_pagename=' . $page_name
                            . '&drts_parent_pagename=' . dirname(trim($slug, '/'))
                            . '&drts_lang=' . $lang;
                    } elseif (isset($slug_info['post_type'])) {
                        if (!empty($slug_info['is_child'])) {
                            $child_post_types[$slug_info['post_type']] = ['slug' => $slug, 'page_name' => $page_name];
                        } else {
                            if ($platform->isAmpEnabled($slug_info['post_type'])) {
                                $post_type_rewrites[$rewrite_path . preg_quote($slug) . '/([^/]+)/amp/?$'] = 'index.php?amp=1&post_type=' . $slug_info['post_type']
                                    . '&name=$matches[1]&drts_route=' . $slug . '/$matches[1]'
                                    . '&drts_pagename=' . $page_name
                                    . '&drts_parent_pagename=' . dirname(trim($slug, '/'))
                                    . '&drts_lang=' . $lang;
                            }
                            $post_type_rewrites[$rewrite_path . preg_quote($slug) . '/([^/]+)/?$'] = 'index.php?post_type=' . $slug_info['post_type']
                                . '&name=$matches[1]&drts_route=' . $slug . '/$matches[1]'
                                . '&drts_pagename=' . $page_name
                                . '&drts_parent_pagename=' . dirname(trim($slug, '/'))
                                . '&drts_lang=' . $lang;
                        }
                    } elseif (!empty($slug_info['is_user'])) {
                        if (isset($page_name)) {
                            $post_type_rewrites[$rewrite_path . preg_quote($slug) . '/([^/]+)/?$'] = 'index.php?pagename=' . $page_name
                                . '&drts_route=' . $slug . '/$matches[1]'
                                . '&drts_pagename=' . $page_name
                                . '&drts_parent_pagename=' . dirname(trim($slug, '/'))
                                . '&drts_is_user=1'
                                . '&drts_lang=' . $lang;
                        }
                    }
                    unset($page_slugs[0][$slug]);
                }
            }

            // Add rewrite for child post types
            if (!empty($child_post_types)) {
                foreach (array_keys($child_post_types) as $child_post_type) {
                    $slug = $child_post_types[$child_post_type]['slug'];
                    $parent_slug = trim(dirname($slug), '/');
                    $child_slug = basename($slug);
                    $child_post_type_rewrites[$rewrite_path . preg_quote($parent_slug) . '/([^/]+)/' . preg_quote($child_slug) . '/([0-9]+)/?$'] = 'index.php?'
                        . 'post_type=' . $child_post_type
                        . '&p=$matches[2]&drts_route=' . $parent_slug . '/$matches[1]/' . $child_slug . '/$matches[2]'
                        . '&drts_pagename=' . $child_post_types[$child_post_type]['page_name']
                        . '&drts_parent_pagename=' . dirname($parent_slug)
                        . '&drts_lang=' . $lang;
                }
            }

            $ret += $child_post_type_rewrites + $post_type_rewrites + $taxonomy_rewrites;

            if (!empty($page_slugs[0])) {
                arsort($page_slugs[0]);
                foreach ($page_slugs[0] as $slug) {
                    $ret[$rewrite_path . preg_quote($slug) . '/(.*)$'] = 'index.php?pagename=' . $slug
                        . '&drts_route=' . $slug . '/$matches[1]'
                        . '&drts_lang=' . $lang;
                    $ret[$rewrite_path . preg_quote($slug) . '/?$'] = 'index.php?pagename=' . $slug
                        . '&drts_route=' . $slug
                        . '&drts_lang=' . $lang;
                }
            }
        }

        $ret[$rewrite_path . '_drts/(.*)$'] = 'index.php?drts_route=_drts/$matches[1]';
        $ret[$rewrite_path . '_drts/?$'] = 'index.php?drts_route=_drts';

        return $ret;
    }
}