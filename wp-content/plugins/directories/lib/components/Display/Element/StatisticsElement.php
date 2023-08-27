<?php
namespace SabaiApps\Directories\Component\Display\Element;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display;

class StatisticsElement extends AbstractElement
{
    protected function _displayElementInfo(Entity\Model\Bundle $bundle)
    {
        return [
            'type' => 'content',
            'label' => _x('Statistics', 'display element name', 'directories'),
            'description' => __('Statistics of current content', 'directories'),
            'default_settings' => [
                'arrangement' => [],
                'hide_empty' => true,
                'show_blocks' => false,
                'separator' => ' &middot; ',
                'statistics' => [],
            ],
            'inlineable' => true,
            'icon' => 'fas fa-chart-pie',
            'designable' => ['margin', 'padding', 'font'],
        ];
    }
    
    public function displayElementSettingsForm(Entity\Model\Bundle $bundle, array $settings, Display\Model\Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        switch ($tab) {  
            case 'stats':
                $form = [];
                $stats_available = $this->_application->Display_Statistics($bundle);
                $root_parents = array_merge(['general'], array_slice($parents, 0, -1));
                $arrangement_selector = sprintf('input[name="%s[]"]', $this->_application->Form_FieldName(array_merge($root_parents, ['arrangement'])));
                $show_blocks_selector = sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($root_parents, ['show_blocks'])));
                foreach (array_keys($stats_available) as $stat_name) {
                    if (!$stat = $this->_application->Display_Statistics_impl($bundle, $stat_name, true)) continue;
            
                    $info = $stat->displayStatisticInfo($bundle);
                    $_parents = $parents;
                    $_parents[] = $stat_name;
                    $stat_parents = $_parents;
                    $stat_parents[] = 'settings';
                    $_settings = [];
                    if (isset($settings['stats'][$stat_name]['settings'])) {
                        $_settings += $settings['stats'][$stat_name]['settings'];
                    }
                    if (isset($info['default_settings'])) {
                        $_settings += $info['default_settings'];
                    }

                    $form[$stat_name] = [
                        '#title' => $info['label'],
                        '#states' => [
                            'enabled' => [
                                $arrangement_selector => ['value' => $stat_name],
                            ],
                        ],
                        'settings' => [
                            '_format' => [
                                '#type' => 'select',
                                '#title' => __('Display format', 'directories'),
                                '#options' => [
                                    'icon_num' => __('Icon + Number', 'directories'),
                                    'icon_text' => __('Icon + Text', 'directories'),
                                    'number' => __('Number only', 'directories'),
                                    'text' => __('Text only', 'directories'),
                                ],
                                '#default_value' => isset($_settings['_format']) ? $_settings['_format'] : 'icon_num',
                                '#horizontal' => true,
                                '#weight' => -10,
                                '#states' => [
                                    'visible' => [
                                        $show_blocks_selector => ['type' => 'checked', 'value' => false],
                                    ],
                                ],
                            ],
                            '_link' => [
                                '#type' => 'checkbox',
                                '#title' => __('Link to page', 'directories'),
                                '#default_value' => !empty($_settings['_link']),
                                '#horizontal' => true,
                                '#weight' => 99,
                            ],
                            '_link_path' => [
                                '#title' => __('URL path', 'directories'),
                                '#type' => 'textfield',
                                '#field_prefix' => '/',
                                '#horizontal' => true,
                                '#states' => [
                                    'visible' => [
                                        sprintf('input[name="%s[_link]"]', $this->_application->Form_FieldName($stat_parents)) => ['type' => 'checked', 'value' => true],
                                    ],
                                ],
                                '#default_value' => @$_settings['_link_path'],
                                '#weight' => 100,
                            ],
                            '_link_fragment' => [
                                '#type' => 'textfield',
                                '#title' => __('URL fragment identifier'),
                                '#description' => __('Add a fragment identifier to the link URL in order to link to a specific section of the page.', 'directories'),
                                '#horizontal' => true,
                                '#field_prefix' => '#',
                                '#weight' => 101,
                                '#states' => [
                                    'visible' => [
                                        sprintf('input[name="%s[_link]"]', $this->_application->Form_FieldName($stat_parents)) => ['type' => 'checked', 'value' => true],
                                    ],
                                ],
                                '#default_value' => @$_settings['_link_fragment'],
                            ],
                        ],
                    ];
                    if (!isset($info['iconable']) || false !== $info['iconable']) {
                        $form[$stat_name]['settings']['_icon'] = [
                            '#type' => 'iconpicker',
                            '#title' => __('Icon', 'directories'),
                            '#default_value' => isset($_settings['_icon']) ? $_settings['_icon'] : null,
                            '#horizontal' => true,
                            '#states' => [
                                'invisible' => [
                                    sprintf('select[name="%s[_format]"]', $this->_application->Form_FieldName($stat_parents)) => ['type' => 'one', 'value' => ['text', 'number']],
                                ],
                            ],
                            '#weight' => -9
                        ];
                    }
                    if ($stat_settings_form = $stat->displayStatisticSettingsForm($bundle, $_settings, $stat_parents, 'icon')) {
                        $form[$stat_name]['settings'] += $stat_settings_form;
                    }
                }
                return $form;
            default:
                $options = [];
                foreach ($this->_application->Display_Statistics($bundle) as $stat_name => $component_name) {
                    if (!$stat = $this->_application->Display_Statistics_impl($bundle, $stat_name, true)) continue;
                    
                    $info = $stat->displayStatisticInfo($bundle);
                    $options[$stat_name] = $info['label'];
                }
                return [
                    '#tabs' => [
                        'stats' => _x('Statistics', 'tab label', 'directories'),
                    ],
                    'arrangement' => [
                        '#type' => 'sortablecheckboxes',
                        '#title' => __('Display order', 'directories'),
                        '#horizontal' => true,
                        '#default_value' => isset($settings['arrangement']) ? $settings['arrangement'] : array_keys($options),
                        '#options' => $options,
                    ],
                    'show_blocks' => [
                        '#type' => 'checkbox',
                        '#title' => __('Show as blocks', 'directories'),
                        '#horizontal' => true,
                        '#default_value' => !empty($settings['show_blocks']),
                    ],
                    'separator' => [
                        '#type' => 'textfield',
                        '#title' => __('Separator', 'directories'),
                        '#default_value' => $settings['separator'],
                        '#horizontal' => true,
                        '#no_trim' => true,
                        '#states' => [
                            'visible' => [
                                sprintf('[name="%s[show_blocks]"]', $this->_application->Form_FieldName($parents)) => ['type' => 'checked', 'value' => false],
                            ],
                        ],
                    ],
                    'hide_empty' => [
                        '#type' => 'checkbox',
                        '#title' => __('Hide empty', 'directories'),
                        '#horizontal' => true,
                        '#default_value' => !empty($settings['hide_empty']),
                    ],
                ];
        }
    }
    
    protected function _displayElementSupports(Entity\Model\Bundle $bundle, Display\Model\Display $display)
    {
        if ($display->type !== 'entity') return false;
        
        $stats = $this->_application->Display_Statistics($bundle);
        return !empty($stats);
    }
    
    public function displayElementRender(Entity\Model\Bundle $bundle, array $element, $var)
    {
        $settings = $element['settings'];
        if (empty($settings['arrangement'])) return '';

        $stats = [];
        $entity_url = $entity_permalink_url = null;
        foreach ($settings['arrangement'] as $stat_name) {
            if ((!$stat_settings = @$settings['stats'][$stat_name]['settings'])
                || (!$stat = $this->_application->Display_Statistics_impl($bundle, $stat_name, true))
            ) continue;

            if ((!$statistic = $stat->displayStatisticRender($bundle, $var, $stat_settings))
                || !isset($statistic['number'])
                || !isset($statistic['format'])
                || !is_numeric($statistic['number'])
                || ($settings['hide_empty'] && !$statistic['number'])
            ) continue;

            if (empty($settings['show_blocks'])) {
                switch ($stat_settings['_format']) {
                    case 'icon_text':
                        $text = sprintf(
                            '<i class="%s"></i> %s',
                            isset($statistic['icon']) ? $statistic['icon'] : $stat_settings['_icon'],
                            $this->_application->H(sprintf($statistic['format'], $statistic['number']))
                        );
                        break;
                    case 'text':
                        $text = $this->_application->H(sprintf($statistic['format'], $statistic['number']));
                        break;
                    case 'number':
                        $text = $statistic['number'];
                        break;
                    default:
                        $text = sprintf(
                            '<i class="%s"></i> %s',
                            isset($statistic['icon']) ? $statistic['icon'] : $stat_settings['_icon'],
                            $statistic['number']
                        );
                }
            } else {
                $text = sprintf(
                    $this->_application->H(strtr($statistic['format'], ['%d' => '%s'])),
                    '<strong>' . $this->_application->H($statistic['number']) . '</strong>'
                );
            }
            
            // Get color
            $color_class = $color_style = '';
            if (isset($statistic['color'])) {
                if (isset($statistic['color']['type'])
                    && $statistic['color']['type'] === 'custom'
                ) {
                    $color_style = 'color:' . $this->_application->H($statistic['color']['value']) . ';';
                } else {
                    $color_class = DRTS_BS_PREFIX . 'text-' . $statistic['color']['value'];
                }
            }

            // Add link title if format is icon and number only
            $title = $stat_settings['_format'] === 'icon_num' ? $this->_application->H(sprintf($statistic['format'], $statistic['number'])) : '';
            if (!empty($stat_settings['_link'])) {
                if (!empty($stat_settings['_link_path'])) {
                    if (!isset($entity_url)) {
                        $entity_url = $this->_application->Entity_Url($var, '/' . $stat_settings['_link_path']);
                    }
                    $_entity_url = $entity_url;
                } else {
                    if (!isset($entity_permalink_url)) {
                        $entity_permalink_url = $this->_application->Entity_PermalinkUrl($var);
                    }
                    $_entity_url = $entity_permalink_url;
                }
                if (!empty($stat_settings['_link_fragment'])) {
                    $_entity_url .= '#' . $stat_settings['_link_fragment'];
                }
                $stats[$stat_name] = sprintf(
                    '<a data-statistic-name="%1$s" class="drts-display-statistic %2$s" style="%3$s" title="%4$s" href="%5$s">%6$s</a>',
                    $stat_name,
                    $color_class,
                    $color_style,
                    $title,
                    $_entity_url,
                    $text
                );
            } else {            
                $stats[$stat_name] = sprintf(
                    '<span data-statistic-name="%1$s" class="drts-display-statistic %2$s" style="%3$s" title="%4$s">%5$s</span>',
                    $stat_name,
                    $color_class,
                    $color_style,
                    $title,
                    $text
                );
            }
        }

        if (empty($stats)) return '';

        if (empty($settings['show_blocks'])) return implode($settings['separator'], $stats);

        return [
            'html' => implode(PHP_EOL, $stats),
            'style' => 'display:flex;justify-content:space-between;',
        ];
    }
    
    public function displayElementIsPreRenderable(Entity\Model\Bundle $bundle, array &$element)
    {
        $settings = $element['settings'];
        foreach ($settings['arrangement'] as $stat_name) {
            if (($stat = $this->_application->Display_Statistics_impl($bundle, $stat_name, true))
                && ($stat->displayStatisticIsPreRenderable($bundle, isset($settings['statistics'][$stat_name]['settings']) ? $settings['statistics'][$stat_name]['settings'] : []))
            ) {
                return true;
            }
        }
        return false;
    }
    
    public function displayElementPreRender(Entity\Model\Bundle $bundle, array $element, &$var)
    {
        foreach ($element['settings']['arrangement'] as $stat_name) {
            if (($stat = $this->_application->Display_Statistics_impl($bundle, $stat_name, true))
                && ($stat->displayStatisticIsPreRenderable($bundle, $stat_settings = (array)@$element['settings']['statistics'][$stat_name]['settings']))
            ) {
                $stat->displayStatisticPreRender($bundle, $stat_settings, $var['entities']);
            }
        }
    }

    protected function _displayElementReadableInfo(Entity\Model\Bundle $bundle, Display\Model\Element $element)
    {
        $settings = $element->data['settings'];
        if (empty($settings['arrangement'])) return;
        
        $stats = []; 
        foreach ($settings['arrangement'] as $stat_name) {
            if (!$label = $this->_application->Display_Statistics_impl($bundle, $stat_name, true)) continue;
            
            $info = $label->displayStatisticInfo($bundle);
            $stats[] = $info['label'];
        }
        $ret = [
            'statistics' => [
                'label' => __('Statistics', 'directories'),
                'value' => implode(', ', $stats),
            ],
        ];
        return ['settings' => ['value' => $ret]];
    }
}
