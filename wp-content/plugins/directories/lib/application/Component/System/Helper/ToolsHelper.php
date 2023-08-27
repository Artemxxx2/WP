<?php
namespace SabaiApps\Directories\Component\System\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class ToolsHelper
{
    private $_impls = [];

    public function help(Application $application, $useCache = true)
    {
        if (!$useCache
            || (!$tools = $application->getPlatform()->getCache('system_tools'))
        ) {
            $tools = [];
            foreach ($application->InstalledComponentsByInterface('System\ITools') as $component_name) {
                if (!$application->isComponentLoaded($component_name)) continue;

                foreach ($application->getComponent($component_name)->systemGetToolNames() as $tool_name) {
                    if (!$tool = $application->getComponent($component_name)->systemGetTool($tool_name)) {
                        continue;
                    }
                    $tools[$tool_name] = [
                        'weight' => $tool->systemToolInfo('weight'),
                        'component' => $component_name,
                    ];
                }
            }
            if (!empty($tools)) {
                uasort($tools, function ($a, $b) {
                    return $a['weight'] < $b['weight'] ? -1 : 1;
                });
                foreach (array_keys($tools) as $tool_name) {
                    $tools[$tool_name] = $tools[$tool_name]['component'];
                }
            } else {
                $tools['system_reload'] = 'System';
                $tools['system_clear_cache'] = 'System';
            }
            $application->getPlatform()->setCache($tools, 'system_tools');
        }

        return $tools;
    }

    public function impl(Application $application, $tool, $returnFalse = false)
    {
        if (!isset($this->_impls[$tool])) {
            if ((!$tools = $this->help($application))
                || !isset($tools[$tool])
                || !$application->isComponentLoaded($tools[$tool])
            ) {
                if ($returnFalse) return false;
                throw new Exception\UnexpectedValueException(sprintf('Invalid tool: %s', $tool));
            }
            $this->_impls[$tool] = $application->getComponent($tools[$tool])->systemGetTool($tool);
        }

        return $this->_impls[$tool];
    }


    public function changeCollation(Application $application, $collation)
    {
        $tables = [
            'directory_directory',
            'display_display',
            'display_element',
            'entity_bundle',
            'entity_field',
            'entity_field_choice',
            'entity_field_claiming_status',
            'entity_field_color',
            'entity_field_date',
            'entity_field_email',
            'entity_field_entity_child_count',
            'entity_field_entity_featured',
            'entity_field_entity_term_content_count',
            'entity_field_entity_terms',
            'entity_field_frontendsubmit_guest',
            'entity_field_icon',
            'entity_field_location_address',
            'entity_field_payment_plan',
            'entity_field_phone',
            'entity_field_range',
            'entity_field_review_rating',
            'entity_field_social_accounts',
            'entity_field_time',
            'entity_field_url',
            'entity_field_video',
            'entity_field_voting_vote',
            'entity_field_wp_file',
            'entity_field_wp_image',
            'entity_fieldconfig',
            'payment_feature',
            'payment_featuregroup',
            'system_component',
            'system_route',
            'view_filter',
            'view_view',
            'voting_vote',
        ];
        $prefix = $application->getDB()->getResourcePrefix();
        $charset = in_array($collation, ['utf8_general_ci', 'utf8_unicode_ci']) ? 'utf8' : 'utf8mb4';
        foreach ($application->Filter('system_collate_tables', $tables) as $table_name) {
            $sql = sprintf(
                'ALTER TABLE %s%s CONVERT TO CHARACTER SET %s COLLATE %s;',
                $prefix,
                $table_name,
                $charset,
                $collation
            );
            try {
                $application->getDB()->exec($sql);
            } catch (\Exception $e) {
                $application->logError($e->getMessage());
            }
        }
    }
}