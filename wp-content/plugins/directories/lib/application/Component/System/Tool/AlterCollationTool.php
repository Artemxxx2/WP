<?php
namespace SabaiApps\Directories\Component\System\Tool;

class AlterCollationTool extends AbstractTool
{
    protected function _systemToolInfo()
    {
        return [
            'label' => __('Change table collation', 'directories'),
            'description' => sprintf(
                __('Use this tool to change the collation of database tables created by %s.', 'directories'),
                'Directories'
            ),
            'weight' => 90,
        ];
    }

    public function systemToolSettingsForm(array $parents = [])
    {
        return [
            'collation' => [
                '#type' => 'select',
                '#options' => [
                    'utf8_general_ci' => 'utf8_general_ci',
                    'utf8_unicode_ci' => 'utf8_unicode_ci',
                    'utf8mb4_general_ci' => 'utf8mb4_general_ci',
                    'utf8mb4_unicode_ci' => 'utf8mb4_unicode_ci',
                ],
                '#default_value' => $this->_application->getPlatform()->getOption('system_table_collation'),
            ],
        ];
    }

    public function systemToolRunTask($task, array $settings, $iteration, $total, array &$storage, array &$logs)
    {
        $this->_application->System_Tools_changeCollation($settings['collation']);
        $this->_application->getPlatform()->setOption('system_table_collation', $settings['collation']);

        return 1;
    }
}