<?php
namespace SabaiApps\Directories\Component\Entity\Controller\Admin;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\System;
use SabaiApps\Directories\Component\Form;

class Edit extends System\Controller\Admin\AbstractSettings
{    
    protected function _getSettingsForm(Context $context, array &$formStorage)
    {
        if ($context->getRequest()->asBool('show_settings')) {
            return [
                'settings' => [
                    '#type' => 'markup',
                    '#markup' => '<pre>' . var_export($context->bundle->info, true) . '</pre>',
                ],
            ];
        }
        
        // Add label settings
        $info = $this->Entity_BundleTypeInfo($context->bundle);
        $form = [
            '#tabs' => [
                'general' => [
                    '#title' => __('General', 'directories'),
                    '#weight' => -1,
                ],
            ],
            '#tab_style' => 'pill_less_margin',
            'general' => [
                '#tab' => 'general',
                '#tree' => false,
                'labels' => [
                    '#title' => __('Label Settings', 'directories'),
                    '#weight' => 1,
                    '#tree' => false,
                    'label' => [
                        '#type' => 'textfield',
                        '#title' => __('Label', 'directories'),
                        '#default_value' => $context->bundle->getLabel(),
                        '#horizontal' => true,
                        '#placeholder' => isset($info['label']) ? $info['label'] : null,
                        '#required' => true,
                        '#weight' => -2,
                    ],
                    'label_singular' => [
                        '#type' => 'textfield',
                        '#title' => __('Singular label', 'directories'),
                        '#default_value' => $context->bundle->getLabel('singular'),
                        '#horizontal' => true,
                        '#placeholder' => isset($info['label_singular']) ? $info['label_singular'] : null,
                        '#required' => true,
                        '#weight' => -1,
                    ],
                ],
            ],
        ];
        $labels = [
            'add' => __('Add item label', 'directories'),
            'all' => __('All items label', 'directories'),
            'select' => __('Select item label', 'directories'),
            'count' => __('Item count label', 'directories'),
            'count2' => __('Item count label (plural)', 'directories'),
        ];
        foreach ($labels as $label_name => $label_title) {
            $label_setting_name = 'label_' . $label_name;
            if (isset($info[$label_setting_name])) {
                $form['general']['labels'][$label_setting_name] = [
                    '#type' => 'textfield',
                    '#title' => $label_title,
                    '#default_value' => $context->bundle->getLabel($label_name),
                    '#horizontal' => true,
                    '#placeholder' => is_string($info[$label_setting_name]) ? $info[$label_setting_name] : null,
                    '#required' => true,
                ];
            }
        }
        if (!empty($info['public'])) {
            if (!empty($info['is_taxonomy'])
                || !empty($info['is_user'])
                || !empty($info['parent'])
            ) {
                $form['general']['labels'] += [
                    'label_page' => [
                        '#type' => 'textfield',
                        '#title' => __('Single item page label', 'directories'),
                        '#default_value' => $context->bundle->getLabel('page'),
                        '#horizontal' => true,
                        '#placeholder' => is_string($info['label_page']) ? $info['label_page'] : null,
                        '#required' => true,
                    ],
                ];
            }
        }

        if (!empty($info['public'])
            && empty($info['internal'])
            && !empty($info['slug'])
        ) {
            $form['general']['permalink'] = [
                '#title' => __('Single Item Page Settings', 'directories'),
                '#weight' => 10,
                '#tree' => false,
                'slug' => [
                    '#type' => 'textfield',
                    '#title' => __('Default slug', 'directories'),
                    '#default_value' => $context->bundle->info['slug'],
                    '#horizontal' => true,
                    '#placeholder' => $info['slug'],
                    '#required' => true,
                    '#regex' => '/^[a-z0-9-_]+$/',
                    '#max_length' => 30,
                    '#regex_error_message' => __('Only alphanumeric characters, underscores, and dashes are allowed.', 'directories'),
                ],
            ];
        }

        if (empty($info['is_taxonomy'])
            && empty($info['is_user'])
            && empty($info['no_title'])
        ) {
            $title_fields = $this->Entity_Field_options($context->bundle, [
                'interface' => 'Field\Type\ITitle',
                'return_disabled' => true,
                'type_exclude' => 'entity_title',
            ]);
            if (!empty($title_fields[0])) {
                $form['general']['title'] = [
                    '#title' => __('Title Settings', 'directories'),
                    '#weight' => 30,
                    '#tree' => false,
                ];
                $form['general']['title']['no_title'] = [
                    '#type' => 'checkbox',
                    '#title' => __('Disable default title field', 'directories'),
                    '#default_value' => !empty($context->bundle->info['no_title']),
                    '#horizontal' => true,
                ];
                $form['general']['title']['title_field'] = [
                    '#type' => 'select',
                    '#title' => __('Autofill title from another field', 'directories'),
                    '#options' => $title_fields[0],
                    '#options_disabled' => array_keys($title_fields[1]),
                    '#default_value' => !empty($context->bundle->info['title_field']) ? $context->bundle->info['title_field'] : (isset($title_fields[0]['post_content']) ? 'post_content' : null),
                    '#horizontal' => true,
                    '#states' => [
                        'visible' => [
                            '[name="no_title"]' => ['type' => 'checked', 'value' => true],
                        ],
                    ],
                ];
            }
        }

        if (isset($info['entity_image'])
            || isset($info['entity_icon'])
        ) {
            $form['general']['image'] = [
                '#title' => __('Image Settings', 'directories'),
                '#weight' => 40,
                '#tree' => false,
            ];
            if (isset($info['entity_image'])) {
                $image_fields = $this->Entity_Field_options($context->bundle, ['interface' => 'Field\Type\IImage', 'return_disabled' => true]);
                $form['general']['image']['entity_image'] = [
                    '#type' => 'select',
                    '#title' => __('Default image field', 'directories'),
                    '#options' => ['' => '— ' . __('Select field', 'directories') . ' —'] + $image_fields[0],
                    '#options_disabled' => array_keys($image_fields[1]),
                    '#default_value' => !empty($context->bundle->info['entity_image']) ? $context->bundle->info['entity_image'] : null,
                    '#horizontal' => true,
                ];
            }
            if (isset($info['entity_icon'])) {
                $icon_fields = $this->Entity_Field_options($context->bundle, ['interface' => 'Field\Type\IconType', 'return_disabled' => true]);
                if (!isset($image_fields)) {
                    $image_fields = $this->Entity_Field_options($context->bundle, ['interface' => 'Field\Type\IImage', 'return_disabled' => true]);
                }
                $form['general']['image']['entity_icon'] = [
                    '#type' => 'select',
                    '#title' => __('Default icon field', 'directories'),
                    '#options' => ['' => '— ' . __('Select field', 'directories') . ' —'] + $icon_fields[0] + $image_fields[0],
                    '#options_disabled' => array_keys($icon_fields[1] + $image_fields[1]),
                    '#default_value' => !empty($context->bundle->info['entity_icon']) ? $context->bundle->info['entity_icon'] : null,
                    '#horizontal' => true,
                ];
                $color_fields = $this->Entity_Field_options($context->bundle, ['interface' => 'Field\Type\ColorType', 'return_disabled' => true]);
                $form['general']['image']['entity_color'] = [
                    '#type' => 'select',
                    '#title' => __('Default color field', 'directories'),
                    '#options' => ['' => '— ' . __('Select field', 'directories') . ' —'] + $color_fields[0],
                    '#options_disabled' => array_keys($color_fields[1]),
                    '#default_value' => !empty($context->bundle->info['entity_color']) ? $context->bundle->info['entity_color'] : null,
                    '#horizontal' => true,
                ];
            }
        }

        if (!empty($info['expirable'])) {
            $form['general']['expiry'] = [
                '#title' => __('Expiry Settings', 'directories'),
                '#weight' => 45,
                '#tree' => false,
                'entity_expire' => [
                    '#title' => __('Enable expiration date', 'directories'),
                    '#type' => 'checkbox',
                    '#horizontal' => true,
                    '#default_value' => !empty($context->bundle->info['entity_expire']),
                ],
                'entity_expire_days' => [
                    '#title' => __('Default expiration period', 'directories'),
                    '#description' => sprintf(__('This setting applies to %s submitted from the frontend only.', 'directories'), strtolower($context->bundle->getLabel())),
                    '#type' => 'slider',
                    '#min_text' => __('Unlimited', 'directories'),
                    '#field_suffix' => __('day(s)', 'directories'),
                    '#min_value' => 0,
                    '#max_value' => 365,
                    '#integer' => true,
                    '#horizontal' => true,
                    '#default_value' => !empty($context->bundle->info['entity_expire_days']) ? $context->bundle->info['entity_expire_days'] : 0,
                    '#states' => [
                        'visible' => [
                            '[name="entity_expire"]' => ['type' => 'checked', 'value' => true],
                        ],
                    ],
                ],
            ];
        }
        
        if (empty($info['is_taxonomy'])
            && !empty($info['public'])
            && empty($info['internal'])
        ) {
            $form['general']['seo'] = [
                '#title' => __('SEO Settings', 'directories'),
                '#weight' => 50,
                '#tree' => false,
                'entity_schemaorg' => ['#tree' => true] + $this->Entity_SchemaOrg_settingsForm(
                    $context->bundle,
                    empty($context->bundle->info['entity_schemaorg']) ? [] : $context->bundle->info['entity_schemaorg'],
                    ['entity_schemaorg']
                ),
                'entity_opengraph' => ['#tree' => true] + $this->Entity_OpenGraph_settingsForm(
                    $context->bundle,
                    empty($context->bundle->info['entity_opengraph']) ? [] : $context->bundle->info['entity_opengraph'],
                    ['entity_opengraph']
                ),
            ];
        }
        
        $submitted_values = $this->_getSubimttedValues($context, $formStorage);
        
        // Add bundle type specific settings
        $form['general'][$context->bundle->type] = ['#tree' => false, '#weight' => 99];
        $form['general'][$context->bundle->type] += (array)$this->Entity_BundleTypes_impl($context->bundle->type)
            ->entityBundleTypeSettingsForm($context->bundle->info, [], $submitted_values);
        
        $form = $this->Filter('entity_bundle_settings_form', $form, array($context->bundle, $submitted_values));
        if (count($form['#tabs']) <= 1) $form['#tabs'] = [];
        
        return $form;
    }
    
