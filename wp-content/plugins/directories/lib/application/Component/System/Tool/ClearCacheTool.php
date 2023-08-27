<?php
namespace SabaiApps\Directories\Component\System\Tool;

class ClearCacheTool extends AbstractTool
{
    protected function _systemToolInfo()
    {
        return [
            'label' => __('Clear cache', 'directories'),
            'description' => __('This tool will clear settings and data currently cached.', 'directories'),
            'weight' => 5,
            'redirect' => true,
        ];
    }

    public function systemToolRunTask($task, array $settings, $iteration, $total, array &$storage, array &$logs)
    {
        $this->_application->getPlatform()->clearCache();
        $this->_application->Action('system_clear_cache');

        return 1;
    }
}