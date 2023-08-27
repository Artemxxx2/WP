<?php
namespace SabaiApps\Directories\Component\WordPressContent\SystemTool;

use SabaiApps\Directories\Component\System\Tool\AbstractTool;
use SabaiApps\Directories\Exception;

class SyncTermsSystemTool extends AbstractTool
{
    protected function _systemToolInfo()
    {
        return [
            'label' => __('Sync taxonomy terms', 'directories'),
            'description' => __('This tool will sync taxonomy terms assigned to each content item in WP with taxonomy term data in Directories Pro.', 'directories'),
            'weight' => 60,
        ];
    }

    public function systemToolSettingsForm(array $parents = [])
    {
        $form = [
            'num' => [
                '#type' => 'number',
                '#title' => __('Number of records to process per request', 'directories'),
                '#horizontal' => true,
                '#default_value' => 50,
                '#min_value' => 1,
                '#integer' => true,
                '#required' => true,
            ],
        ];

        return $form;
    }

    public function systemToolInit(array $settings, array &$storage, array &$logs)
    {
        $ret = [];
        $langs = $this->_application->getPlatform()->getLanguages();
        foreach ($this->_application->Entity_Bundles() as $bundle) {
            if (empty($bundle->info['taxonomies'])) continue;

            if (empty($langs)) {
                $ret[$bundle->name] = $this->_application->Entity_Query($bundle->entitytype_name, $bundle->name)->count();
            } else {
                foreach ($langs as $lang) {
                    $ret[$bundle->name . '-' . $lang] = $this->_application->Entity_Query($bundle->entitytype_name, $bundle->name)->count($lang);
                }
            }
        }
        return $ret;
    }

    public function systemToolRunTask($task, array $settings, $iteration, $total, array &$storage, array &$logs)
    {
        if (strpos($task, '-')) {
            $task_parts = explode('-', $task);
            if (!$bundle = $this->_application->Entity_Bundle($task_parts[0])) return false;

            $lang = $task_parts[1];
        } else {
            if (!$bundle = $this->_application->Entity_Bundle($task)) return false;

            $lang = null;
        }
        // Skip modification check for taxonomy term fields to force update
        $extra_args = ['skip_is_modified_check' => []];
        foreach (array_keys($bundle->info['taxonomies']) as $bundle_type) {
            $extra_args['skip_is_modified_check'][$bundle_type] = true;
        }
        $paginator = $this->_application->Entity_Query($bundle->entitytype_name, $bundle->name)
            ->sortById()
            ->paginate($settings['num'], 0, $lang)
            ->setCurrentPage($iteration);
        foreach ($paginator->getElements() as $entity) {
            $values = [];
            foreach ($bundle->info['taxonomies'] as $bundle_type => $taxonomy) {
                $term_ids = wp_get_object_terms($entity->getId(), $taxonomy, ['fields' => 'ids']);
                if (!is_array($term_ids)) continue;

                $values[$bundle_type] = $term_ids;
            }
            try {
                $this->_application->Entity_Save($entity, $values, $extra_args);
            } catch (Exception\IException $e) {
                $logs['error'][] = $e->getMessage();
            }
        }

        $label = $bundle->getGroupLabel() . ' - ' . $bundle->getLabel();
        $offset = $paginator->getElementOffset();
        $logs['success'][] = sprintf(
            'Synchronized taxonomy terms for %s (%d - %d)',
            isset($lang) ? $label . '[' . $lang . ']' : $label,
            $offset + 1,
            $offset + $paginator->getElementLimit()
        );

        return $paginator->getElementLimit();
    }
}