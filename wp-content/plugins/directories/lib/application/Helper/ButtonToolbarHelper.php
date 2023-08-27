<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;

class ButtonToolbarHelper
{
    public function help(Application $application, array $links, array $options = [])
    {
        $options += [
            'tooltip' => false,
            'label' => true,
            'separator' => PHP_EOL,
            'class' => DRTS_BS_PREFIX . 'd-inline-flex ' . DRTS_BS_PREFIX . 'justify-content-center ' . DRTS_BS_PREFIX . 'justify-content-sm-start',
        ];
        $margin_x_class = $application->getPlatform()->isRtl() ? DRTS_BS_PREFIX . 'ml-2 ' : DRTS_BS_PREFIX . 'mr-2 ';
        $margin_x_class .= DRTS_BS_PREFIX . 'mb-2 ' . DRTS_BS_PREFIX . 'mb-sm-none';
        foreach (array_keys($links) as $i) {
            if (is_string($links[$i])) continue;
            
            $is_first = !isset($is_first);
            $links[$i] = $application->ButtonLinks(
                [$links[$i]],
                ['separator' => PHP_EOL, 'group' => true, 'class' => $margin_x_class ] + $options
            );
        }
        return '<div class="' . DRTS_BS_PREFIX . 'btn-toolbar ' . $options['class'] . '">' . implode($options['separator'], $links) . '</div>';
    }
}