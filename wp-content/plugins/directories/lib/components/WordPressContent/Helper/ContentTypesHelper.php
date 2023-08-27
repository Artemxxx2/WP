<?php
namespace SabaiApps\Directories\Component\WordPressContent\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;

class ContentTypesHelper
{
    public function help(Application $application, $force = false)
    {
        $cache_id = 'wordpress_content_types';
        if ($force
            || false === ($content_types = $application->getPlatform()->getCache($cache_id))
        ) {
            $application->getPlatform()->setCache($content_types = $this->_getContentTypes($application), $cache_id, 0);
        }

        return $content_types;
    }

    public function clearCache(Application $application)
    {
        $application->getPlatform()->deleteCache('wordpress_content_types');
    }

    protected function _getContentTypes(Application $application)
    {
        $ret = array('post_types' => [], 'taxonomies' => []);
        $taxonomy_bundles = $taxonomy_to_post_types = [];
        $cap_prefix = 'drts_entity_';
        foreach ($application->Entity_Bundles() as $bundle) {
            if (!in_array($bundle->entitytype_name, ['post', 'term'])) continue;

            if (!$application->Entity_BundleTypes($bundle->type)) { // check component exists for this bundle type
                continue;
            }

            if ($bundle->entitytype_name === 'term') {
                $taxonomy_bundles[$bundle->name] = $bundle;
                continue;
            }

            Entity\Helper\BundleHelper::add($bundle);
            $label = $bundle->getLabel();
            $label_singular = $bundle->getLabel('singular');
            $label_lower = strtolower($label);
            $group_label = $bundle->getGroupLabel();
            $no_title = !empty($bundle->info['no_title']);
            $public = !isset($bundle->info['public']) || !empty($bundle->info['public']);
            $internal = !empty($bundle->info['internal']);
            $supports = $no_title ? ['author'] : ['author', 'title'];
            if ($public
                && !$internal
            ) {
                $supports[] = 'comments';
                $supports[] = 'buddypress-activity';
            }
            if (!empty($bundle->info['wp_post_thumbnail'])) {
                $supports[] ='thumbnail';
            }
            $ret['post_types'][$bundle->name] = array(
                'labels' => $application->Filter('wordpress_post_type_labels', array(
                    'name' => $group_label . ' - ' . $label,
                    'singular_name' => $group_label . ' - ' . $label_singular,
                    'menu_name' => empty($bundle->info['parent']) ? $bundle->component : $label,
                    'all_items' => empty($bundle->info['parent']) ? $bundle->getLabel('all') : $label,
                    'add_new' => $label_add = $bundle->getLabel('add'),
                    'add_new_item' => $label_add,
                    'edit_item' => sprintf(__('Edit %s', 'directories'), $label_singular),
                    'new_item' => sprintf(__('New %s', 'directories'), $label_singular),
                    'view_item' => sprintf(__('View %s', 'directories'), $label_singular),
                    'search_items' => sprintf(__('Search %s', 'directories'), $label),
                    'not_found' => sprintf(__('No %s found', 'directories'), $label_lower, $label),
                    'not_found_in_trash' => sprintf(__('No %s found in Trash', 'directories'), $label_lower, $label),
                    'parent_item_colon' => empty($bundle->info['parent']) || (!$parent_bundle = $application->Entity_Bundle($bundle->info['parent']))
                        ? ''
                        : sprintf(__('Parent %s', 'directories'), $parent_bundle->getLabel('singular')),
                ), [$bundle]),
                'public' => $public,
                'publicly_queryable' => $public,
                'exclude_from_search' => false,
                'rewrite' => array(
                    'slug' => $bundle->name,
                    'with_front' => false,
                ),
                'has_archive' => false,
                'supports' => $supports,
                'show_ui' => !$internal && (!isset($bundle->info['system']) || !$bundle->info['system']),
                'show_in_menu' => $internal ? false : (empty($bundle->info['parent']) ? $public : 'edit.php?post_type=' . $bundle->info['parent']),
                'show_in_rest' => $public,
                'parent' => empty($bundle->info['parent']) ? null : $bundle->info['parent'],
                'capability_type' => $bundle->name,
                'map_meta_cap' => true,
            );
            if ($public
                && !$internal
            ) {
                $ret['post_types'][$bundle->name]['capabilities'] = array(
                    'read' => $cap_prefix . 'read_' . $bundle->name,
                    'create_posts' => $cap_prefix . 'create_' . $bundle->name,
                    'edit_posts' => $cap_prefix . 'edit_' . $bundle->name,
                    'edit_others_posts' => $cap_prefix . 'edit_others_' . $bundle->name,
                    'publish_posts' => $cap_prefix . 'publish_' . $bundle->name,
                    'read_private_posts' => $cap_prefix . 'read_private_' . $bundle->name,
                    'delete_posts' => $cap_prefix . 'delete_' . $bundle->name,
                    'delete_private_posts' => $cap_prefix . 'delete_private_' . $bundle->name,
                    'delete_published_posts' => $cap_prefix . 'delete_published_' . $bundle->name,
                    'delete_others_posts' => $cap_prefix . 'delete_others_' . $bundle->name,
                    'edit_private_posts' => $cap_prefix . 'edit_private_' . $bundle->name,
                    'edit_published_posts' => $cap_prefix . 'edit_published_' . $bundle->name,
                    'moderate_comments' => $cap_prefix . 'moderate_comments_' . $bundle->name,
                );
                if (function_exists('buddypress')) {
                    $singular = $ret['post_types'][$bundle->name]['labels']['singular_name'];
                    $ret['post_types'][$bundle->name]['labels'] += [
                        'bp_activity_admin_filter' => str_replace('%s', $singular, _x('New %s published', 'buddypress', 'directories')),
                        'bp_activity_front_filter' => $ret['post_types'][$bundle->name]['labels']['name'],
                        'bp_activity_new_post' => str_replace('%4$s', $singular, _x('%1$s posted a new <a href="%2$s">%4$s</a>', 'buddypress', 'directories')),
                        'bp_activity_new_post_ms' => str_replace('%4$s', $singular, _x('%1$s posted a new <a href="%2$s">%4$s</a>, on the site %3$s', 'buddypress', 'directories')),
                        'bp_activity_comments_admin_filter' => $comments_str = str_replace('%s', $singular, _x('%s - Comments', 'buddypress', 'directories')),
                        'bp_activity_comments_front_filter' => $comments_str,
                        'bp_activity_new_comment' => str_replace('%4$s', $singular, _x('%1$s commented on the <a href="%2$s">%4$s</a>', 'buddypress', 'directories')),
                        'bp_activity_new_comment_ms' => str_replace('%4$s', $singular, _x('%1$s commented on the <a href="%2$s">%4$s</a>, on the site %3$s', 'buddypress', 'directories'))
                    ];
                    $ret['post_types'][$bundle->name]['bp_activity'] = [
                        'component_id' => buddypress()->activity->id,
                        'action_id' => 'new_' . $bundle->name,
                        'comment_action_id' => 'new_' . $bundle->name . '_comment',
                        'contexts' => ['activity', 'member'],
                        'position' => 40,
                    ];
                }
            } else {
                $ret['post_types'][$bundle->name]['capabilities'] = array(
                    'read' => $cap_prefix . 'create_' . $bundle->name,
                    'read_private_posts' => $cap_prefix . 'create_' . $bundle->name,
                    'create_posts' => $cap_prefix . 'create_' . $bundle->name,
                    'publish_posts' => $cap_prefix . 'create_' . $bundle->name,
                    'edit_posts' => $cap_prefix . 'edit_' . $bundle->name,
                    'edit_others_posts' => $cap_prefix . 'edit_others_' . $bundle->name,
                    'edit_private_posts' => $cap_prefix . 'edit_others_' . $bundle->name,
                    'edit_published_posts' => $cap_prefix . 'edit_' . $bundle->name,
                    'delete_posts' => $cap_prefix . 'delete_' . $bundle->name,
                    'delete_others_posts' => $cap_prefix . 'delete_others_' . $bundle->name,
                    'delete_private_posts' => $cap_prefix . 'delete_others_' . $bundle->name,
                    'delete_published_posts' => $cap_prefix . 'delete_' . $bundle->name,
                );
            }
            if (!empty($bundle->info['taxonomies'])) {
                foreach ($bundle->info['taxonomies'] as $taxonomy_type => $taxonomy_name) {
                    $taxonomy_to_post_types[$taxonomy_name] = $bundle->name;
                }
            }

            // Let other components modify
            $ret['post_types'][$bundle->name] = $application->Filter(
                'wordpress_post_type',
                $ret['post_types'][$bundle->name],
                array($bundle)
            );
        }

        if (!empty($taxonomy_to_post_types)) {
            foreach ($taxonomy_bundles as $taxonomy_bundle_name => $taxonomy_bundle) {
                if (!isset($taxonomy_to_post_types[$taxonomy_bundle_name])) continue;

                Entity\Helper\BundleHelper::add($taxonomy_bundle);
                $tax_label = $taxonomy_bundle->getLabel();
                $tax_label_singular = $taxonomy_bundle->getLabel('singular');
                $tax_label_lower = strtolower($tax_label);
                $ret['taxonomies'][$taxonomy_bundle->type][$taxonomy_bundle_name] = array(
                    'post_type' => $taxonomy_to_post_types[$taxonomy_bundle_name],
                    'labels' => $application->Filter('wordpress_taxonomy_labels', array(
                        'name' => $group_label . ' - ' . $tax_label,
                        'singular_name' => $group_label . ' - ' . $tax_label_singular,
                        'menu_name' => $tax_label,
                        'all_items' => $taxonomy_bundle->getLabel('all'),
                        'add_new_item' => $taxonomy_bundle->getLabel('add'),
                        'new_item_name' => sprintf(__('New %s Name', 'directories'), $tax_label_singular),
                        'parent_item' => sprintf(__('Parent %s', 'directories'), $tax_label_singular),
                        'parent_item_colon' => sprintf(__('Parent %s:', 'directories'), $tax_label_singular),
                        'edit_item' => sprintf(__('Edit %s', 'directories'), $tax_label_singular),
                        'view_item' => sprintf(__('View %s', 'directories'), $tax_label_singular),
                        'update_item' => sprintf(__('Update %s', 'directories'), $tax_label_singular),
                        'search_items' => sprintf(__('Search %s', 'directories'), $tax_label),
                        'not_found' => sprintf(__('No %s found', 'directories'), $tax_label_lower, $tax_label),
                    ), [$taxonomy_bundle]),
                    'query_var' => true,
                    'public' => true,
                    'show_admin_column' => true,
                    'rewrite' => array(
                        'slug' => $taxonomy_bundle_name,
                        'with_front' => false,
                    ),
                    'capabilities' => array(
                        'manage_terms' => $cap_prefix . 'manage_' . $taxonomy_bundle_name,
                        'edit_terms' => $cap_prefix . 'edit_' . $taxonomy_bundle_name,
                        'delete_terms' => $cap_prefix . 'delete_' . $taxonomy_bundle_name,
                        'assign_terms' => $cap_prefix . 'assign_' . $taxonomy_bundle_name,
                    ),
                );
                if (!empty($taxonomy_bundle->info['is_hierarchical'])) {
                    $ret['taxonomies'][$taxonomy_bundle->type][$taxonomy_bundle_name]['hierarchical'] = true;
                } else {
                    $ret['taxonomies'][$taxonomy_bundle->type][$taxonomy_bundle_name] += array(
                        'hierarchical' => false,
                        'popular_items' => sprintf(__('Popular %s', 'directories'), $tax_label),
                        'separate_items_with_commas' => sprintf(__('Separate %s with commas', 'directories'), $tax_label_lower, $tax_label),
                        'add_or_remove_items' => sprintf(__('Add or remove %s', 'directories'), $tax_label_lower, $tax_label),
                        'choose_from_most_used' => sprintf(__('Choose from the most used %s', 'directories'), $tax_label_lower, $tax_label),
                    );
                }

                // Let other components modify
                $ret['taxonomies'][$taxonomy_bundle->type][$taxonomy_bundle_name] = $application->Filter(
                    'wordpress_taxonomy',
                    $ret['taxonomies'][$taxonomy_bundle->type][$taxonomy_bundle_name],
                    array($taxonomy_bundle)
                );
            }
        }

        return $ret;
    }
}
