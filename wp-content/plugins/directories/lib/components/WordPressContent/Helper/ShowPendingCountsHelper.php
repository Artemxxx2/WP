<?php
namespace SabaiApps\Directories\Component\WordPressContent\Helper;

use SabaiApps\Directories\Application;

class ShowPendingCountsHelper
{
    public function help(Application $application, $menu)
    {
        $post_types = $groups = [];
        $claim_enabled = false;
        foreach ($application->Entity_Bundles(null, 'Directory') as $bundle) {
            if (!empty($bundle->info['is_taxonomy'])
                || !empty($bundle->info['internal'])
                || !empty($bundle->info['is_user'])
            ) continue;

            $group_key = 'edit.php?post_type=' . (empty($bundle->info['parent']) ? $bundle->name : $bundle->info['parent']);
            $submenu_key = 'drts_entity_edit_' . $bundle->name;
            $groups[$group_key][$submenu_key] = $bundle->name;
            $post_types[$bundle->name] = $group_key;
            if (!$claim_enabled
                && !empty($bundle->info['claiming_enable'])
            ) {
                $claim_enabled = true;
            }
        }

        if (!empty($post_types)) {
            $pending_counts_by_group = [];
            $query = $application->Entity_Query('post', array_keys($post_types))->groupByField('bundle_name');
            if ($claim_enabled) {
                $query->startCriteriaGroup('OR')
                    ->fieldIs('status', 'pending')
                    ->startCriteriaGroup('AND')
                        ->fieldIs('bundle_type', 'claiming_claim')
                        ->fieldIs('status', 'publish')
                        ->fieldIsNull('claiming_status')
                    ->finishCriteriaGroup()
                    ->finishCriteriaGroup();
            } else {
                $query->fieldIs('status', 'pending');
            }
            $pending_counts = $query->count();
            foreach ($pending_counts as $bundle_name => $count) {
                if (empty($count)) continue;

                $group_key = $post_types[$bundle_name];
                if (!isset($pending_counts_by_group[$group_key])) {
                    $pending_counts_by_group[$group_key] = $count;
                } else {
                    $pending_counts_by_group[$group_key] += $count;
                }
            }

            if (!empty($pending_counts_by_group)) {
                foreach($menu as $menu_key => $menu_data) {
                    $group_key = $menu_data[2];
                    if (empty($pending_counts_by_group[$group_key])) continue;

                    // Add pending count to menu item
                    $pending_count = $pending_counts_by_group[$group_key];
                    $menu[$menu_key][0] .= ' <span class="update-plugins count-' . $pending_count . '"><span class="plugin-count">'
                        . number_format_i18n($pending_count)
                        . '</span></span>';

                    // Add pending count to each submenu item
                    if (!empty($GLOBALS['submenu'][$group_key])) {
                        foreach ($GLOBALS['submenu'][$group_key] as $weight => $submenu) {
                            $submenu_key = $submenu[1];
                            if (isset($groups[$group_key][$submenu_key])) {
                                $bundle_name = $groups[$group_key][$submenu_key];
                                if (isset($pending_counts[$bundle_name])) {
                                    $pending_count = $pending_counts[$bundle_name];
                                    $GLOBALS['submenu'][$group_key][$weight][0] .= ' <span class="update-plugins count-' . $pending_count . '"><span class="plugin-count">'
                                        . number_format_i18n($pending_count)
                                        . '</span></span>';
                                }
                            }

                        }
                    }
                }
            }
        }

        return $menu;
    }
}
