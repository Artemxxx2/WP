<?php
namespace SabaiApps\Directories\Component\Entity\FieldRenderer;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class TermsFieldRenderer extends Field\Renderer\AbstractRenderer
{    
    protected function _fieldRendererInfo()
    {
        return array(
            'field_types' => array($this->_name),
            'default_settings' => array(
                'icon' => false,
                'icon_size' => 'sm',
                'no_link' => false,
                'show_top_only' => false,
                'show_parents' => false,
                'show_top_parent_only' => false,
                'parent_sep' => ' / ',
                'show_count' => false,
                '_separator' => ', ',
            ),
            'inlineable' => true,
        );
    }
    
    protected function _fieldRendererSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        if (!$taxonomy_bundle = $field->getTaxonomyBundle()) return;
        
        $ret = [
            'icon' => [
                '#type' => 'checkbox',
                '#title' => __('Show icon', 'directories'),
                '#default_value' => !empty($settings['icon']),
                '#horizontal' => true,
                '#weight' => 1,
            ],
            'icon_size' => [
                '#type' => 'select',
                '#title' => __('Icon size', 'directories'),
                '#default_value' => isset($settings['icon_size']) ? $settings['icon_size'] : null,
                '#options' => $this->_application->System_Util_iconSizeOptions(),
                '#horizontal' => true,
                '#weight' => 2,
                '#states' => [
                    'visible' => [
                        sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['icon']))) => ['type' => 'checked', 'value' => true],
                    ],
                ],
            ],
            'no_link' => [
                '#type' => 'checkbox',
                '#title' => __('Do not link', 'directories'),
                '#default_value' => !empty($settings['no_link']),
                '#horizontal' => true,
                '#weight' => 5,
            ],
            'show_count' => [
                '#type' => 'checkbox',
                '#title' => __('Show post count', 'directories'),
                '#default_value' => !empty($settings['show_count']),
                '#weight' => 16,
            ],
        ];
        if (!empty($taxonomy_bundle->info['is_hierarchical'])) {
            $ret += [
                'show_top_only' => [
                    '#type' => 'checkbox',
                    '#title' => __('Show top level term only', 'directories'),
                    '#default_value' => !empty($settings['show_top_only']),
                    '#horizontal' => true,
                    '#weight' => 10,
                ],
                'show_parents' => [
                    '#type' => 'checkbox',
                    '#title' => __('Show parent terms', 'directories'),
                    '#default_value' => !empty($settings['show_parents']),
                    '#horizontal' => true,
                    '#weight' => 10,
                    '#states' => [
                        'visible' => [
                            sprintf('input[name="%s"]', $show_top_only_selector = $this->_application->Form_FieldName(array_merge($parents, ['show_top_only']))) => ['type' => 'checked', 'value' => false],
                        ],
                    ],
                ],
                'show_top_parent_only' => [
                    '#type' => 'checkbox',
                    '#title' => __('Show top level parent term only', 'directories'),
                    '#default_value' => !empty($settings['show_top_parent_only']),
                    '#horizontal' => true,
                    '#weight' => 12,
                    '#states' => [
                        'visible' => [
                            sprintf('input[name="%s"]', $show_top_only_selector) => ['type' => 'checked', 'value' => false],
                            sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['show_parents']))) => ['type' => 'checked', 'value' => true],
                        ],
                    ],
                ],
                'parent_sep' => [
                    '#type' => 'textfield',
                    '#field_prefix' => __('Separator', 'directories'),
                    '#default_value' => $settings['parent_sep'],
                    '#horizontal' => true,
                    '#no_trim' => true,
                    '#weight' => 11,
                    '#states' => [
                        'visible' => [
                            sprintf('input[name="%s"]', $show_top_only_selector) => ['type' => 'checked', 'value' => false],
                            sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['show_parents']))) => ['type' => 'checked', 'value' => true],
                        ],
                    ],
                ],
            ];
        }

        return $ret;
    }

    protected function _fieldRendererRenderField(Field\IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        $ret = $ids = [];
        $options = ['no_link' => !empty($settings['no_link'])];
        if (!empty($settings['icon'])) {
            $options['icon_size'] = $settings['icon_size'];
            foreach (array_keys($values) as $i) {
                $term = $values[$i];
                if (!is_object($term)) continue;

                $options['icon'] = true;
                if ($image_src = $term->getCustomProperty('image_src')) {
                    $options['icon'] = $image_src;
                    $options['icon_is_value'] = $options['icon_is_image'] = true;
                } elseif ($icon_src = $term->getCustomProperty('icon_src')) {
                    $options['icon'] = $icon_src;
                    $options['icon_is_value'] = $options['icon_is_image'] = $options['icon_is_full'] = true;
                } else {
                    if ($icon = $term->getCustomProperty('icon')) {
                        $options['icon'] = $icon;
                        $options['icon_is_value'] = true;
                    }
                    $options['icon_color'] = $term->getCustomProperty('color');
                }
                if ($link = $this->_getTermPermalink($term, $settings, $options, $ids)) {
                    $ret[$term->getId()] = $link;
                }
            }
        } else {
            foreach (array_keys($values) as $i) {
                $term = $values[$i];
                if (!is_object($term)) continue;

                if ($link = $this->_getTermPermalink($term, $settings, $options, $ids)) {
                    $ret[$term->getId()] = $link;
                }
            }
        }

        ksort($ret, SORT_NUMERIC);
        return implode($settings['_separator'], $ret);
    }

    protected function _getTermPermalink(Entity\Type\IEntity $term, array $settings, array $permalinkOptions, array &$ids)
    {
        if (!empty($settings['show_top_only'])) {
            if ($term->getCustomProperty('parent_titles')) return;
        } else {
            if (!empty($settings['show_parents'])) {
                if (empty($settings['show_top_parent_only'])) {
                    if ($parent_titles = $term->getCustomProperty('parent_titles')) {
                        $parts = [];
                        foreach ($parent_titles as $term_title) {
                            $parts[] = $term_title;
                        }
                        $parts[] = $permalinkOptions['atts']['title'] = $this->_application->Entity_Title($term);
                        $permalinkOptions['title'] = implode($settings['parent_sep'], $parts);
                    }
                } else {
                    if (($parent_titles = $term->getCustomProperty('parent_titles'))
                        && isset($parent_titles[0])
                        && ($parent_slugs = $term->getCustomProperty('parent_slugs'))
                        && isset($parent_slugs[0])
                    ) {
                        $parent_slug = $parent_slugs[0];
                        if (in_array($parent_slug, $ids)) return; // prevent duplicate term

                        $ids[] = $parent_slug;
                        if (!$term = $this->_application->Entity_Types_impl($term->getType())->entityTypeEntityBySlug($term->getBundleName(), $parent_slug)) return;

                        $permalinkOptions['title'] = $parent_titles[0];
                    } else {
                        if (in_array($term->getSlug(), $ids)) return; // prevent duplicate term

                        $ids[] = $term->getSlug();
                    }
                }
            }
        }

        $link = $this->_application->Entity_Permalink($term, $permalinkOptions);
        if (!empty($settings['show_count'])
            && (null !== $count = $term->getCustomProperty('content_count'))
        ) {
            $link .= ' <span style="vertical-align:middle">(' . $count . ')</span>';
        }
        return $link;
    }
    
    public function fieldRendererSupportsAmp(Entity\Model\Bundle $bundle)
    {
        return true;
    }
    
    protected function _fieldRendererReadableSettings(Field\IField $field, array $settings)
    {
        $ret = [
            'icon' => [
                'label' => __('Show icon', 'directories'),
                'value' => !empty($settings['icon']),
                'is_bool' => true,
                'weight' => 1,
            ],
            'no_link' => [
                'label' => __('Do not link', 'directories'),
                'value' => !empty($settings['no_link']),
                'is_bool' => true,
                'weight' => 5,
            ],
            'show_parents' => [
                'label' => __('Show parent terms', 'directories'),
                'value' => !empty($settings['show_parents']),
                'is_bool' => true,
                'weight' => 10,
            ],
        ];
        if (!empty($settings['icon'])) {
            if (isset($settings['icon_size'])) {
                $icon_sizes = $this->_application->System_Util_iconSizeOptions();
                if (isset($icon_sizes[$settings['icon_size']])) {
                    $ret['icon_size'] = [
                        'label' => __('Icon size', 'directories'),
                        'value' => $icon_sizes[$settings['icon_size']],
                        'weight' => 2,
                    ];
                }
            }
        }
        return $ret;
    }
    
    public function fieldRendererIsPreRenderable(Field\IField $field, array $settings)
    {
        return false;
        // Require pre-rendering if icon or icon colfor needs to be fetched from a field
        
        if (empty($settings['icon'])) return false;
        
        if (isset($settings['icon_settings']['field']) && $settings['icon_settings']['field'] !== '') return true;
        
        if (isset($settings['icon_settings']['color']['type'])
            && $settings['icon_settings']['color']['type'] !== ''
            && $settings['icon_settings']['color']['type'] !== 'custom'
        ) return true;
        
        return false;
    }
    
    public function fieldRendererPreRender(Field\IField $field, array $settings, array $entities)
    {
        $terms = [];
        foreach (array_keys($entities) as $entity_id) {
            foreach ($entities[$entity_id]->getFieldValue($field->getFieldName()) as $term) {
                $terms[$term->getId()] = $term;
            }
        }
        if (!empty($terms)) {
            $this->_application->Entity_Field_load($term->getType(), $terms);
        }
    }
}