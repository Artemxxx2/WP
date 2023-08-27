<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;

class DisplaysHelper
{
    public function help(Application $application, Entity\Model\Bundle $bundle, $activeOnly = true, $useCache = true)
    {
        $displays = [];
        if (!empty($bundle->info['public'])) {
            $displays['detailed'] = [
                'label' => _x('Detailed', 'display name', 'directories'),
                'weight' => 0,
            ];
        }

        $weight = 50;
        foreach (array_keys($application->View_Modes(false, $useCache)) as $view_mode_name) {
            if ((!$view_mode = $application->View_Modes_impl($view_mode_name, true))
                || (!$_displays = $view_mode->viewModeInfo('displays'))
                || !$view_mode->viewModeSupports($bundle)
            ) continue;

            foreach ($_displays as $display_name => $display) {
                if (is_array($display)) {
                    $displays[$display_name] = [
                        'label' => $display['label'],
                        'weight' => isset($display['weight']) ? $display['weight'] : ++$weight,
                    ];
                } else {
                    $displays[$display_name] = [
                        'label' => $display,
                        'weight' => ++$weight,
                    ];
                }
            }
        }
        uasort($displays, function ($a, $b) { return $a['weight'] < $b['weight'] ? -1 : 1; });
        foreach (array_keys($displays) as $display_name) {
            $displays[$display_name] = $displays[$display_name]['label'];
        }

        return $displays;
    }
}