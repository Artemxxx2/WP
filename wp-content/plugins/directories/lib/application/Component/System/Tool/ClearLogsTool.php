<?php
namespace SabaiApps\Directories\Component\System\Tool;

class ClearLogsTool extends AbstractTool
{
    protected function _systemToolInfo()
    {
        return [
            'label' => __('Clear log files', 'directories'),
            'description' => sprintf(
                __('This tool will clear all log files saved under %s.', 'directories'),
                $this->_application->getComponent('System')->getLogDir()
            ),
            'weight' => 20,
            'redirect' => true,
        ];
    }

    public function systemToolRunTask($task, array $settings, $iteration, $total, array &$storage, array &$logs)
    {
        foreach (glob($this->_application->getComponent('System')->getLogDir() . '/drts_*.log') as $log_file) {
            if (!@unlink($log_file)) {
                $logs['error'][] = sprintf(__('Failed deleting log file: %s', 'directories'), $log_file);
            }
        }

        return 1;
    }
}