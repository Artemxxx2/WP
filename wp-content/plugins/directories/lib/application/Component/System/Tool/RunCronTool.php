<?php
namespace SabaiApps\Directories\Component\System\Tool;

class RunCronTool extends AbstractTool
{
    protected function _systemToolInfo()
    {
        return [
            'label' => __('Run cron', 'directories'),
            'description' => __('Use this tool to manually run cron.', 'directories'),
            'weight' => 15,
        ];
    }

    public function systemToolRunTask($task, array $settings, $iteration, $total, array &$storage, array &$logs)
    {
        $this->_application->callHelper('System_Cron', [&$logs, true]);

        return 1;
    }
}