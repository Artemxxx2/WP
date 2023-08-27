<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;

class DropdownButtonLinksHelper
{
    public function help(Application $application, array $links = null, array $options = [])
    {
        if (empty($links)) return '';
        
        $options += [
            'size' => null,
            'tooltip' => false,
            'label' => true,
            'separator' => PHP_EOL,
            'right' => false,
            'format' => null,
            'color' => null,
            'class' => '',
            'split' => false,
            'btn' => true,
        ];
        
        $dropdown_links = [];
        $dropdown_link_class = DRTS_BS_PREFIX . 'dropdown-item';
        $i = 0;
        foreach ($links as $link) {
            if (is_array($link)) {
                $first_link = array_shift($link);
                foreach ($link as $_link) {
                    if (!$_link->isActive() || isset($current)) {
                        if (!$_link->getIcon() && $first_link->getIcon()) {
                            $_link->setIcon($first_link->getIcon());
                        }
                        if ($first_link->getLabel()) {
                            $_link->setLabel($first_link->getLabel() . ' - ' . $_link->getLabel());
                        }
                        if (null === $_link->getBtn()) {
                            $_link->setBtn($options['btn']);
                        }
                        $_link->setAttribute('title', '');
                        $dropdown_links[$i] = $_link;
                        $dropdown_links[$i]->setAttribute('class', ($class = $_link->getAttribute('class')) ? $dropdown_link_class . ' ' . $class : $dropdown_link_class);
                        ++$i;
                        continue;
                    }
                    $current = $this->_markCurrent($application, $_link, $options);
                }
            } elseif(is_string($link)) {
                // Divider
                $dropdown_links[$i] = '<div class="' . DRTS_BS_PREFIX . 'dropdown-divider"></div>';
                ++$i;
            } else {
                if (!$link->isActive() || isset($current)) {
                    if (null === $link->getBtn()) {
                        $link->setBtn($options['btn']);
                    }
                    $link->setAttribute('title', ''); 
                    $dropdown_links[$i] = $link;
                    $dropdown_links[$i]->setAttribute('class', ($class = $link->getAttribute('class')) ? $dropdown_link_class . ' ' . $class : $dropdown_link_class);
                    ++$i;
                    continue;
                }
                $current = $this->_markCurrent($application, $link, $options);
            }
        }
        if (!isset($current)) { // this should not happen if there is at least one active link
            $first_link = array_shift($links);
            $current = $this->_markCurrent($application, is_array($first_link) ? $first_link[1] : $first_link, $options);
            array_shift($dropdown_links);
        }
        if ($options['split']) {
            $current .= $application->LinkTo(
                '<span class="' . DRTS_BS_PREFIX . 'sr-only">Toggle Dropdown</span>',
                '',
                ['btn' => true, 'no_escape' => true],
                ['class' => $current->getAttribute('class') . ' ' . DRTS_BS_PREFIX . 'dropdown-toggle ' . DRTS_BS_PREFIX . 'dropdown-split']
            )->setAttribute('data-toggle', DRTS_BS_PREFIX . 'dropdown');
        }
        $dropdown_class = DRTS_BS_PREFIX . 'dropdown-menu';
        if ($options['right']) {
            $dropdown_class .= ' ' . DRTS_BS_PREFIX . 'dropdown-menu-right';
        }
        return '<div class="' . DRTS_BS_PREFIX . 'btn-group ' . $options['class'] . '">' . $current . '<div class="' . $dropdown_class . '">'
            . implode(PHP_EOL, $dropdown_links) . '</div></div>';
    }
    
    private function _markCurrent(Application $application, $link, array $options)
    {
        $class = DRTS_BS_PREFIX . 'btn';
        if (!$options['split']) {
            $class .= ' ' . DRTS_BS_PREFIX . 'dropdown-toggle';
            $link->setActive(false)
                ->setAttribute('onclick', '')
                ->setAttribute('data-toggle', DRTS_BS_PREFIX . 'dropdown');
        }
        if ($options['size']) {
            $class .= ' ' . DRTS_BS_PREFIX . 'btn-' . $options['size'];
        }
        if ($options['color']) {
            $class .= ' ' . DRTS_BS_PREFIX . 'btn-' . $options['color'];
        }
        if ($_class = $link->getAttribute('class')) {
            $class .= ' ' . $_class;
        }
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
        if ($options['label'] || isset($options['format'])) {
            $label = $link->isNoEscape() ? $link->getLabel() : $application->H($link->getLabel());
            $link->setLabel(sprintf(isset($options['format']) ? $application->H($options['format']) : '%s', $label), false);
        } else {
            $link->setLabel('', false);
        }
        return $link->setBtn(true)->setAttribute('class', trim(str_replace(DRTS_BS_PREFIX . 'dropdown-item', '', $class)));
    }
}
