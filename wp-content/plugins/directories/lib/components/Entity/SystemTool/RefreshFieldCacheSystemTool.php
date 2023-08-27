<?php
namespace SabaiApps\Directories\Component\Entity\SystemTool;

use SabaiApps\Directories\Component\System\Tool\AbstractTool;

class RefreshFieldCacheSystemTool extends AbstractTool
{
    protected function _systemToolInfo()
    {
        return [
            'label' => __('Refresh field cache', 'directories'),
            'description' => __('This tool will clear and reload field cache for each content item.', 'directories'),
            'weight' => 11,
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
        $this->_application->Entity_Field_cleanCache();

        $ret = [];
        $langs = $this->_application->getPlatform()->getLanguages();
        foreach ($this->_application->Entity_Bundles() as $bundle) {
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
        $paginator = $this->_application->Entity_Query($bundle->entitytype_name, $bundle->name)
            ->sortById()
            ->paginate($settings['num'], 0, $lang)
            ->setCurrentPage($iteration);
        $this->_application->Entity_Field_load($bundle->entitytype_name, $paginator->getElements(), true);

        $label = $bundle->getGroupLabel() . ' - ' . $bundle->getLabel();
        $offset = $paginator->getElementOffset();
        $logs['success'][] = sprintf(
            'Refreshed field cache for %s (%d - %d)',
            isset($lang) ? $label . '[' . $lang . ']' : $label,
            $offset + 1,
            $offset + $paginator->getElementLimit()
        );

        return $paginator->getElementLimit();
    }
}