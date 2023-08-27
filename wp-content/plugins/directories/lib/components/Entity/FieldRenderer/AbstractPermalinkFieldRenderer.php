<?php
namespace SabaiApps\Directories\Component\Entity\FieldRenderer;

use SabaiApps\Directories\Component\Field\Renderer\AbstractRenderer;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Field\Type\ILinkable;

abstract class AbstractPermalinkFieldRenderer extends AbstractRenderer
{
    protected $_isDefault = true;

    protected function _fieldRendererInfo()
    {
        return [
            'label' => $this->_isDefault ? null : __('Permalink', 'directories'),
            'field_types' => $this->_getFieldTypes(),
            'default_settings' => [
                'link' => 'post',
                'link_field' => null,
                'link_target' => '_self',
                'link_rel' => null,
                'link_custom_label' => null,
                'max_chars' => 0,
                'show_count' => false,
                'count_self_only' => false,
                'content_bundle_type' => null,
                'skip_entity_title_filter' => false,
            ],
            'inlineable' => true,
        ];
    }

    /**
     * @return array
     */
    protected function _getFieldTypes()
    {
        return [$this->_name];
    }

    protected function _fieldRendererSettingsForm(IField $field, array $settings, array $parents = [])
    {
        if ($settings['link'] === 'field_post') $settings['link'] = 'field'; // backward compat with 1.2.x

        $bundle = $this->_application->Entity_Bundle($field->bundle_name);
        $form = array(
            'link' => array(
                '#type' => 'select',
                '#title' => __('Link type', 'directories'),
                '#options' => $this->_getTitleLinkTypeOptions($bundle),
                '#default_value' => $settings['link'],
                '#weight' => 1,
            ),
            'link_target' => array(
                '#title' => __('Open link in', 'directories'),
                '#type' => 'select',
                '#options' => $this->_getLinkTargetOptions(),
                '#default_value' => $settings['link_target'],
                '#weight' => 4,
                '#states' => array(
                    'invisible' => array(
                        sprintf('select[name="%s[link]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'value', 'value' => ''), 
                    ),
                ),
            ),
            'link_custom_label' => array(
                '#title' => __('Custom link label', 'directories'),
                '#type' => 'textfield',
                '#default_value' => $settings['link_custom_label'],
                '#weight' => 5,
                '#states' => array(
                    'invisible' => array(
                        sprintf('select[name="%s[link]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'value', 'value' => ''),
                    ),
                ),
            ),
        );
        
        // Allow linking title to another field if any linkable field exists
        if ($linkable_fields = $this->_application->Entity_Field_options($bundle, ['interface' => 'Field\Type\ILinkable', 'return_disabled' => true])) {
            $form['link']['#options']['field'] = __('Link to URL of another field', 'directories');
            $form['link_field'] = array(
                '#type' => 'select',
                '#default_value' => $settings['link_field'],
                '#options' => $linkable_fields[0],
                '#options_disabled' => array_keys($linkable_fields[1]),
                '#weight' => 2,
                '#required' => function($form) use ($parents) { return in_array($form->getValue(array_merge($parents, array('link'))), ['field']); },
                '#states' => array(
                    'visible' => array(
                        sprintf('select[name="%s[link]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'one', 'value' => ['field']),
                    ),
                ),
            );
            $form['link_rel'] = array(
                '#title' => __('Add to "rel" attribute', 'directories'),
                '#weight' => 3,
                '#type' => 'checkboxes',
                '#options' => $this->_getLinkRelAttrOptions(),
                '#default_value' => $settings['link_rel'],
                '#states' => array(
                    'visible' => array(
                        sprintf('select[name="%s[link]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'one', 'value' => ['field']),
                    ),
                ),
            );
        }
        
        $form['max_chars'] = [
            '#title' => __('Max number of characters', 'directories'),
            '#type' => 'slider',
            '#integer' => true,
            '#min_value' => 0,
            '#max_value' => 500,
            '#min_text' => __('Unlimited', 'directories'),
            '#step' => 10,
            '#weight' => 10,
            '#default_value' => $settings['max_chars'],
        ];
        
        $form += $this->_application->System_Util_iconSettingsForm($bundle, $settings, $parents, 10);
        
        if (!empty($bundle->info['is_taxonomy'])
            && ($taxonomy_content_bundle_types = $this->_application->Entity_TaxonomyContentBundleTypes($bundle->type))
        ) {
            $form['show_count'] = array(
                '#type' => 'checkbox',
                '#title' => __('Show post count', 'directories'),
                '#default_value' => !empty($settings['show_count']),
                '#weight' => 15,
            );
            $show_count_selector = sprintf('input[name="%s[show_count]"]', $this->_application->Form_FieldName($parents));
            if (count($taxonomy_content_bundle_types) > 1) {
                $options = [];
                foreach ($taxonomy_content_bundle_types as $content_bundle_type) {
                    $options[$content_bundle_type] = $this->_application->Entity_Bundle($content_bundle_type, $bundle->component, $bundle->group)->getLabel('singular');
                }
                $form['content_bundle_type'] = array(
                    '#type' => 'select',
                    '#options' => $options,
                    '#default_value' => $settings['content_bundle_type'],
                    '#states' => [
                        'visible' => [
                            $show_count_selector => [
                                'type' => 'checked',
                                'value' => true,
                            ],
                        ],
                    ],
                    '#weight' => 16,
                );
            } else {
                $form['content_bundle_type'] = array(
                    '#type' => 'hidden',
                    '#value' => current($taxonomy_content_bundle_types),
                );
            }
            $form['show_count_label'] = array(
                '#type' => 'checkbox',
                '#title' => __('Show post count with label', 'directories'),
                '#default_value' => !empty($settings['show_count_label']),
                '#weight' => 17,
                '#states' => [
                    'visible' => [
                        $show_count_selector => [
                            'type' => 'checked',
                            'value' => true,
                        ],
                    ],
                ],
            );
            $form['link']['#options']['post_no_empty'] = __('Permalink (do not link if empty)', 'directories');
            if (!empty($bundle->info['is_hierarchical'])) {
                $form['count_self_only'] = [
                    '#type' => 'checkbox',
                    '#title' => __('Do not count child term posts', 'directories'),
                    '#default_value' => !empty($settings['count_self_only']),
                    '#weight' => 18,
                    '#states' => $show_post_count_states = [
                        'visible_or' => [
                            $show_count_selector => [
                                'type' => 'checked',
                                'value' => true,
                            ],
                            sprintf('select[name="%s[link]"]', $this->_application->Form_FieldName($parents)) => ['value' => 'post_no_empty'],
                        ],
                    ],
                ];
            }
        }
        $form['skip_entity_title_filter'] = [
            '#type' => 'checkbox',
            '#title' => __('Skip title filter', 'directories'),
            '#weight' => 20,
            '#default_value' => !empty($settings['skip_entity_title_filter']),
        ];

        return $form;
    }

    protected function _fieldRendererRenderField(IField $field, array &$settings, IEntity $entity, array $values, $more = 0)
    {
        // Init permalink options
        $options = [
            'atts' => ['target' => $settings['link_target']],
            'no_link' => $settings['link'] === '',
        ];

        // Set title
        if (empty($options['no_link'])
            && isset($settings['link_custom_label'])
            && strlen($settings['link_custom_label'])
        ) {
            $title = $settings['link_custom_label'];
        } else {
            $title = $this->_getTitle($field, $settings, $entity, $values);
        }
        // Limit number of chars?
        if (!empty($settings['max_chars'])) {
            $title = $this->_application->Summarize($title, $settings['max_chars']);
        }
        $options['title'] = $title;

        if (empty($options['no_link'])
            && $entity->isPublished()
            && $this->_application->Entity_IsRoutable($entity->getBundleName(), 'link', $entity)
        ) {
            if ($settings['link'] === 'field'
                || $settings['link'] === 'field_post' // backward compat with 1.2.x
            ) {
                if (!empty($settings['link_field'])
                    && ($link_field = $this->_application->Entity_Field($entity, $settings['link_field']))
                    && ($link_field_type = $this->_application->Field_Type($link_field->getFieldType(), true))
                    && $link_field_type instanceof ILinkable
                    && ($url = $link_field_type->fieldLinkableUrl($link_field, $entity))
                ) {
                    $options['script_url'] = $url;
                    if (!empty($settings['link_rel'])) {
                        $options['atts']['rel'] = implode(' ', $settings['link_rel']);
                    }
                }
            } elseif ($settings['link'] === 'post_no_empty') {
                if (!empty($settings['content_bundle_type'])
                    && (!$entity->getSingleFieldValue('entity_term_content_count', empty($settings['count_self_only']) ? '_' . $settings['content_bundle_type'] : $settings['content_bundle_type']))
                ) {
                    $options['no_link'] = true;
                }
            }
        } else {
            $options['no_link'] = true;
        }
        $options += $this->_application->System_Util_iconSettingsToPermalinkOptions($entity, $settings);

        // Render permalink
        $ret = $this->_application->Entity_Permalink($entity, $options);

        // Add count if any
        if (!empty($settings['show_count'])
            && !empty($settings['content_bundle_type'])
            && ($count = (int)$entity->getSingleFieldValue('entity_term_content_count', empty($settings['count_self_only']) ? '_' . $settings['content_bundle_type'] : $settings['content_bundle_type']))
        ) {
            if (!empty($settings['show_count_label'])
                && ($bundle = $field->Bundle)
                && ($content_bundle = $this->_application->Entity_Bundle($settings['content_bundle_type'], $bundle->component, $bundle->group))
            ) {
                $count = sprintf(_n($content_bundle->getLabel('count'), $content_bundle->getLabel('count2'), $count), $count);
            } else {
                $count = '(' . $count . ')';
            }
            $ret .= ' <span style="vertical-align:middle">' . $count . '</span>';
        }
        
        return $ret;
    }

    protected function _getTermPostCount(IEntity $entity, array $settings)
    {
        if (empty($settings['content_bundle_type'])) return;

        return $entity->getSingleFieldValue('entity_term_content_count', empty($settings['count_self_only']) ? '_' . $settings['content_bundle_type'] : $settings['content_bundle_type']);
    }

    protected function _getTitle(IField $field, array &$settings, IEntity $entity, array $values)
    {
        return $this->_application->Entity_Title($entity, !empty($settings['skip_entity_title_filter']));
    }
    
    protected function _getTitleLinkTypeOptions(Bundle $bundle)
    {
        $options = [
            '' => __('Do not link', 'directories'),
        ];
        if (!empty($bundle->info['public'])) {
            $options['post'] = __('Permalink', 'directories');
            $options['post_no_empty'] = __('Permalink (do not link if empty)', 'directories');
        }
        return $options;
    }
    
    protected function _fieldRendererReadableSettings(IField $field, array $settings)
    {
        $bundle = $this->_application->Entity_Bundle($field->bundle_name);
        if ($settings['link'] === 'field'
            || $settings['link'] === 'field_post' // backward compat with 1.2.x
        ) {
            $link_value = __('Link to URL of another field', 'directories') . ' - ' . explode(',', $settings['link_field'])[0];
        } elseif (!empty($settings['post_no_empty'])) {
            $link_value = __('Permalink (do not link if empty)', 'directories');
        } else {
            $link_options = $this->_getTitleLinkTypeOptions($bundle);
            $link_value = $link_options[$settings['link']];
        }
        $targets = $this->_getLinkTargetOptions(true);
        $ret = [
            'link' => [
                'label' => __('Link type', 'directories'),
                'value' => $link_value,
            ],
        ];
        if ($settings['link'] !== '') {
            if ($settings['link'] !== 'post') {
                if (!empty($settings['link_rel'])) {
                    $rels = $this->_getLinkRelAttrOptions();
                    $value = [];
                    foreach ($settings['link_rel'] as $rel) {
                        $value[] = $rels[$rel];
                    }
                    $ret['link_rel'] = [
                        'label' => __('Link "rel" attribute', 'directories'),
                        'value' => implode(', ', $value),
                    ];
                }
            }
            $ret['link_target'] = [
                'label' => __('Open link in', 'directories'),
                'value' => $targets[$settings['link_target']],
            ];
            if (isset($settings['link_custom_label'])
                && strlen($settings['link_custom_label'])
            ) {
                $ret['link_custom_label'] = [
                    'label' => __('Custom link label', 'directories'),
                    'value' => $settings['link_custom_label'],
                ];
            }
        }
        if (isset($settings['icon'])) {
            $ret['icon'] = [
                'label' => __('Show icon', 'directories'),
                'value' => !empty($settings['icon']),
                'is_bool' => true, 
            ];
        }
        if (!empty($bundle->info['is_taxonomy'])) {
            $ret['show_count'] = [
                'label' => __('Show post count', 'directories'),
                'value' => !empty($settings['show_count']),
                'is_bool' => true,
            ];
        }
        return $ret;
    }
}
