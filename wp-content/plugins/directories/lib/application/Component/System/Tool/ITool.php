<?php
namespace SabaiApps\Directories\Component\System\Tool;

interface ITool
{
    public function systemToolInfo();
    public function systemToolSettingsForm(array $parents = []);
    public function systemToolInit(array $settings, array &$storage, array &$logs);
    public function systemToolRunTask($task, array $settings, $iteration, $total, array &$storage, array &$logs);
}