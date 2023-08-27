<?php
namespace SabaiApps\Directories\Component\Entity\SystemTool;

use SabaiApps\Directories\Component\System\Tool\AbstractTool;

class RefreshTermCacheSystemTool extends AbstractTool
{
    protected function _systemToolInfo()
    {
        return [
            'label' => __('Refresh taxonomy term cache', 'directories'),
            'description' => __('This tool will clear and reload term cache for all taxonomies.', 'directories'),
            'weight' => 10,
        ];
    }

    public function systemToolInit(array $settings, array &$storage, array &$logs)
    {
        // Make sure existing cache is deleted.
        $this->_application->Entity_TaxonomyTerms_clearCache();

        $ret = [];
        $langs = $this->_application->getPlatform()->getLanguages();
        foreach ($this->_application->Entity_Bundles() as $bundle) {
            if (empty($bundle->info['is_taxonomy'])) continue;

            if (empty($langs)) {
                $ret[$bundle->name] = 1;
            } else {
                foreach ($langs as $lang) {
                    $ret[$bundle->name . '-' . $lang] = 1;
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
        $this->_application->Entity_TaxonomyTerms($bundle->name, null, null, $lang);

        $label = $bundle->getGroupLabel() . ' - ' . $bundle->getLabel();
        $logs['success'][] = sprintf(
            'Refreshed term cache for %s',
            isset($lang) ? $label . ' (' . $lang . ')' : $label
        );

        return 1;
    }
}