    protected function _saveConfig(Context $context, array $values, Form\Form $form)
    {
        parent::_saveConfig($context, $values, $form);

        if ($this->Entity_BundleTypeInfo($context->bundle, 'entity_icon') !== null) {
            if (!empty($values['entity_icon'])
                && ($icon_field = $this->_application->Entity_Field($context->bundle, $values['entity_icon']))
            ) {
                $values['entity_icon_is_image'] = $icon_field->getFieldType() !== 'icon';
            } else {
                $values['entity_icon'] = null;
            }
        }

        // Clear taxonomy cache if image or icon field changed
        if (!empty($context->bundle->info['is_taxonomy'])) {
            if ((isset($values['entity_image']) && $context->bundle->info['entity_image'] !== $values['entity_image'])
                || (isset($values['entity_icon']) && $context->bundle->info['entity_icon'] !== $values['entity_icon'])
            ) {
                $clear_taxonomy_cache = true;
            }
        }

        $old_info = $context->bundle->info;
        $context->bundle->setInfo($values)->commit();

        if ($old_info['slug'] !== $context->bundle->info['slug']) {
            // Run upgrade process to notify directory slugs may have been updated
            $this->System_Component_upgradeAll(array_keys($this->System_Slugs()));

            $this->getComponent('System')->reloadAllRoutes(true); // reload main routes only for now
        }

        if (!empty($clear_taxonomy_cache)) {
            $this->Entity_TaxonomyTerms_clearCache($context->bundle->name);
        }

        $this->Entity_Field_cleanCache($context->bundle->name);

        $this->Action('entity_admin_bundle_info_edited', [$context->bundle, $old_info]);
    }
}
