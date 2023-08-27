<?php
namespace SabaiApps\Directories\Component\System\Tool;

use SabaiApps\Directories\Exception;

class ReloadComponentsTool extends AbstractTool
{
    protected function _systemToolInfo()
    {
        return [
            'label' => __('Reload components', 'directories'),
            'description' => __('This tool will reload all componentns to ensure they are in sync with stored data.', 'directories'),
            'weight' => 1,
            'redirect' => true,
        ];
    }

    public function systemToolRunTask($task, array $settings, $iteration, $total, array &$storage, array &$logs)
    {
        $components_upgraded = $this->_application->System_Component_upgradeAll(null, true);
        foreach (array_keys($this->_application->LocalComponents()) as $component_name) {
            if (!in_array($component_name, $components_upgraded)) {
                try {
                    $this->_application->System_Component_install($component_name);
                } catch (Exception\IException $e) {
                    $logs['error'][] = sprintf(
                        'Failed installing component %s (Error: %s)',
                        $component_name,
                        $e->getMessage()
                    );
                }
            }
        }
        $this->_application->getComponent('System')->reloadAllRoutes();

        return 1;
    }
}