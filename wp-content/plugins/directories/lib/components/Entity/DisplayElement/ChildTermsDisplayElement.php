<?php
namespace SabaiApps\Directories\Component\Entity\DisplayElement;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display;

class ChildTermsDisplayElement extends Display\Element\AbstractElement
{
    static protected $_jsLoaded;

    protected function _displayElementInfo(Entity\Model\Bundle $bundle)
    {
        return array(
            'type' => 'content',
            'label' => _x('Child Terms', 'display element name', 'directories'),
            'description' => __('Display child terms of current taxonomy term', 'directories'),
            'default_settings' => array(
                'hide_empty' => false,
                'show_count' => true,
                'show_count_as_badge' => true,
                'content_bundle_type' => null,
                'child_count' => 0,
                'columns' => 1,
                'inline' => true,
                'dropdown' => false,
                'separator' => ', ',
            ),
            'icon' => 'fas fa-list',
            'cacheable' => true,
            'designable' => ['margin', 'padding'],
        );
    }
    
    protected function _displayElementSupports(Entity\Model\Bundle $bundle, Display\Model\Display $display)
    {
        return $display->type === 'entity';
    }
    
    public function displayElementSettingsForm(Entity\Model\Bundle $bundle, array $settings, Display\Model\Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        $form = array(
            'child_count' => array(
                '#type' => 'slider',
                '#title' => __('Number of child terms to display', 'directories'),
                '#default_value' => $settings['child_count'],
                '#min_text' => __('Unlimited', 'directories'),
                '#min_value' => 0,
                '#max_value' => 10,
                '#horizontal' => true,
            ),
            'inline' => array(
                '#title' => __('Display inline', 'directories'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['inline']),
                '#horizontal' => true,
            ),
            'dropdown' => [
                '#title' => __('Display dropdown', 'directories'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['dropdown']),
                '#horizontal' => true,
                '#states' => [
                    'visible' => [
                        $inline_selector = sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['inline']))) => [
                            'type' => 'checked',
                            'value' => false,
                        ],
                    ],
                ],
            ],
            'columns' => array(
                '#type' => 'select',
                '#options' => array(1 => 1, 2 => 2, 3=> 3, 4 => 4, 6 => 6, 12 => 12),
                '#title' => __('Number of columns', 'directories'),
                '#default_value' => $settings['columns'],
                '#horizontal' => true,
                '#states' => [
                    'visible' => [
                        sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['dropdown']))) => [
                            'type' => 'checked',
                            'value' => false,
                        ],
                    ],
                ],
            ),
            'separator' => array(
                '#title' => __('Separator', 'directories'),
                '#type' => 'textfield',
                '#default_value' => $settings['separator'],
                '#horizontal' => true,
                '#states' => array(
                    'visible' => array(
                        $inline_selector => array(
                            'type' => 'checked', 
                            'value' => true,
                        ),
                    ),
                ),
                '#no_trim' => true,
            ),
            'hide_empty' => array(
                '#type' => 'checkbox',
                '#title' => __('Hide empty terms', 'directories'),
                '#default_value' => !empty($settings['hide_empty']),
                '#horizontal' => true,
            ),
        );
        if ($taxonomy_content_bundle_types = $this->_application->Entity_TaxonomyContentBundleTypes($bundle->type)) {
            $form['show_count'] = array(
                '#type' => 'checkbox',
                '#title' => __('Show post count', 'directories'),
                '#default_value' => !empty($settings['show_count']),
                '#horizontal' => true,
            );
            if (count($taxonomy_content_bundle_types) > 1) {
                $content_bundle_names = [];
                foreach ($taxonomy_content_bundle_types as $content_bundle_type) {
                    $content_bundle_names[$content_bundle_type] = $this->_application->Entity_Bundle($content_bundle_type, $bundle->component, $bundle->group)->getLabel('singular');
                }
                $form['content_bundle_type'] = array(
                    '#type' => 'select',
                    '#options' => $content_bundle_names,
                    '#default_value' => $settings['content_bundle_type'],
                    '#horizontal' => true,
                    '#states' => array(
                        'visible' => array(
                            sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['show_count']))) => array(
                                'type' => 'checked', 
                                'value' => true,
                            ),
                        ),
                    ),
                );
            } else {
                $form['content_bundle_type'] = array(
                    '#type' => 'hidden',
                    '#value' => array_pop($taxonomy_content_bundle_types),
                );
            }
            $form['show_count_as_badge'] = [
                '#type' => 'checkbox',
                '#title' => __('Show post count as badge', 'directories'),
                '#default_value' => !isset($settings['show_count_as_badge']) || !empty($settings['show_count_as_badge']),
                '#horizontal' => true,
                '#states' => [
                    'visible' => [
                        sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['show_count']))) => [
                            'type' => 'checked',
                            'value' => true,
                        ],
                        sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['dropdown']))) => [
                            'type' => 'checked',
                            'value' => false,
                        ],
                    ],
                ],
            ];
        }
        $form += $this->_application->System_Util_iconSettingsForm($bundle, $settings, $parents);
        
        return $form;
    }
    
    public function displayElementRender(Entity\Model\Bundle $bundle, array $element, $var)
    {
        $html = [];
        if (!isset($var->data['child_terms'])
            || (!$count = count($var->data['child_terms']))
        ) return '';
        
        $settings = $element['settings'];
        $child_terms = $var->data['child_terms'];
        if (!empty($settings['child_count'])) {
            if (count($child_terms) > $settings['child_count']) {
                $child_terms = array_slice($child_terms, 0, $settings['child_count']);
                $has_more = true;
            }
        }
        $span = intval(12 / $settings['columns']);
        $div_class = DRTS_BS_PREFIX . 'row';
        if (empty($settings['inline'])) {
            if (empty($settings['dropdown'])) {
                $permalink_options = $this->_application->System_Util_iconSettingsToPermalinkOptions($bundle, $settings);
                foreach ($this->_application->SliceArray($child_terms, $settings['columns']) as $columns) {
                    $li = [];
                    foreach ($columns as $term) {
                        if (!empty($settings['hide_empty'])
                            && !$term->getSingleFieldValue('entity_term_content_count', '_all')
                        ) continue;

                        $li[] = '<li>' . $this->_getTermTitle($term, $settings, $permalink_options) . '</li>';
                    }
                    if (!empty($li)) {
                        $html[] = '<div class="' . DRTS_BS_PREFIX . 'col-sm-' . $span . '"><ul class="drts-display-list">';
                        $html[] = implode(PHP_EOL, $li);
                        $html[] = '</ul></div>';
                    }
                }
            } else {
                $div_class = 'drts-row';
                $options = [];
                foreach ($child_terms as $term) {
                    if (!empty($settings['hide_empty'])
                        && !$term->getSingleFieldValue('entity_term_content_count', '_all')
                    ) continue;

                    $title = $term->getTitle();
                    if (!empty($settings['show_count'])
                        && !empty($settings['content_bundle_type'])
                    ) {
                        $count = (int)$term->getSingleFieldValue('entity_term_content_count', '_' . $settings['content_bundle_type']);
                        $title = sprintf('%s (%s)', $title, $count);
                    }
                    $options[] = '<option value="' . $this->_application->H($this->_application->Entity_PermalinkUrl($term) ). '">' . $this->_application->H($title) . '</option>';;
                }
                if (!empty($options)) {
                    $html[] = '<div class="drts-col-lg-6"><select class="' . DRTS_BS_PREFIX .'form-control">';
                    $html[] = '<option value="">' . $this->_application->H(__('— Select —', 'directories')) . '</option>';
                    $html[] = implode(PHP_EOL, $options);
                    $html[] = '</select></div>';
                }
            }
        } else {
            $permalink_options = $this->_application->System_Util_iconSettingsToPermalinkOptions($bundle, $settings);
            foreach ($this->_application->SliceArray($child_terms, $settings['columns']) as $columns) {
                $li = [];
                foreach ($columns as $term) {
                    if (!empty($settings['hide_empty'])
                        && !$term->getSingleFieldValue('entity_term_content_count', '_all')
                    ) continue;

                    $li[] = '<span>' . $this->_getTermTitle($term, $settings, $permalink_options) . '</span>';
                }
                if (!empty($li)) {
                    if (!empty($has_more)) {
                        $li[] = '...';
                    }
                    $html[] = '<div class="' . DRTS_BS_PREFIX . 'col-sm-' . $span . '"><span>';
                    $html[] = implode($settings['separator'], $li);
                    $html[] = '</span></div>';
                }
            }
        }
        
        return empty($html) ? '' : '<div class="' . $div_class . '">' . implode(PHP_EOL, $html) . '</div>';
    }
        
    protected function _getTermTitle(Entity\Type\IEntity $term, array $settings, array $permalinkOptions)
    {
        $title = $this->_application->Entity_Permalink($term, $permalinkOptions);
        if (empty($settings['show_count']) || empty($settings['content_bundle_type'])) return $title;

        $count = (int)$term->getSingleFieldValue('entity_term_content_count', '_' . $settings['content_bundle_type']);
        if (isset($settings['show_count_as_badge'])
            && empty($settings['show_count_as_badge'])
        ) {
            return $title . ' <span style="vertical-align:middle">(' . $count . ')</span>';
        }
        return sprintf(
            __('%1$s <span class="drts-display-list-badge %2$sbadge %2$sbadge-pill %2$sbadge-light">%3$d</span>', 'directories'),
            $title,
            DRTS_BS_PREFIX,
            $count
        );
    }
    
    public function displayElementIsPreRenderable(Entity\Model\Bundle $bundle, array &$element)
    {
        return true;
    }
    
    public function displayElementPreRender(Entity\Model\Bundle $bundle, array $element, &$var)
    {
        $entity_ids = array_keys($var['entities']);
        // Get child terms
        $child_terms = $child_terms_by_parent = [];
        $load_fields = !empty($element['settings']['icon'])
            || (!empty($element['settings']['show_count']) && !empty($element['settings']['content_bundle_type']));
        $terms = $this->_application->Entity_Query($bundle->entitytype_name)->fieldIsIn('parent', $entity_ids);
        $this->_application->Entity_TaxonomyTerms_sort($terms, $bundle);
        foreach ($terms->fetch(0, 0, $load_fields) as $child_term) {
            $child_terms[$child_term->getParentId()][$child_term->getId()] = $child_term;
        }
        if (empty($child_terms)) return;
        
        foreach (array_keys($var['entities']) as $entity_id) {
            if (!empty($child_terms[$entity_id])) {
                usort($child_terms[$entity_id], function ($a, $b) {
                    return strcasecmp($a->getTitle(), $b->getTitle());
                });
                $var['entities'][$entity_id]->data['child_terms'] = $child_terms[$entity_id];
            } else {
                $var['entities'][$entity_id]->data['child_terms'] = [];
            }
        }

        if (!isset(self::$_jsLoaded)) {
            self::$_jsLoaded = true;
            if (!empty($element['settings']['dropdown'])) {
                $this->_application->getPlatform()->addJs(
                    '$(".drts-display-element[data-name=\'entity_child_terms\'] select").change(function() {
    var url = $(this).find(":selected").val();
    if (url) window.location.href = url;
  });',
                    true
                );
            }
        }
    }
    
    protected function _displayElementReadableInfo(Entity\Model\Bundle $bundle, Display\Model\Element $element)
    {
        $settings = $element->data['settings'];
        $ret = [
            'child_count' => [
                'label' => __('Number of child terms to display', 'directories'),
                'value' => empty($settings['child_count']) ? __('Unlimited', 'directories') : $settings['child_count'],
            ],
            'icon' => [
                'label' => __('Show icon', 'directories'),
                'value' => !empty($settings['icon']),
                'is_bool' => true,
            ],
            'inline' => [
                'label' => __('Display inline', 'directories'),
                'value' => !empty($settings['inline']),
                'is_bool' => true,
            ],
        ];
        if (!empty($settings['dropdown'])) {
            $ret['dropdown'] = [
                'label' => __('Display dropdown', 'directories'),
                'value' => !empty($settings['dropdown']),
                'is_bool' => true,
            ];
        } else {
            if (!empty($settings['inline'])) {
                $ret['inline'] = [
                    'label' => __('Display inline', 'directories'),
                    'value' => !empty($settings['inline']),
                    'is_bool' => true,
                ];
            }
            $ret['columns'] = [
                'label' => __('Number of columns', 'directories'),
                'value' => $settings['columns'],
            ];
            if (strlen($settings['separator'])) {
                $ret['separator'] = [
                    'label' => __('Separator', 'directories'),
                    'value' => $settings['separator'],
                ];
            }
        }
        $ret['hide_empty'] = [
            'label' => __('Hide empty terms', 'directories'),
            'value' => !empty($settings['hide_empty']),
            'is_bool' => true,
        ];
        $ret['show_count'] = [
            'label' => __('Show post count', 'directories'),
            'value' => !empty($settings['show_count']),
            'is_bool' => true,
        ];
        
        return ['settings' => ['value' => $ret]];
    }
}