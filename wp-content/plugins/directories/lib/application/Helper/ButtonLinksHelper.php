<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;

class ButtonLinksHelper
{
    public function help(Application $application, array $links = null, $options = [])
    {
        if (empty($links)) return '';
        
        $options += [
            'size' => null,
            'tooltip' => false,
            'label' => false,
            'separator' => PHP_EOL,
            'group' => false,
            'right' => false,
            'color' => null,
            'class' => '',
            'btn' => true,
            'group_children' => false,
        ];
        $btn_class = DRTS_BS_PREFIX . 'btn';
        foreach (array_keys($links) as $i) {
            // Show dropdowns if multiple links
            if (is_array($links[$i])) {
                if (count($links[$i]) > 1) {
                    if ($options['group_children']) {
                        $links[$i] = $application->ButtonLinks($links[$i], ['group' => true, 'separator' => ''] + $options);
                    } else {
                        $links[$i] = $application->DropdownButtonLinks($links[$i], $options);
                    }
                    continue;
                } else {
                    $links[$i] = array_shift($links[$i]);
                }
            } elseif (is_string($links[$i])) {
                continue;
            }
            $link = $links[$i];
            // Single link
            $class = $btn_class;
            if ($options['size']) {
                $class .= ' ' . $btn_class . '-' . $options['size'];
            }
            if ($options['color']) {
                $class .= ' ' . $btn_class . '-' . $options['color'];
            }
            if ($_class = $link->getAttribute('class')) {
                $class .= ' ' . $_class;
            }
            if (null === $link->getBtn()) {
                $link->setBtn($options['btn']);
            }
            $link->setAttribute('class', $class);
            if ($options['tooltip']) {
                if ($rel = $link->getAttribute('rel')) {
                    $rel .= ' sabaitooltip';
                } else {
                    $rel = 'sabaitooltip';
                }
                $link->setAttribute('rel', $rel);
                // Use label as tooltip if no title is set
                if (!$options['label'] && !$link->getAttribute('title')) {
                    $link->setAttribute('title', strip_tags($link->getLabel()));
                }
            }
            if (!$options['label']) {
                $link->setLabel('');
            }
        }
        $buttons = implode($options['separator'], $links);
        if (!$options['group']) return $buttons;
        
        return '<div class="' . $btn_class . '-group ' . $options['class'] . '">' . $buttons . '</div>';
    }
}